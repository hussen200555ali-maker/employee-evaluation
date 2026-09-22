<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
final class EvaluationExcelService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const DEFAULT_MAX_ROWS = 100;
    private const MAX_ROWS_LIMIT = 1000;
    private const MAX_ROWS = 100;
    private const SHEET_NAME = 'التقييمات';
    private const LOOKUP_SHEET = '__lookup';
    private const SHEET_PASSWORD = 'local-evaluation-single';
    private Database $database;
    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? Database::getInstance();
    }
    public function buildTemplate(int $templateId, int $evaluatorEmployeeId, ?int $branchId = null, ?int $departmentId = null, int $maxRows = 100, ?string $mode = null, ?array $prefillIds = null): Spreadsheet
    {
        $maxRows = max(1, min($maxRows, self::MAX_ROWS_LIMIT));
        if ($maxRows > self::DEFAULT_MAX_ROWS && $maxRows <= self::MAX_ROWS_LIMIT) {
        }
        $template = $this->getTemplateOrFail($templateId);
        $sections = $this->getSectionsOrdered($templateId);
        if ($sections === []) {
            throw new RuntimeException('القالب المحدد لا يحتوي على أي بنود تقييم.');
        }
        $branches = $this->getBranchNames();
        $departments = $this->getDepartmentNames();
        $evaluatorInfo = $this->getEvaluatorInfo($evaluatorEmployeeId);
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(self::SHEET_NAME);
        $sheet->setRightToLeft(true);
        $sheet->getDefaultRowDimension()->setRowHeight(18);
        $targetType = $template['target_type'] ?? 'employee';
        if ($targetType === 'course') {
            $fixedHeaders = ['عنوان الدورة *', 'الفرع', 'القسم', 'تاريخ البداية', 'تاريخ النهاية'];
            $fixedCount = 5;
            $instruction = 'كل صف = دورة واحدة. العنوان إلزامي + الفرع أو القسم أحدهما إلزامي. حتى ' . $maxRows . ' دورة.';
        } elseif ($targetType === 'workshop') {
            $fixedHeaders = ['عنوان الورشة *', 'الفرع', 'القسم', 'تاريخ البداية', 'تاريخ النهاية'];
            $fixedCount = 5;
            $instruction = 'كل صف = ورشة واحدة. العنوان إلزامي + الفرع أو القسم أحدهما إلزامي. حتى ' . $maxRows . ' ورشة.';
        } elseif ($targetType === 'unit') {
            $fixedHeaders = ['نوع الوحدة *', 'رمز الوحدة *', 'الفترة *', 'الفرع', 'القسم'];
            $fixedCount = 5;
            $instruction = 'كل صف = وحدة واحدة (فرع/قسم) مع فترة. النوع (branch/department) + الرمز (اسم الفرع/القسم) + الفترة (YYYY-MM أو schedule_id) إلزامي. حتى ' . $maxRows . ' وحدة.';
        } elseif (in_array($targetType, ['employee_in_course','employee_in_workshop'], true)) {
            $fixedHeaders = ['الرقم الوظيفي *', 'الاسم الكامل *', 'معرف السياق *', 'الفرع', 'القسم'];
            $fixedCount = 5;
            $ctx = $targetType === 'employee_in_course' ? 'الدورة' : 'الورشة';
            $instruction = 'كل صف = موظف داخل '.$ctx.'. الرقم أو الاسم + معرف '.$ctx.' إلزامي. حتى ' . $maxRows . ' صف.';
        } else {
            $fixedHeaders = ['الرقم الوظيفي *', 'الاسم الكامل *', 'الصفة أو الكنية', 'الفرع *', 'القسم *'];
            $fixedCount = 5;
            $instruction = 'كل صف = موظف واحد + تقييم واحد. الرقم الوظيفي أو الاسم الكامل أحدهما إلزامي. التاريخ YYYY-MM-DD أو DD-MM-YYYY وليس مستقبلاً. الأعمدة الصفراء للتحرير فقط. حتى ' . $maxRows . ' موظف.';
        }
        $tailCount = 4;
        $colCount = $fixedCount + count($sections) + $tailCount;
        $lastCol = $this->colLetter($colCount);
        $helperColIdx = $colCount + 1;
        $helperCol = $this->colLetter($helperColIdx);
        $titleText = 'قالب استيراد التقييم — ' . $template['template_name'] . ' [' . $targetType . '] | معرف القالب: ' . $templateId . ' | المقيّم: ' . $evaluatorEmployeeId . ' | الحد: ' . $maxRows;
        $sheet->setCellValue('A1', $titleText);
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0F5B50');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E9');
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->setCellValue('A2', $instruction);
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('555555');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $grouped = [];
        foreach ($sections as $section) {
            $cName = $section['criterion_name'];
            if (!isset($grouped[$cName])) {
                $grouped[$cName] = ['weight' => $section['criterion_weight'], 'sections' => []];
            }
            $grouped[$cName]['sections'][] = $section;
        }
        // fixedHeaders already defined above
        foreach ($fixedHeaders as $idx => $header) {
            $col = $this->colLetter($idx + 1);
            $sheet->setCellValue($col . '3', $header);
            $sheet->mergeCells($col . '3:' . $col . '4');
        }
        $sheet->getStyle('A3:E4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A3:E4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F5B50');
        $sheet->getStyle('A3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A3:E4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F5B50');
        $currentCol = 6;
        $sectionHints = [];
        foreach ($grouped as $cName => $group) {
            $groupSize = count($group['sections']);
            $startCol = $this->colLetter($currentCol);
            $endCol = $this->colLetter($currentCol + $groupSize - 1);
            $weightText = number_format((float) $group['weight'], 0) . '%';
            $criterionLabel = $cName . ' (' . $weightText . ')';
            $sheet->setCellValue($startCol . '3', $criterionLabel);
            if ($groupSize > 1) {
                $sheet->mergeCells($startCol . '3:' . $endCol . '3');
            }
            $sheet->getStyle($startCol . '3:' . $endCol . '3')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($startCol . '3:' . $endCol . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F5B50');
            $sheet->getStyle($startCol . '3:' . $endCol . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle($startCol . '3:' . $endCol . '3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F5B50');
            foreach ($group['sections'] as $section) {
                $col = $this->colLetter($currentCol);
                $typeLabel = $this->typeLabel($section['evaluation_type']);
                $sectionLabel = $section['section_name'] . ' [' . $typeLabel . ']';
                $sheet->setCellValue($col . '4', $sectionLabel);
                $sectionHints[$col] = $this->sectionHint($section);
                $currentCol++;
            }
        }
        $sheet->getStyle($this->colLetter($fixedCount + 1) . '4:' . $this->colLetter($fixedCount + count($sections)) . '4')->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('0F5B50');
        $sheet->getStyle($this->colLetter($fixedCount + 1) . '4:' . $this->colLetter($fixedCount + count($sections)) . '4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1E7DD');
        $sheet->getStyle($this->colLetter($fixedCount + 1) . '4:' . $this->colLetter($fixedCount + count($sections)) . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($this->colLetter($fixedCount + 1) . '4:' . $this->colLetter($fixedCount + count($sections)) . '4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F5B50');
        $sheet->getStyle($this->colLetter($fixedCount + 1) . '3:' . $lastCol . '3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F5B50');
        $tailHeaders = ['ملاحظة عامة', 'تاريخ التقييم *', 'مسؤول التقييم *', 'الصفة'];
        $tailStartCol = $fixedCount + 1 + count($sections);
        foreach ($tailHeaders as $idx => $h) {
            $col = $this->colLetter($tailStartCol + $idx);
            $sheet->setCellValue($col . '3', $h);
            $sheet->mergeCells($col . '3:' . $col . '4');
        }
        $tailRange = $this->colLetter($tailStartCol) . '3:' . $lastCol . '4';
        $sheet->getStyle($tailRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($tailRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('374151');
        $sheet->getStyle($tailRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($tailRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F5B50');
        if ($targetType === 'course') {
            $hintRow = ['اسم الدورة (نص حر)','اختر من القائمة','اختر من القائمة','YYYY-MM-DD','YYYY-MM-DD'];
        } elseif ($targetType === 'workshop') {
            $hintRow = ['اسم الورشة (نص حر)','اختر من القائمة','اختر من القائمة','YYYY-MM-DD','YYYY-MM-DD'];
        } elseif ($targetType === 'unit') {
            $hintRow = ['branch أو department','اسم الفرع/القسم','YYYY-MM أو schedule_id','اختر من القائمة','اختر من القائمة'];
        } elseif (in_array($targetType, ['employee_in_course','employee_in_workshop'], true)) {
            $hintRow = ['رمز الموظف','الاسم الكامل','معرف السياق (رقم الدورة/الورشة)','اختر من القائمة','اختر من القائمة'];
        } else {
            $hintRow = ['رمز فريد — أو اتركه واملأ الاسم','الاسم الكامل — أو اتركه واملأ الرمز','اختياري','اختر من القائمة','اختر من القائمة'];
        }
        for ($i = $fixedCount + 1; $i < $tailStartCol; $i++) {
            $col = $this->colLetter($i);
            $hintRow[] = $sectionHints[$col] ?? '';
        }
        $hintRow[] = 'نص حر حتى 4000 حرف';
        $hintRow[] = 'YYYY-MM-DD أو DD-MM-YYYY مثال 2026-05-20 أو 20-05-2026';
        $hintRow[] = 'يُملأ تلقائياً — قابل للتعديل';
        $hintRow[] = 'صفة المقيّم';
        $sheet->fromArray($hintRow, null, 'A5');
        $sheet->getStyle('A5:' . $lastCol . '5')->getFont()->setSize(7)->setItalic(true)->getColor()->setRGB('6B7280');
        $sheet->getStyle('A5:' . $lastCol . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5:' . $lastCol . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $dataStartRow = 6;
        $dataEndRow = $dataStartRow + $maxRows - 1;
        $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $dataEndRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4');
        $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $dataEndRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $dataEndRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
        $sheet->getStyle('A1:' . $lastCol . '5')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $sheet->getColumnDimension($helperCol)->setVisible(false);
        $sheet->setCellValue($helperCol . '5', 'branch_id_helper');
        for ($r = $dataStartRow; $r <= $dataEndRow; $r++) {
            $sheet->setCellValue($helperCol . $r, '=IF(D' . $r . '="","",IFERROR(INDEX(' . self::LOOKUP_SHEET . '!$A:$A,MATCH(D' . $r . ',' . self::LOOKUP_SHEET . '!$B:$B,0)),""))');
        }
        $sheet->getStyle($helperCol . $dataStartRow . ':' . $helperCol . $dataEndRow)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        $this->buildLookupSheet($book);
        if (in_array($targetType, ['employee','employee_in_course','employee_in_workshop'], true)) {
            $prefill = $this->getPrefillEmployees($branchId, $departmentId, $maxRows);
            $rowIdx = $dataStartRow;
            foreach ($prefill as $emp) {
                if ($rowIdx > $dataEndRow) break;
                $sheet->setCellValue('A' . $rowIdx, $emp['emp_code'] ?? '');
                $sheet->setCellValue('B' . $rowIdx, $emp['full_name'] ?? '');
                $sheet->setCellValue('C' . $rowIdx, $emp['nickname'] ?? '');
                $sheet->setCellValue('D' . $rowIdx, $emp['branch_name'] ?? '');
                $sheet->setCellValue('E' . $rowIdx, $emp['department_name'] ?? '');
                $rowIdx++;
            }
        }
        if (in_array($targetType, ['course', 'workshop'], true)) {
            $prefillItems = [];
            $effectiveMode = $mode ?? 'prefill';
            if ($effectiveMode !== 'empty') {
                $pdo = $this->database->getPdo();
                if ($targetType === 'course') {
                    $sql = 'SELECT cc.id as catalog_id, cc.title as course_title, b.name as branch_name, d.name as department_name, tc.start_date, tc.end_date FROM course_catalog cc LEFT JOIN training_courses tc ON tc.catalog_id = cc.id LEFT JOIN branches b ON tc.branch_id = b.id LEFT JOIN departments d ON tc.department_id = d.id';
                    if (!empty($prefillIds)) {
                        $placeholders = implode(',', array_fill(0, count($prefillIds), '?'));
                        $sql .= ' WHERE cc.id IN (' . $placeholders . ')';
                        $sql .= ' ORDER BY cc.title ASC';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($prefillIds);
                    } else {
                        $sql .= ' ORDER BY cc.title ASC LIMIT ' . $maxRows;
                        $stmt = $pdo->query($sql);
                    }
                    $prefillItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $sql = 'SELECT wc.id as catalog_id, wc.title as workshop_title, b.name as branch_name, d.name as department_name, tw.start_date, tw.end_date FROM workshop_catalog wc LEFT JOIN training_workshops tw ON tw.catalog_id = wc.id LEFT JOIN branches b ON tw.branch_id = b.id LEFT JOIN departments d ON tw.department_id = d.id';
                    if (!empty($prefillIds)) {
                        $placeholders = implode(',', array_fill(0, count($prefillIds), '?'));
                        $sql .= ' WHERE wc.id IN (' . $placeholders . ')';
                        $sql .= ' ORDER BY wc.title ASC';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($prefillIds);
                    } else {
                        $sql .= ' ORDER BY wc.title ASC LIMIT ' . $maxRows;
                        $stmt = $pdo->query($sql);
                    }
                    $prefillItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            $rowIdx = $dataStartRow;
            foreach ($prefillItems as $item) {
                if ($rowIdx > $dataEndRow) break;
                $sheet->setCellValue('A' . $rowIdx, $targetType === 'course' ? ($item['course_title'] ?? '') : ($item['workshop_title'] ?? ''));
                $sheet->setCellValue('B' . $rowIdx, $item['branch_name'] ?? '');
                $sheet->setCellValue('C' . $rowIdx, $item['department_name'] ?? '');
                $sheet->setCellValue('D' . $rowIdx, $item['start_date'] ?? '');
                $sheet->setCellValue('E' . $rowIdx, $item['end_date'] ?? '');
                $rowIdx++;
            }
        }
        $this->applyDropdowns($sheet, $branches, $departments, $sections, $dataStartRow, $dataEndRow, $tailStartCol);
        if (in_array($targetType, ['course', 'workshop'], true)) {
            $this->applyTrainingDropdowns($sheet, $branches, $departments, $dataStartRow, $dataEndRow);
        }
        $this->applyDuplicateFormatting($sheet, $dataStartRow, $dataEndRow);
        $this->applyDateFormatting($sheet, $dataStartRow, $dataEndRow, $tailStartCol);
        $sheet->freezePane('A6');
        $sheet->setAutoFilter('A4:' . $lastCol . '4');
        if (in_array($targetType, ['course', 'workshop'], true)) {
            $widths = [28, 18, 20, 14, 14];
        } else {
            $widths = [16, 24, 18, 20, 20];
        }
        foreach ($widths as $idx => $width) {
            $sheet->getColumnDimension($this->colLetter($idx + 1))->setWidth($width);
        }
        for ($i = $fixedCount + 1; $i < $tailStartCol; $i++) {
            $sheet->getColumnDimension($this->colLetter($i))->setWidth(18);
        }
        $sheet->getColumnDimension($this->colLetter($tailStartCol))->setWidth(28);
        $sheet->getColumnDimension($this->colLetter($tailStartCol + 1))->setWidth(16);
        $sheet->getColumnDimension($this->colLetter($tailStartCol + 2))->setWidth(22);
        $sheet->getColumnDimension($this->colLetter($tailStartCol + 3))->setWidth(18);
        $sheet->getRowDimension(3)->setRowHeight(26);
        $sheet->getRowDimension(4)->setRowHeight(28);
        $sheet->getRowDimension(5)->setRowHeight(18);
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword(self::SHEET_PASSWORD);
        $sheet->getProtection()->setSort(false);
        $sheet->getProtection()->setInsertRows(false);
        $sheet->getProtection()->setFormatCells(false);
        $book->getProperties()->setCreator('نظام تقييم الموظفين')->setTitle('قالب استيراد — ' . $template['template_name'])->setDescription('معرف القالب: ' . $templateId . ' | المقيّم: ' . $evaluatorEmployeeId);
        $book->setActiveSheetIndex(0);
        return $book;
    }
    public function previewUpload(array $upload, int $userId, int $evaluatorEmployeeId): array
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            throw new RuntimeException('يرجى اختيار ملف XLSX صالح للمعاينة.');
        }
        return $this->previewWorkbookPath((string) $upload['tmp_name'], (string) $upload['name'], (int) $upload['size'], $userId, $evaluatorEmployeeId);
    }
    public function previewWorkbookPath(string $path, string $originalFilename, int $size, int $userId, int $evaluatorEmployeeId): array
    {
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('حجم ملف الاستيراد يجب ألا يتجاوز 10 ميغابايت.');
        }
        if (strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('صيغة الملف المسموحة هي XLSX فقط.');
        }
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('تعذر قراءة ملف الاستيراد.');
        }
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName(self::SHEET_NAME);
        if (!$sheet instanceof Worksheet) {
            $sheet = $book->getActiveSheet();
            if ($sheet === null || $sheet->getHighestRow() < 3) {
                throw new RuntimeException('الملف لا يحتوي على ورقة البيانات المطلوبة.');
            }
        }
        $allRows = $sheet->toArray(null, true, true, false);
        if (count($allRows) < 3) {
            throw new RuntimeException('الملف لا يحتوي على ترويسات صالحة.');
        }
        $templateId = $this->extractTemplateId($sheet, $book);
        if ($templateId <= 0) {
            throw new RuntimeException('تعذر تحديد قالب التقييم من الملف. يرجى إعادة تنزيل القالب من النظام.');
        }
        $template = $this->getTemplateOrFail($templateId);
        $sections = $this->getSectionsOrdered($templateId);
        if ($sections === []) {
            throw new RuntimeException('القالب المرتبط بالملف لا يحتوي على أي بنود.');
        }
        $branchMap = $this->getBranchMap();
        $departmentMap = $this->getDepartmentMap();
        $pdo = $this->database->getPdo();
        $pdo->beginTransaction();
        try {
            $batch = $pdo->prepare('INSERT INTO evaluation_import_batches (uploaded_by_user_id, original_filename, status) VALUES (:user_id, :filename, "previewed")');
            $batch->execute(['user_id' => $userId, 'filename' => basename($originalFilename)]);
            $batchId = (int) $pdo->lastInsertId();
            $rowInsert = $pdo->prepare('INSERT INTO evaluation_import_rows (batch_id, source_row_number, payload_json, is_valid, status) VALUES (:batch_id, :source_row, :payload, :valid, :status)');
            $errorInsert = $pdo->prepare('INSERT INTO evaluation_import_row_errors (batch_id, import_row_id, source_row_number, error_code, error_message) VALUES (:batch_id, :row_id, :source_row, :code, :message)');
            $previewRows = [];
            $validCount = 0;
            $invalidCount = 0;
            $seenEmpCodes = [];
            $seenNames = [];
            // جميع الأنواع لها 5 أعمدة ثابتة بعد إلغاء عمود الحالة من الدورات/الورش
            $fixedColCount = 5;
            $tailStartColIndex = $fixedColCount + count($sections);
            $dataRows = array_slice($allRows, 5);
            $excelRowNumber = 6;
            foreach ($dataRows as $row) {
                $isEmptyRow = true;
                foreach ($row as $cell) {
                    if (trim((string) $cell) !== '') { $isEmptyRow = false; break; }
                }
                if ($isEmptyRow) { $excelRowNumber++; continue; }
                if (count($previewRows) >= self::MAX_ROWS_LIMIT) { break; }
                $targetType = $template['target_type'] ?? 'employee';
                $empCode=''; $fullName=''; $nickname=''; $branchName=''; $departmentName='';
                $courseId=''; $workshopId=''; $unitType=''; $unitCode=''; $period='';
                $startDate=''; $endDate='';
                if (in_array($targetType, ['employee','employee_in_course','employee_in_workshop'], true)) {
                    $empCode = trim((string) ($row[0] ?? ''));
                    $fullName = trim((string) ($row[1] ?? ''));
                    if (in_array($targetType, ['employee_in_course','employee_in_workshop'], true)) {
                        $contextId = trim((string) ($row[2] ?? ''));
                        $branchName = trim((string) ($row[3] ?? ''));
                        $departmentName = trim((string) ($row[4] ?? ''));
                        if ($targetType==='employee_in_course') $courseId=$contextId; else $workshopId=$contextId;
                    } else {
                        $nickname = trim((string) ($row[2] ?? ''));
                        $branchName = trim((string) ($row[3] ?? ''));
                        $departmentName = trim((string) ($row[4] ?? ''));
                    }
                } elseif ($targetType==='course') {
                    $fullName = trim((string) ($row[0] ?? ''));
                    $branchName = trim((string) ($row[1] ?? ''));
                    $departmentName = trim((string) ($row[2] ?? ''));
                    $startDate = trim((string) ($row[3] ?? ''));
                    $endDate = trim((string) ($row[4] ?? ''));
                } elseif ($targetType==='workshop') {
                    $fullName = trim((string) ($row[0] ?? ''));
                    $branchName = trim((string) ($row[1] ?? ''));
                    $departmentName = trim((string) ($row[2] ?? ''));
                    $startDate = trim((string) ($row[3] ?? ''));
                    $endDate = trim((string) ($row[4] ?? ''));
                } elseif ($targetType==='unit') {
                    $unitType = trim((string) ($row[0] ?? ''));
                    $unitCode = trim((string) ($row[1] ?? ''));
                    $period = trim((string) ($row[2] ?? ''));
                    $branchName = trim((string) ($row[3] ?? ''));
                    $departmentName = trim((string) ($row[4] ?? ''));
                }
                $sectionValues = [];
                foreach ($sections as $idx => $section) {
                    $colIdx = $fixedColCount + $idx;
                    $sectionValues[$section['id']] = trim((string) ($row[$colIdx] ?? ''));
                }
                $notes = trim((string) ($row[$tailStartColIndex] ?? ''));
                $rawDateCell = $row[$tailStartColIndex + 1] ?? '';
                $evaluatorName = trim((string) ($row[$tailStartColIndex + 2] ?? ''));
                $evaluatorTitle = trim((string) ($row[$tailStartColIndex + 3] ?? ''));
                if (is_numeric($rawDateCell) && $rawDateCell !== '' && $rawDateCell !== null) {
                    try { $dateRaw = Date::excelToDateTimeObject((float) $rawDateCell)->format('Y-m-d'); } catch (\Throwable $e) { $dateRaw = trim((string) $rawDateCell); }
                } else { $dateRaw = trim((string) $rawDateCell); }
                $payload = [
                    'template_id' => $templateId,
                    'target_type' => $targetType,
                    'evaluator_employee_id' => $evaluatorEmployeeId,
                    'emp_code' => $empCode,
                    'full_name' => $fullName,
                    'nickname' => $nickname,
                    'branch_name' => $branchName,
                    'department_name' => $departmentName,
                    'course_id' => $courseId,
                    'workshop_id' => $workshopId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'unit_type' => $unitType,
                    'unit_code' => $unitCode,
                    'period' => $period,
                    'section_values' => $sectionValues,
                    'notes' => $notes,
                    'evaluation_date' => $dateRaw,
                    'evaluator_name' => $evaluatorName,
                    'evaluator_title' => $evaluatorTitle,
                    'excel_row' => $excelRowNumber,
                ];
                $errors = $this->validateSingleRow($payload, $evaluatorEmployeeId, $seenEmpCodes, $seenNames, $branchMap, $departmentMap, $sections);
                if ($empCode !== '' && isset($seenEmpCodes[$empCode])) {
                    $prevRow = $seenEmpCodes[$empCode];
                    $already=false; foreach($errors as $e) if($e['code']==='EMP_CODE_DUPLICATE') {$already=true;break;}
                    if(!$already) $errors[]=['code'=>'EMP_CODE_DUPLICATE','message'=>'رمز الموظف مكرر داخل الملف (سبق في الصف '.$prevRow.').','column'=>'A'];
                } elseif ($empCode !== '') { $seenEmpCodes[$empCode]=$excelRowNumber; }
                if ($empCode===''){
                    $nameKey = mb_strtolower(trim($fullName));
                    if($nameKey!=='' && isset($seenNames[$nameKey])){
                        $prevRow=$seenNames[$nameKey];
                        $already=false; foreach($errors as $e) if($e['code']==='NAME_DUPLICATE') {$already=true;break;}
                        if(!$already) $errors[]=['code'=>'NAME_DUPLICATE','message'=>'الاسم مكرر داخل الملف بدون رقم وظيفي (سبق في الصف '.$prevRow.'). يرجى إدخال الرقم الوظيفي.','column'=>'B'];
                    } elseif($nameKey!=='') { $seenNames[$nameKey]=$excelRowNumber; }
                }
                $isValid = $errors === [];
                $colsWithErrors=[]; foreach($errors as $err) if(!empty($err['column'])) $colsWithErrors[]=$err['column'];
                $rowInsert->execute(['batch_id'=>$batchId,'source_row'=>$excelRowNumber,'payload'=>json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'valid'=>$isValid?1:0,'status'=>$isValid?'valid':'invalid']);
                $importRowId=(int)$pdo->lastInsertId();
                foreach($errors as $error){ $errorInsert->execute(['batch_id'=>$batchId,'row_id'=>$importRowId,'source_row'=>$excelRowNumber,'code'=>$error['code'],'message'=>$error['message']]); }
                $previewRows[]=['row'=>$excelRowNumber,'payload'=>$payload,'section_values'=>$sectionValues,'notes'=>$notes,'columns_with_errors'=>$colsWithErrors,'errors'=>array_column($errors,'message')];
                $isValid?$validCount++:$invalidCount++;
                $excelRowNumber++;
            }
            if ($previewRows === []) { $pdo->rollBack(); throw new RuntimeException('لا يحتوي الملف على أي صفوف بيانات قابلة للاستيراد.'); }
            $summary=['total'=>count($previewRows),'valid'=>$validCount,'invalid'=>$invalidCount,'template_id'=>$templateId,'template_name'=>$template['template_name'],'target_type'=>$targetType];
            $pdo->prepare('UPDATE evaluation_import_batches SET total_rows=:total, valid_rows=:valid, invalid_rows=:invalid, summary_json=:summary WHERE id=:id')->execute(['total'=>$summary['total'],'valid'=>$validCount,'invalid'=>$invalidCount,'summary'=>json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'id'=>$batchId]);
            $pdo->commit();
            return ['batch_id'=>$batchId,'total'=>$summary['total'],'valid'=>$validCount,'invalid'=>$invalidCount,'template_id'=>$templateId,'template_name'=>$template['template_name'],'target_type'=>$targetType,'rows'=>$previewRows];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }
    public function commitBatch(int $batchId, int $userId, int $evaluatorEmployeeId): array
    {
        $pdo=$this->database->getPdo();
        $batchQuery=$pdo->prepare('SELECT * FROM evaluation_import_batches WHERE id=:id AND uploaded_by_user_id=:user_id FOR UPDATE');
        $pdo->beginTransaction();
        try {
            $batchQuery->execute(['id'=>$batchId,'user_id'=>$userId]);
            $batch=$batchQuery->fetch(PDO::FETCH_ASSOC);
            if(!$batch||$batch['status']!=='previewed'){ throw new RuntimeException('دفعة الاستيراد غير متاحة للالتزام.'); }
            if((int)$batch['invalid_rows']>0||(int)$batch['valid_rows']===0){ throw new RuntimeException('لا يمكن الالتزام قبل تصحيح جميع أخطاء المعاينة وإعادة الرفع.'); }
            $summary=json_decode((string)($batch['summary_json']??'{}'),true);
            $templateId=(int)($summary['template_id']??0);
            if($templateId<=0){ throw new RuntimeException('معرف القالب مفقود من ملخص الدفعة.'); }
            $template=$this->getTemplateOrFail($templateId);
            $sections=$this->getSectionsOrdered($templateId);
            $sectionMap=[]; foreach($sections as $s){ $sectionMap[(int)$s['id']]=$s; }
            $branchMap=$this->getBranchMap();
            $departmentMap=$this->getDepartmentMap();
            $targetType=$template['target_type']??'employee';
            $rowsQuery=$pdo->prepare('SELECT id, payload_json FROM evaluation_import_rows WHERE batch_id=:batch_id AND is_valid=1 ORDER BY source_row_number FOR UPDATE');
            $rowsQuery->execute(['batch_id'=>$batchId]);
            $rows=$rowsQuery->fetchAll(PDO::FETCH_ASSOC);
            if($rows===[]){ throw new RuntimeException('لا توجد صفوف صالحة للالتزام.'); }
            $recheckSeenCodes=[]; $recheckSeenNames=[]; $validatedPayloads=[];
            foreach($rows as $row){
                $payload=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);
                if((int)$payload['template_id']!==$templateId){ $payload['template_id']=$templateId; }
                $payload['target_type']=$targetType;
                $payload['evaluator_employee_id']=$evaluatorEmployeeId;
                $errors=$this->validateSingleRow($payload,$evaluatorEmployeeId,$recheckSeenCodes,$recheckSeenNames,$branchMap,$departmentMap,$sections);
                $empCode=trim((string)($payload['emp_code']??''));
                $fullName=trim((string)($payload['full_name']??''));
                if(in_array($targetType,['employee','employee_in_course','employee_in_workshop'],true)){
                    if($empCode!==''){
                        if(isset($recheckSeenCodes[$empCode])){ $errors[]=['code'=>'EMP_CODE_DUPLICATE','message'=>'رمز الموظف مكرر داخل الملف.']; }
                        else { $recheckSeenCodes[$empCode]=(int)$payload['excel_row']; }
                    } elseif($fullName!==''){
                        $k=mb_strtolower(trim($fullName));
                        if(isset($recheckSeenNames[$k])){ $errors[]=['code'=>'NAME_DUPLICATE','message'=>'الاسم مكرر بدون رقم وظيفي.']; }
                        else { $recheckSeenNames[$k]=(int)$payload['excel_row']; }
                    }
                }
                if($errors!==[]){ throw new RuntimeException('تعذر إعادة تحقق أحد صفوف الدفعة قبل الالتزام: '.$errors[0]['message']); }
                $validatedPayloads[]=$payload;
            }
            $pdo->prepare('UPDATE evaluation_import_batches SET status="committing" WHERE id=:id')->execute(['id'=>$batchId]);
            $createdEmployees=0; $committedEvaluations=0;
            $answerInsert=$pdo->prepare('INSERT INTO evaluation_answers (evaluation_id, section_id, raw_value, notes, normalized_score) VALUES (:evaluation_id, :section_id, :raw_value, :notes, :normalized_score)');
            foreach($validatedPayloads as $payload){
                $empCode=trim((string)($payload['emp_code']??''));
                $fullName=trim((string)($payload['full_name']??''));
                $nickname=trim((string)($payload['nickname']??''));
                $branchName=trim((string)($payload['branch_name']??''));
                $departmentName=trim((string)($payload['department_name']??''));
                $evaluationDate=trim((string)($payload['evaluation_date']??''));
                if($evaluationDate===''){ $evaluationDate=date('Y-m-d'); }
                $rowEvaluatorName=trim((string)($payload['evaluator_name']??''));
                $rowEvaluatorTitle=trim((string)($payload['evaluator_title']??''));
                $rowEvaluatorEmployeeId=null;
                if($rowEvaluatorName!==''){
                    $foundEval=$this->findEmployeeByName($rowEvaluatorName,'','');
                    if($foundEval!==false) $rowEvaluatorEmployeeId=(int)$foundEval['id'];
                }
                if(in_array($targetType,['course','workshop'],true)){
                    $entityName=trim((string)($fullName));
                    $branchIdVal=$branchMap[$branchName]??null;
                    $deptIdVal=$departmentMap[$departmentName]??null;
                    $startDateVal=$payload['start_date']??date('Y-m-d');
                    $endDateVal=$payload['end_date']??date('Y-m-d');
                    $statusVal='completed';
                    if($startDateVal!==''){ $sd=$this->parseFlexibleDate($startDateVal); if($sd) $startDateVal=$sd; }
                    if($endDateVal!==''){ $ed=$this->parseFlexibleDate($endDateVal); if($ed) $endDateVal=$ed; }
                    if($targetType==='course'){
                        $entityId=$this->findOrCreateCourseByName($entityName,$branchIdVal,$deptIdVal,$statusVal,$startDateVal,$endDateVal,$userId);
                    } else {
                        $entityId=$this->findOrCreateWorkshopByName($entityName,$branchIdVal,$deptIdVal,$statusVal,$startDateVal,$endDateVal,$userId);
                    }
                    $evalData=[
                        'template_id'=>$templateId,'entity_type'=>$targetType,'entity_id'=>$entityId,
                        'evaluated_employee_id'=>null,'evaluator_employee_id'=>$rowEvaluatorEmployeeId,
                        'evaluator_name'=>$rowEvaluatorName,'evaluator_title'=>$rowEvaluatorTitle,
                        'evaluation_date'=>$evaluationDate,'created_by_user_id'=>$userId,
                        'employee_name'=>$entityName,'employee_code'=>(string)$entityId,
                        'nickname'=>null,'branch_name'=>$branchName,'department_name'=>$departmentName,
                        'position_name'=>'','entity_snapshot'=>['entity_type'=>$targetType,'entity_id'=>$entityId,'name'=>$entityName],
                        'status'=>'completed',
                    ];
                    $existingEval=$pdo->prepare('SELECT id, status FROM evaluations WHERE template_id=:template_id AND entity_type=:et AND entity_id=:eid AND evaluation_date=:edate LIMIT 1 FOR UPDATE');
                    $existingEval->execute(['template_id'=>$templateId,'et'=>$targetType,'eid'=>$entityId,'edate'=>$evaluationDate]);
                    $existing=$existingEval->fetch(PDO::FETCH_ASSOC);
                    if($existing!==false&&$existing['status']!=='draft'){ throw new RuntimeException('يوجد تقييم نهائي لنفس الكيان والقالب والتاريخ: '.$entityName.' بتاريخ '.$evaluationDate); }
                    if($existing!==false){
                        $evaluationId=(int)$existing['id'];
                        $pdo->prepare('DELETE FROM evaluation_answers WHERE evaluation_id=:id')->execute(['id'=>$evaluationId]);
                        $pdo->prepare('UPDATE evaluations SET final_score=NULL, status="draft", evaluator_employee_id=:eval_eid, evaluator_name=:eval_name, evaluator_title=:eval_title, updated_at=NOW() WHERE id=:id')->execute(['id'=>$evaluationId,'eval_eid'=>$rowEvaluatorEmployeeId,'eval_name'=>$rowEvaluatorName,'eval_title'=>$rowEvaluatorTitle]);
                    } else {
                        $evalData['status']='draft';
                        $evaluationId=$this->createEvaluation($evalData);
                        if(!$evaluationId) throw new RuntimeException('فشل إنشاء تقييم الكيان: '.$entityName);
                    }
                } elseif($targetType==='unit'){
                    $unitType=trim((string)($payload['unit_type']??''));
                    $unitCode=trim((string)($payload['unit_code']??''));
                    $period=trim((string)($payload['period']??''));
                    $resolvedEntityType=($unitType==='department'||(!in_array($unitType,['branch','department'],true)&&isset($departmentMap[$unitCode])))?'department':'branch';
                    $unitId=(int)($payload['_unit_id']??0);
                    if($unitId<=0){
                        if($resolvedEntityType==='branch'&&isset($branchMap[$unitCode])){ $unitId=$branchMap[$unitCode]; }
                        elseif($resolvedEntityType==='department'&&isset($departmentMap[$unitCode])){ $unitId=$departmentMap[$unitCode]; }
                        else { throw new RuntimeException('الوحدة غير موجودة: '.$unitCode); }
                    }
                    $scheduleId=(int)($payload['_schedule_id']??0);
                    if($scheduleId<=0){
                        $schedCheck=$pdo->prepare('SELECT id FROM evaluation_schedules WHERE template_id=:tid AND entity_type=:et AND unit_id=:uid LIMIT 1');
                        $schedCheck->execute(['tid'=>$templateId,'et'=>$resolvedEntityType,'uid'=>$unitId]);
                        $schedRow=$schedCheck->fetch(PDO::FETCH_ASSOC);
                        if($schedRow){ $scheduleId=(int)$schedRow['id']; }
                        else {
                            $schedInsert=$pdo->prepare('INSERT INTO evaluation_schedules (template_id, entity_type, unit_id, periodicity, title, start_date, is_active, created_by) VALUES (:tid, :et, :uid, "monthly", :title, :start, 1, :uid2)');
                            $schedInsert->execute(['tid'=>$templateId,'et'=>$resolvedEntityType,'uid'=>$unitId,'title'=>$unitCode.' - '.$period,'start'=>$period.'-01','uid2'=>$userId]);
                            $scheduleId=(int)$pdo->lastInsertId();
                        }
                    }
                    $branchIdVal=$branchMap[$branchName]??null;
                    $deptIdVal=$departmentMap[$departmentName]??null;
                    $evalData=[
                        'template_id'=>$templateId,'entity_type'=>$resolvedEntityType,'entity_id'=>$unitId,
                        'context_type'=>null,'context_id'=>null,'schedule_id'=>$scheduleId,
                        'evaluated_employee_id'=>null,'evaluator_employee_id'=>$rowEvaluatorEmployeeId,
                        'evaluator_name'=>$rowEvaluatorName,'evaluator_title'=>$rowEvaluatorTitle,
                        'evaluation_date'=>$evaluationDate,'created_by_user_id'=>$userId,
                        'employee_name'=>$unitCode,'employee_code'=>(string)$unitId,
                        'nickname'=>null,'branch_name'=>$branchName,'department_name'=>$departmentName,
                        'position_name'=>'','entity_snapshot'=>['entity_type'=>$resolvedEntityType,'unit_code'=>$unitCode,'period'=>$period],
                        'status'=>'pending_review',
                    ];
                    $existingEval=$pdo->prepare('SELECT id, status FROM evaluations WHERE template_id=:template_id AND entity_type=:et AND entity_id=:eid AND schedule_id=:sid AND evaluation_date=:edate LIMIT 1 FOR UPDATE');
                    $existingEval->execute(['template_id'=>$templateId,'et'=>$resolvedEntityType,'eid'=>$unitId,'sid'=>$scheduleId,'edate'=>$evaluationDate]);
                    $existing=$existingEval->fetch(PDO::FETCH_ASSOC);
                    if($existing!==false&&$existing['status']!=='draft'&&$existing['status']!=='pending_review'){
                        throw new RuntimeException('يوجد تقييم نهائي لنفس الوحدة والقالب والفترة: '.$unitCode.' بتاريخ '.$evaluationDate);
                    }
                    if($existing!==false){
                        $evaluationId=(int)$existing['id'];
                        $pdo->prepare('DELETE FROM evaluation_answers WHERE evaluation_id=:id')->execute(['id'=>$evaluationId]);
                        $pdo->prepare('UPDATE evaluations SET final_score=NULL, status="draft", evaluator_employee_id=:eval_eid, evaluator_name=:eval_name, evaluator_title=:eval_title, updated_at=NOW() WHERE id=:id')->execute(['id'=>$evaluationId,'eval_eid'=>$rowEvaluatorEmployeeId,'eval_name'=>$rowEvaluatorName,'eval_title'=>$rowEvaluatorTitle]);
                    } else {
                        $evalData['status']='draft';
                        $evaluationId=$this->createEvaluation($evalData);
                        if(!$evaluationId) throw new RuntimeException('فشل إنشاء تقييم الوحدة: '.$unitCode);
                    }
                } else {
                    $employee=false; $employeeId=0;
                    if($empCode!==''){ $employee=$this->findEmployeeByCode($empCode); }
                    if($employee===false&&$empCode===''&&$fullName!==''){ $employee=$this->findEmployeeByName($fullName,$branchName,$departmentName); }
                    if($employee===false){
                        $bId=$branchMap[$branchName]??null;
                        $dId=$departmentMap[$departmentName]??null;
                        $check=$pdo->prepare('SELECT id FROM employees WHERE emp_code=:code LIMIT 1');
                        $check->execute(['code'=>$empCode!==''?$empCode:'AUTO_'.uniqid()]);
                        if($check->fetch()!==false&&$empCode!==''){ throw new RuntimeException('رمز الموظف موجود مسبقاً: '.$empCode); }
                        $newCode=$empCode!==''?$empCode:'EMP'.str_pad((string)(1000+random_int(1,8999)),4,'0',STR_PAD_LEFT);
                        $pdo->prepare('INSERT INTO employees (emp_code, full_name, nickname, branch_id, department_id, is_active) VALUES (:emp_code, :full_name, :nickname, :branch_id, :department_id, 1)')->execute(['emp_code'=>$newCode,'full_name'=>$fullName!==''?$fullName:'موظف بدون اسم','nickname'=>$nickname!==''?$nickname:null,'branch_id'=>$bId,'department_id'=>$dId]);
                        $employeeId=(int)$pdo->lastInsertId();
                        if($empCode===''){ $empCode=$newCode; }
                        $createdEmployees++;
                    } else { $employeeId=(int)$employee['id']; }
                    $contextType=null; $contextId=null;
                    if($targetType==='employee_in_course'){
                        $contextType='course'; $contextId=(int)($payload['_context_id']??$payload['course_id']??0);
                    } elseif($targetType==='employee_in_workshop'){
                        $contextType='workshop'; $contextId=(int)($payload['_context_id']??$payload['workshop_id']??0);
                    }
                    $existingEvalQ=$pdo->prepare('SELECT id, status FROM evaluations WHERE evaluated_employee_id=:evaluated AND template_id=:template_id AND (evaluator_employee_id=:evaluator OR (evaluator_employee_id IS NULL AND evaluator_name=:eval_name)) AND evaluation_date=:eval_date AND (entity_type=:et OR entity_type IS NULL) LIMIT 1 FOR UPDATE');
                    $existingEvalQ->execute(['evaluated'=>$employeeId,'template_id'=>$templateId,'evaluator'=>$rowEvaluatorEmployeeId??0,'eval_name'=>$rowEvaluatorName,'eval_date'=>$evaluationDate,'et'=>'employee']);
                    $existing=$existingEvalQ->fetch(PDO::FETCH_ASSOC);
                    if($existing!==false&&$existing['status']!=='draft'){ throw new RuntimeException('يوجد تقييم نهائي لنفس الموظف والقالب والتاريخ: '.$empCode.' بتاريخ '.$evaluationDate); }
                    $snapshot=$this->getEmployeeSnapshot($employeeId);
                    if($existing!==false){
                        $evaluationId=(int)$existing['id'];
                        $pdo->prepare('DELETE FROM evaluation_answers WHERE evaluation_id=:id')->execute(['id'=>$evaluationId]);
                        $updateData=['id'=>$evaluationId,'notes'=>$payload['notes']??null,'nickname'=>$snapshot['nickname']??null,'eval_eid'=>$rowEvaluatorEmployeeId,'eval_name'=>$rowEvaluatorName,'eval_title'=>$rowEvaluatorTitle];
                        $pdo->prepare('UPDATE evaluations SET final_score=NULL, status="draft", notes=:notes, nickname=:nickname, evaluator_employee_id=:eval_eid, evaluator_name=:eval_name, evaluator_title=:eval_title, updated_at=NOW() WHERE id=:id')->execute($updateData);
                    } else {
                        $evalData=[
                            'template_id'=>$templateId,'entity_type'=>'employee','entity_id'=>$employeeId,
                            'evaluated_employee_id'=>$employeeId,'evaluator_employee_id'=>$rowEvaluatorEmployeeId,
                            'evaluator_name'=>$rowEvaluatorName,'evaluator_title'=>$rowEvaluatorTitle,
                            'evaluation_date'=>$evaluationDate,'created_by_user_id'=>$userId,
                            'employee_name'=>$snapshot['full_name'],'employee_code'=>$snapshot['emp_code'],
                            'nickname'=>$snapshot['nickname']??null,'branch_name'=>$snapshot['branch_name']??'',
                            'department_name'=>$snapshot['department_name']??'','position_name'=>$snapshot['position_name']??'',
                            'entity_snapshot'=>null,'status'=>'draft',
                        ];
                        $evalData['context_type']=$contextType;
                        $evalData['context_id']=$contextId;
                        $evaluationId=$this->createEvaluation($evalData);
                        if(!$evaluationId) throw new RuntimeException('فشل إنشاء تقييم الموظف: '.$empCode);
                    }
                }
                foreach($payload['section_values'] as $sectionId=>$value){
                    $sectionIdInt=(int)$sectionId;
                    $section=$sectionMap[$sectionIdInt]??null;
                    if($section===null) continue;
                    $trimmedValue=trim((string)$value);
                    if($trimmedValue==='') continue;
                    $answerInsert->execute(['evaluation_id'=>$evaluationId,'section_id'=>$sectionIdInt,'raw_value'=>$trimmedValue,'notes'=>null,'normalized_score'=>$this->normalize($section,$trimmedValue)]);
                }
                $final=$this->calculateFinalScore($evaluationId,$templateId);
                if($targetType==='unit'){
                    $pdo->prepare('UPDATE evaluations SET final_score=:score, status="completed", updated_at=NOW() WHERE id=:id')->execute(['score'=>$final,'id'=>$evaluationId]);
                } else {
                    $pdo->prepare('UPDATE evaluations SET final_score=:score, status="completed", updated_at=NOW() WHERE id=:id')->execute(['score'=>$final,'id'=>$evaluationId]);
                }
                $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (:user_id, "EVALUATION_EXCEL_IMPORTED", "evaluations", :evaluation_id, :details, :ip)')->execute(['user_id'=>$userId,'evaluation_id'=>$evaluationId,'details'=>json_encode(['batch_id'=>$batchId,'target_type'=>$targetType,'emp_code'=>$empCode,'evaluation_date'=>$evaluationDate],JSON_UNESCAPED_UNICODE),'ip'=>$_SERVER['REMOTE_ADDR']??null]);
                $committedEvaluations++;
            }
            $pdo->prepare('UPDATE evaluation_import_rows SET status="committed" WHERE batch_id=:id')->execute(['id'=>$batchId]);
            $pdo->prepare('UPDATE evaluation_import_batches SET status="completed", committed_at=NOW() WHERE id=:id')->execute(['id'=>$batchId]);
            $pdo->commit();
            return ['committed_evaluations'=>$committedEvaluations,'committed_rows'=>count($validatedPayloads),'created_employees'=>$createdEmployees];
        } catch(\Throwable $exception){
            if($pdo->inTransaction()){ $pdo->rollBack(); }
            throw $exception;
        }
    }
    private function createEvaluation(array $data): int|false
    {
        $pdo=$this->database->getPdo();
        $isUnit=!empty($data['entity_type'])&&in_array($data['entity_type'],['branch','department','course','workshop'],true);
        if($isUnit){
            $sql='INSERT INTO evaluations (template_id, entity_type, entity_id, context_type, context_id, schedule_id, evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluator_title, evaluation_date, created_by_user_id, employee_name, employee_code, nickname, branch_name, department_name, position_name, entity_snapshot, status) VALUES (:template_id, :entity_type, :entity_id, :context_type, :context_id, :schedule_id, :evaluated_employee_id, :evaluator_employee_id, :evaluator_name, :evaluator_title, :evaluation_date, :created_by_user_id, :employee_name, :employee_code, :nickname, :branch_name, :department_name, :position_name, :entity_snapshot, :status)';
        } else {
            $sql='INSERT INTO evaluations (template_id, entity_type, entity_id, context_type, context_id, evaluated_employee_id, evaluator_employee_id, evaluator_name, evaluator_title, evaluation_date, created_by_user_id, employee_name, employee_code, nickname, branch_name, department_name, position_name, entity_snapshot, status) VALUES (:template_id, :entity_type, :entity_id, :context_type, :context_id, :evaluated_employee_id, :evaluator_employee_id, :evaluator_name, :evaluator_title, :evaluation_date, :created_by_user_id, :employee_name, :employee_code, :nickname, :branch_name, :department_name, :position_name, :entity_snapshot, :status)';
        }
        $stmt=$pdo->prepare($sql);
        $params=[
            'template_id'=>$data['template_id'],'entity_type'=>$data['entity_type']??'employee',
            'entity_id'=>$data['entity_id']??$data['evaluated_employee_id']??null,
            'context_type'=>$data['context_type']??null,'context_id'=>$data['context_id']??null,
            'evaluated_employee_id'=>$data['evaluated_employee_id']??null,
            'evaluator_employee_id'=>$data['evaluator_employee_id']??null,
            'evaluator_name'=>$data['evaluator_name']??null,'evaluator_title'=>$data['evaluator_title']??null,
            'evaluation_date'=>$data['evaluation_date'],'created_by_user_id'=>$data['created_by_user_id'],
            'employee_name'=>$data['employee_name']??'','employee_code'=>$data['employee_code']??'',
            'nickname'=>$data['nickname']??null,'branch_name'=>$data['branch_name']??'',
            'department_name'=>$data['department_name']??'','position_name'=>$data['position_name']??'',
            'entity_snapshot'=>isset($data['entity_snapshot'])?json_encode($data['entity_snapshot'],JSON_UNESCAPED_UNICODE):null,
            'status'=>$data['status']??'draft',
        ];
        if($isUnit){ $params['schedule_id']=$data['schedule_id']??null; }
        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    }
    /**
     * تحليل تاريخ مرن — يقبل Y-m-d, d-m-Y, m-d-Y, Y/m/d, d/m/Y مع أو بدون أصفار.
     * @param string $raw النص الخام من خلية Excel
     * @return string|null تاريخ بصيغة Y-m-d أو null إذا فشل التحليل
     * @throws \Exception لا يرمي، يعيد null عند الفشل
     */
    private function parseFlexibleDate(string $raw): ?string
    {
        $raw=trim($raw);
        if($raw==='') return null;
        $raw=str_replace(['/', '.', '\\'], '-', $raw);
        $raw=preg_replace('/\s+/', '', $raw);
        $formats=['Y-m-d','d-m-Y','m-d-Y','Y-n-j','j-n-Y','n-j-Y','d-m-y','m-d-y','Y/m/d','d/m/Y'];
        foreach($formats as $fmt){
            $dt=\DateTime::createFromFormat($fmt, $raw);
            if($dt!==false && $dt->format($fmt)===$raw){
                return $dt->format('Y-m-d');
            }
        }
        $parts=explode('-', $raw);
        if(count($parts)===3){
            $p=array_map('intval',$parts);
            if($p[0]>31){
                $try=sprintf('%04d-%02d-%02d',$p[0],$p[1],$p[2]);
                $dt=\DateTime::createFromFormat('Y-m-d',$try);
                if($dt!==false) return $dt->format('Y-m-d');
            } else {
                $try=sprintf('%04d-%02d-%02d',$p[2],$p[1],$p[0]);
                $dt=\DateTime::createFromFormat('Y-m-d',$try);
                if($dt!==false) return $dt->format('Y-m-d');
                $try=sprintf('%04d-%02d-%02d',$p[2],$p[0],$p[1]);
                $dt=\DateTime::createFromFormat('Y-m-d',$try);
                if($dt!==false) return $dt->format('Y-m-d');
            }
        }
        $ts=strtotime($raw);
        if($ts!==false) return date('Y-m-d',$ts);
        return null;
    }
    private function validateSingleRow(array &$payload, int $evaluatorEmployeeId, array $seenEmpCodes, array $seenNames, array $branchMap, array $departmentMap, array $sections): array
    {
        $errors=[];
        $targetType=$payload['target_type']??($payload['template_id']?$this->getTemplateTargetType((int)$payload['template_id']):'employee');
        $payload['target_type']=$targetType;
        $empCode=trim((string)($payload['emp_code']??''));
        $fullName=trim((string)($payload['full_name']??''));
        $nickname=trim((string)($payload['nickname']??''));
        $branchName=trim((string)($payload['branch_name']??''));
        $departmentName=trim((string)($payload['department_name']??''));
        $dateRaw=trim((string)($payload['evaluation_date']??''));
        $evaluatorName=trim((string)($payload['evaluator_name']??''));
        if(in_array($targetType,['course','workshop'],true)){
            $startDate=trim((string)($payload['start_date']??''));
            $endDate=trim((string)($payload['end_date']??''));
            if($fullName===''){ $errors[]=['code'=>'TITLE_REQUIRED','message'=>'عنوان '.($targetType==='course'?'الدورة':'الورشة').' مطلوب.','column'=>'A']; }
            if($fullName!==''){
                if(preg_match('/^[=+@-]/',ltrim($fullName))){ $errors[]=['code'=>'FORMULA','message'=>'العنوان لا يمكن أن يبدأ بصيغة Excel.','column'=>'A']; }
                if(mb_strlen($fullName)>255){ $errors[]=['code'=>'TITLE_TOO_LONG','message'=>'العنوان طويل جداً (255 حرف).','column'=>'A']; }
            }
            if($branchName===''&&$departmentName===''){ $errors[]=['code'=>'BRANCH_OR_DEPT_REQUIRED','message'=>'يجب إدخال الفرع أو القسم على الأقل.','column'=>'B']; }
            if($branchName!==''&&!isset($branchMap[$branchName])){ $errors[]=['code'=>'BRANCH_NOT_FOUND','message'=>'الفرع '.$branchName.' غير موجود.','column'=>'B']; }
            if($departmentName!==''&&!isset($departmentMap[$departmentName])){ $errors[]=['code'=>'DEPARTMENT_NOT_FOUND','message'=>'القسم '.$departmentName.' غير موجود.','column'=>'C']; }
            if($startDate!==''&&$this->parseFlexibleDate($startDate)===null){ $errors[]=['code'=>'DATE_INVALID','message'=>'صيغة تاريخ البداية غير صحيحة. استخدم YYYY-MM-DD أو DD-MM-YYYY.','column'=>'D']; }
            if($endDate!==''&&$this->parseFlexibleDate($endDate)===null){ $errors[]=['code'=>'DATE_INVALID','message'=>'صيغة تاريخ النهاية غير صحيحة. استخدم YYYY-MM-DD أو DD-MM-YYYY.','column'=>'E']; }
        } elseif($targetType==='unit'){
            $ut=trim((string)($payload['unit_type']??''));
            $uc=trim((string)($payload['unit_code']??''));
            $period=trim((string)($payload['period']??''));
            if(!in_array($ut,['branch','department'],true)){ $errors[]=['code'=>'UNIT_TYPE_INVALID','message'=>'نوع الوحدة يجب أن يكون branch أو department.','column'=>'A']; }
            if($uc===''){ $errors[]=['code'=>'UNIT_NOT_FOUND','message'=>'رمز الوحدة (اسم الفرع/القسم) مطلوب.','column'=>'B']; }
            else {
                if($ut==='branch'||$ut===''){
                    if(!isset($branchMap[$uc])) $errors[]=['code'=>'UNIT_NOT_FOUND','message'=>'الفرع '.$uc.' غير موجود في النظام.','column'=>'B'];
                    else $payload['_unit_id']=$branchMap[$uc];
                }
                if($ut==='department'||$ut===''){
                    if(!isset($departmentMap[$uc])) $errors[]=['code'=>'UNIT_NOT_FOUND','message'=>'القسم '.$uc.' غير موجود في النظام.','column'=>'B'];
                    else $payload['_unit_id']=$departmentMap[$uc];
                }
            }
            if($period===''){ $errors[]=['code'=>'SCHEDULE_MISMATCH','message'=>'الفترة (YYYY-MM أو schedule_id) مطلوبة للوحدة.','column'=>'C']; }
            elseif(ctype_digit($period)){
                $sched=$this->findScheduleById((int)$period);
                if(!$sched) $errors[]=['code'=>'SCHEDULE_MISMATCH','message'=>'الجدولة غير موجودة: '.$period,'column'=>'C'];
                else $payload['_schedule_id']=(int)$period;
            } else {
                if(!preg_match('/^\d{4}-\d{2}$/',$period)){ $errors[]=['code'=>'SCHEDULE_MISMATCH','message'=>'صيغة الفترة غير صحيحة. استخدم YYYY-MM مثل 2026-01.','column'=>'C']; }
            }
            if($branchName!=='' && !isset($branchMap[$branchName])){ $errors[]=['code'=>'BRANCH_NOT_FOUND','message'=>'الفرع '.$branchName.' غير موجود.','column'=>'D']; }
            if($departmentName!=='' && !isset($departmentMap[$departmentName])){ $errors[]=['code'=>'DEPARTMENT_NOT_FOUND','message'=>'القسم '.$departmentName.' غير موجود.','column'=>'E']; }
        } elseif(in_array($targetType,['employee_in_course','employee_in_workshop'],true)){
            $ctxCol=$targetType==='employee_in_course'?'course_id':'workshop_id';
            $ctxId=trim((string)($payload[$ctxCol]??''));
            if($ctxId===''||!ctype_digit($ctxId)){
                $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'معرف السياق ('.($targetType==='employee_in_course'?'الدورة':'الورشة').') مطلوب.','column'=>'C'];
            } else {
                $exists=$targetType==='employee_in_course'?$this->findCourseById((int)$ctxId):$this->findWorkshopById((int)$ctxId);
                if(!$exists) $errors[]=['code'=>'COURSE_NOT_FOUND','message'=>'السياق غير موجود: '.$ctxId,'column'=>'C'];
                else $payload['_context_id']=(int)$ctxId;
            }
            if($empCode===''&&$fullName===''){ $errors[]=['code'=>'EMP_OR_NAME_REQUIRED','message'=>'الرقم الوظيفي أو الاسم مطلوب.','column'=>'A']; }
        } else {
            if($empCode===''&&$fullName===''){ $errors[]=['code'=>'EMP_OR_NAME_REQUIRED','message'=>'يجب إدخال الرقم الوظيفي أو الاسم الكامل على الأقل في الصف '.($payload['excel_row']??'?').'.','column'=>'A']; }
        }
        if(in_array($targetType,['employee','employee_in_course','employee_in_workshop'],true)){
            if($empCode!==''&&preg_match('/^[=+@-]/',ltrim($empCode))){ $errors[]=['code'=>'FORMULA','message'=>'رمز الموظف لا يمكن أن يبدأ بصيغة Excel.','column'=>'A']; }
            if($fullName!==''&&preg_match('/^[=+@-]/',ltrim($fullName))){ $errors[]=['code'=>'FORMULA','message'=>'الاسم لا يمكن أن يبدأ بصيغة Excel.','column'=>'B']; }
            if($nickname!==''&&preg_match('/^[=+@-]/',ltrim($nickname))){ $errors[]=['code'=>'FORMULA','message'=>'الصفة لا يمكن أن تبدأ بصيغة Excel.','column'=>'C']; }
            if($nickname!==''&&mb_strlen($nickname)>255){ $errors[]=['code'=>'NICKNAME_TOO_LONG','message'=>'الصفة طويلة جداً (255 حرف كحد أقصى).','column'=>'C']; }
            $existingEmployee=null;
            if($empCode!==''){ $existingEmployee=$this->findEmployeeByCode($empCode); }
            elseif($fullName!==''){ $existingEmployee=$this->findEmployeeByName($fullName,$branchName,$departmentName); }
            if($existingEmployee===false||$existingEmployee===null){
                if($branchName!==''&&!isset($branchMap[$branchName])){ $errors[]=['code'=>'BRANCH_NOT_FOUND','message'=>'الفرع '.$branchName.' غير موجود في النظام.','column'=>'D']; }
                if($departmentName!==''&&!isset($departmentMap[$departmentName])){ $errors[]=['code'=>'DEPARTMENT_NOT_FOUND','message'=>'القسم '.$departmentName.' غير موجود في النظام.','column'=>'E']; }
                if($branchName!==''&&$departmentName!==''&&isset($branchMap[$branchName])&&isset($departmentMap[$departmentName])){
                    $deptBranchId=$this->getDepartmentBranchId($departmentName);
                    if($deptBranchId!==null&&$deptBranchId!==(int)$branchMap[$branchName]){
                        $errors[]=['code'=>'DEPARTMENT_BRANCH_MISMATCH','message'=>'القسم '.$departmentName.' لا يتبع الفرع '.$branchName.'.','column'=>'E'];
                    }
                }
                if($existingEmployee===false&&$empCode===''&&$fullName!==''){
                    $candidates=$this->findEmployeesByName($fullName);
                    if(count($candidates)>1){ $errors[]=['code'=>'NAME_AMBIGUOUS','message'=>'الاسم '.$fullName.' موجود لأكثر من موظف. يرجى إدخال الرقم الوظيفي.','column'=>'B']; }
                }
            } else {
                if($branchName!==''&&$branchName!==($existingEmployee['branch_name']??'')){ $errors[]=['code'=>'BRANCH_MISMATCH','message'=>'الفرع لا يطابق بيانات الموظف المحفوظة ('.($existingEmployee['branch_name']??'غير محدد').').','column'=>'D']; }
                if($departmentName!==''&&$departmentName!==($existingEmployee['department_name']??'')){ $errors[]=['code'=>'DEPARTMENT_MISMATCH','message'=>'القسم لا يطابق بيانات الموظف المحفوظة ('.($existingEmployee['department_name']??'غير محدد').').','column'=>'E']; }
            }
        }
        if($dateRaw===''){ $errors[]=['code'=>'DATE_REQUIRED','message'=>'تاريخ التقييم مطلوب.','column'=>$this->colLetter(6+count($sections)+1)]; }
        else {
            $normalized=$this->parseFlexibleDate($dateRaw);
            if($normalized===null){ $errors[]=['code'=>'DATE_INVALID','message'=>'صيغة التاريخ غير صحيحة. استخدم YYYY-MM-DD أو DD-MM-YYYY.','column'=>$this->colLetter(6+count($sections)+1)]; }
            else {
                $parsed=new \DateTime($normalized); $parsed->setTime(0,0,0);
                $today=new \DateTime('today'); $today->setTime(0,0,0);
                if($parsed>$today){ $errors[]=['code'=>'DATE_FUTURE','message'=>'تاريخ التقييم لا يمكن أن يكون في المستقبل.','column'=>$this->colLetter(6+count($sections)+1)]; }
                else { $payload['evaluation_date']=$normalized; }
            }
        }
        $evaluatorTitle=trim((string)($payload['evaluator_title']??''));
        if($evaluatorName===''&&$evaluatorTitle===''){ $errors[]=['code'=>'EVALUATOR_REQUIRED','message'=>'يجب إدخال مسؤول التقييم أو الصفة على الأقل.','column'=>$this->colLetter(6+count($sections)+2)]; }
        if($evaluatorName!==''&&preg_match('/^[=+@-]/',ltrim($evaluatorName))){ $errors[]=['code'=>'FORMULA','message'=>'اسم المقيّم لا يمكن أن يبدأ بصيغة Excel.','column'=>$this->colLetter(6+count($sections)+2)]; }
        if($evaluatorTitle!==''&&preg_match('/^[=+@-]/',ltrim($evaluatorTitle))){ $errors[]=['code'=>'FORMULA','message'=>'صفة المقيّم لا يمكن أن تبدأ بصيغة Excel.','column'=>$this->colLetter(6+count($sections)+3)]; }
        if($evaluatorName!==''&&mb_strlen($evaluatorName)>255){ $errors[]=['code'=>'EVALUATOR_NAME_TOO_LONG','message'=>'اسم المقيّم طويل جداً (255 حرف).','column'=>$this->colLetter(6+count($sections)+2)]; }
        if($evaluatorTitle!==''&&mb_strlen($evaluatorTitle)>255){ $errors[]=['code'=>'EVALUATOR_TITLE_TOO_LONG','message'=>'صفة المقيّم طويلة جداً (255 حرف).','column'=>$this->colLetter(6+count($sections)+3)]; }
        $hasEntity=in_array($targetType,['employee','employee_in_course','employee_in_workshop'],true)?$empCode!==''||$fullName!=='':true;
        if($hasEntity){
            foreach($payload['section_values']??[] as $sectionId=>$value){
                $trimmed=trim((string)$value);
                if($trimmed==='') continue;
                if(preg_match('/^[=+@-]/',ltrim($trimmed))){
                    $section=$this->findSectionById((int)$sectionId,$sections);
                    $colLetter=$this->colLetter(6+$this->sectionIndex((int)$sectionId,$sections));
                    $label=$section?$section['section_name']:('العمود '.$colLetter);
                    $errors[]=['code'=>'FORMULA','message'=>'قيمة '.$label.' لا يمكن أن تبدأ بصيغة Excel.','column'=>$colLetter];
                    continue;
                }
                $section=$this->findSectionById((int)$sectionId,$sections);
                if($section===null) continue;
                if(!$this->isValueValid($section,$trimmed)){
                    $colLetter=$this->colLetter(6+$this->sectionIndex((int)$sectionId,$sections));
                    $hint=$this->sectionHint($section);
                    $errors[]=['code'=>'VALUE','message'=>'قيمة '.$section['section_name'].' غير صالحة. '.$hint,'column'=>$colLetter];
                }
            }
        }
        if(isset($payload['notes'])&&mb_strlen((string)$payload['notes'])>4000){ $errors[]=['code'=>'NOTES_TOO_LONG','message'=>'ملاحظة عامة طويلة جداً (4000 حرف كحد أقصى).','column'=>$this->colLetter(6+count($sections))]; }
        return $errors;
    }
    private function getTemplateTargetType(int $tid): string {
        $q=$this->database->getPdo()->prepare('SELECT target_type FROM form_templates WHERE id=:id');
        $q->execute(['id'=>$tid]);
        $r=$q->fetch(PDO::FETCH_ASSOC);
        return $r['target_type'] ?? 'employee';
    }
    private function findCourseById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM training_courses WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function findWorkshopById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM training_workshops WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function findOrCreateCourseByName(string $title, ?int $branchId, ?int $deptId, string $status, string $startDate, string $endDate, int $userId): int {
        $pdo=$this->database->getPdo();
        $q=$pdo->prepare('SELECT tc.id FROM training_courses tc JOIN course_catalog cc ON tc.catalog_id=cc.id WHERE TRIM(LOWER(cc.title))=TRIM(LOWER(:title))' . ($branchId!==null?' AND tc.branch_id=:bid':'') . ' LIMIT 1');
        $params=['title'=>$title];
        if($branchId!==null) $params['bid']=$branchId;
        $q->execute($params);
        $existing=$q->fetch(PDO::FETCH_ASSOC);
        if($existing) return (int)$existing['id'];
        $catQ=$pdo->prepare('SELECT id FROM course_catalog WHERE TRIM(LOWER(title))=TRIM(LOWER(:title)) LIMIT 1');
        $catQ->execute(['title'=>$title]);
        $cat=$catQ->fetch(PDO::FETCH_ASSOC);
        if($cat){ $catalogId=(int)$cat['id']; }
        else {
            $ins=$pdo->prepare('INSERT INTO course_catalog (title,description,created_by) VALUES (:title,:desc,:uid)');
            $ins->execute(['title'=>trim($title),'desc'=>'','uid'=>$userId]);
            $catalogId=(int)$pdo->lastInsertId();
        }
        $ins=$pdo->prepare('INSERT INTO training_courses (catalog_id,branch_id,department_id,start_date,end_date,status,created_by) VALUES (:cat,:bid,:did,:sd,:ed,:st,:uid)');
        $ins->execute(['cat'=>$catalogId,'bid'=>$branchId,'did'=>$deptId,'sd'=>$startDate?:date('Y-m-d'),'ed'=>$endDate?:date('Y-m-d'),'st'=>$status?:'completed','uid'=>$userId]);
        $courseId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,details,ip_address) VALUES (:uid,"COURSE_CREATED_BY_IMPORT","training_courses",:eid,:details,:ip)')
            ->execute(['uid'=>$userId,'eid'=>$courseId,'details'=>json_encode(['title'=>$title,'branch_id'=>$branchId,'department_id'=>$deptId,'status'=>$status],JSON_UNESCAPED_UNICODE),'ip'=>$_SERVER['REMOTE_ADDR']??null]);
        return $courseId;
    }
    private function findOrCreateWorkshopByName(string $title, ?int $branchId, ?int $deptId, string $status, string $startDate, string $endDate, int $userId): int {
        $pdo=$this->database->getPdo();
        $q=$pdo->prepare('SELECT tw.id FROM training_workshops tw JOIN workshop_catalog wc ON tw.catalog_id=wc.id WHERE TRIM(LOWER(wc.title))=TRIM(LOWER(:title))' . ($branchId!==null?' AND tw.branch_id=:bid':'') . ' LIMIT 1');
        $params=['title'=>$title];
        if($branchId!==null) $params['bid']=$branchId;
        $q->execute($params);
        $existing=$q->fetch(PDO::FETCH_ASSOC);
        if($existing) return (int)$existing['id'];
        $catQ=$pdo->prepare('SELECT id FROM workshop_catalog WHERE TRIM(LOWER(title))=TRIM(LOWER(:title)) LIMIT 1');
        $catQ->execute(['title'=>$title]);
        $cat=$catQ->fetch(PDO::FETCH_ASSOC);
        if($cat){ $catalogId=(int)$cat['id']; }
        else {
            $ins=$pdo->prepare('INSERT INTO workshop_catalog (title,description,created_by) VALUES (:title,:desc,:uid)');
            $ins->execute(['title'=>trim($title),'desc'=>'','uid'=>$userId]);
            $catalogId=(int)$pdo->lastInsertId();
        }
        $ins=$pdo->prepare('INSERT INTO training_workshops (catalog_id,branch_id,department_id,start_date,end_date,status,created_by) VALUES (:cat,:bid,:did,:sd,:ed,:st,:uid)');
        $ins->execute(['cat'=>$catalogId,'bid'=>$branchId,'did'=>$deptId,'sd'=>$startDate?:date('Y-m-d'),'ed'=>$endDate?:date('Y-m-d'),'st'=>$status?:'completed','uid'=>$userId]);
        $workshopId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,details,ip_address) VALUES (:uid,"WORKSHOP_CREATED_BY_IMPORT","training_workshops",:eid,:details,:ip)')
            ->execute(['uid'=>$userId,'eid'=>$workshopId,'details'=>json_encode(['title'=>$title,'branch_id'=>$branchId,'department_id'=>$deptId,'status'=>$status],JSON_UNESCAPED_UNICODE),'ip'=>$_SERVER['REMOTE_ADDR']??null]);
        return $workshopId;
    }
    private function findScheduleById(int $id): ?array {
        $q=$this->database->getPdo()->prepare('SELECT id FROM evaluation_schedules WHERE id=:id');
        $q->execute(['id'=>$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    private function isValueValid(array $section, string $value): bool
    {
        if($section['evaluation_type']==='text'){ return mb_strlen($value)<=4000; }
        if(in_array($section['evaluation_type'],['grade','multiple_choice'],true)){
            $options=json_decode((string)($section['evaluation_options']??'[]'),true);
            if(!is_array($options)||$options===[]){ return false; }
            foreach($options as $option){
                if(is_array($option)){
                    $label=$option['label']??null; $optValue=$option['value']??null;
                    if((string)$label===$value || (string)$optValue===$value){ return true; }
                } elseif((string)$option===$value){ return true; }
            }
            return false;
        }
        if($section['evaluation_type']==='boolean'){ return in_array(mb_strtolower($value),['0','1','true','false','نعم','لا'],true); }
        if(!is_numeric($value)){ return false; }
        $number=(float)$value;
        $min=$section['min_value']===null?0.0:(float)$section['min_value'];
        $max=$section['max_value']===null?100.0:(float)$section['max_value'];
        return $number>=$min && $number<=$max;
    }
    private function normalize(array $section, string $value): float
    {
        if($section['evaluation_type']==='text'){ return 0.0; }
        if($section['evaluation_type']==='boolean'){ return in_array(mb_strtolower($value),['1','true','نعم'],true)?100.0:0.0; }
        if(in_array($section['evaluation_type'],['grade','multiple_choice'],true)){
            $options=json_decode((string)($section['evaluation_options']??'[]'),true);
            if(is_array($options)){
                foreach($options as $option){
                    if(is_array($option)){
                        $label=$option['label']??null; $optValue=$option['value']??null;
                        if((string)$label===$value && $optValue!==null){ return round((float)$optValue,4); }
                        if((string)$optValue===$value){ return round((float)$optValue,4); }
                    }
                }
            }
            return round((float)$value,4);
        }
        $number=(float)$value;
        if($section['evaluation_type']==='numeric'){
            $min=(float)($section['min_value']??0);
            $max=(float)($section['max_value']??100);
            return $max>$min?round((($number-$min)/($max-$min))*100,4):0.0;
        }
        return round($number,4);
    }
    private function calculateFinalScore(int $evaluationId, int $templateId): float
    {
        $query=$this->database->getPdo()->prepare('SELECT c.criterion_weight, cs.section_weight, COALESCE(ea.normalized_score, 0) AS normalized_score FROM criteria c JOIN criteria_sections cs ON cs.criterion_id = c.id LEFT JOIN evaluation_answers ea ON ea.section_id = cs.id AND ea.evaluation_id = :evaluation_id WHERE c.template_id = :template_id');
        $query->execute(['evaluation_id'=>$evaluationId,'template_id'=>$templateId]);
        $total=0.0;
        foreach($query->fetchAll(PDO::FETCH_ASSOC) as $row){ $total+=((float)$row['normalized_score']*(float)$row['section_weight']/100)*((float)$row['criterion_weight']/100); }
        return round($total,2);
    }
    private function extractTemplateId(Worksheet $sheet, Spreadsheet $book): int
    {
        $title=(string)$sheet->getCell('A1')->getValue();
        if(preg_match('/معرف القالب:\s*(\d+)/u',$title,$matches)){ return (int)$matches[1]; }
        $props=$book->getProperties()->getCustomProperties();
        foreach($props as $propName){ if($propName==='TemplateId'){ return (int)$book->getProperties()->getCustomPropertyValue($propName); } }
        return 0;
    }
    private function getTemplateOrFail(int $templateId): array
    {
        $query=$this->database->getPdo()->prepare('SELECT * FROM form_templates WHERE id = :id');
        $query->execute(['id'=>$templateId]);
        $template=$query->fetch(PDO::FETCH_ASSOC);
        if($template===false){ throw new RuntimeException('القالب المطلوب غير موجود.'); }
        return $template;
    }
    private function getSectionsOrdered(int $templateId): array
    {
        $query=$this->database->getPdo()->prepare('SELECT cs.*, c.criterion_name, c.criterion_weight, c.order_index AS criterion_order FROM criteria_sections cs JOIN criteria c ON c.id = cs.criterion_id WHERE c.template_id = :template_id ORDER BY c.order_index ASC, cs.order_index ASC, cs.id ASC');
        $query->execute(['template_id'=>$templateId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    private function getBranchMap(): array
    {
        $rows=$this->database->getPdo()->query('SELECT id, name FROM branches')->fetchAll(PDO::FETCH_ASSOC);
        $map=[]; foreach($rows as $row){ $map[trim((string)$row['name'])]=(int)$row['id']; }
        return $map;
    }
    private function getBranchNames(): array { return array_keys($this->getBranchMap()); }
    private function getDepartmentMap(): array
    {
        $rows=$this->database->getPdo()->query('SELECT id, name FROM departments')->fetchAll(PDO::FETCH_ASSOC);
        $map=[]; foreach($rows as $row){ $map[trim((string)$row['name'])]=(int)$row['id']; }
        return $map;
    }
    private function getDepartmentNames(): array { return array_keys($this->getDepartmentMap()); }
    /**
     * جلب branch_id لقسم معين — للتحقق من تبعية القسم للفرع.
     * @param string $deptName اسم القسم
     * @return int|null معرف الفرع أو null
     */
    private function getDepartmentBranchId(string $deptName): ?int
    {
        $q=$this->database->getPdo()->prepare('SELECT branch_id FROM departments WHERE name=:name LIMIT 1');
        $q->execute(['name'=>$deptName]);
        $r=$q->fetch(PDO::FETCH_ASSOC);
        return $r ? (int)$r['branch_id'] : null;
    }
    private function getEvaluatorInfo(int $employeeId): array
    {
        if($employeeId<=0) return ['full_name'=>'','position_title'=>''];
        $q=$this->database->getPdo()->prepare('SELECT e.full_name, p.title AS position_title FROM employees e LEFT JOIN positions p ON p.id=e.position_id WHERE e.id=:id');
        $q->execute(['id'=>$employeeId]);
        $row=$q->fetch(PDO::FETCH_ASSOC);
        return $row!==false ? $row : ['full_name'=>'','position_title'=>''];
    }
    private function findEmployeeByCode(string $empCode): array|false
    {
        $query=$this->database->getPdo()->prepare('SELECT e.*, b.name AS branch_name, d.name AS department_name FROM employees e LEFT JOIN branches b ON b.id = e.branch_id LEFT JOIN departments d ON d.id = e.department_id WHERE e.emp_code = :emp_code LIMIT 1');
        $query->execute(['emp_code'=>$empCode]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    private function findEmployeeByName(string $fullName, string $branchName='', string $departmentName=''): array|false
    {
        $pdo=$this->database->getPdo();
        if($branchName!=='' || $departmentName!==''){
            $sql='SELECT e.*, b.name AS branch_name, d.name AS department_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id LEFT JOIN departments d ON d.id=e.department_id WHERE e.full_name=:name';
            $params=['name'=>$fullName];
            if($branchName!==''){ $sql.=' AND b.name=:branch'; $params['branch']=$branchName; }
            if($departmentName!==''){ $sql.=' AND d.name=:dept'; $params['dept']=$departmentName; }
            $sql.=' LIMIT 1';
            $q=$pdo->prepare($sql); $q->execute($params); $r=$q->fetch(PDO::FETCH_ASSOC);
            if($r!==false) return $r;
        }
        $q=$pdo->prepare('SELECT e.*, b.name AS branch_name, d.name AS department_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id LEFT JOIN departments d ON d.id=e.department_id WHERE e.full_name=:name LIMIT 1');
        $q->execute(['name'=>$fullName]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }
    private function findEmployeesByName(string $fullName): array
    {
        $q=$this->database->getPdo()->prepare('SELECT id FROM employees WHERE full_name=:name');
        $q->execute(['name'=>$fullName]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    private function getEmployeeSnapshot(int $employeeId): array
    {
        $query=$this->database->getPdo()->prepare('SELECT e.emp_code, e.full_name, e.nickname, b.name AS branch_name, d.name AS department_name, p.title AS position_name FROM employees e LEFT JOIN branches b ON b.id = e.branch_id LEFT JOIN departments d ON d.id = e.department_id LEFT JOIN positions p ON p.id = e.position_id WHERE e.id = :id');
        $query->execute(['id'=>$employeeId]);
        $row=$query->fetch(PDO::FETCH_ASSOC);
        return $row!==false ? $row : ['emp_code'=>'','full_name'=>'','nickname'=>'','branch_name'=>'','department_name'=>'','position_name'=>''];
    }
    private function findSectionById(int $sectionId, array $sections): ?array
    {
        foreach($sections as $section){ if((int)$section['id']===$sectionId){ return $section; } }
        return null;
    }
    private function sectionIndex(int $sectionId, array $sections): int
    {
        foreach($sections as $idx=>$section){ if((int)$section['id']===$sectionId){ return $idx; } }
        return 0;
    }
    private function typeLabel(string $type): string
    {
        return match($type){
            'numeric'=>'رقمي','percentage'=>'نسبة %','grade'=>'تقدير','boolean'=>'نعم/لا','multiple_choice'=>'اختيار','text'=>'نص','scale'=>'مقياس',default=>$type,
        };
    }
    private function sectionHint(array $section): string
    {
        return match($section['evaluation_type']){
            'numeric'=>'رقم بين '.($section['min_value']??0).' و '.($section['max_value']??100),
            'percentage'=>'نسبة 0-100',
            'grade','multiple_choice'=>'اختر من القائمة',
            'boolean'=>'نعم أو لا',
            'text'=>'نص حتى 4000 حرف',
            'scale'=>'قيمة رقمية',
            default=>'',
        };
    }
    private function applyDropdowns(Worksheet $sheet, array $branches, array $departments, array $sections, int $startRow, int $endRow, int $tailStartCol): void
    {
        $branchList='"'.implode(',',array_map(fn($v)=>str_replace('"','""',$v),$branches)).'"';
        $helperCol=$this->colLetter($tailStartCol + 4);
        for($row=$startRow;$row<=$endRow;$row++){
            $bv=$sheet->getCell('D'.$row)->getDataValidation(); $bv->setType(DataValidation::TYPE_LIST); $bv->setErrorStyle(DataValidation::STYLE_STOP); $bv->setAllowBlank(true); $bv->setShowDropDown(true); $bv->setFormula1($branchList); $bv->setShowErrorMessage(true); $bv->setErrorTitle('فرع غير صالح'); $bv->setError('اختر فرعاً من القائمة.');
            $dv=$sheet->getCell('E'.$row)->getDataValidation(); $dv->setType(DataValidation::TYPE_LIST); $dv->setErrorStyle(DataValidation::STYLE_STOP); $dv->setAllowBlank(true); $dv->setShowDropDown(true); $dv->setFormula1('INDIRECT("Dept_"&'.$helperCol.$row.')'); $dv->setShowErrorMessage(true); $dv->setErrorTitle('قسم غير صالح'); $dv->setError('اختر قسماً تابعاً للفرع المختار في نفس الصف.');
        }
        foreach($sections as $idx=>$section){
            $colLetter=$this->colLetter(6+$idx);
            $range=$colLetter.$startRow.':'.$colLetter.$endRow;
            if($section['evaluation_type']==='numeric'){
                $min=$section['min_value']??0; $max=$section['max_value']??100;
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');
                for($row=$startRow;$row<=$endRow;$row++){ $v=$sheet->getCell($colLetter.$row)->getDataValidation(); $v->setType(DataValidation::TYPE_DECIMAL); $v->setErrorStyle(DataValidation::STYLE_STOP); $v->setAllowBlank(true); $v->setOperator(DataValidation::OPERATOR_BETWEEN); $v->setFormula1((string)$min); $v->setFormula2((string)$max); $v->setShowErrorMessage(true); $v->setErrorTitle('قيمة خارج النطاق'); $v->setError('أدخل رقماً بين '.$min.' و '.$max.'.'); }
            } elseif($section['evaluation_type']==='percentage'){
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0');
                for($row=$startRow;$row<=$endRow;$row++){ $v=$sheet->getCell($colLetter.$row)->getDataValidation(); $v->setType(DataValidation::TYPE_WHOLE); $v->setErrorStyle(DataValidation::STYLE_STOP); $v->setAllowBlank(true); $v->setOperator(DataValidation::OPERATOR_BETWEEN); $v->setFormula1('0'); $v->setFormula2('100'); $v->setShowErrorMessage(true); $v->setErrorTitle('نسبة غير صالحة'); $v->setError('أدخل نسبة بين 0 و 100.'); }
            } elseif(in_array($section['evaluation_type'],['grade','multiple_choice'],true)){
                $options=json_decode((string)($section['evaluation_options']??'[]'),true);
                $labels=[];
                if(is_array($options)){ foreach($options as $opt){ if(is_array($opt)&&isset($opt['label'])){ $labels[]=(string)$opt['label']; } elseif(is_string($opt)){ $labels[]=$opt; } } }
                if($labels!==[]){
                    $listFormula='"'.implode(',',array_map(fn($v)=>str_replace('"','""',$v),$labels)).'"';
                    for($row=$startRow;$row<=$endRow;$row++){ $v=$sheet->getCell($colLetter.$row)->getDataValidation(); $v->setType(DataValidation::TYPE_LIST); $v->setErrorStyle(DataValidation::STYLE_STOP); $v->setAllowBlank(true); $v->setShowDropDown(true); $v->setFormula1($listFormula); $v->setShowErrorMessage(true); $v->setErrorTitle('تقدير غير صالح'); $v->setError('اختر من القائمة: '.implode('، ',$labels)); }
                }
            } elseif($section['evaluation_type']==='boolean'){
                for($row=$startRow;$row<=$endRow;$row++){ $v=$sheet->getCell($colLetter.$row)->getDataValidation(); $v->setType(DataValidation::TYPE_LIST); $v->setErrorStyle(DataValidation::STYLE_STOP); $v->setAllowBlank(true); $v->setShowDropDown(true); $v->setFormula1('"نعم,لا"'); $v->setShowErrorMessage(true); $v->setErrorTitle('قيمة غير صالحة'); $v->setError('اختر: نعم أو لا.'); }
            }
        }
        $dateCol=$this->colLetter($tailStartCol+1);
        for($row=$startRow;$row<=$endRow;$row++){
            $v=$sheet->getCell($dateCol.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_CUSTOM);
            $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $v->setAllowBlank(false);
            $v->setShowDropDown(false);
            $v->setFormula1('LEN('.$dateCol.$row.')>0');
            $v->setShowErrorMessage(false);
            $v->setShowInputMessage(true);
            $v->setPromptTitle('تاريخ التقييم');
            $v->setPrompt('يقبل YYYY-MM-DD أو DD-MM-YYYY مثال 2026-05-20 أو 20-05-2026');
        }
    }
    private function applyTrainingDropdowns(Worksheet $sheet, array $branches, array $departments, int $startRow, int $endRow): void
    {
        $branchList='"'.implode(',',array_map(fn($v)=>str_replace('"','""',$v),$branches)).'"';
        $deptList='"'.implode(',',array_map(fn($v)=>str_replace('"','""',$v),$departments)).'"';
        for($row=$startRow;$row<=$endRow;$row++){
            $bv=$sheet->getCell('B'.$row)->getDataValidation(); $bv->setType(DataValidation::TYPE_LIST); $bv->setErrorStyle(DataValidation::STYLE_STOP); $bv->setAllowBlank(true); $bv->setShowDropDown(true); $bv->setFormula1($branchList); $bv->setShowErrorMessage(true); $bv->setErrorTitle('فرع غير صالح'); $bv->setError('اختر فرعاً من القائمة.');
            $cv=$sheet->getCell('C'.$row)->getDataValidation(); $cv->setType(DataValidation::TYPE_LIST); $cv->setErrorStyle(DataValidation::STYLE_STOP); $cv->setAllowBlank(true); $cv->setShowDropDown(true); $cv->setFormula1($deptList); $cv->setShowErrorMessage(true); $cv->setErrorTitle('قسم غير صالح'); $cv->setError('اختر قسماً من القائمة.');
        }
    }
    private function applyDuplicateFormatting(Worksheet $sheet, int $startRow, int $endRow): void
    {
        $condA=new Conditional(); $condA->setConditionType(Conditional::CONDITION_EXPRESSION); $condA->setOperatorType(Conditional::OPERATOR_NONE); $condA->addCondition('COUNTIF($A$'.$startRow.':$A$'.$endRow.',A'.$startRow.')>1'); $condA->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA'); $condA->getStyle()->getFont()->getColor()->setRGB('991B1B'); $condA->setStopIfTrue(false);
        $condB=new Conditional(); $condB->setConditionType(Conditional::CONDITION_EXPRESSION); $condB->setOperatorType(Conditional::OPERATOR_NONE); $condB->addCondition('AND($A'.$startRow.'="",COUNTIF($B$'.$startRow.':$B$'.$endRow.',B'.$startRow.')>1)'); $condB->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA'); $condB->getStyle()->getFont()->getColor()->setRGB('991B1B'); $condB->setStopIfTrue(false);
        $sheet->getStyle('A'.$startRow.':A'.$endRow)->setConditionalStyles([$condA]);
        $sheet->getStyle('B'.$startRow.':B'.$endRow)->setConditionalStyles([$condB]);
    }
    private function applyDateFormatting(Worksheet $sheet, int $startRow, int $endRow, int $tailStartCol): void
    {
        $dateCol=$this->colLetter($tailStartCol+1);
        $sheet->getStyle($dateCol.$startRow.':'.$dateCol.$endRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    }
    /**
     * بناء ورقة __lookup المخفية مع نطاقات Dept_{branchId} للفلترة الديناميكية.
     * الورقة مقفلة ومخفية ولا تدخل نطاق الطباعة — تُستخدم عبر INDIRECT في العمود E.
     * @param Spreadsheet $book المصنف الحالي
     * @return void
     */
    private function buildLookupSheet(Spreadsheet $book): void
    {
        $lookup=$book->createSheet();
        $lookup->setTitle(self::LOOKUP_SHEET);
        $lookup->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $pdo=$this->database->getPdo();
        $branches=$pdo->query('SELECT id, name FROM branches ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $lookup->setCellValue('A1', 'branch_id');
        $lookup->setCellValue('B1', 'branch_name');
        $row=2;
        foreach($branches as $br){
            $lookup->setCellValue('A'.$row, (int)$br['id']);
            $lookup->setCellValue('B'.$row, (string)$br['name']);
            $row++;
        }
        $colIdx=3;
        foreach($branches as $br){
            $bid=(int)$br['id'];
            $depts=$pdo->prepare('SELECT name FROM departments WHERE branch_id=:bid ORDER BY name');
            $depts->execute(['bid'=>$bid]);
            $names=$depts->fetchAll(PDO::FETCH_COLUMN);
            if($names===[]){
                $colLetter=$this->colLetter($colIdx);
                $lookup->setCellValue($colLetter.'1', 'Dept_'.$bid);
                $book->addNamedRange(new NamedRange('Dept_'.$bid, $lookup, $colLetter.'2:'.$colLetter.'2'));
                $lookup->setCellValue($colLetter.'2', '');
                $colIdx++;
                continue;
            }
            $colLetter=$this->colLetter($colIdx);
            $lookup->setCellValue($colLetter.'1', 'Dept_'.$bid);
            $r=2;
            foreach($names as $nm){
                $lookup->setCellValue($colLetter.$r, (string)$nm);
                $r++;
            }
            $book->addNamedRange(new NamedRange('Dept_'.$bid, $lookup, $colLetter.'2:'.$colLetter.($r-1)));
            $colIdx++;
        }
        $lookup->getProtection()->setSheet(true);
        $lookup->getProtection()->setPassword(self::SHEET_PASSWORD);
    }
    private function getPrefillEmployees(?int $branchId, ?int $departmentId, int $limit = 100): array
    {
        $pdo=$this->database->getPdo();
        $limit=max(1, min($limit, self::MAX_ROWS_LIMIT));
        $sql='SELECT e.emp_code, e.full_name, e.nickname, b.name AS branch_name, d.name AS department_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id LEFT JOIN departments d ON d.id=e.department_id WHERE e.is_active=1';
        $params=[];
        if($departmentId!==null && $departmentId>0){ $sql.=' AND e.department_id=:dept'; $params['dept']=$departmentId; }
        elseif($branchId!==null && $branchId>0){ $sql.=' AND e.branch_id=:branch'; $params['branch']=$branchId; }
        else { return []; }
        $sql.=' ORDER BY e.full_name ASC LIMIT '.(int)$limit;
        $stmt=$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private function colLetter(int $colIndex): string
    {
        $letter=''; while($colIndex>0){ $colIndex--; $letter=chr(65+($colIndex%26)).$letter; $colIndex=(int)($colIndex/26); } return $letter;
    }
}
