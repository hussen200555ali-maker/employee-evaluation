<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Audit;
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
                        d.name AS department_name, b.name AS branch_name,
                        e.personal_email, e.phone, e.join_date, e.is_active,
                        manager.full_name AS manager_name
                 FROM employees e
                 LEFT JOIN positions p ON p.id = e.position_id
                 LEFT JOIN departments d ON d.id = e.department_id
                 LEFT JOIN branches b ON b.id = e.branch_id
                 LEFT JOIN employees manager ON manager.id = e.manager_id
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
                    'email' => $employee->personal_email,
                    'phone' => $employee->phone,
                    'manager_name' => $employee->manager_name,
                    'hire_date' => $employee->join_date,
                    'status' => (int)$employee->is_active === 1 ? 'نشط' : 'غير نشط',
                ],
                'report' => $this->enrichReportWithAttachments($this->reportService->getProfileReport($employeeId)),
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

            Audit::log('REPORT_ISSUED', $snapshotId, 'report_snapshots', [
                'report_number' => $reportNumber,
                'employee_id' => $employeeId,
            ]);

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

    private function enrichReportWithAttachments(array $report): array
    {
        foreach (($report['records'] ?? []) as $index => $record) {
            $recordArray = is_array($record) ? $record : (array)$record;
            $evaluationId = (int)($recordArray['id'] ?? 0);
            $attachment = null;
            if ($evaluationId > 0) {
                $evaluation = (new \App\Models\Evaluation())->getFullEvaluationData($evaluationId);
                $answers = (new \App\Models\Evaluation())->getEvaluationAnswers($evaluationId);
                $employee = $evaluation ? (new \App\Models\Employee())->findById((int)$evaluation->evaluated_employee_id) : null;
                $template = null;
                if ($evaluation && !empty($evaluation->template_id)) {
                    $template = (new \App\Models\Template())->getTemplateWithStructure((int)$evaluation->template_id);
                }
                $program = null;
                if ($evaluation) {
                    $program = $this->fetchProgramForEvaluation($evaluation);
                }
                $attachment = [
                    'evaluation_id' => $evaluationId,
                    'employee' => $employee ? [
                        'full_name' => $employee->full_name,
                        'emp_code' => $employee->emp_code,
                        'branch_name' => $employee->branch_name,
                        'department_name' => $employee->department_name,
                    ] : [
                        'full_name' => $evaluation->evaluated_name ?? null,
                        'emp_code' => $evaluation->evaluated_code ?? null,
                        'branch_name' => null,
                        'department_name' => null,
                    ],
                    'evaluation' => $evaluation ? json_decode(json_encode($evaluation), true) : null,
                    'template' => $template ? json_decode(json_encode($template), true) : null,
                    'answers' => $answers ? json_decode(json_encode($answers), true) : [],
                    'program' => $program ? json_decode(json_encode($program), true) : null,
                    'captured_at' => gmdate('c'),
                ];
            }
            $recordArray['attachment'] = $attachment;
            $report['records'][$index] = $recordArray;
        }
        return $report;
    }

    private function fetchProgramForEvaluation(object $evaluation): ?object
    {
        $contextType = $evaluation->context_type ?? null;
        $contextId = (int)($evaluation->context_id ?? 0);
        if (in_array($contextType, ['course', 'workshop'], true) && $contextId > 0) {
            $this->db->query('SELECT tp.*, b.name AS branch_name, d.name AS department_name, c.name AS catalog_title
                            FROM training_programs tp
                            LEFT JOIN branches b ON b.id = tp.executing_branch_id
                            LEFT JOIN departments d ON d.id = tp.executing_department_id
                            LEFT JOIN training_catalogs c ON c.id = tp.catalog_id
                            WHERE tp.id = :id LIMIT 1');
            $this->db->bind(':id', $contextId);
            $row = $this->db->fetch();
            if ($row) {
                $row->is_legacy_snapshot = false;
                return $row;
            }
        }
        if (!empty($evaluation->entity_snapshot)) {
            $snap = json_decode((string)$evaluation->entity_snapshot, true);
            if (is_array($snap) && !empty($snap['course_title'])) {
                return (object)[
                    'id' => null, 'type' => $contextType ?: 'course',
                    'title' => $snap['course_title'], 'description' => null,
                    'start_date' => null, 'end_date' => null,
                    'branch_name' => null, 'department_name' => null,
                    'catalog_title' => null, 'responsible_person' => null,
                    'status' => null, 'is_legacy_snapshot' => true,
                ];
            }
        }
        return null;
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
