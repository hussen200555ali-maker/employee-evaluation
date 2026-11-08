<?php

use App\Core\Database;
use App\Services\ReportSnapshotService;

class ReportSnapshotExpandedDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfDbUnavailable();
    }

    public function testSnapshotContainsExpandedEmployeeData(): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $previousSession = $_SESSION;

        try {
            $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
            $userId = (int)$db->fetchColumn();
            $this->assertTrue($userId > 0, 'A user fixture is required.');

            $managerCode = 'CD035-MGR-' . bin2hex(random_bytes(4));
            $db->query('INSERT INTO employees (emp_code, full_name) VALUES (:code, :name)');
            $db->bind(':code', $managerCode);
            $db->bind(':name', 'Snapshot Manager');
            $db->execute();
            $managerId = (int)$db->lastInsertId();

            $employeeCode = 'CD035-EMP-' . bin2hex(random_bytes(4));
            $db->query(
                'INSERT INTO employees
                    (emp_code, full_name, personal_email, phone, join_date, is_active, manager_id)
                 VALUES (:code, :name, :email, :phone, :join_date, 0, :manager_id)'
            );
            $db->bind(':code', $employeeCode);
            $db->bind(':name', 'Expanded Snapshot Employee');
            $db->bind(':email', 'snapshot@example.invalid');
            $db->bind(':phone', '5550100');
            $db->bind(':join_date', '2020-02-03');
            $db->bind(':manager_id', $managerId);
            $db->execute();
            $employeeId = (int)$db->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $snapshot = (new ReportSnapshotService($db))->createForEmployee($employeeId, $userId);
            $employee = $snapshot->snapshot_data['employee'];

            $this->assertSame('snapshot@example.invalid', $employee['email']);
            $this->assertSame('5550100', $employee['phone']);
            $this->assertSame('Snapshot Manager', $employee['manager_name']);
            $this->assertSame('2020-02-03', $employee['hire_date']);
            $this->assertSame('غير نشط', $employee['status']);
        } finally {
            $_SESSION = $previousSession;
            $db->rollBack();
        }
    }
}
