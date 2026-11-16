<?php
declare(strict_types=1);

namespace App\Services\Excel;

/**
 * CD-043 P3: مطابقة البرنامج (§11) — مفتاح مطابقة + إنشاء Instance.
 */
final class ProgramMatcher
{
    /** مفتاح مطابقة البرنامج (§11.3): عنوان + فرع منفذ + قسم منفذ + بدء + انتهاء. */
    public static function matchKey(string $type, string $title, string $branch, string $department, string $start, string $end): string
    {
        return implode('|', [
            trim($type),
            trim($title),
            trim($branch),
            trim($department),
            trim($start),
            trim($end),
        ]);
    }

    /** تطبيع تاريخ (نص أو رقم Excel التسلسلي) إلى Y-m-d. */
    public static function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') { return ''; }
        if (is_numeric($value) && (float) $value > 0) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d'); }
            catch (\Throwable $e) { /* fall through */ }
        }
        // fallback: parseFlexibleDate (replicated minimal)
        $raw = str_replace(['/', '.', '\\'], '-', $value);
        $raw = preg_replace('/\s+/', '', $raw);
        $dt = \DateTime::createFromFormat('Y-m-d', $raw);
        if ($dt !== false) { return $dt->format('Y-m-d'); }
        $ts = strtotime($raw);
        return $ts !== false ? date('Y-m-d', $ts) : '';
    }
}
