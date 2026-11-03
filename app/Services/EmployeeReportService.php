<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

class EmployeeReportService
{
    private const CANONICAL_SCALE = 'السلم الخماسي';

    private Database $db;
    private AveragesEngine $averagesEngine;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->averagesEngine = new AveragesEngine();
    }

    public function getProfileReport(int $employeeId): array
    {
        if ($employeeId < 1) {
            throw new InvalidArgumentException('معرّف الموظف غير صالح.');
        }

        $this->db->query(
            "SELECT e.id, e.evaluation_type, e.evaluation_date, e.status,
                    e.final_score, e.final_grade_label, e.final_grade_value,
                    t.template_name
             FROM evaluations e
             LEFT JOIN form_templates t ON t.id = e.template_id
             INNER JOIN employees emp ON emp.id = e.evaluated_employee_id
             WHERE e.evaluated_employee_id = :employee_id
               AND e.status IN ('approved', 'completed')
               AND emp.emp_code IS NOT NULL AND emp.emp_code != ''
             ORDER BY e.evaluation_date DESC, e.id DESC"
        );
        $this->db->bind(':employee_id', $employeeId);
        $records = $this->db->fetchAll();
        $scale = $this->getCanonicalScale();

        foreach ($records as $record) {
            $record->grade_value = $record->final_grade_value !== null
                ? (float)$record->final_grade_value
                : $this->averagesEngine->gradeValueForScore($record->final_score, $scale);
            $record->grade_label = $record->final_grade_label
                ?: $this->averagesEngine->gradeLabelForScore($record->final_score, $scale);
            $record->evaluation_type_label = $this->evaluationTypeLabel($record->evaluation_type);
        }

        $averages = [];
        foreach (['overall', 'annual', 'periodic', 'course', 'workshop'] as $type) {
            $matchingRecords = $type === 'overall'
                ? $records
                : array_values(array_filter(
                    $records,
                    static fn(object $record): bool => $record->evaluation_type === $type
                ));
            $averages[$type] = $this->averagesEngine->summarize($matchingRecords, $scale);
        }

        return [
            'records' => $records,
            'averages' => $averages,
            'yearly' => $this->averagesEngine->summarizeByYear($records, $scale),
            'grading_scale_available' => $scale !== [],
            'grading_scale_max_value' => $scale === []
                ? null
                : max(array_map(static fn(object $grade): float => (float)$grade->numeric_value, $scale)),
        ];
    }

    private function getCanonicalScale(): array
    {
        $this->db->query(
            'SELECT grade_label, min_value, max_value, numeric_value
             FROM grading_scales
             WHERE scale_name = :scale_name AND is_active = 1
             ORDER BY sort_order, min_value, id'
        );
        $this->db->bind(':scale_name', self::CANONICAL_SCALE);
        return $this->db->fetchAll();
    }

    private function evaluationTypeLabel(?string $type): string
    {
        return match ($type) {
            'annual' => 'سنوي',
            'periodic' => 'دوري',
            'course' => 'دورة',
            'workshop' => 'ورشة',
            default => 'غير محدد',
        };
    }
}
