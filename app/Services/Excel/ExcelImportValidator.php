<?php
declare(strict_types=1);

namespace App\Services\Excel;

use App\Services\EvaluationExcelService;

/**
 * CD-043 P3: محرك التحقق (Validator) — wraps EvaluationExcelService validation.
 * يوفر واجهة موحدة لمعاينة/الالتزام (§10/§11).
 */
final class ExcelImportValidator
{
    private EvaluationExcelService $service;

    public function __construct(?EvaluationExcelService $service = null)
    {
        $this->service = $service ?? new EvaluationExcelService();
    }

    /**
     * التحقق من صف واحد (ي委託 إلى EvaluationExcelService).
     */
    public function validateRow(array &$payload, int $evaluatorEmployeeId, array $seenEmpCodes, array $seenNames, array $branchMap, array $departmentMap, array $sections): array
    {
        return $this->service->validateImportRow($payload, $evaluatorEmployeeId, $seenEmpCodes, $seenNames, $branchMap, $departmentMap, $sections);
    }

    public function service(): EvaluationExcelService
    {
        return $this->service;
    }
}
