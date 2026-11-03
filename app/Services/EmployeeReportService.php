<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

class EmployeeReportService
{
    private const CANONICAL_SCALE = 'السلم الخماسي';

    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
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
             WHERE e.evaluated_employee_id = :employee_id
               AND e.status IN ('approved', 'completed')
             ORDER BY e.evaluation_date DESC, e.id DESC"
        );
        $this->db->bind(':employee_id', $employeeId);
        $records = $this->db->fetchAll();
        $scale = $this->getCanonicalScale();

        foreach ($records as $record) {
            $record->grade_value = $record->final_grade_value !== null
                ? (float)$record->final_grade_value
                : $this->gradeValueForScore($record->final_score, $scale);
            $record->grade_label = $record->final_grade_label
                ?: $this->gradeLabelForScore($record->final_score, $scale);
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
            $averages[$type] = $this->summarize($matchingRecords, $scale);
        }

        return [
            'records' => $records,
            'averages' => $averages,
            'yearly' => $this->summarizeByYear($records, $scale),
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

    private function gradeValueForScore(mixed $score, array $scale): ?float
    {
        if ($score === null) {
            return null;
        }

        $score = (float)$score;
        foreach ($scale as $grade) {
            if ($score >= (float)$grade->min_value && $score <= (float)$grade->max_value) {
                return (float)$grade->numeric_value;
            }
        }

        return null;
    }

    private function gradeLabelForScore(mixed $score, array $scale): ?string
    {
        if ($score === null) {
            return null;
        }

        $score = (float)$score;
        foreach ($scale as $grade) {
            if ($score >= (float)$grade->min_value && $score <= (float)$grade->max_value) {
                return (string)$grade->grade_label;
            }
        }

        return null;
    }

    private function summarize(array $records, array $scale): array
    {
        $values = [];
        foreach ($records as $record) {
            if ($record->grade_value !== null) {
                $values[] = (float)$record->grade_value;
            }
        }

        $average = $values === [] ? null : round(array_sum($values) / count($values), 2);

        return [
            'evaluation_count' => count($records),
            'scored_count' => count($values),
            'average' => $average,
            'grade_label' => $average === null ? null : $this->nearestGradeLabel($average, $scale),
        ];
    }

    private function summarizeByYear(array $records, array $scale): array
    {
        $recordsByYear = [];
        foreach ($records as $record) {
            $year = (int)date('Y', strtotime((string)$record->evaluation_date));
            $recordsByYear[$year][] = $record;
        }

        if ($recordsByYear === []) {
            return [];
        }

        $firstYear = min(array_keys($recordsByYear));
        $lastYear = max(array_keys($recordsByYear));
        $summaries = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $summary = $this->summarize($recordsByYear[$year] ?? [], $scale);
            $summaries[] = ['year' => $year] + $summary;
        }

        return $summaries;
    }

    private function nearestGradeLabel(float $value, array $scale): ?string
    {
        if ($scale === []) {
            return null;
        }

        $closest = null;
        $smallestDifference = INF;
        foreach ($scale as $grade) {
            $gradeValue = (float)$grade->numeric_value;
            $difference = abs($value - $gradeValue);
            if ($difference < $smallestDifference || (
                $difference === $smallestDifference
                && $closest !== null
                && $gradeValue > (float)$closest->numeric_value
            )) {
                $closest = $grade;
                $smallestDifference = $difference;
            }
        }

        return $closest ? (string)$closest->grade_label : null;
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
