<?php
declare(strict_types=1);

namespace App\Services\Excel;

/**
 * CD-043 P3: فحوصات النطاق (فرع/قسم) — §10.3 + R-SCP-01.
 */
final class ExcelScope
{
    /**
     * فحص تبعية القسم للفرع.
     * @return string|null رسالة خطأ أو null إذا OK.
     */
    public static function departmentBelongsToBranch(?int $deptBranchId, ?int $branchId): ?string
    {
        if ($branchId === null || $deptBranchId === null) { return null; }
        if ($deptBranchId !== $branchId) {
            return 'القسم المحدد لا يتبع الفرع المختار.';
        }
        return null;
    }

    /**
     * فحص وجود فرع/قسم في الخرائط.
     * @return array قائمة أخطاء (code,message).
     */
    public static function validateBranchDepartment(string $branchName, string $departmentName, array $branchMap, array $departmentMap): array
    {
        $errors = [];
        if ($branchName !== '' && !isset($branchMap[$branchName])) {
            $errors[] = ['code' => 'BRANCH_NOT_FOUND', 'message' => 'الفرع ' . $branchName . ' غير موجود.'];
        }
        if ($departmentName !== '' && !isset($departmentMap[$departmentName])) {
            $errors[] = ['code' => 'DEPARTMENT_NOT_FOUND', 'message' => 'القسم ' . $departmentName . ' غير موجود.'];
        }
        return $errors;
    }
}
