<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\GradingScale;
use App\Models\Template;

/**
 * CD-043 P4: محرك التقييم (EvaluationCalculator) — الميكانيزم الموحّد لحساب final_score.
 * يُستخدم من Evaluation::calculateFinalScore و EvaluationExcelService::calculateFinalScore
 * لضمان 100% تطابق (parity) بين التقييم اليدوي والمستورد.
 *
 * ضمانة 1: للدورة الوصفية (descriptive) → final_score = max_value للتقدير العام
 * (final_grade_label/value) — لا يعتمد على evaluation_answers.
 */
final class EvaluationCalculator
{
    public static function calculate(int $evaluationId): float
    {
        $db = Database::getInstance();
        $db->query('SELECT * FROM evaluations WHERE id = :id');
        $db->bind(':id', $evaluationId);
        $evaluation = $db->fetch();
        if (!$evaluation) { return 0.0; }

        $templateModel = new Template();
        $template = $templateModel->getTemplateWithStructure((int) $evaluation->template_id);
        if (!$template) { return 0.0; }

        $mode = (string) ($template['evaluation_mode'] ?? 'numeric');

        // Descriptive: final_score = max_value of the overall grade.
        if ($mode === 'descriptive') {
            $score = self::descriptiveScore($evaluation, $template);
            return $score === null ? 0.0 : round($score, 2);
        }

        // Mixed: if a grade was chosen, use its max_value; otherwise numeric.
        if ($mode === 'mixed' && !empty($evaluation->final_grade_label)) {
            $score = self::gradeMaxValue((string) $evaluation->final_grade_label, $template);
            if ($score !== null) { return round($score, 2); }
        }

        // Numeric: weighted average over sections (skip text/textarea/overall_grade).
        $db->query('SELECT section_id, normalized_score FROM evaluation_answers WHERE evaluation_id = :id');
        $db->bind(':id', $evaluationId);
        $answers = $db->fetchAll(\PDO::FETCH_KEY_PAIR);

        if (empty($answers)) { return 0.0; }

        $totalScore = 0.0;
        foreach ($template['criteria'] as $criterion) {
            $criterionScore = 0.0;
            foreach ($criterion['sections'] as $section) {
                $sectionType = (string) ($section['evaluation_type'] ?? 'percentage');
                if (in_array($sectionType, ['textarea', 'text', 'overall_grade'], true)) { continue; }
                $sectionId = (int) $section['id'];
                $normalizedScore = (float) ($answers[$sectionId] ?? 0);
                $sectionWeight = (float) ($section['section_weight'] ?? 0);
                $criterionScore += ($normalizedScore * $sectionWeight / 100.0);
            }
            $criterionWeight = (float) ($criterion['criterion_weight'] ?? 0);
            $totalScore += ($criterionScore * $criterionWeight / 100.0);
        }
        return round($totalScore, 2);
    }

    private static function descriptiveScore(object $evaluation, array $template): ?float
    {
        if (empty($evaluation->final_grade_label)) { return null; }
        return self::gradeMaxValue((string) $evaluation->final_grade_label, $template);
    }

    private static function gradeMaxValue(string $gradeLabel, array $template): ?float
    {
        $scaleName = 'السلم الخماسي';
        foreach ($template['criteria'] ?? [] as $criterion) {
            foreach ($criterion['sections'] ?? [] as $section) {
                if (($section['evaluation_type'] ?? '') === 'overall_grade') {
                    $opts = $section['evaluation_options'] ?? [];
                    if (is_string($opts)) { $opts = json_decode($opts, true); }
                    if (is_array($opts) && !empty($opts['scale_name'])) {
                        $scaleName = (string) $opts['scale_name'];
                    }
                    break 2;
                }
            }
        }

        $scaleRows = (new GradingScale())->getByName($scaleName);
        foreach ($scaleRows as $g) {
            if ($g->grade_label === $gradeLabel) { return (float) $g->max_value; }
        }
        $allScales = (new GradingScale())->getActive();
        foreach ($allScales as $g) {
            if ($g->grade_label === $gradeLabel) { return (float) $g->max_value; }
        }
        return null;
    }
}
