<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;

/**
 * Validates and persists the link between an employee evaluation and a unified
 * training program. The caller owns the surrounding transaction.
 */
final class EvaluationProgramContextService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public static function contextTypeForTargetType(string $targetType): ?string
    {
        return match ($targetType) {
            'course', 'employee_in_course' => 'course',
            'workshop', 'employee_in_workshop' => 'workshop',
            default => null,
        };
    }

    public static function evaluationTypeForTargetType(string $targetType, ?string $fallback = null): string
    {
        return match ($targetType) {
            'course', 'employee_in_course' => 'course',
            'workshop', 'employee_in_workshop' => 'workshop',
            'periodic' => 'periodic',
            default => in_array($fallback, ['annual', 'periodic', 'course', 'workshop', 'skill'], true)
                ? ($fallback === 'skill' ? 'annual' : $fallback)
                : 'annual',
        };
    }

    /**
     * Validate request context against the template type and the real program.
     * Non-training templates must not carry stale training context values.
     *
     * @return array{type:string,id:int}|null
     */
    public function resolveContext(string $targetType, int $contextId, string $providedType): ?array
    {
        $expectedType = self::contextTypeForTargetType($targetType);
        if ($expectedType === null) {
            if ($contextId > 0 || $providedType !== '') {
                throw new InvalidArgumentException('هذا القالب لا يقبل برنامجاً تدريبياً.');
            }
            return null;
        }

        if ($contextId < 1 || $providedType !== $expectedType) {
            $label = $expectedType === 'course' ? 'دورة تدريبية' : 'ورشة';
            throw new InvalidArgumentException('يجب اختيار ' . $label . ' صحيحة قبل حفظ التقييم.');
        }

        $this->db->query('SELECT id, type FROM training_programs WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $contextId);
        $program = $this->db->fetch();
        if (!$program || $program->type !== $expectedType) {
            throw new InvalidArgumentException('البرنامج المختار غير موجود أو لا يطابق نوع قالب التقييم.');
        }

        return ['type' => $expectedType, 'id' => $contextId];
    }

    /**
     * Persist evaluation context and mark its employee as completed in the same
     * transaction managed by the calling controller.
     *
     * @param array{type:string,id:int}|null $context
     */
    public function persist(int $evaluationId, int $employeeId, string $evaluationType, ?array $context, float $finalScore): void
    {
        if ($evaluationId < 1 || ($context !== null && $employeeId < 1)) {
            throw new InvalidArgumentException('معرّف التقييم أو الموظف غير صالح.');
        }
        if (!in_array($evaluationType, ['annual', 'periodic', 'course', 'workshop'], true)) {
            throw new InvalidArgumentException('نوع التقييم غير صالح.');
        }

        $contextType = $context['type'] ?? null;
        $contextId = $context['id'] ?? null;
        if ($context !== null && !in_array($contextType, ['course', 'workshop'], true)) {
            throw new InvalidArgumentException('نوع سياق التدريب غير صالح.');
        }

        $this->db->query(
            'UPDATE evaluations
             SET evaluation_type = :evaluation_type,
                 context_type = :context_type,
                 context_id = :context_id,
                 updated_at = NOW()
             WHERE id = :evaluation_id'
        );
        $this->db->bind(':evaluation_type', $evaluationType);
        $this->db->bind(':context_type', $contextType);
        $this->db->bind(':context_id', $contextId);
        $this->db->bind(':evaluation_id', $evaluationId);
        if (!$this->db->execute()) {
            throw new RuntimeException('تعذر حفظ نوع التقييم وسياق البرنامج.');
        }

        if ($context === null) {
            return;
        }

        $this->db->query(
            "INSERT INTO program_participants (program_id, employee_id, status, final_score, completed_at)
             VALUES (:program_id, :employee_id, 'completed', :final_score, NOW())
             ON DUPLICATE KEY UPDATE
                 status = 'completed',
                 final_score = VALUES(final_score),
                 completed_at = NOW()"
        );
        $this->db->bind(':program_id', $contextId);
        $this->db->bind(':employee_id', $employeeId);
        $this->db->bind(':final_score', $finalScore);
        if (!$this->db->execute()) {
            throw new RuntimeException('تعذر تحديث مشاركة الموظف في البرنامج.');
        }
    }
}
