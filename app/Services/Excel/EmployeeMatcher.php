<?php
declare(strict_types=1);

namespace App\Services\Excel;

/**
 * CD-043 P3: مطابقة الموظف (§10) — 12 حالة + تطبيع الاسم.
 * يُستخدم من EvaluationExcelService (ولاحقاً من ExcelImportValidator).
 */
final class EmployeeMatcher
{
    /**
     * تطبيع الاسم (§10.2 حالة 12): أ/إ/آ → ا، ة → ه، ى → ي، إزالة التشكيل والتطويل،
     * وضغط المسافات الزائدة.
     */
    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') { return ''; }
        // إزالة التشكيل (harakat) والتطويل (kashida)
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $name);
        $name = str_replace('ـ', '', $name);
        // توحيد الهمزات والألف
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $name);
        // ة → ه، ى → ي
        $name = str_replace('ة', 'ه', $name);
        $name = str_replace('ى', 'ي', $name);
        // واو همزة
        $name = str_replace('ؤ', 'و', $name);
        $name = str_replace('ئ', 'ي', $name);
        // ضغط المسافات
        $name = preg_replace('/\s+/u', ' ', $name);
        return trim($name);
    }

    /**
     * تطبّع للبحث (lowercase بعد تطبيع عربي).
     */
    public static function normalizeKey(string $name): string
    {
        return mb_strtolower(self::normalizeName($name), 'UTF-8');
    }

    /**
     * هل الاسمان متكافئان بعد التطبيع؟
     */
    public static function namesEqual(string $a, string $b): bool
    {
        if (trim($a) === '' || trim($b) === '') { return false; }
        return self::normalizeKey($a) === self::normalizeKey($b);
    }
}
