<?php
declare(strict_types=1);

namespace App\Services\Excel;

use App\Core\Database;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * CD-043 — P2: باني قالب Excel وفق SRS-EXCEL-IE-3.1-final §5/§6/§12.
 *
 * القواعد الملزمة:
 *  - ورقتان فقط: الرئيسية + Lists مخفية (لا ورقة تعليمات).
 *  - لا كلمة سر لحماية الورقة.
 *  - لا معرّفات تقنية في ورقة Lists (أسماء فقط).
 *  - الأنواع الأربعة: employee / employee_in_course / employee_in_workshop / skill.
 *  - فردي/مهاري: الاسم ثم الرقم الوظيفي ثم الفرع ثم القسم (بلا كنية).
 *  - دورة/ورشة: رأس برنامج من 6 حقول لكل موظف ثم بيانات الموظف (4 حقول).
 *  - التقدير العام: فقط employee_in_course + evaluation_mode=descriptive، قبل الملاحظات العامة.
 *  - الذيل: ملاحظات عامة ← اسم المقيّم ← صفة المقيّم ← تاريخ التقييم.
 *  - صف 1 تعليمة واحدة، صف 2 مجموعات المعايير + الثوابت (مدمجة 2:3)، صف 3 أسماء الأقسام، صف 4 تلميحات، البيانات من صف 5.
 */
final class ExcelTemplateBuilder
{
    public const MAIN_SHEET = 'التقييمات';
    public const LISTS_SHEET = 'Lists';
    public const HEADER_ROWS = 4;
    public const DATA_START_ROW = 5;

    private const TARGET_TYPES = ['employee', 'employee_in_course', 'employee_in_workshop', 'skill'];
    private const INPUT_TYPES = ['percentage', 'numeric', 'grade', 'boolean', 'scale', 'text', 'textarea', 'multiple_choice', 'overall_grade'];
    private const SKILL_LEVELS = ['متقدم', 'جيد', 'مقبول'];
    private const FAILED_GRADE_FALLBACK = ['ممتاز', 'جيد جدًا', 'جيد', 'مقبول', 'ضعيف'];

    private Database $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?? Database::getInstance();
    }

    /** حدود التصدير من جدول settings (لا قيم ثابتة في الكود). */
    public function limits(): array
    {
        $defaults = ['min_rows' => 50, 'max_rows' => 2000, 'max_file_size_mb' => 50];
        try {
            $stmt = $this->database->getPdo()->query(
                "SELECT setting_key, setting_value FROM settings
                 WHERE setting_key IN ('excel.min_rows','excel.max_rows','excel.max_file_size_mb')"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return $defaults;
        }
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (int) $row['setting_value'];
        }
        $min = max(1, (int) ($map['excel.min_rows'] ?? $defaults['min_rows']));
        // سقف صلب 2000 صف — لا يتجاوزه الإعداد المركزي (قرار معتمد).
        $max = min(2000, max($min, (int) ($map['excel.max_rows'] ?? $defaults['max_rows'])));
        return [
            'min_rows' => $min,
            'max_rows' => $max,
            'max_file_size_mb' => max(1, (int) ($map['excel.max_file_size_mb'] ?? $defaults['max_file_size_mb'])),
        ];
    }

    /**
     * Phase 3 (A4/Q7): count available/exportable rows for selected strategy and filters.
     */
    public function countExport(int $templateId, ?int $branchId, ?int $departmentId, int $maxRows, array $options = []): array
    {
        $layout = $this->layout($templateId, $branchId, $departmentId, $options);
        $strategy = (string) ($options['prefill_strategy'] ?? '');
        if ($strategy === '') {
            $strategy = (($options['mode'] ?? 'prefill') === 'empty') ? 'blank' : 'prefill';
        }
        if ($strategy === 'prefill') {
            $isProgram = in_array((string)($layout['target_type'] ?? ''), ['employee_in_course', 'employee_in_workshop'], true);
            $strategy = (array_key_exists('prefill_ids', $options) || (!$isProgram && $branchId === null && $departmentId === null)) ? 'blank' : 'org';
        }
        $maxRows = max(0, $maxRows);
        if ($strategy === 'blank') {
            return ['total_available' => 0, 'exported_rows' => 0, 'capped' => false, 'prefill_strategy' => 'blank'];
        }
        if ($strategy === 'org') {
            $total = $this->countOrgRows($branchId, $departmentId);
        } elseif ($strategy === 'previous_evals') {
            $total = $this->countPreviousEvaluationRows($layout, $branchId, $departmentId, $options);
        } elseif ($strategy === 'executing') {
            $total = $this->countExecutingEvaluationRows($layout, $options);
        } else {
            $total = 0;
        }
        $exported = min($total, $maxRows);
        return [
            'total_available' => $total,
            'exported_rows' => $exported,
            'capped' => $total > $maxRows,
            'prefill_strategy' => $strategy,
        ];
    }

    /**
     * وصف تخطيط القالب (أعمدة/صفوف) — يُستخدم من الباني والمستورد معاً
     * حتى يبقى ملف التصدير وملف الاستيراد متطابقين 100%.
     */
    public function layout(int $templateId, ?int $branchId = null, ?int $departmentId = null, array $options = []): array
    {
        $template = $this->getTemplateOrFail($templateId);
        $targetType = (string) ($template['target_type'] ?? 'employee');
        if (!in_array($targetType, self::TARGET_TYPES, true)) {
            throw new RuntimeException('نوع القالب غير مدعوم في CD-043: ' . $targetType);
        }

        $sections = $this->getSections($templateId);
        if ($sections === []) {
            throw new RuntimeException('القالب المحدد لا يحتوي على أي بنود تقييم.');
        }

        $evaluationMode = (string) ($template['evaluation_mode'] ?? 'numeric');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        $contextLabel = $targetType === 'employee_in_workshop' ? 'الورشة' : 'الدورة';

        // التقدير العام: دورة وصفية فقط، ولا يظهر للورشة أبداً.
        $overallSection = null;
        $inputSections = [];
        foreach ($sections as $section) {
            $type = (string) ($section['evaluation_type'] ?? '');
            if ($type === 'overall_grade') {
                if ($targetType === 'employee_in_course' && $evaluationMode === 'descriptive') {
                    $overallSection = $section;
                }
                continue; // لا يُعرض كبند عادي في أي نمط آخر.
            }
            if (!in_array($type, self::INPUT_TYPES, true)) {
                continue; // formula/auto/helper محسوبة ولا تُستورد.
            }
            $inputSections[] = $section;
        }

        $hasOverall = ($targetType === 'employee_in_course' && $evaluationMode === 'descriptive');

        $columns = [];
        $index = 0;
        $add = static function (array $col) use (&$columns, &$index): void {
            $col['index'] = $index;
            $col['letter'] = Coordinate::stringFromColumnIndex($index + 1);
            $columns[] = $col;
            $index++;
        };

        // Display-only serial column (not part of import; helper columns are ignored by layout-driven import)
        $add(['key' => 'row_serial', 'kind' => 'helper', 'label' => 'م', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'مسلسل مرجعي (لا يُستورد)']);

        if ($isProgram) {
            $add(['key' => 'program_title', 'kind' => 'fixed', 'label' => 'اسم البرنامج *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'اختر ' . $contextLabel . ' من القائمة']);
            $add(['key' => 'program_branch', 'kind' => 'fixed', 'label' => 'الفرع المنفذ *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'فرع تنفيذ ' . $contextLabel]);
            $add(['key' => 'program_branch_safe_id', 'kind' => 'helper', 'label' => '', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => '']);
            $add(['key' => 'program_department', 'kind' => 'fixed', 'label' => 'القسم المنفذ *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'قسم تنفيذ ' . $contextLabel . ' (مرتبط بالفرع المنفذ)']);
            $add(['key' => 'program_start', 'kind' => 'fixed', 'label' => 'تاريخ البدء *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'YYYY-MM-DD']);
            $add(['key' => 'program_end', 'kind' => 'fixed', 'label' => 'تاريخ الانتهاء *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'YYYY-MM-DD — بعد تاريخ البدء']);
            $add(['key' => 'program_responsible', 'kind' => 'fixed', 'label' => 'اسم المسؤول *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'مسؤول ' . $contextLabel]);
            $add(['key' => 'employee_name', 'kind' => 'fixed', 'label' => 'الاسم *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'اسم الموظف — أو اتركه واملأ الرقم الوظيفي']);
            $add(['key' => 'employee_code', 'kind' => 'fixed', 'label' => 'الرقم الوظيفي', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'رمز فريد — أو اتركه واملأ الاسم']);
            $add(['key' => 'employee_branch', 'kind' => 'fixed', 'label' => 'الفرع', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'فرع الموظف']);
            $add(['key' => 'employee_branch_safe_id', 'kind' => 'helper', 'label' => '', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => '']);
            $add(['key' => 'employee_department', 'kind' => 'fixed', 'label' => 'القسم', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'قسم الموظف (مرتبط بالفرع)']);
        } else {
            $add(['key' => 'employee_name', 'kind' => 'fixed', 'label' => 'الاسم *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'الاسم الكامل — أو اتركه واملأ الرقم الوظيفي']);
            $add(['key' => 'employee_code', 'kind' => 'fixed', 'label' => 'الرقم الوظيفي', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'رمز فريد — أو اتركه واملأ الاسم']);
            $add(['key' => 'employee_branch', 'kind' => 'fixed', 'label' => 'الفرع', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'اختر من القائمة (الفرع أو القسم أحدهما إلزامي)']);
            $add(['key' => 'employee_branch_safe_id', 'kind' => 'helper', 'label' => '', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => '']);
            $add(['key' => 'employee_department', 'kind' => 'fixed', 'label' => 'القسم', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'اختر من القائمة (مرتبط بالفرع)']);
        }

        $groups = [];
        $criteriaColumns = [];
        $groupIndex = -1;
        foreach ($inputSections as $section) {
            $criterion = (string) ($section['criterion_name'] ?? '');
            if ($groupIndex < 0 || $criterion !== $groups[$groupIndex]['name']) {
                $groups[] = ['name' => $criterion, 'weight' => (float) ($section['criterion_weight'] ?? 0), 'count' => 0];
                $groupIndex = count($groups) - 1;
            }

            $required = $this->sectionRequired($section);
            $type = (string) $section['evaluation_type'];
            $label = (string) $section['section_name'] . ($required ? ' *' : '');
            $add([
                'key' => 'section_' . (int) $section['id'],
                'kind' => 'section',
                'label' => $label,
                'group' => $criterion,
                'required' => $required,
                'section_type' => $type,
                'section_id' => (int) $section['id'],
                'section_name' => (string) $section['section_name'],
                'hint' => $this->sectionHint($section),
            ]);
            $criteriaColumns[] = ['kind' => 'section', 'section_id' => (int) $section['id'], 'index' => count($columns) - 1];
            $groups[$groupIndex]['count']++;

            if ((int) ($section['allow_notes'] ?? 0) === 1) {
                $notesRequired = (int) ($section['notes_required'] ?? 0) === 1;
                $add([
                    'key' => 'notes_' . (int) $section['id'],
                    'kind' => 'notes',
                    'label' => 'ملاحظة' . ($notesRequired ? ' *' : ''),
                    'group' => $criterion,
                    'required' => $notesRequired,
                    'section_type' => 'notes',
                    'section_id' => (int) $section['id'],
                    'section_name' => (string) $section['section_name'],
                    'hint' => $notesRequired ? 'ملاحظة إلزامية' : 'ملاحظة اختيارية',
                ]);
                $criteriaColumns[] = ['kind' => 'notes', 'section_id' => (int) $section['id'], 'index' => count($columns) - 1];
                $groups[$groupIndex]['count']++;
            }
        }

        $overallColumn = null;
        if ($hasOverall) {
            $add([
                'key' => 'overall_grade',
                'kind' => 'overall_grade',
                'label' => 'التقدير العام *',
                'group' => 'التقدير العام',
                'required' => true,
                'section_type' => 'overall_grade',
                'section_id' => $overallSection ? (int) $overallSection['id'] : null,
                'section_name' => 'التقدير العام',
                'hint' => 'اختر من سلم التقديرات',
            ]);
            $overallColumn = $columns[count($columns) - 1];
            $groups[] = ['name' => 'التقدير العام', 'weight' => 0.0, 'count' => 1];
        }

        $add(['key' => 'notes', 'kind' => 'tail', 'label' => 'ملاحظات عامة', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'نص حر حتى 4000 حرف']);
        $add(['key' => 'evaluator_name', 'kind' => 'tail', 'label' => 'اسم المقيّم *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'اسم المقيّم — أو املأ الصفة']);
        $add(['key' => 'evaluator_title', 'kind' => 'tail', 'label' => 'صفة المقيّم', 'group' => '', 'required' => false, 'section_type' => null, 'hint' => 'صفة المقيّم']);
        $add(['key' => 'evaluation_date', 'kind' => 'tail', 'label' => 'تاريخ التقييم *', 'group' => '', 'required' => true, 'section_type' => null, 'hint' => 'YYYY-MM-DD أو DD-MM-YYYY — ليس مستقبلياً']);

        $maxRows = (int) ($options['max_rows'] ?? 100);
        $maxRows = max(1, min($maxRows, 2000));

        $program = $isProgram ? $this->resolveSingleProgram($targetType, $options) : null;

        return [
            'template_id' => $templateId,
            'template_name' => (string) $template['template_name'],
            'target_type' => $targetType,
            'evaluation_mode' => $evaluationMode,
            'has_overall_grade' => $hasOverall,
            'overall_grade_section_id' => $overallSection ? (int) $overallSection['id'] : null,
            'main_sheet' => self::MAIN_SHEET,
            'lists_sheet' => self::LISTS_SHEET,
            'header_rows' => self::HEADER_ROWS,
            'data_start_row' => self::DATA_START_ROW,
            'max_rows' => $maxRows,
            'columns' => $columns,
            'fixed_columns' => array_values(array_filter($columns, static fn(array $c): bool => $c['kind'] === 'fixed')),
            'criteria_columns' => $criteriaColumns,
            'overall_column' => $overallColumn,
            'tail_columns' => array_values(array_filter($columns, static fn(array $c): bool => $c['kind'] === 'tail')),
            'groups' => array_map(static fn(array $g): array => ['name' => (string) $g['name'], 'weight' => (float) $g['weight'], 'count' => (int) $g['count']], $groups),
            'program' => $program,
            'sections' => $inputSections,
            'overall_section' => $overallSection,
        ];
    }

    /**
     * بناء المصنف الكامل (ورقة رئيسية + Lists مخفية).
     */
    public function build(int $templateId, int $evaluatorEmployeeId, ?int $branchId = null, ?int $departmentId = null, int $maxRows = 100, array $options = []): Spreadsheet
    {
        $options['max_rows'] = $maxRows;
        $layout = $this->layout($templateId, $branchId, $departmentId, $options);
        $maxRows = (int) $layout['max_rows'];
        $dataStart = self::DATA_START_ROW;
        $dataEnd = $dataStart + $maxRows - 1;

        $evaluator = $this->getEvaluatorInfo($evaluatorEmployeeId);
        $branches = $this->getBranches($branchId);
        $departmentsByBranch = $this->getDepartmentsByBranch($branches);
        $gradeLabels = $this->getGradeLabels($layout);
        $programTitles = $this->getProgramTitles($layout, $branchId, $departmentId, $options);

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(self::MAIN_SHEET);
        $sheet->setRightToLeft(true);
        $sheet->getDefaultRowDimension()->setRowHeight(26);

        $columns = $layout['columns'];
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        $contextLabel = $layout['target_type'] === 'employee_in_workshop' ? 'الورشة' : 'الدورة';

        // ── صف 1: تعليمة واحدة (تحوي معرف القالب لربط الاستيراد) ──
        $scopeNote = in_array($layout['target_type'], ['employee_in_course', 'employee_in_workshop'], true)
            ? ' كل صف = تقييم موظف داخل ' . $contextLabel . ' محدد.'
            : ' كل صف = تقييم موظف واحد.';
        $instruction = 'قالب استيراد: ' . $layout['template_name'] . ' [' . $layout['target_type'] . ']'
            . ' | معرف القالب: ' . $templateId
            . ' | المقيّم: ' . $evaluatorEmployeeId
            . ' | الحد الأقصى: ' . $maxRows
            . ' صف |' . $scopeNote
            . ' لا تغيّر بنية الأعمدة/العناوين أو أسماء الأوراق. عمود "م" مرجعي ولا يُستورد.';
        $sheet->setCellValue('A1', $instruction);
        $sheet->mergeCells('A1:' . $lastCol . '1');

        // Header rows 2-3 (labels/groups) and row 4 (hints)
        foreach ($columns as $col) {
            $letter = $col['letter'];
            $kind = (string) $col['kind'];
            $label = (string) ($col['label'] ?? '');
            $isHeaderCol = in_array($kind, ['fixed', 'tail'], true) || ($kind === 'helper' && $label !== '');
            if ($isHeaderCol) {
                $sheet->setCellValue($letter . '2', $label);
                $sheet->mergeCells($letter . '2:' . $letter . '3');
            }
            $sheet->setCellValue($letter . '4', (string) ($col['hint'] ?? ''));
        }

        $groupCols = [];
        foreach ($columns as $i => $col) {
            if (in_array($col['kind'], ['section', 'notes', 'overall_grade'], true)) {
                $groupCols[$col['group']][] = $i;
            }
        }
        foreach ($layout['groups'] as $group) {
            $name = (string) $group['name'];
            if (!isset($groupCols[$name])) {
                continue;
            }
            $idxs = $groupCols[$name];
            $startCol = Coordinate::stringFromColumnIndex(min($idxs) + 1);
            $endCol = Coordinate::stringFromColumnIndex(max($idxs) + 1);
            $weightText = ($name === '??????? ?????' || (float) ($group['weight'] ?? 0) <= 0)
                ? ''
                : ' (' . number_format((float) $group['weight'], 0) . '%)';
            $sheet->setCellValue($startCol . '2', $name . $weightText);
            if ($startCol !== $endCol) {
                $sheet->mergeCells($startCol . '2:' . $endCol . '2');
            }
        }
        foreach ($columns as $col) {
            if (in_array($col['kind'], ['section', 'notes', 'overall_grade'], true)) {
                $label = (string) ($col['label'] ?? '');
                if ($col['kind'] === 'section' && !empty($col['section_type'])) {
                    $label .= ' [' . $this->typeLabel((string) $col['section_type']) . ']';
                }
                $sheet->setCellValue($col['letter'] . '3', $label);
            }
        }

        // Styling by ranges (performance): instruction, headers, hints, borders
        $fullHeaderRange = 'A1:' . $lastCol . '4';
        $headerRowsRange = 'A2:' . $lastCol . '3';
        $hintRange = 'A4:' . $lastCol . '4';
        $sheet->getStyle($fullHeaderRange)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0F5B50');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E9');
        $sheet->getRowDimension(1)->setRowHeight(28);

        $fixedEndIndex = 0;
        $criteriaStartIndex = count($columns) + 1;
        $tailStartIndex = count($columns) - count($layout['tail_columns']) + 1;
        foreach ($columns as $i => $col) {
            $pos = $i + 1;
            $label = (string) ($col['label'] ?? '');
            if (in_array($col['kind'], ['fixed'], true) || ($col['kind'] === 'helper' && $label !== '') || ($col['kind'] === 'helper' && $label === '' && $i < $tailStartIndex - 1)) { $fixedEndIndex = max($fixedEndIndex, $pos); }
            if (in_array($col['kind'], ['section', 'notes', 'overall_grade'], true)) { $criteriaStartIndex = min($criteriaStartIndex, $pos); }
        }
        if ($fixedEndIndex < 1) { $fixedEndIndex = 1; }
        $criteriaStartIndex = min($criteriaStartIndex, max(1, $tailStartIndex - 1));
        $fixedEnd = Coordinate::stringFromColumnIndex($fixedEndIndex);
        $tailStart = Coordinate::stringFromColumnIndex($tailStartIndex);
        $criteriaStart = Coordinate::stringFromColumnIndex($criteriaStartIndex);
        $criteriaEnd = Coordinate::stringFromColumnIndex(max($criteriaStartIndex, $tailStartIndex - 1));

        $sheet->getStyle($headerRowsRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRowsRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A2:' . $fixedEnd . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F5B50');
        $sheet->getStyle($tailStart . '2:' . $lastCol . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('374151');
        if ($criteriaStartIndex < $tailStartIndex) {
            $sheet->getStyle($criteriaStart . '2:' . $criteriaEnd . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $sheet->getStyle($criteriaStart . '3:' . $criteriaEnd . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDF3EE');
            $sheet->getStyle($criteriaStart . '3:' . $criteriaEnd . '3')->getFont()->setSize(9.5)->getColor()->setRGB('0B5D52');
        }
        $sheet->getStyle($headerRowsRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0B5D52');

        $sheet->getStyle($hintRange)->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('5B6B7A');
        $sheet->getStyle($hintRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($hintRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F6F8');
        $sheet->getStyle($hintRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6DEE6');
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(36);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Serial (?) column styling in header (range-based)
        $serialColObj = $this->findColumn($layout, 'row_serial');
        if ($serialColObj !== null) {
            $sLetter = $serialColObj['letter'];
            $sheet->getStyle($sLetter . '2:' . $sLetter . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('334155');
            $sheet->getStyle($sLetter . '2:' . $sLetter . '4')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($sLetter . '4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('64748B');
        }

        // Data range base styling (range-based, not per-cell loops)
        $range = 'A' . $dataStart . ':' . $lastCol . $dataEnd;
        $sheet->getStyle($range)->getFont()->setSize(11)->getColor()->setRGB('102A43');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9E6');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
        $sheet->getStyle($range)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle('A1:' . $lastCol . '4')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Prefill data rows
        $mode = (string) ($options['mode'] ?? 'prefill');
        if ($mode !== 'empty') {
            $this->prefillRows($sheet, $layout, $branchId, $departmentId, $dataStart, $dataEnd, $options);
        }

        // Display-only serial numbers (1..N), reference column (not imported)
        if ($serialColObj !== null) {
            $sLetter = $serialColObj['letter'];
            for ($r = $dataStart; $r <= $dataEnd; $r++) {
                $sheet->setCellValue($sLetter . $r, $r - $dataStart + 1);
            }
            $serialRange = $sLetter . $dataStart . ':' . $sLetter . $dataEnd;
            $sheet->getStyle($serialRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F6');
            $sheet->getStyle($serialRange)->getFont()->setBold(true)->getColor()->setRGB('334E68');
            $sheet->getStyle($serialRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($serialRange)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
        }

        // BUG-004: helper columns (hidden) map branch name -> safe ID for dependent dropdowns
        $this->applyBranchHelperColumns($sheet, $layout, $dataStart, $dataEnd);

        // Validations, formats, conditional formatting
        $this->applyValidations($sheet, $layout, $branches, $departmentsByBranch, $gradeLabels, $programTitles, $dataStart, $dataEnd);
        $this->applyDuplicateFormatting($sheet, $layout, $dataStart, $dataEnd);
        $this->applyDateFormats($sheet, $layout, $dataStart, $dataEnd);
        $this->applyDepartmentMismatchFormatting($sheet, $layout, $dataStart, $dataEnd);

        // Lists sheet (hidden, named ranges)
        $this->buildListsSheet($book, $branches, $departmentsByBranch, $gradeLabels, $programTitles);

        // Column widths (defaults) and view
        $this->applyColumnWidths($sheet, $layout);
        $freezeAt = ($serialColObj !== null) ? (Coordinate::stringFromColumnIndex(2) . $dataStart) : ('A' . $dataStart); // keep serial visible
        $sheet->freezePane($freezeAt);
        $sheet->getProtection()->setSheet(true);
        // SRS ?12: must remain unlocked for data entry; no sheet password in XML.
        $sheet->getProtection()->setHashValue('');
        $sheet->getProtection()->setSort(false);
        $sheet->getProtection()->setInsertRows(false);
        $sheet->getProtection()->setFormatCells(false);
        // Allow users to resize/format column widths in the protected sheet for usability and printing.
        $sheet->getProtection()->setFormatColumns(false);

        // Professional print setup (RTL, fit-to-width, repeat headers, footer)
        $this->applyPrintSetup($sheet, $layout, $dataStart, $dataEnd, $lastCol);

        $book->getProperties()
            ->setCreator('???? ????? ????????')
            ->setTitle('???? ??????? - ' . $layout['template_name'])
            ->setDescription('???? ??????: ' . $templateId . ' | ???????: ' . $evaluatorEmployeeId)
            ->setCustomProperty('TemplateId', $templateId);

        $book->setActiveSheetIndex(0);
        return $book;
    }


    /**
     * وصف هيكل القالب للوحة المعاينة في شاشة الاستيراد.
     * يُشتق من نفس layout() لضمان تطابق المعاينة مع الملف المنزّل.
     */
    public function describe(int $templateId, int $evaluatorEmployeeId = 0): array
    {
        $layout = $this->layout($templateId);
        $branches = $this->getBranches(null);
        $departments = $this->getDepartmentNames();

        $columns = [];
        foreach ($layout['columns'] as $col) {
            $columns[] = [
                'letter' => $col['letter'],
                'label' => $col['label'],
                'group' => $col['group'],
                'kind' => $col['kind'] === 'notes' ? 'section' : $col['kind'],
                'required' => (bool) $col['required'],
                'rowspan' => ($col['kind'] === 'fixed' || $col['kind'] === 'tail') ? 2 : 1,
                'colspan' => 1,
                'hint' => (string) ($col['hint'] ?? ''),
                'dropdown' => in_array($col['kind'], ['fixed', 'overall_grade'], true)
                    || in_array(($col['section_type'] ?? ''), ['grade', 'multiple_choice', 'boolean', 'overall_grade'], true),
                'options' => $col['kind'] === 'overall_grade' ? $this->getGradeLabels($layout)
                    : (in_array(($col['section_type'] ?? ''), ['grade', 'multiple_choice'], true)
                        ? $this->sectionOptions($layout, (int) ($col['section_id'] ?? 0))
                        : []),
                'section_id' => $col['section_id'] ?? null,
                'section_type' => $col['kind'] === 'notes' ? 'notes' : ($col['kind'] === 'overall_grade' ? 'overall_grade' : ($col['section_type'] ?? null)),
                'section_name' => $col['section_name'] ?? null,
            ];
        }

        $sample = [];
        foreach ($layout['columns'] as $col) {
            $sample[$col['letter']] = $this->sampleValue($col, $branches, $departments, $layout);
        }
        $sample2 = $sample;
        $nameCol = $this->findColumn($layout, 'employee_name');
        $codeCol = $this->findColumn($layout, 'employee_code');
        if ($nameCol !== null) { $sample2[$nameCol['letter']] = '＋ موظف جديد'; }
        if ($codeCol !== null) { $sample2[$codeCol['letter']] = 'EMP-999'; }

        return [
            'target_type' => $layout['target_type'],
            'template_name' => $layout['template_name'],
            'fixed_count' => count($layout['fixed_columns']),
            'section_count' => count($layout['criteria_columns']) + ($layout['has_overall_grade'] ? 1 : 0),
            'tail_count' => count($layout['tail_columns']),
            'columns' => $columns,
            'criteria' => $layout['groups'],
            'sections' => $layout['groups'],
            'dropdowns' => [
                'branches' => $branches,
                'departments' => $departments,
            ],
            'sample_rows' => [$sample, $sample2],
            'has_overall_grade' => $layout['has_overall_grade'],
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // التخطيط والبيانات
    // ────────────────────────────────────────────────────────────────

    private function getTemplateOrFail(int $templateId): array
    {
        $stmt = $this->database->getPdo()->prepare('SELECT * FROM form_templates WHERE id = :id');
        $stmt->execute(['id' => $templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($template === false) {
            throw new RuntimeException('القالب المطلوب غير موجود.');
        }
        return $template;
    }

    private function getSections(int $templateId): array
    {
        $stmt = $this->database->getPdo()->prepare(
            'SELECT cs.*, c.criterion_name, c.criterion_weight, c.order_index AS criterion_order
             FROM criteria_sections cs
             JOIN criteria c ON c.id = cs.criterion_id
             WHERE c.template_id = :template_id
             ORDER BY c.order_index ASC, cs.order_index ASC, cs.id ASC'
        );
        $stmt->execute(['template_id' => $templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sectionRequired(array $section): bool
    {
        if ((int) ($section['notes_required'] ?? 0) === 1) {
            return true;
        }
        $options = json_decode((string) ($section['evaluation_options'] ?? ''), true);
        return is_array($options) && !empty($options['required']);
    }

    private function sectionHint(array $section): string
    {
        $min = $section['min_value'] ?? 0;
        $max = $section['max_value'] ?? 100;
        return match ((string) ($section['evaluation_type'] ?? '')) {
            'numeric' => 'رقم بين ' . $min . ' و ' . $max,
            'percentage' => 'نسبة 0-100',
            'grade', 'multiple_choice' => 'اختر من القائمة',
            'overall_grade' => 'اختر من سلم التقديرات',
            'boolean' => 'نعم أو لا',
            'text', 'textarea' => 'نص حر حتى 4000 حرف',
            'scale' => 'قيمة من المقياس',
            default => 'نص حر',
        };
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'numeric' => 'رقمي',
            'percentage' => 'نسبة %',
            'grade' => 'تقدير',
            'boolean' => 'نعم/لا',
            'multiple_choice' => 'اختيار',
            'text' => 'نص',
            'textarea' => 'نص طويل',
            'scale' => 'مقياس',
            'overall_grade' => 'تقدير',
            'notes' => 'ملاحظة',
            default => $type,
        };
    }

    private function getBranches(?int $branchId): array
    {
        $pdo = $this->database->getPdo();
        if ($branchId !== null && $branchId > 0) {
            $stmt = $pdo->prepare('SELECT id, name FROM branches WHERE id = :id ORDER BY name');
            $stmt->execute(['id' => $branchId]);
        } else {
            $stmt = $pdo->query('SELECT id, name FROM branches ORDER BY name');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDepartmentsByBranch(array $branches): array
    {
        $pdo = $this->database->getPdo();
        $out = [];
        foreach ($branches as $branch) {
            $stmt = $pdo->prepare('SELECT name FROM departments WHERE branch_id = :bid ORDER BY name');
            $stmt->execute(['bid' => (int) $branch['id']]);
            $out[(string) $branch['name']] = array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        }
        return $out;
    }

    private function getDepartmentNames(): array
    {
        return array_map('strval', $this->database->getPdo()->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getGradeLabels(array $layout): array
    {
        $scaleName = null;
        $overall = $layout['overall_section'] ?? null;
        if (is_array($overall)) {
            $options = json_decode((string) ($overall['evaluation_options'] ?? ''), true);
            if (is_array($options) && !empty($options['scale_name'])) {
                $scaleName = (string) $options['scale_name'];
            }
        }
        $pdo = $this->database->getPdo();
        try {
            if ($scaleName !== null && $scaleName !== '') {
                $stmt = $pdo->prepare('SELECT grade_label FROM grading_scales WHERE is_active = 1 AND scale_name = :scale ORDER BY sort_order, min_value DESC, id');
                $stmt->execute(['scale' => $scaleName]);
                $labels = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if ($labels !== []) {
                    return array_values(array_unique(array_map('strval', $labels)));
                }
            }
            $labels = $pdo->query('SELECT grade_label FROM grading_scales WHERE is_active = 1 ORDER BY scale_name, sort_order, min_value DESC, id')->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $labels = [];
        }
        $labels = array_values(array_unique(array_map('strval', $labels)));
        return $labels !== [] ? $labels : self::FAILED_GRADE_FALLBACK;
    }

    private function sectionOptions(array $layout, int $sectionId): array
    {
        foreach ($layout['sections'] as $section) {
            if ((int) $section['id'] !== $sectionId) {
                continue;
            }
            $opts = json_decode((string) ($section['evaluation_options'] ?? ''), true);
            $labels = [];
            if (is_array($opts)) {
                foreach ($opts as $opt) {
                    if (is_array($opt) && isset($opt['label'])) {
                        $labels[] = (string) $opt['label'];
                    } elseif (is_string($opt)) {
                        $labels[] = $opt;
                    }
                }
            }
            return $labels;
        }
        return [];
    }

    private function resolveSingleProgram(string $targetType, array $options): ?array
    {
        $type = $targetType === 'employee_in_workshop' ? 'workshop' : 'course';
        $pdo = $this->database->getPdo();
        $sql = 'SELECT tp.*, b.name AS branch_name, d.name AS department_name
                FROM training_programs tp
                LEFT JOIN branches b ON b.id = tp.executing_branch_id
                LEFT JOIN departments d ON d.id = tp.executing_department_id
                WHERE tp.type = :type';
        $params = ['type' => $type];
        if (!empty($options['program_id'])) {
            $sql .= ' AND tp.id = :pid';
            $params['pid'] = (int) $options['program_id'];
        }
        if (!empty($options['program_title'])) {
            $sql .= ' AND tp.title = :title';
            $params['title'] = (string) $options['program_title'];
        }
        if (!empty($options['executing_branch_id'])) {
            $sql .= ' AND tp.executing_branch_id = :ebid';
            $params['ebid'] = (int) $options['executing_branch_id'];
        }
        if (!empty($options['executing_department_id'])) {
            $sql .= ' AND tp.executing_department_id = :edid';
            $params['edid'] = (int) $options['executing_department_id'];
        }
        $sql .= ' ORDER BY tp.start_date DESC, tp.id DESC LIMIT 2';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($rows) === 1 ? $rows[0] : null;
    }

    private function getProgramTitles(array $layout, ?int $branchId, ?int $departmentId, array $options): array
    {
        if (!in_array($layout['target_type'], ['employee_in_course', 'employee_in_workshop'], true)) {
            return [];
        }
        if (is_array($layout['program'])) {
            return [(string) $layout['program']['title']];
        }
        $type = $layout['target_type'] === 'employee_in_workshop' ? 'workshop' : 'course';
        $pdo = $this->database->getPdo();
        $sql = 'SELECT DISTINCT title FROM training_programs WHERE type = :type';
        $params = ['type' => $type];
        $executingBranchId = ($branchId !== null && $branchId > 0)
            ? $branchId
            : (!empty($options['executing_branch_id']) ? (int) $options['executing_branch_id'] : null);
        $deptId = !empty($options['executing_department_id']) ? (int) $options['executing_department_id'] : $departmentId;
        if ($executingBranchId !== null && $executingBranchId > 0) {
            $sql .= ' AND executing_branch_id = :bid';
            $params['bid'] = $executingBranchId;
        }
        if ($deptId !== null && $deptId > 0) {
            $sql .= ' AND executing_department_id = :edid';
            $params['edid'] = $deptId;
        }
        $sql .= ' ORDER BY title';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }
    private function getEvaluatorInfo(int $employeeId): array
    {
        if ($employeeId <= 0) {
            return ['full_name' => '', 'position_title' => ''];
        }
        $stmt = $this->database->getPdo()->prepare(
            'SELECT e.full_name, p.title AS position_title FROM employees e
             LEFT JOIN positions p ON p.id = e.position_id WHERE e.id = :id'
        );
        $stmt->execute(['id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : ['full_name' => '', 'position_title' => ''];
    }

    private function prefillRows(Worksheet $sheet, array $layout, ?int $branchId, ?int $departmentId, int $start, int $end, array $options): void
    {
        $strategy = (string) ($options['prefill_strategy'] ?? '');
        if ($strategy === '') {
            // Backward compatibility (training/program exports, legacy mode)
            $mode = (string) ($options['mode'] ?? 'prefill');
            if ($mode === 'empty') {
                return;
            }
            $strategy = 'legacy';
        }

        if ($strategy === 'blank') {
            return;
        }
        if ($strategy === 'org') {
            $this->prefillOrgRows($sheet, $layout, $branchId, $departmentId, $start, $end, $options);
            return;
        }
        if ($strategy === 'previous_evals') {
            $this->prefillPreviousEvalRows($sheet, $layout, $branchId, $departmentId, $start, $end, $options);
            return;
        }
        if ($strategy === 'executing') {
            $this->prefillExecutingRows($sheet, $layout, $branchId, $departmentId, $start, $end, $options);
            return;
        }

        // legacy behavior
        $targetType = $layout['target_type'];
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);

        if ($isProgram) {
            $program = $layout['program'];
            if (!is_array($program) || (empty($options['program_id']) && empty($options['program_title']))) {
                return; // «كل البرامج»: صفوف فارغة لتعبئة رأس البرنامج يدوياً.
            }
            $stmt = $this->database->getPdo()->prepare(
                'SELECT e.emp_code, e.full_name, b.name AS branch_name, d.name AS department_name
                 FROM program_participants pp
                 JOIN employees e ON e.id = pp.employee_id
                 LEFT JOIN branches b ON b.id = e.branch_id
                 LEFT JOIN departments d ON d.id = e.department_id
                 WHERE pp.program_id = :pid
                 ORDER BY e.full_name ASC, e.id ASC'
            );
            $stmt->execute(['pid' => (int) $program['id']]);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $row = $start;
            foreach ($employees as $emp) {
                if ($row > $end) { break; }
                $this->writeRow($sheet, $layout, $row, [
                    'program_title' => (string) $program['title'],
                    'program_branch' => (string) ($program['branch_name'] ?? ''),
                    'program_department' => (string) ($program['department_name'] ?? ''),
                    'program_start' => (string) ($program['start_date'] ?? ''),
                    'program_end' => (string) ($program['end_date'] ?? ''),
                    'program_responsible' => (string) ($program['responsible_person'] ?? ''),
                    'employee_name' => (string) ($emp['full_name'] ?? ''),
                    'employee_code' => (string) ($emp['emp_code'] ?? ''),
                    'employee_branch' => (string) ($emp['branch_name'] ?? ''),
                    'employee_department' => (string) ($emp['department_name'] ?? ''),
                ]);
                $row++;
            }
            return;
        }

        if (($branchId === null || $branchId <= 0) && ($departmentId === null || $departmentId <= 0)) {
            return;
        }
        $pdo = $this->database->getPdo();
        $sql = 'SELECT e.emp_code, e.full_name, b.name AS branch_name, d.name AS department_name
                FROM employees e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.is_active = 1';
        $params = [];
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' AND e.department_id = :dept';
            $params['dept'] = $departmentId;
        } elseif ($branchId !== null && $branchId > 0) {
            $sql .= ' AND e.branch_id = :branch';
            $params['branch'] = $branchId;
        }
        $sql .= ' ORDER BY e.full_name ASC, e.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $start;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $emp) {
            if ($row > $end) { break; }
            $this->writeRow($sheet, $layout, $row, [
                'employee_name' => (string) ($emp['full_name'] ?? ''),
                'employee_code' => (string) ($emp['emp_code'] ?? ''),
                'employee_branch' => (string) ($emp['branch_name'] ?? ''),
                'employee_department' => (string) ($emp['department_name'] ?? ''),
            ]);
            $row++;
        }
    }

    /**
     * Phase 1 / Option 2 (Org Structure): employees in org scope with latest evaluation reference (if any).
     * - A1/A2: latest eval per employee (evaluation_date DESC, id DESC), deterministic ordering + limit.
     * - Q2 (program, all programs): latest eval regardless of program; header from that eval.
     * - Q2 (program, single): latest eval within selected program context.
     * - Q6: no previous eval => basic employee data, grades/reference empty.
     * - A5/A6: fill branch names (helpers via formulas) and actual section/overall/notes/date/header fields.
     */
    private function prefillOrgRows(Worksheet $sheet, array $layout, ?int $branchId, ?int $departmentId, int $start, int $end, array $options): void
    {
        $pdo = $this->database->getPdo();
        $targetType = (string) ($layout['target_type'] ?? 'employee');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        $templateId = (int) ($layout['template_id'] ?? 0);
        $limit = max(0, $end - $start + 1);
        if ($templateId <= 0 || $limit <= 0) {
            return;
        }

        // Employee org scope (option 2): filter by employee branch/department
        $empParams = [];
        $empWhere = 'e.is_active = 1';
        if ($departmentId !== null && $departmentId > 0) {
            $empWhere .= ' AND e.department_id = :dept';
            $empParams['dept'] = $departmentId;
        } elseif ($branchId !== null && $branchId > 0) {
            $empWhere .= ' AND e.branch_id = :branch';
            $empParams['branch'] = $branchId;
        }
        $empSql = 'SELECT e.id, e.emp_code, e.full_name, b.name AS branch_name, d.name AS department_name
                   FROM employees e
                   LEFT JOIN branches b ON b.id = e.branch_id
                   LEFT JOIN departments d ON d.id = e.department_id
                   WHERE ' . $empWhere . '
                   ORDER BY e.full_name ASC, e.id ASC
                   LIMIT ' . (int) $limit;
        $empStmt = $pdo->prepare($empSql);
        $empStmt->execute($empParams);
        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($employees === []) {
            return;
        }

        $employeeIds = array_values(array_map(static fn($r) => (int) $r['id'], $employees));
        $programId = isset($options['program_id']) ? (int) $options['program_id'] : 0;

        $latestByEmp = $this->fetchLatestEvaluationsForEmployees($templateId, $employeeIds, $isProgram, $programId);
        $evalIds = array_values(array_unique(array_filter(array_map(static fn($r) => (int) ($r['id'] ?? 0), $latestByEmp))));
        $answersByEval = $this->fetchEvaluationAnswersMap($evalIds);

        $row = $start;
        foreach ($employees as $emp) {
            if ($row > $end) { break; }
            $empId = (int) $emp['id'];
            $ev = $latestByEmp[$empId] ?? null;
            $values = [
                'employee_name' => (string) ($emp['full_name'] ?? ''),
                'employee_code' => (string) ($emp['emp_code'] ?? ''),
                'employee_branch' => (string) ($emp['branch_name'] ?? ''),
                'employee_department' => (string) ($emp['department_name'] ?? ''),
            ];

            if (is_array($ev)) {
                $evalId = (int) $ev['id'];
                $values['evaluation_date'] = $this->formatDateForExcel((string) ($ev['evaluation_date'] ?? ''));
                $values['evaluator_name'] = (string) ($ev['evaluator_name'] ?? '');
                $values['evaluator_title'] = (string) ($ev['evaluator_title'] ?? '');
                $values['notes'] = (string) ($ev['notes'] ?? '');
                // overall grade label (descriptive course overall handled via overall_grade column)
                $values['overall_grade'] = (string) ($ev['final_grade_label'] ?? '');

                if ($isProgram) {
                    $values['program_title'] = (string) ($ev['program_title'] ?? '');
                    $values['program_branch'] = (string) ($ev['program_branch_name'] ?? '');
                    $values['program_department'] = (string) ($ev['program_department_name'] ?? '');
                    $values['program_start'] = $this->formatDateForExcel((string) ($ev['program_start_date'] ?? ''));
                    $values['program_end'] = $this->formatDateForExcel((string) ($ev['program_end_date'] ?? ''));
                    $values['program_responsible'] = (string) ($ev['program_responsible'] ?? '');
                }

                foreach (($answersByEval[$evalId] ?? []) as $sectionId => $ans) {
                    $sid = (int) $sectionId;
                    $values['section_' . $sid] = (string) ($ans['raw_value'] ?? '');
                    $noteVal = trim((string) ($ans['notes'] ?? ''));
                    if ($noteVal !== '') {
                        $values['notes_' . $sid] = $noteVal;
                    }
                }
            }

            $this->writeRow($sheet, $layout, $row, $values);
            $row++;
        }
    }

    /**
     * Fetch latest evaluation per employee for template.
     * - Program + specific programId: restrict to that program/context.
     * - Program + all programs (option 2 Q2): latest regardless of program (no context restriction).
     * - Non-program: latest for template+employee.
     * Returns map [employee_id => evaluation row].
     */
    private function fetchLatestEvaluationsForEmployees(int $templateId, array $employeeIds, bool $isProgram, int $programId = 0): array
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds), static fn($v) => $v > 0)));
        if ($templateId <= 0 || $employeeIds === []) {
            return [];
        }
        $pdo = $this->database->getPdo();
        $in = implode(',', array_fill(0, count($employeeIds), '?'));

        $ctxFilter = '';
        if ($isProgram && $programId > 0) {
            $ctxFilter = ' AND e.context_id = ?';
        }

        // A1/Q2: deterministic latest-per-employee selection via window ranking (MariaDB 10.4 supports ROW_NUMBER)
        $sql = 'SELECT x.id, x.evaluated_employee_id, x.evaluation_date, x.notes, x.final_grade_label, x.final_grade_value,
                       x.evaluator_name, x.evaluator_title, x.context_id, x.context_type,
                       tp.title AS program_title, tb.name AS program_branch_name, td.name AS program_department_name,
                       tp.start_date AS program_start_date, tp.end_date AS program_end_date, tp.responsible_person AS program_responsible
                FROM (
                    SELECT e.id, e.evaluated_employee_id, e.evaluation_date, e.notes, e.final_grade_label, e.final_grade_value,
                           e.evaluator_name, e.evaluator_title, e.context_id, e.context_type,
                           ROW_NUMBER() OVER (PARTITION BY e.evaluated_employee_id ORDER BY e.evaluation_date DESC, e.id DESC) AS rn
                    FROM evaluations e
                    WHERE e.template_id = ?' . $ctxFilter . ' AND e.evaluated_employee_id IN (' . $in . ')
                ) x
                LEFT JOIN training_programs tp ON tp.id = x.context_id
                LEFT JOIN branches tb ON tb.id = tp.executing_branch_id
                LEFT JOIN departments td ON td.id = tp.executing_department_id
                WHERE x.rn = 1';

        $params = [$templateId];
        if ($ctxFilter !== '') { $params[] = $programId; }
        foreach ($employeeIds as $id) { $params[] = $id; }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['evaluated_employee_id']] = $r;
        }
        return $map;
    }

    /** @return array<int,array<int,array{raw_value:string,notes:string}>> [evaluation_id => [section_id => answer]] */
    private function fetchEvaluationAnswersMap(array $evaluationIds): array
    {
        $evaluationIds = array_values(array_unique(array_filter(array_map('intval', $evaluationIds), static fn($v) => $v > 0)));
        if ($evaluationIds === []) {
            return [];
        }
        $pdo = $this->database->getPdo();
        $in = implode(',', array_fill(0, count($evaluationIds), '?'));
        $stmt = $pdo->prepare('SELECT evaluation_id, section_id, raw_value, notes FROM evaluation_answers WHERE evaluation_id IN (' . $in . ')');
        $stmt->execute($evaluationIds);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $eid = (int) $row['evaluation_id'];
            $sid = (int) $row['section_id'];
            if (!isset($map[$eid])) { $map[$eid] = []; }
            $map[$eid][$sid] = [
                'raw_value' => (string) ($row['raw_value'] ?? ''),
                'notes' => (string) ($row['notes'] ?? ''),
            ];
        }
        return $map;
    }

    /**
     * Phase 2 / Option 3 (Previous Evals): each evaluation is one row with its historical context (Q3/Q4).
     * Employee org filters (branch/department of employee) apply as per spec matrix.
     */
    private function prefillPreviousEvalRows(Worksheet $sheet, array $layout, ?int $branchId, ?int $departmentId, int $start, int $end, array $options): void
    {
        $rows = $this->fetchPreviousEvaluationRows($layout, $branchId, $departmentId, $start, $end, $options);
        $this->writeEvaluationRows($sheet, $layout, $rows, $start, $end);
    }

    /**
     * Phase 2 / Option 4 (Executing Scope, course/workshop): rows from evaluations matching
     * program + executing branch/department filters (Q1/Q5: employee branch/dept filters disabled).
     * Row context/program fields come from the evaluation's historical context (Q3/Q4).
     */
    private function prefillExecutingRows(Worksheet $sheet, array $layout, ?int $branchId, ?int $departmentId, int $start, int $end, array $options): void
    {
        $rows = $this->fetchExecutingEvaluationRows($layout, $start, $end, $options);
        $this->writeEvaluationRows($sheet, $layout, $rows, $start, $end);
    }

    /**
     * @param array<int,array<string,mixed>> $evalRows
     */
    private function writeEvaluationRows(Worksheet $sheet, array $layout, array $evalRows, int $start, int $end): void
    {
        if ($evalRows === []) {
            return;
        }
        $isProgram = in_array((string) ($layout['target_type'] ?? ''), ['employee_in_course', 'employee_in_workshop'], true);
        $evalIds = array_values(array_unique(array_filter(array_map(static fn($r) => (int) ($r['id'] ?? 0), $evalRows))));
        $answersByEval = $this->fetchEvaluationAnswersMap($evalIds);
        $rowNum = $start;
        foreach ($evalRows as $ev) {
            if ($rowNum > $end) { break; }
            $values = $this->buildValuesFromEvaluationRow($layout, $ev, $answersByEval, $isProgram);
            $this->writeRow($sheet, $layout, $rowNum, $values);
            $rowNum++;
        }
    }

    /**
     * Build export row values from a joined evaluation record.
     * Q3/Q4: program/context fields are taken from the evaluation's historical context (joined program fields).
     * Employee identity/branch/department come from joined employee record (current employee org fields).
     */
    private function buildValuesFromEvaluationRow(array $layout, array $ev, array $answersByEval, bool $isProgram): array
    {
        $evalId = (int) ($ev['id'] ?? 0);
        $values = [
            'employee_name' => (string) ($ev['emp_full_name'] ?? $ev['employee_name'] ?? ''),
            'employee_code' => (string) ($ev['emp_code'] ?? $ev['employee_code'] ?? ''),
            'employee_branch' => (string) ($ev['emp_branch_name'] ?? $ev['branch_name'] ?? ''),
            'employee_department' => (string) ($ev['emp_department_name'] ?? $ev['department_name'] ?? ''),
            'evaluation_date' => $this->formatDateForExcel((string) ($ev['evaluation_date'] ?? '')),
            'evaluator_name' => (string) ($ev['evaluator_name'] ?? ''),
            'evaluator_title' => (string) ($ev['evaluator_title'] ?? ''),
            'notes' => (string) ($ev['notes'] ?? ''),
            'overall_grade' => (string) ($ev['final_grade_label'] ?? ''),
        ];

        if ($isProgram) {
            $values['program_title'] = (string) ($ev['program_title'] ?? '');
            $values['program_branch'] = (string) ($ev['program_branch_name'] ?? '');
            $values['program_department'] = (string) ($ev['program_department_name'] ?? '');
            $values['program_start'] = $this->formatDateForExcel((string) ($ev['program_start_date'] ?? ''));
            $values['program_end'] = $this->formatDateForExcel((string) ($ev['program_end_date'] ?? ''));
            $values['program_responsible'] = (string) ($ev['program_responsible'] ?? '');
        }

        foreach (($answersByEval[$evalId] ?? []) as $sectionId => $ans) {
            $sid = (int) $sectionId;
            $values['section_' . $sid] = (string) ($ans['raw_value'] ?? '');
            $noteVal = trim((string) ($ans['notes'] ?? ''));
            if ($noteVal !== '') {
                $values['notes_' . $sid] = $noteVal;
            }
        }
        return $values;
    }

    /**
     * Option 3: all previous evaluations for template in employee org scope.
     * - Employee branch/department filters apply (employees).
     * - Program types: if program_id provided, restrict to that program context; else include all matching eval contexts.
     * - Rows carry historical context from evaluation (Q3/Q4).
     */
    private function fetchPreviousEvaluationRows(array $layout, ?int $branchId, ?int $departmentId, int $start, int $end, array $options): array
    {
        $pdo = $this->database->getPdo();
        $templateId = (int) ($layout['template_id'] ?? 0);
        $targetType = (string) ($layout['target_type'] ?? 'employee');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        $limit = max(0, $end - $start + 1);
        if ($templateId <= 0 || $limit <= 0) {
            return [];
        }

        $where = ['ev.template_id = :tid'];
        $params = ['tid' => $templateId];

        // Employee org scope filters apply (matrix)
        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'e.department_id = :dept';
            $params['dept'] = $departmentId;
        } elseif ($branchId !== null && $branchId > 0) {
            $where[] = 'e.branch_id = :branch';
            $params['branch'] = $branchId;
        }

        $programId = isset($options['program_id']) ? (int) $options['program_id'] : 0;
        if ($isProgram && $programId > 0) {
            $where[] = 'ev.context_id = :pid';
            $params['pid'] = $programId;
        }

        $sql = 'SELECT ev.id, ev.evaluated_employee_id, ev.evaluation_date, ev.notes, ev.final_grade_label, ev.final_grade_value,
                       ev.evaluator_name, ev.evaluator_title, ev.context_id, ev.context_type,
                       e.emp_code, e.full_name AS emp_full_name, eb.name AS emp_branch_name, ed.name AS emp_department_name,
                       tp.title AS program_title, tb.name AS program_branch_name, td.name AS program_department_name,
                       tp.start_date AS program_start_date, tp.end_date AS program_end_date, tp.responsible_person AS program_responsible
                FROM evaluations ev
                INNER JOIN employees e ON e.id = ev.evaluated_employee_id
                LEFT JOIN branches eb ON eb.id = e.branch_id
                LEFT JOIN departments ed ON ed.id = e.department_id
                LEFT JOIN training_programs tp ON tp.id = ev.context_id
                LEFT JOIN branches tb ON tb.id = tp.executing_branch_id
                LEFT JOIN departments td ON td.id = tp.executing_department_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.full_name ASC, e.id ASC, ev.evaluation_date DESC, ev.id DESC
                LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Option 4 (executing scope, course/workshop): evaluations whose linked program matches
     * executing branch/department (and optional program) filters. Employee org filters disabled (Q1/Q5).
     * Rows carry historical context from evaluation (Q3/Q4).
     */
    private function fetchExecutingEvaluationRows(array $layout, int $start, int $end, array $options): array
    {
        $targetType = (string) ($layout['target_type'] ?? 'employee');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        if (!$isProgram) {
            return [];
        }
        $pdo = $this->database->getPdo();
        $templateId = (int) ($layout['template_id'] ?? 0);
        $limit = max(0, $end - $start + 1);
        if ($templateId <= 0 || $limit <= 0) {
            return [];
        }

        $where = ['ev.template_id = :tid', 'ev.context_id IS NOT NULL'];
        $params = ['tid' => $templateId];

        $programId = isset($options['program_id']) ? (int) $options['program_id'] : 0;
        if ($programId > 0) {
            $where[] = 'ev.context_id = :pid';
            $params['pid'] = $programId;
        }

        $execBranch = isset($options['executing_branch_id']) ? (int) $options['executing_branch_id'] : 0;
        $execDept = isset($options['executing_department_id']) ? (int) $options['executing_department_id'] : 0;
        if ($execBranch > 0) {
            $where[] = 'tp.executing_branch_id = :ebid';
            $params['ebid'] = $execBranch;
        }
        if ($execDept > 0) {
            $where[] = 'tp.executing_department_id = :edid';
            $params['edid'] = $execDept;
        }

        $sql = 'SELECT ev.id, ev.evaluated_employee_id, ev.evaluation_date, ev.notes, ev.final_grade_label, ev.final_grade_value,
                       ev.evaluator_name, ev.evaluator_title, ev.context_id, ev.context_type,
                       e.emp_code, e.full_name AS emp_full_name, eb.name AS emp_branch_name, ed.name AS emp_department_name,
                       tp.title AS program_title, tb.name AS program_branch_name, td.name AS program_department_name,
                       tp.start_date AS program_start_date, tp.end_date AS program_end_date, tp.responsible_person AS program_responsible
                FROM evaluations ev
                INNER JOIN employees e ON e.id = ev.evaluated_employee_id
                LEFT JOIN branches eb ON eb.id = e.branch_id
                LEFT JOIN departments ed ON ed.id = e.department_id
                INNER JOIN training_programs tp ON tp.id = ev.context_id
                LEFT JOIN branches tb ON tb.id = tp.executing_branch_id
                LEFT JOIN departments td ON td.id = tp.executing_department_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY tp.start_date DESC, tp.id DESC, e.full_name ASC, e.id ASC, ev.evaluation_date DESC, ev.id DESC
                LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countOrgRows(?int $branchId, ?int $departmentId): int
    {
        $pdo = $this->database->getPdo();
        $sql = 'SELECT COUNT(*) FROM employees e WHERE e.is_active = 1';
        $params = [];
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' AND e.department_id = :dept';
            $params['dept'] = $departmentId;
        } elseif ($branchId !== null && $branchId > 0) {
            $sql .= ' AND e.branch_id = :branch';
            $params['branch'] = $branchId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function countPreviousEvaluationRows(array $layout, ?int $branchId, ?int $departmentId, array $options): int
    {
        $pdo = $this->database->getPdo();
        $templateId = (int) ($layout['template_id'] ?? 0);
        $targetType = (string) ($layout['target_type'] ?? 'employee');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        if ($templateId <= 0) { return 0; }

        $where = ['ev.template_id = :tid'];
        $params = ['tid' => $templateId];
        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'e.department_id = :dept';
            $params['dept'] = $departmentId;
        } elseif ($branchId !== null && $branchId > 0) {
            $where[] = 'e.branch_id = :branch';
            $params['branch'] = $branchId;
        }
        $programId = isset($options['program_id']) ? (int) $options['program_id'] : 0;
        if ($isProgram && $programId > 0) {
            $where[] = 'ev.context_id = :pid';
            $params['pid'] = $programId;
        }

        $sql = 'SELECT COUNT(*)
                FROM evaluations ev
                INNER JOIN employees e ON e.id = ev.evaluated_employee_id
                WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function countExecutingEvaluationRows(array $layout, array $options): int
    {
        $targetType = (string) ($layout['target_type'] ?? 'employee');
        $isProgram = in_array($targetType, ['employee_in_course', 'employee_in_workshop'], true);
        if (!$isProgram) { return 0; }
        $pdo = $this->database->getPdo();
        $templateId = (int) ($layout['template_id'] ?? 0);
        if ($templateId <= 0) { return 0; }

        $where = ['ev.template_id = :tid', 'ev.context_id IS NOT NULL'];
        $params = ['tid' => $templateId];

        $programId = isset($options['program_id']) ? (int) $options['program_id'] : 0;
        if ($programId > 0) {
            $where[] = 'ev.context_id = :pid';
            $params['pid'] = $programId;
        }

        $execBranch = isset($options['executing_branch_id']) ? (int) $options['executing_branch_id'] : 0;
        $execDept = isset($options['executing_department_id']) ? (int) $options['executing_department_id'] : 0;
        if ($execBranch > 0) {
            $where[] = 'tp.executing_branch_id = :ebid';
            $params['ebid'] = $execBranch;
        }
        if ($execDept > 0) {
            $where[] = 'tp.executing_department_id = :edid';
            $params['edid'] = $execDept;
        }

        $sql = 'SELECT COUNT(*)
                FROM evaluations ev
                INNER JOIN employees e ON e.id = ev.evaluated_employee_id
                INNER JOIN training_programs tp ON tp.id = ev.context_id
                WHERE ' . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    private function formatDateForExcel(string $date): string
    {
        $date = trim($date);
        if ($date === '' || $date === '0000-00-00') {
            return '';
        }
        // normalize common DB formats to YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : $date;
    }

    private function writeRow(Worksheet $sheet, array $layout, int $row, array $values): void
    {
        foreach ($layout['columns'] as $col) {
            $key = (string) $col['key'];
            if (array_key_exists($key, $values)) {
                $sheet->setCellValue($col['letter'] . $row, $values[$key]);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────
    // التحقق من الصحة (على مستوى العمود — أداء 2000 صف)
    // ────────────────────────────────────────────────────────────────

    private function applyValidations(Worksheet $sheet, array $layout, array $branches, array $departmentsByBranch, array $gradeLabels, array $programTitles, int $start, int $end): void
    {
        $hasBranches = $branches !== [];
        $hasGrades = $gradeLabels !== [];
        $hasPrograms = $programTitles !== [];

        foreach ($layout['columns'] as $col) {
            $key = (string) $col['key'];
            $letter = $col['letter'];
            $range = $letter . $start . ':' . $letter . $end;

            switch ($key) {
                case 'program_title':
                    if ($hasPrograms) {
                        $this->addListValidation($sheet, $range, 'ProgramList', 'برنامج غير صالح', 'اختر برنامجاً من القائمة.');
                    }
                    break;
                case 'program_branch':
                    if ($hasBranches) {
                        $this->addListValidation($sheet, $range, 'BranchList', 'فرع غير صالح', 'اختر فرعاً من القائمة.');
                    }
                    break;
                case 'program_department':
                    if ($hasBranches) {
                        $helperCol = $this->findColumn($layout, 'program_branch_safe_id');
                        $formula = $helperCol !== null
                            ? 'INDIRECT($' . $helperCol['letter'] . $start . ')'
                            : 'DepartmentList';
                        $this->addListValidation($sheet, $range, $formula, 'القسم المنفذ', 'اختر قسماً تابعاً للفرع المنفّذ.');
                    }
                    break;
                case 'program_start':
                case 'program_end':
                    $this->addDateValidation($sheet, $range, 'تاريخ البرنامج');
                    break;
                case 'employee_branch':
                    if ($hasBranches) {
                        $this->addListValidation($sheet, $range, 'BranchList', 'فرع غير صالح', 'اختر فرعاً من القائمة.');
                    }
                    break;
                case 'employee_department':
                    if ($hasBranches) {
                        $helperCol = $this->findColumn($layout, 'employee_branch_safe_id');
                        $formula = $helperCol !== null
                            ? 'INDIRECT($' . $helperCol['letter'] . $start . ')'
                            : 'DepartmentList';
                        $this->addListValidation($sheet, $range, $formula, 'القسم', 'اختر قسماً تابعاً للفرع المختار.');
                    }
                    break;
                case 'evaluation_date':
                    $this->addDateValidation($sheet, $range, 'تاريخ التقييم');
                    break;
                case 'overall_grade':
                    if ($hasGrades) {
                        $this->addListValidation($sheet, $range, 'GradeList', 'تقدير غير صالح', 'اختر التقدير العام من القائمة.');
                    }
                    break;
            }

            if ($col['kind'] === 'notes') {
                continue;
            }
            if ($col['kind'] !== 'section') {
                continue;
            }
            $type = (string) ($col['section_type'] ?? '');
            $sectionId = (int) ($col['section_id'] ?? 0);
            if ($type === 'numeric') {
                $min = '0';
                $max = '100';
                foreach ($layout['sections'] as $section) {
                    if ((int) $section['id'] === $sectionId) {
                        $min = (string) ($section['min_value'] ?? 0);
                        $max = (string) ($section['max_value'] ?? 100);
                        break;
                    }
                }
                $this->addDecimalValidation($sheet, $range, $min, $max, 'قيمة خارج النطاق', 'أدخل رقماً بين ' . $min . ' و ' . $max . '.');
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');
            } elseif ($type === 'percentage') {
                $this->addWholeValidation($sheet, $range, '0', '100', 'نسبة غير صالحة', 'أدخل نسبة بين 0 و 100.');
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0');
            } elseif (in_array($type, ['grade', 'multiple_choice'], true)) {
                $options = $this->sectionOptions($layout, $sectionId);
                if ($options === [] && $layout['target_type'] === 'skill') {
                    $options = self::SKILL_LEVELS;
                }
                if ($options !== []) {
                    $this->addListValidation($sheet, $range, $layout['target_type'] === 'skill' && $options === self::SKILL_LEVELS ? 'SkillLevelList' : $this->inlineList($options), 'قيمة غير صالحة', 'اختر من القائمة: ' . implode('، ', $options));
                }
            } elseif ($type === 'boolean') {
                $this->addListValidation($sheet, $range, '"نعم,لا"', 'قيمة غير صالحة', 'اختر: نعم أو لا.');
            } elseif ($type === 'scale') {
                if ($layout['target_type'] === 'skill') {
                    $this->addListValidation($sheet, $range, 'SkillLevelList', 'مستوى غير صالح', 'اختر: متقدم / جيد / مقبول.');
                }
            }
        }
    }

    private function addListValidation(Worksheet $sheet, string $range, string $formula, string $title, string $message): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setFormula1($formula)
            ->setShowErrorMessage(true)
            ->setErrorTitle($title)
            ->setError($message);
        $sheet->setDataValidation($range, $dv);
    }

    private function addDecimalValidation(Worksheet $sheet, string $range, string $min, string $max, string $title, string $message): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_DECIMAL)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setOperator(DataValidation::OPERATOR_BETWEEN)
            ->setFormula1($min)
            ->setFormula2($max)
            ->setShowErrorMessage(true)
            ->setErrorTitle($title)
            ->setError($message);
        $sheet->setDataValidation($range, $dv);
    }

    private function addWholeValidation(Worksheet $sheet, string $range, string $min, string $max, string $title, string $message): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_WHOLE)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setOperator(DataValidation::OPERATOR_BETWEEN)
            ->setFormula1($min)
            ->setFormula2($max)
            ->setShowErrorMessage(true)
            ->setErrorTitle($title)
            ->setError($message);
        $sheet->setDataValidation($range, $dv);
    }

    private function addDateValidation(Worksheet $sheet, string $range, string $title): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_CUSTOM)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setFormula1('LEN(' . strtok($range, ':') . ')>0')
            ->setShowErrorMessage(false)
            ->setShowInputMessage(true)
            ->setPromptTitle($title)
            ->setPrompt('يقبل YYYY-MM-DD أو DD-MM-YYYY مثال 2026-05-20 أو 20-05-2026');
        $sheet->setDataValidation($range, $dv);
    }

    /**
     * BUG-004: Fill hidden helper columns with safe branch IDs via VLOOKUP(BranchName, BranchSafeMap, 2, FALSE).
     * Used by dependent department dropdowns (INDIRECT(helperCell)).
     */
    private function applyBranchHelperColumns(Worksheet $sheet, array $layout, int $start, int $end): void
    {
        $pairs = [
            ['branch' => 'program_branch', 'helper' => 'program_branch_safe_id'],
            ['branch' => 'employee_branch', 'helper' => 'employee_branch_safe_id'],
        ];
        foreach ($pairs as $pair) {
            $branchCol = $this->findColumn($layout, $pair['branch']);
            $helperCol = $this->findColumn($layout, $pair['helper']);
            if ($branchCol === null || $helperCol === null) {
                continue;
            }
            $b = $branchCol['letter'];
            $h = $helperCol['letter'];
            $sheet->getColumnDimension($h)->setVisible(false);
            for ($row = $start; $row <= $end; $row++) {
                $formula = '=IFERROR(VLOOKUP(' . $b . $row . ',BranchSafeMap,2,FALSE),"")';
                $sheet->setCellValue($h . $row, $formula);
            }
        }
    }

    private function applyDuplicateFormatting(Worksheet $sheet, array $layout, int $start, int $end): void
    {
        $isProgram = in_array($layout['target_type'], ['employee_in_course', 'employee_in_workshop'], true);
        $nameCol = $this->findColumn($layout, 'employee_name');
        $codeCol = $this->findColumn($layout, 'employee_code');
        if ($nameCol === null || $codeCol === null) {
            return;
        }
        $n = $nameCol['letter'];
        $c = $codeCol['letter'];
        $p = $isProgram ? ($this->findColumn($layout, 'program_title')['letter'] ?? 'A') : null;

        $codeCond = new Conditional();
        $codeCond->setConditionType(Conditional::CONDITION_EXPRESSION);
        $codeCond->setOperatorType(Conditional::OPERATOR_NONE);
        $codeFormula = $isProgram
            ? 'COUNTIFS($' . $p . '$' . $start . ':$' . $p . '$' . $end . ',$' . $p . $start . ',$' . $c . '$' . $start . ':$' . $c . '$' . $end . ',$' . $c . $start . ')>1'
            : 'COUNTIF($' . $c . '$' . $start . ':$' . $c . '$' . $end . ',$' . $c . $start . ')>1';
        $codeCond->addCondition($codeFormula);
        $this->styleDuplicateCondition($codeCond);
        $sheet->getStyle($c . $start . ':' . $c . $end)->setConditionalStyles([$codeCond]);

        $nameCond = new Conditional();
        $nameCond->setConditionType(Conditional::CONDITION_EXPRESSION);
        $nameCond->setOperatorType(Conditional::OPERATOR_NONE);
        $nameFormula = $isProgram
            ? 'AND($' . $c . $start . '="",COUNTIFS($' . $p . '$' . $start . ':$' . $p . '$' . $end . ',$' . $p . $start . ',$' . $n . '$' . $start . ':$' . $n . '$' . $end . ',$' . $n . $start . ')>1)'
            : 'AND($' . $c . $start . '="",COUNTIF($' . $n . '$' . $start . ':$' . $n . '$' . $end . ',$' . $n . $start . ')>1)';
        $nameCond->addCondition($nameFormula);
        $this->styleDuplicateCondition($nameCond);
        $sheet->getStyle($n . $start . ':' . $n . $end)->setConditionalStyles([$nameCond]);
    }

    private function styleDuplicateCondition(Conditional $cond): void
    {
        $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA');
        $cond->getStyle()->getFont()->getColor()->setRGB('991B1B');
        $cond->setStopIfTrue(false);
    }

    private function applyDateFormats(Worksheet $sheet, array $layout, int $start, int $end): void
    {
        foreach (['program_start', 'program_end', 'evaluation_date'] as $key) {
            $col = $this->findColumn($layout, $key);
            if ($col !== null) {
                $sheet->getStyle($col['letter'] . $start . ':' . $col['letter'] . $end)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }
        }
    }

    /**
     * UX: Highlight department cells when selected department does not belong to the selected branch.
     * Uses Safe ID helper + Branch_N named ranges (BUG-004). Macro-free visual warning before import.
     */
    private function applyDepartmentMismatchFormatting(Worksheet $sheet, array $layout, int $start, int $end): void
    {
        $pairs = [
            ['dept' => 'program_department', 'helper' => 'program_branch_safe_id'],
            ['dept' => 'employee_department', 'helper' => 'employee_branch_safe_id'],
        ];
        foreach ($pairs as $pair) {
            $deptCol = $this->findColumn($layout, $pair['dept']);
            $helperCol = $this->findColumn($layout, $pair['helper']);
            if ($deptCol === null || $helperCol === null) {
                continue;
            }
            $d = $deptCol['letter'];
            $h = $helperCol['letter'];
            $formula = 'AND($' . $d . $start . '<>"",$' . $h . $start . '<>"",ISERROR(MATCH($' . $d . $start . ',INDIRECT($' . $h . $start . '),0)))';
            $cond = new Conditional();
            $cond->setConditionType(Conditional::CONDITION_EXPRESSION);
            $cond->setOperatorType(Conditional::OPERATOR_NONE);
            $cond->addCondition($formula);
            $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA');
            $cond->getStyle()->getFont()->getColor()->setRGB('991B1B');
            $cond->setStopIfTrue(false);

            $range = $d . $start . ':' . $d . $end;
            $existing = $sheet->getStyle($range)->getConditionalStyles();
            $existing[] = $cond;
            $sheet->getStyle($range)->setConditionalStyles($existing);
        }
    }

    // ────────────────────────────────────────────────────────────────
    // ورقة Lists المخفية
    // ────────────────────────────────────────────────────────────────

    private function buildListsSheet(Spreadsheet $book, array $branches, array $departmentsByBranch, array $gradeLabels, array $programTitles): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle(self::LISTS_SHEET);
        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $sheet->setRightToLeft(true);

        $branchNames = array_map(static fn(array $b): string => (string) $b["name"], $branches);
        $allDepartments = [];
        foreach ($departmentsByBranch as $depts) {
            foreach ($depts as $d) { $allDepartments[$d] = true; }
        }
        $allDepartments = array_keys($allDepartments);
        if ($allDepartments === []) { $allDepartments = [""]; }

        $this->writeListColumn($sheet, "A", "الفروع", $branchNames);
        $this->writeListColumn($sheet, "B", "الأقسام", $allDepartments);
        $this->writeListColumn($sheet, "C", "البرامج", $programTitles);
        $this->writeListColumn($sheet, "D", "سلم التقديرات", $gradeLabels);
        $this->writeListColumn($sheet, "E", "مستويات المهارة", self::SKILL_LEVELS);

        $this->addNamedRange($book, "BranchList", $sheet, "A", max(2, count($branchNames) + 1));
        $this->addNamedRange($book, "DepartmentList", $sheet, "B", max(2, count($allDepartments) + 1));
        $this->addNamedRange($book, "ProgramList", $sheet, "C", max(2, count($programTitles) + 1));
        $this->addNamedRange($book, "GradeList", $sheet, "D", max(2, count($gradeLabels) + 1));
        $this->addNamedRange($book, "SkillLevelList", $sheet, "E", max(2, count(self::SKILL_LEVELS) + 1));

        // BUG-004: Safe ID (Map & VLOOKUP) for dependent branch->department dropdowns.
        // F: branch name (map key), G: safe ID (Branch_1...), H+: departments per safe ID.
        $safeIdCol = 7; // G
        $deptStartCol = 8; // H
        $mapLastRow = max(2, count($branchNames) + 1);
        $sheet->setCellValue('F1', 'BranchName');
        $sheet->setCellValue('G1', 'المعرّف الآمن');
        $row = 2;
        $colIndex = $deptStartCol;
        $lastStyledCol = 'G';
        foreach ($branchNames as $i => $branchName) {
            $safeId = 'Branch_' . ($i + 1);
            $sheet->setCellValue('F' . $row, (string) $branchName);
            $sheet->setCellValue('G' . $row, $safeId);

            $depts = array_values($departmentsByBranch[$branchName] ?? []);
            $deptCol = Coordinate::stringFromColumnIndex($colIndex);
            $this->writeListColumn($sheet, $deptCol, $safeId, $depts);
            $this->addNamedRange($book, $safeId, $sheet, $deptCol, max(2, count($depts) + 1));

            $lastStyledCol = $deptCol;
            $row++;
            $colIndex++;
        }
        if ($branchNames === []) {
            $sheet->setCellValue('F2', '');
            $sheet->setCellValue('G2', '');
        }
        // Named range for branch->safeID map (used by VLOOKUP in main sheet helper columns)
        $book->addNamedRange(new NamedRange('BranchSafeMap', $sheet, '$F$2:$G$' . $mapLastRow));

        $sheet->getStyle("A1:" . $lastStyledCol . "1")->getFont()->setBold(true);
        $sheet->getStyle("A1:" . $lastStyledCol . "1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB("D1E7DD");
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setHashValue("");
    }

    private function writeListColumn(Worksheet $sheet, string $col, string $header, array $values): void
    {
        $sheet->setCellValue($col . '1', $header);
        $row = 2;
        foreach ($values as $value) {
            $sheet->setCellValue($col . $row, (string) $value);
            $row++;
        }
        if ($values === []) {
            $sheet->setCellValue($col . '2', '');
        }
    }

    private function addNamedRange(Spreadsheet $book, string $name, Worksheet $sheet, string $col, int $lastRow): void
    {
        $end = max(2, $lastRow);
        $book->addNamedRange(new NamedRange($name, $sheet, '$' . $col . '$2:$' . $col . '$' . $end));
    }

    // ────────────────────────────────────────────────────────────────
    // أدوات مساعدة
    // ────────────────────────────────────────────────────────────────


    private function applyPrintSetup(Worksheet $sheet, array $layout, int $dataStart, int $dataEnd, string $lastCol): void
    {
        $sheet->setRightToLeft(true); // ensure RTL layout for Arabic printing/viewing

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0); // as many pages tall as needed
        $pageSetup->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $pageSetup->setHorizontalCentered(true);

        // Print the full template grid area (headers + allocated data rows)
        $sheet->getPageSetup()->setPrintArea('A1:' . $lastCol . $dataEnd);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.5);
        $margins->setBottom(0.6);
        $margins->setLeft(0.3);
        $margins->setRight(0.3);
        $margins->setHeader(0.2);
        $margins->setFooter(0.3);

        $title = (string) ($layout['template_name'] ?? '???? ???????');
        $safeTitle = str_replace(['&', '"'], ['?', "'"], $title);
        $sheet->getHeaderFooter()->setOddFooter('&R' . $safeTitle . ' | ????? ???????: &D &T&C???? &P ?? &N');
        $sheet->setShowGridlines(false);
        $sheet->setPrintGridlines(true);
    }
    private function applyColumnWidths(Worksheet $sheet, array $layout): void
    {
        foreach ($layout['columns'] as $idx => $col) {
            $letter = $col['letter'];
            $kind = (string) $col['kind'];
            $key = (string) $col['key'];
            if ($kind === 'helper') {
                if ($key === 'row_serial') {
                    $sheet->getColumnDimension($letter)->setWidth(6.5);
                } else {
                    $sheet->getColumnDimension($letter)->setWidth(2);
                }
                continue;
            }
            if ($kind === 'fixed') {
                $w = match ($key) {
                    'program_title' => 26,
                    'program_branch' => 20,
                    'program_department' => 20,
                    'program_start', 'program_end' => 14,
                    'program_responsible' => 20,
                    'employee_name' => 24,
                    'employee_code' => 16,
                    'employee_branch' => 20,
                    'employee_department' => 20,
                    default => 18,
                };
                $sheet->getColumnDimension($letter)->setWidth($w);
                continue;
            }
            if ($kind === 'section' && ($col['section_type'] ?? '') === 'textarea') {
                $sheet->getColumnDimension($letter)->setWidth(30);
            } elseif ($kind === 'section') {
                $sheet->getColumnDimension($letter)->setWidth(18);
            } elseif ($kind === 'notes') {
                $sheet->getColumnDimension($letter)->setWidth(18);
            } elseif ($kind === 'overall_grade') {
                $sheet->getColumnDimension($letter)->setWidth(18);
            } elseif ($kind === 'tail') {
                $w = match ($key) {
                    'notes' => 30,
                    'evaluation_date' => 16,
                    'evaluator_name' => 22,
                    'evaluator_title' => 18,
                    default => 18,
                };
                $sheet->getColumnDimension($letter)->setWidth($w);
            }
        }
    }

    private function findColumn(array $layout, string $key): ?array
    {
        foreach ($layout['columns'] as $col) {
            if ((string) $col['key'] === $key) {
                return $col;
            }
        }
        return null;
    }

    private function colLetter(array $layout, string $key): string
    {
        $col = $this->findColumn($layout, $key);
        return $col !== null ? (string) $col['letter'] : 'C';
    }

    private function inlineList(array $values): string
    {
        return '"' . implode(',', array_map(static fn(string $v): string => str_replace('"', '""', $v), $values)) . '"';
    }

    private function sampleValue(array $col, array $branches, array $departments, array $layout): string
    {
        $key = (string) $col['key'];
        return match ($key) {
            'row_serial' => '1',
            'employee_name' => '???? ??????',
            'employee_code' => 'EMP-101',
            'employee_branch', 'program_branch' => (string) ($branches[0]['name'] ?? '????? ???????'),
            'employee_department', 'program_department' => (string) ($departments[0] ?? '?????'),
            'program_title' => (string) ($layout['program']['title'] ?? '?????? ??????'),
            'program_start' => date('Y-m-d', strtotime('-10 days')),
            'program_end' => date('Y-m-d', strtotime('-3 days')),
            'program_responsible' => '???????',
            'overall_grade' => '???',
            'evaluation_date' => date('Y-m-d'),
            'evaluator_name' => '??? ???????',
            'evaluator_title' => '?????? ???????',
            'notes' => '???????',
            default => $this->sampleSectionValue($col),
        };
    }

    private function sampleSectionValue(array $col): string
    {
        if (($col['kind'] ?? '') === 'notes') {
            return 'ملاحظة موجزة';
        }
        return match ((string) ($col['section_type'] ?? '')) {
            'percentage' => '85',
            'numeric' => '8',
            'boolean' => 'نعم',
            'grade', 'multiple_choice' => 'ممتاز',
            'scale' => 'متقدم',
            'text', 'textarea' => 'نص نموذجي',
            default => '',
        };
    }
}
