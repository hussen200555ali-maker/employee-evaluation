<?php
namespace App\Services;

final class AveragesEngine
{
    public function summarize(array $records, array $scale): array
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
            'scored_count'     => count($values),
            'average'          => $average,
            'grade_label'      => $average === null ? null : $this->nearestGradeLabel($average, $scale),
        ];
    }

    public function summarizeByYear(array $records, array $scale): array
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
        $lastYear  = max(array_keys($recordsByYear));
        $summaries = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $summary = $this->summarize($recordsByYear[$year] ?? [], $scale);
            $summaries[] = ['year' => $year] + $summary;
        }
        return $summaries;
    }

    public function gradeValueForScore(mixed $score, array $scale): ?float
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

    public function gradeLabelForScore(mixed $score, array $scale): ?string
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

    public function nearestGradeLabel(float $value, array $scale): ?string
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
}
