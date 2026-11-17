<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Phase 1 (CD-REPORTS): server-side organizational scope for the reports module.
 *
 * Policy:
 *  - Admin  : full bypass (all branches, departments, archive and direct links).
 *  - HR     : full visibility.
 *  - Manager: branch scope when designated as a branch manager (branches.manager_id),
 *             otherwise department scope, otherwise self only.
 *  - Others : full only with reports.view.all; otherwise team/self scope.
 *
 * The scope is always injected into SQL as an additional mandatory predicate, so
 * request parameters can only narrow the result — never widen it.
 */
final class ReportScopeService
{
    public const MODE_ALL = 'all';
    public const MODE_BRANCH = 'branch';
    public const MODE_DEPARTMENT = 'department';
    public const MODE_EMPLOYEE = 'employee';
    public const MODE_NONE = 'none';

    private Database $db;
    private ?array $scope = null;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * @return array{mode:string, branch_id:?int, department_id:?int, employee_id:?int, label:string, is_admin:bool}
     */
    public function resolve(): array
    {
        if ($this->scope !== null) {
            return $this->scope;
        }

        $scope = [
            'mode' => self::MODE_NONE,
            'branch_id' => null,
            'department_id' => null,
            'employee_id' => null,
            'label' => '',
            'is_admin' => false,
        ];

        if (!Auth::check()) {
            return $this->scope = $scope;
        }

        $roles = $this->roles();
        $employeeId = $this->currentEmployeeId();

        // 1) System administrator: explicit full bypass.
        if (in_array('Admin', $roles, true)) {
            $scope['mode'] = self::MODE_ALL;
            $scope['label'] = 'كل الفروع (مدير النظام)';
            $scope['is_admin'] = true;
            return $this->scope = $scope;
        }

        // 2) HR sees everything.
        if (in_array('HR', $roles, true)) {
            $scope['mode'] = self::MODE_ALL;
            $scope['label'] = 'كل الفروع (الموارد البشرية)';
            return $this->scope = $scope;
        }

        // 3) Manager: branch manager if designated, otherwise department manager.
        if (in_array('Manager', $roles, true)) {
            $employee = $this->findEmployee($employeeId);
            if ($employee === null) {
                return $this->scope = $scope;
            }

            $branchId = $this->managedBranchId($employeeId);
            if ($branchId !== null) {
                $scope['mode'] = self::MODE_BRANCH;
                $scope['branch_id'] = $branchId;
                $scope['department_id'] = $this->nullableInt($employee->department_id ?? null);
                $scope['employee_id'] = $employeeId;
                $scope['label'] = 'نطاقك: فرع ' . $this->branchName($branchId);
                return $this->scope = $scope;
            }

            $departmentId = $this->nullableInt($employee->department_id ?? null);
            if ($departmentId !== null) {
                $scope['mode'] = self::MODE_DEPARTMENT;
                $scope['branch_id'] = $this->nullableInt($employee->branch_id ?? null);
                $scope['department_id'] = $departmentId;
                $scope['employee_id'] = $employeeId;
                $scope['label'] = 'نطاقك: قسم ' . $this->departmentName($departmentId);
                return $this->scope = $scope;
            }

            $branchId = $this->nullableInt($employee->branch_id ?? null);
            if ($branchId !== null) {
                $scope['mode'] = self::MODE_BRANCH;
                $scope['branch_id'] = $branchId;
                $scope['employee_id'] = $employeeId;
                $scope['label'] = 'نطاقك: فرع ' . $this->branchName($branchId);
                return $this->scope = $scope;
            }

            $scope['mode'] = self::MODE_EMPLOYEE;
            $scope['employee_id'] = $employeeId;
            $scope['label'] = 'نطاقك: سجلك الشخصي';
            return $this->scope = $scope;
        }

        // 4) Any other role with full report visibility.
        if (Auth::userHasPermission('reports.view.all')) {
            $scope['mode'] = self::MODE_ALL;
            $scope['label'] = 'كل الفروع';
            return $this->scope = $scope;
        }

        // 5) Team/self scope fallback.
        $employee = $this->findEmployee($employeeId);
        if ($employee !== null) {
            $departmentId = $this->nullableInt($employee->department_id ?? null);
            $branchId = $this->nullableInt($employee->branch_id ?? null);
            $scope['employee_id'] = $employeeId;
            if ($departmentId !== null) {
                $scope['mode'] = self::MODE_DEPARTMENT;
                $scope['branch_id'] = $branchId;
                $scope['department_id'] = $departmentId;
                $scope['label'] = 'نطاقك: قسم ' . $this->departmentName($departmentId);
            } elseif ($branchId !== null) {
                $scope['mode'] = self::MODE_BRANCH;
                $scope['branch_id'] = $branchId;
                $scope['label'] = 'نطاقك: فرع ' . $this->branchName($branchId);
            } else {
                $scope['mode'] = self::MODE_EMPLOYEE;
                $scope['label'] = 'نطاقك: سجلك الشخصي';
            }
        }

        return $this->scope = $scope;
    }

    public function isAll(): bool
    {
        return $this->resolve()['mode'] === self::MODE_ALL;
    }

    public function label(): string
    {
        return (string)$this->resolve()['label'];
    }

    public function canAccessEmployee(int $employeeId): bool
    {
        $scope = $this->resolve();
        if ($scope['mode'] === self::MODE_ALL) {
            return true;
        }
        if ($employeeId < 1) {
            return false;
        }

        $employee = $this->findEmployee($employeeId);
        if ($employee === null) {
            return false;
        }

        return match ($scope['mode']) {
            self::MODE_BRANCH => $this->nullableInt($employee->branch_id ?? null) === $scope['branch_id'],
            self::MODE_DEPARTMENT => $this->nullableInt($employee->department_id ?? null) === $scope['department_id'],
            self::MODE_EMPLOYEE => (int)$employee->id === (int)$scope['employee_id'],
            default => false,
        };
    }

    public function canAccessSnapshot(object $snapshot): bool
    {
        return $this->canAccessEmployee((int)($snapshot->employee_id ?? 0));
    }

    /**
     * Appends a mandatory employee-table predicate to an existing WHERE clause.
     */
    public function applyEmployeeConstraint(string $alias, array &$params): string
    {
        $scope = $this->resolve();

        return match ($scope['mode']) {
            self::MODE_BRANCH => $this->bindBranch($alias . '.branch_id', $params, (int)$scope['branch_id']),
            self::MODE_DEPARTMENT => $this->bindDepartment($alias . '.department_id', $params, (int)$scope['department_id']),
            self::MODE_EMPLOYEE => $this->bindEmployee($alias . '.id', $params, (int)$scope['employee_id']),
            self::MODE_ALL => '',
            default => ' AND 1=0',
        };
    }

    /**
     * Appends a mandatory predicate for snapshot queries joined to employees.
     */
    public function applySnapshotConstraint(string $snapshotAlias, string $employeeAlias, array &$params): string
    {
        unset($snapshotAlias); // reserved for future snapshot-native scope columns
        return $this->applyEmployeeConstraint($employeeAlias, $params);
    }

    private function bindBranch(string $column, array &$params, int $branchId): string
    {
        $params[':scope_branch'] = $branchId;
        return ' AND ' . $column . ' = :scope_branch';
    }

    private function bindDepartment(string $column, array &$params, int $departmentId): string
    {
        $params[':scope_department'] = $departmentId;
        return ' AND ' . $column . ' = :scope_department';
    }

    private function bindEmployee(string $column, array &$params, int $employeeId): string
    {
        $params[':scope_employee'] = $employeeId;
        return ' AND ' . $column . ' = :scope_employee';
    }

    /**
     * @return string[]
     */
    private function roles(): array
    {
        $roles = $_SESSION['user_roles'] ?? [];
        if (is_array($roles) && $roles !== []) {
            return array_values(array_map('strval', $roles));
        }

        $this->db->query(
            'SELECT r.name FROM roles r
             JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id'
        );
        $this->db->bind(':user_id', (int)Auth::id());
        return array_map(
            static fn(object $row): string => (string)$row->name,
            $this->db->fetchAll()
        );
    }

    private function currentEmployeeId(): int
    {
        $user = Auth::user();
        $employeeId = (int)($user->employee_id ?? 0);
        if ($employeeId > 0) {
            return $employeeId;
        }

        $this->db->query('SELECT employee_id FROM users WHERE id = :id');
        $this->db->bind(':id', (int)Auth::id());
        $value = $this->db->fetchColumn();
        return $value !== false && $value !== null ? (int)$value : 0;
    }

    private function findEmployee(int $employeeId): ?object
    {
        if ($employeeId < 1) {
            return null;
        }
        $this->db->query('SELECT id, branch_id, department_id FROM employees WHERE id = :id');
        $this->db->bind(':id', $employeeId);
        $employee = $this->db->fetch();
        return $employee ?: null;
    }

    private function managedBranchId(int $employeeId): ?int
    {
        if ($employeeId < 1) {
            return null;
        }
        $this->db->query('SELECT id FROM branches WHERE manager_id = :employee_id ORDER BY id LIMIT 1');
        $this->db->bind(':employee_id', $employeeId);
        $branchId = $this->db->fetchColumn();
        return $branchId !== false && $branchId !== null ? (int)$branchId : null;
    }

    private function branchName(int $branchId): string
    {
        $this->db->query('SELECT name FROM branches WHERE id = :id');
        $this->db->bind(':id', $branchId);
        $name = $this->db->fetchColumn();
        return $name !== false && $name !== null ? (string)$name : ('#' . $branchId);
    }

    private function departmentName(int $departmentId): string
    {
        $this->db->query('SELECT name FROM departments WHERE id = :id');
        $this->db->bind(':id', $departmentId);
        $name = $this->db->fetchColumn();
        return $name !== false && $name !== null ? (string)$name : ('#' . $departmentId);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}
