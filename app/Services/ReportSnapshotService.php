<?php
namespace App\Services;

use App\Core\Database;
use JsonException;
use RuntimeException;
use Throwable;

final class ReportSnapshotService
{
    private Database $db;
    private EmployeeReportService $reportService;

    public function __construct(?Database $db = null, ?EmployeeReportService $reportService = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->reportService = $reportService ?? new EmployeeReportService($this->db);
    }

    public function createForEmployee(int $employeeId, int $createdByUserId): object
    {
        if ($employeeId < 1 || $createdByUserId < 1) {
            throw new \InvalidArgumentException('معرّف الموظف أو المستخدم غير صالح.');
        }

        $pdo = $this->db->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $this->db->query(
                'SELECT e.id, e.emp_code, e.full_name, p.title AS position_title,
                        d.name AS department_name, b.name AS branch_name
                 FROM employees e
                 LEFT JOIN positions p ON p.id = e.position_id
                 LEFT JOIN departments d ON d.id = e.department_id
                 LEFT JOIN branches b ON b.id = e.branch_id
                 WHERE e.id = :employee_id'
            );
            $this->db->bind(':employee_id', $employeeId);
            $employee = $this->db->fetch();
            if (!$employee) {
                throw new RuntimeException('الموظف المطلوب غير موجود.');
            }

            $this->db->query('SELECT username FROM users WHERE id = :user_id');
            $this->db->bind(':user_id', $createdByUserId);
            $creator = $this->db->fetch();
            if (!$creator) {
                throw new RuntimeException('المستخدم الذي ينشئ التقرير غير موجود.');
            }

            $snapshotData = [
                'version' => 1,
                'generated_at' => gmdate('c'),
                'created_by' => [
                    'user_id' => $createdByUserId,
                    'username' => (string)$creator->username,
                ],
                'employee' => [
                    'id' => (int)$employee->id,
                    'emp_code' => $employee->emp_code,
                    'full_name' => $employee->full_name,
                    'position_title' => $employee->position_title,
                    'department_name' => $employee->department_name,
                    'branch_name' => $employee->branch_name,
                ],
                'report' => $this->reportService->getProfileReport($employeeId),
            ];
            $this->db->query(
                'UPDATE report_snapshot_counters
                 SET last_number = LAST_INSERT_ID(last_number + 1)
                 WHERE id = 1'
            );
            $this->db->execute();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException('تعذر حجز رقم تسلسلي للتقرير.');
            }
            $this->db->query('SELECT LAST_INSERT_ID()');
            $reportNumberSequence = (int)$this->db->fetchColumn();
            $reportNumber = 'ER-' . str_pad((string)$reportNumberSequence, 8, '0', STR_PAD_LEFT);
            $snapshotData['report_number'] = $reportNumber;
            $snapshotJson = json_encode(
                $snapshotData,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $this->db->query(
                'INSERT INTO report_snapshots (report_number, employee_id, created_by_user_id, snapshot_json)
                 VALUES (:report_number, :employee_id, :created_by_user_id, :snapshot_json)'
            );
            $this->db->bind(':report_number', $reportNumber);
            $this->db->bind(':employee_id', $employeeId);
            $this->db->bind(':created_by_user_id', $createdByUserId);
            $this->db->bind(':snapshot_json', $snapshotJson);
            $this->db->execute();
            $snapshotId = (int)$this->db->lastInsertId();

            $snapshot = $this->findForEmployee($employeeId, $snapshotId);
            if ($snapshot === null) {
                throw new RuntimeException('تعذر استرجاع نسخة التقرير بعد حفظها.');
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return $snapshot;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function listForEmployee(int $employeeId): array
    {
        if ($employeeId < 1) {
            throw new \InvalidArgumentException('معرّف الموظف غير صالح.');
        }

        $this->db->query(
            'SELECT s.id, s.report_number, s.created_at,
                    JSON_UNQUOTE(JSON_EXTRACT(s.snapshot_json, \'$.created_by.username\')) AS created_by_username
             FROM report_snapshots s
             WHERE s.employee_id = :employee_id
             ORDER BY s.id DESC'
        );
        $this->db->bind(':employee_id', $employeeId);
        return $this->db->fetchAll();
    }

    public function findForEmployee(int $employeeId, int $snapshotId): ?object
    {
        if ($employeeId < 1 || $snapshotId < 1) {
            throw new \InvalidArgumentException('معرّف التقرير أو الموظف غير صالح.');
        }

        $this->db->query(
            'SELECT id, report_number, employee_id, created_by_user_id, created_at, snapshot_json
             FROM report_snapshots
             WHERE employee_id = :employee_id AND id = :snapshot_id'
        );
        $this->db->bind(':employee_id', $employeeId);
        $this->db->bind(':snapshot_id', $snapshotId);
        $snapshot = $this->db->fetch();
        if (!$snapshot) {
            return null;
        }

        try {
            $snapshot->snapshot_data = json_decode($snapshot->snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('تعذر قراءة بيانات نسخة التقرير المحفوظة.', 0, $exception);
        }
        unset($snapshot->snapshot_json);

        return $snapshot;
    }
}
