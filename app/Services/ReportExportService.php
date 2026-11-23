<?php
namespace App\Services;

use InvalidArgumentException;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

final class ReportExportService
{
    public function render(string $format, object $snapshot): array
    {
        if (isset($snapshot->snapshot_data['report']) && is_array($snapshot->snapshot_data['report'])) {
            $snapshot->snapshot_data['report'] = $this->normalizeReport($snapshot->snapshot_data['report']);
        }
        $baseName = 'report-' . $snapshot->report_number;
        return match ($format) {
            'pdf' => ['content' => $this->renderPdf($snapshot), 'content_type' => 'application/pdf', 'filename' => $baseName . '.pdf'],
            'xlsx' => ['content' => $this->renderXlsx($snapshot), 'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'filename' => $baseName . '.xlsx'],
            'docx' => ['content' => $this->renderDocx($snapshot), 'content_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'filename' => $baseName . '.docx'],
            default => throw new InvalidArgumentException('صيغة التصدير غير مدعومة.'),
        };
    }

    private function renderPdf(object $snapshot): string
    {
        $brand = $this->getBrand();
        $fontDir = dirname($brand['font']['regular']);
        if (!is_file($brand['font']['regular']) || !is_file($brand['font']['bold'])) {
            throw new \RuntimeException('ملفات الخط العربي المطلوبة لتصدير PDF غير موجودة.');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => $brand['font']['family'],
            'fontDir' => [$fontDir],
            'fontdata' => [
                $brand['font']['family'] => [
                    'R' => basename($brand['font']['regular']),
                    'B' => basename($brand['font']['bold']),
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'tempDir' => sys_get_temp_dir(),
            'margin_top' => 14,
            'margin_bottom' => 18,
            'margin_header' => 8,
            'margin_footer' => 8,
            'margin_left' => 14,
            'margin_right' => 14,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('تقرير التقييم الشامل للموظف — ' . $snapshot->report_number);
        $mpdf->SetAuthor('نظام تقييم أداء الموظفين');
        $mpdf->SetCreator('نظام تقييم أداء الموظفين');

        // التذييل يُثبَّت قبل كتابة أول صفحة ليظهر على جميع الصفحات بما فيها الأولى.
        $mpdf->SetHTMLFooter($this->buildFooterHtml($snapshot));

        $mpdf->WriteHTML($this->wrapDocument($this->buildStyles($brand), $this->buildHtml($snapshot)));

        // Attachments (official documents)
        $data = $snapshot->snapshot_data;
        $report = $data['report'] ?? [];
        $records = $report['records'] ?? [];
        $attachmentList = [];
        foreach ($records as $record) {
            $attachment = is_array($record) ? ($record['attachment'] ?? null) : ($record->attachment ?? null);
            if (!empty($attachment) && is_array($attachment)) {
                $attachmentList[] = $attachment;
            }
        }
        $totalAttachments = count($attachmentList);
        $attachmentIndex = 0;
        foreach ($attachmentList as $attachment) {
            $attachmentIndex++;
            $attachment['index'] = $attachmentIndex;
            $attachment['total'] = $totalAttachments;
            $mpdf->AddPage();
            $mpdf->WriteHTML($this->buildAttachmentHtml($attachment, $brand));
        }

        return $mpdf->Output('', 'S');
    }

    private function renderXlsx(object $snapshot): string
    {
        $data = $snapshot->snapshot_data;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('تقرير الموظف');
        $employee = $data['employee'];
        $report = $data['report'];

        // Logo
        $logoPath = $this->getLogoPath();
        if ($logoPath !== null) {
            $drawing = new Drawing();
            $drawing->setName('شعار الشركة');
            $drawing->setPath($logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('A1', 'شعار الشركة');
        }

        // Header (row 4+)
        $row = 4;
        $sheet->setCellValue("A{$row}", 'تقرير تقييم الموظف');
        $row++;
        $sheet->setCellValue("A{$row}", 'الاسم');
        $this->setText($sheet, "B{$row}", $employee['full_name'] ?? '');
        $row++;
        foreach ([
            'البريد الإلكتروني' => $employee['email'] ?? '',
            'الهاتف' => $employee['phone'] ?? '',
            'المدير المباشر' => $employee['manager_name'] ?? '',
            'تاريخ الالتحاق' => $employee['hire_date'] ?? '',
            'الحالة' => $employee['status'] ?? '',
        ] as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $this->setText($sheet, "B{$row}", $value);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'الرقم الوظيفي');
        $this->setText($sheet, "B{$row}", $employee['emp_code'] ?? '');
        $row++;
        $sheet->setCellValue("A{$row}", 'القسم');
        $this->setText($sheet, "B{$row}", $employee['department_name'] ?? '');
        $row++;
        $sheet->setCellValue("A{$row}", 'الفرع');
        $this->setText($sheet, "B{$row}", $employee['branch_name'] ?? '');
        $row++;
        $sheet->setCellValue("A{$row}", 'رقم التقرير');
        $this->setText($sheet, "B{$row}", $snapshot->report_number);
        $row++;
        $sheet->setCellValue("A{$row}", 'تاريخ الإنشاء');
        $this->setText($sheet, "B{$row}", $snapshot->created_at);
        $row++;

        // Averages
        $averages = $report['averages'];
        $row++;
        $sheet->setCellValue("A{$row}", 'المؤشر');
        $sheet->setCellValue("B{$row}", 'المتوسط');
        $sheet->setCellValue("C{$row}", 'التقدير');
        $labels = [
            'overall' => 'المتوسط العام',
            'employee' => 'تقييم فردي عام',
            'annual' => 'التقييم السنوي',
            'periodic' => 'التقييم الدوري',
            'course' => 'تقييمات الدورات',
            'workshop' => 'تقييمات الورش',
            'skill' => 'التقييمات المهارية',
        ];
        $row++;
        foreach ($labels as $key => $label) {
            // Snapshots issued before BUG-H do not carry every averages key: keep export tolerant.
            $summary = $averages[$key] ?? [];
            $average = $summary['average'] ?? null;
            $this->setText($sheet, "A{$row}", $label);
            if ($average === null) {
                $this->setText($sheet, "B{$row}", '—');
            } else {
                $sheet->setCellValue("B{$row}", (float)$average);
            }
            $this->setText($sheet, "C{$row}", $summary['grade_label'] ?? '—');
            $row++;
        }

        // Records
        $row++;
        foreach (['التاريخ', 'النوع', 'القالب', 'الدرجة / 100', 'التقدير'] as $column => $heading) {
            $sheet->setCellValue([$column + 1, $row], $heading);
        }
        $row++;
        foreach ($report['records'] as $record) {
            $values = [
                ['text', $this->recordValue($record, 'evaluation_date')],
                ['text', $this->derivedTypeLabel($record)],
                ['text', $this->recordValue($record, 'template_name')],
                ['number', $this->getRecordScore($record)],
                ['text', $this->recordValue($record, 'grade_label')],
            ];
            foreach ($values as $column => [$type, $value]) {
                $coordinate = [$column + 1, $row];
                if ($type === 'text') { $sheet->setCellValueExplicit($coordinate, (string)$value, DataType::TYPE_STRING); }
                else { $sheet->setCellValue($coordinate, $value); }
            }
            $row++;
        }

        // Signatures
        $row++;
        $this->setText($sheet, "A{$row}", 'التوقيعات');
        $row++;
        foreach (['الموظف', 'المدير المباشر', 'مدير الموارد البشرية'] as $column => $role) {
            $sheet->setCellValue([$column + 1, $row], $role);
        }
        $row++;
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            foreach (['A', 'B', 'C'] as $column) { $this->setText($sheet, "{$column}{$row}", $line); }
            $row++;
        }

        foreach (range('A', 'E') as $column) { $sheet->getColumnDimension($column)->setAutoSize(true); }

        // Attachments as separate sheets
        $reportFlow = $report['records'] ?? [];
        $attachmentIndex = 0;
        foreach ($reportFlow as $record) {
            $attachment = is_array($record) ? ($record['attachment'] ?? null) : ($record->attachment ?? null);
            if (empty($attachment) || !is_array($attachment)) {
                continue;
            }
            $attachmentIndex++;
            $sheetAtt = $spreadsheet->createSheet();
            $sheetAtt->setRightToLeft(true);
            $sheetAtt->setTitle('مرفق ' . $attachmentIndex);

            $rowAtt = 1;
            $sheetAtt->setCellValue("A{$rowAtt}", 'مرفق ' . $attachmentIndex);
            $sheetAtt->getStyle("A{$rowAtt}")->getFont()->setBold(true);
            $sheetAtt->getStyle("A{$rowAtt}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheetAtt->getStyle("A{$rowAtt}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheetAtt->getStyle("A{$rowAtt}")->getFill()->getStartColor()->setARGB('FF0c2823');
            $rowAtt++;
            $this->setText($sheetAtt, "A{$rowAtt}", 'التقييم: ' . ($attachment['template']['template_name'] ?? ($attachment['evaluation']['template_name'] ?? 'تقييم')));
            $rowAtt++;
            $this->setText($sheetAtt, "A{$rowAtt}", 'رقم التقييم: ' . ($attachment['evaluation_id'] ?? ''));
            $rowAtt++;
            $this->setText($sheetAtt, "A{$rowAtt}", 'التاريخ: ' . ($this->formatDate($attachment['evaluation']['evaluation_date'] ?? '')));
            $rowAtt += 2;

            $employee = $attachment['employee'] ?? [];
            if (!is_array($employee)) { $employee = (array)($employee ?? []); }
            $sheetAtt->setCellValue("A{$rowAtt}", 'بيانات الموظف');
            $sheetAtt->getStyle("A{$rowAtt}")->getFont()->setBold(true);
            $sheetAtt->getStyle("A{$rowAtt}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheetAtt->getStyle("A{$rowAtt}")->getFill()->getStartColor()->setARGB('FFF5F5F5');
            $rowAtt++;
            foreach ([
                'الاسم' => $employee['full_name'] ?? '',
                'الرقم الوظيفي' => $employee['emp_code'] ?? '',
                'الفرع' => $employee['branch_name'] ?? '',
                'القسم' => $employee['department_name'] ?? '',
            ] as $label => $value) {
                $this->setText($sheetAtt, "A{$rowAtt}", $label);
                $sheetAtt->getStyle("A{$rowAtt}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheetAtt->getStyle("A{$rowAtt}")->getFill()->getStartColor()->setARGB('FFF5F5F5');
                $this->setText($sheetAtt, "B{$rowAtt}", ($value ?: '—'));
                $rowAtt++;
            }
            $rowAtt += 1;

            $criteria = $attachment['template']['criteria'] ?? [];
            $answers = [];
            foreach (($attachment['answers'] ?? []) as $answerRow) {
                if (is_array($answerRow) && isset($answerRow['section_id'])) {
                    $answers[$answerRow['section_id']] = $answerRow;
                }
            }
            if ($criteria !== []) {
                $sheetAtt->setCellValue("A{$rowAtt}", 'معايير التقييم');
                $rowAtt++;
                $sheetAtt->setCellValue("A{$rowAtt}", 'عنصر التقييم');
                $sheetAtt->setCellValue("B{$rowAtt}", 'المستوى');
                $sheetAtt->setCellValue("C{$rowAtt}", 'ملاحظات');
                foreach (['A','B','C'] as $colLetter) {
                    $sheetAtt->getStyle("{$colLetter}{$rowAtt}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheetAtt->getStyle("{$colLetter}{$rowAtt}")->getFill()->getStartColor()->setARGB('FF0c2823');
                    $sheetAtt->getStyle("{$colLetter}{$rowAtt}")->getFont()->setBold(true);
                    $sheetAtt->getStyle("{$colLetter}{$rowAtt}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                }
                $rowAtt++;
                foreach ($criteria as $criterion) {
                    $this->setText($sheetAtt, "A{$rowAtt}", ($criterion['criterion_name'] ?? ''));
                    $rowAtt++;
                    foreach (($criterion['sections'] ?? []) as $section) {
                        $answer = $answers[$section['id'] ?? 0] ?? null;
                        $display = '—';
                        if ($answer) {
                            if (($section['evaluation_type'] ?? '') === 'grade') {
                                $opts = json_decode($section['evaluation_options'] ?? '[]', true);
                                foreach (($opts ?? []) as $opt) {
                                    if ((string)($opt['value'] ?? '') === (string)($answer['raw_value'] ?? '') || (string)($opt['label'] ?? '') === (string)($answer['raw_value'] ?? '')) {
                                        $display = $opt['label'] ?? $answer['raw_value'];
                                        break;
                                    }
                                }
                                if ($display === '—') { $display = $answer['raw_value'] ?? '—'; }
                            } else {
                                $display = $answer['raw_value'] ?? '—';
                            }
                        }
                        $this->setText($sheetAtt, "A{$rowAtt}", $section['section_name'] ?? '');
                        $this->setText($sheetAtt, "B{$rowAtt}", $display);
                        $this->setText($sheetAtt, "C{$rowAtt}", ($answer['notes'] ?? '') !== '' ? ($answer['notes'] ?? '') : '—');
                        $rowAtt++;
                    }
                }
            }
        }

        return $this->captureOutput(static function () use ($spreadsheet): void { (new Xlsx($spreadsheet))->save('php://output'); });
    }

    private function renderDocx(object $snapshot): string
    {
        $brand = $this->getBrand();
        $word = new PhpWord();
        $word->setDefaultFontName($brand['font']['word_family']);
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
            'marginRight' => 900,
            'footerHeight' => 500,
        ]);

        $this->addDocxFooter($section, $brand);
        $this->addDocxHeader($section, $snapshot, $brand);

        $data = $snapshot->snapshot_data;
        $employee = $data['employee'] ?? [];
        $report = $data['report'] ?? [];
        $records = $report['records'] ?? [];
        $averages = $report['averages'] ?? [];

        $this->addDocxEmployeeBox($section, $employee);

        $rendered = false;
        foreach ($this->groupRecords($records) as $bucket) {
            if ($bucket['records'] === []) {
                continue;
            }
            $rendered = true;
            $summary = $this->summarizeRecords($bucket['records'], $bucket['summary']);
            $this->addDocxSection($section, $bucket['title'], $bucket['records'], $summary);
        }

        if (!$rendered) {
            $section->addTextBreak(1);
            $emptyTable = $section->addTable([
                'width' => 100, 'unit' => 'pct', 'borderSize' => 6, 'borderColor' => 'c8943f', 'cellMargin' => 120,
            ]);
            $emptyTable->addRow();
            $emptyTable->addCell(10100, ['bgColor' => 'fdfbf6', 'valign' => 'center'])
                ->addText(
                    'لا توجد تقييمات مسجلة لهذا الموظف حتى الآن.',
                    $this->docxFont(false, 11, $brand['colors']['gray']),
                    ['alignment' => Jc::CENTER, 'bidi' => true]
                );
        }

        $this->addDocxSignatures($section, $brand);
        $this->addDocxAttachments($word, $snapshot, $brand);

        return $this->captureOutput(static function () use ($word): void { IOFactory::createWriter($word, 'Word2007')->save('php://output'); });
    }

    /**
     * يُستخدم في الاختبارات ولمعاينة HTML الكامل دون كتابة ملفات.
     */
    protected function buildHtml(object $snapshot): string
    {
        $brand = $this->getBrand();
        return $this->wrapDocument(
            $this->buildStyles($brand),
            $this->buildHeaderHtml($snapshot, $brand) . $this->buildContentHtml($snapshot, $brand)
        );
    }

    private function wrapDocument(string $styles, string $body): string
    {
        return '<!doctype html><html lang="ar" dir="rtl"><meta charset="UTF-8">'
            . $styles . '<body>' . $body . '</body></html>';
    }

    private function buildStyles(array $brand): string
    {
        // Navy/gold official-document palette (mirrors _document_styles.php on the web preview)
        $navy = '#0f2a44';
        $navyLight = '#1e3a5f';
        $gold = '#c5a880';
        $goldLight = '#e8d5b5';
        $goldSoft = '#f5f0e3';
        $zebra = '#fbf9f3';
        $canvas = '#fffdf9';
        $border = '#d6d9e0';
        $borderLight = '#e8eaed';
        $ink = '#1a2332';
        $muted = '#6b7280';

        return '<style>'
            . '*{font-family:cairo;margin:0;padding:0;box-sizing:border-box}'
            . 'body{direction:rtl;color:' . $ink . ';font-size:9.5pt;line-height:1.55;background:#ffffff}'
            // الشريط العلوي (navy|gold|navy) — 3 أعمدة خامس
            . 'table.top-rule{width:100%;border-collapse:collapse;margin-bottom:0}'
            . 'table.top-rule td{border:0;padding:0;height:4px;line-height:0;font-size:0}'
            . 'td.rule-navy{background:' . $navy . ';width:38%}'
            . 'td.rule-gold{background:' . $gold . ';width:24%}'
            // الترويسة الرسمية (masthead)
            . 'table.mast{width:100%;border-collapse:collapse;border-bottom:1px solid ' . $borderLight . ';margin:0 0 4mm 0}'
            . 'table.mast td{border:0;padding:4mm 3mm;vertical-align:middle}'
            . 'td.mast-emblem{width:24mm;text-align:center}'
            . '.emblem{border:2.5px solid ' . $gold . ';border-radius:50%;width:22mm;height:22mm;background:#ffffff;text-align:center;display:inline-block}'
            . '.emblem-inner{margin-top:5.5mm;display:inline-block}'
            . 'td.mast-title{width:auto;text-align:center}'
            . '.company{font-size:10px;color:' . $muted . ';margin:0 0 1px 0}'
            . '.basmala{font-size:11px;color:#2d3a4f;margin:0 0 2px 0;font-weight:normal}'
            . '.doc-title{font-size:16.5px;font-weight:800;color:' . $navy . ';margin:0;line-height:1.35;border-bottom:2.5px solid ' . $gold . ';display:inline-block;padding:0 18pt 2px 18pt}'
            . '.doc-employee{font-size:21pt;font-weight:800;color:#000;line-height:1.3;margin-top:2mm}'
            . 'td.mast-meta{width:34mm;text-align:right;background:#f8fafc;border:1px solid ' . $borderLight . ';border-radius:8px;font-size:9.5px;line-height:1.7;color:#334155;padding:4mm 4mm}'
            . '.meta-row{margin:1px 0}'
            . '.meta-strong{color:' . $navy . ';font-weight:700}'
            . '.meta-div{border-top:1px dashed #d6d9e0;margin:3px 0;height:0}'
            . '.meta-ref{font-size:8.5px;color:#64748b;padding-top:1mm}'
            . '.ref-hash{color:' . $gold . ';font-weight:bold;font-size:9px}'
            . '.ref-num{font-weight:800;color:' . $navy . ';font-size:9.5px;letter-spacing:0.4px}'
            // الكتل ومقامات الأقسام
            . '.doc-body{padding:0 0 8mm 0}'
            . '.section-head{border-bottom:2px solid ' . $navy . ';padding:8px 0 6px 0;margin:14px 0 6px 0}'
            . '.section-num{display:inline-block;background:' . $navy . ';color:#fff;border-radius:50%;width:13pt;height:13pt;line-height:13pt;text-align:center;font-weight:800;font-size:10pt;padding-left:0.5pt;padding-right:0.5pt;margin-left:3mm}'
            . '.section-title{font-size:13pt;font-weight:800;color:' . $navy . ';display:inline-block;vertical-align:middle}'
            . '.section-count{font-size:8px;color:' . $muted . ';margin:2px 0 6px 0}'
            // بطاقة بيانات الموظف (program-card)
            . '.program-card{background:#fffbf3;border:2px solid ' . $gold . ';border-radius:10px;overflow:hidden;margin-bottom:8px;page-break-inside:avoid}'
            . 'table.prog{width:100%;border-collapse:collapse}'
            . 'table.prog td{text-align:center;vertical-align:middle;padding:4mm 3mm;border-left:1px solid #f1f3f5}'
            . 'table.prog td:last-child{border-left:none}'
            . '.cell-icon{display:block;margin-bottom:1mm}'
            . '.prog-lbl{font-size:9px;font-weight:700;color:' . $muted . ';text-transform:uppercase;letter-spacing:0.4px}'
            . '.prog-val{font-size:12.5px;font-weight:700;color:' . $ink . ';display:block}'
            // الجداول (زي ما هو على الويب)
            . 'table.data{width:100%;border-collapse:collapse;margin:4px 0 12px 0;font-size:9.5pt;background:#ffffff;border:1px solid ' . $border . ';border-radius:8px}'
            . 'table.data th{background:' . $navy . ';color:#ffffff;font-weight:800;font-size:10pt;padding:7pt 8pt;text-align:center;border-bottom:2px solid ' . $gold . '}'
            . 'table.data td{padding:6pt 8pt;border:1px solid #e8eaed;text-align:center;color:' . $ink . '}'
            . 'table.data tr:nth-child(even){background:' . $zebra . '}'
            . 'table.data tr.footer-row td{background:' . $goldLight . ';color:' . $navy . ';font-weight:800;border-top:2px solid ' . $gold . ';text-align:right}'
            . 'table.data tr.footer-row td.center{text-align:center}'
            // رابطان ومقاطع الفراغ
            . '.empty-state{margin:6mm 0;border:1px dashed ' . $gold . ';background:#fdfbf6;color:' . $muted . ';text-align:center;padding:8mm 3mm;font-size:11pt}'
            // جدول التوقيعات
            . 'table.sig{width:100%;border-collapse:collapse;margin-top:12mm;border:1px solid ' . $goldLight . '}'
            . 'table.sig th{background:' . $navy . ';color:#ffffff;font-weight:800;padding:6px;font-size:10pt;text-align:center;border:1px solid ' . $navy . ';border-bottom:2px solid ' . $gold . '}'
            . 'table.sig td{text-align:center;padding:12pt 6pt;border:1px solid #e8eaed;color:' . $muted . ';font-size:9pt;height:18mm;background:#ffffff}'
            . '</style>';
    }

    /**
     * SVG FontAwesome تضمين مضمّن (مPDF لا يتعامل مع عناصر نصية داخل SVG فائدته fill عبر المسار `<path>` مباشرة).
     */
    public static function inlineFileSvg(string $name, string $color, string $size): string
    {
        $dir = APP_ROOT . '/public/libs/fontawesome/svgs/solid/';
        $path = $dir . $name;
        if (!is_file($path)) {
            $path = $dir . $name . '.svg';
        }
        if (!is_file($path)) {
            $path = $dir . preg_replace('/\.svg$/i', '', $name) . '.svg';
        }
        $svg = is_file($path) ? (string)file_get_contents($path) : '';
        if ($svg === '') {
            return '';
        }
        $svg = (string)preg_replace('/<\?xml[^>]*\?>/', '', $svg);
        $svg = (string)preg_replace('/<!--.*?-->/s', '', $svg);
        // force fill color on every path — mPDF does not cascade root fill
        $svg = (string)preg_replace('/<path\b/i', '<path fill="' . $color . '"', $svg);
        // enforce size on the root
        if (preg_match('/<svg\b([^>]*)>/s', $svg, $m)) {
            $inner = $m[1];
            $newTag = '<svg ' . $inner . ' width="' . $size . '" height="' . $size . '"';
            if (stripos($inner, 'viewBox') === false && stripos($inner, 'viewbox') === false) {
                $newTag .= ' viewBox="0 0 512 512"';
            }
            $svg = str_replace($m[0], $newTag . '>', $svg);
        }
        return $svg;
    }

    private function buildHeaderHtml(object $snapshot, array $brand): string
    {
        $employee = $snapshot->snapshot_data['employee'] ?? [];
        $dates = self::getDocumentDates($snapshot->created_at ?? '');
        $identity = $this->getEmployeeIdentity($employee);
        $reportNumber = $snapshot->report_number ?? '';

        $emblem = self::inlineFileSvg('file-signature', '#0f2a44', '58');

        return '<table class="top-rule"><tr><td class="rule-navy"></td><td class="rule-gold"></td><td class="rule-navy"></td></tr></table>'
            . '<table class="mast"><tr>'
            . '<td class="mast-emblem"><div class="emblem"><span class="emblem-inner">' . $emblem . '</span></div></td>'
            . '<td class="mast-title">'
            . '<p class="company">اسم الشركة — وحدة الموارد البشرية</p>'
            . '<p class="basmala">بسم الله الرحمن الرحيم</p>'
            . '<h1 class="doc-title">تقرير التقييم الشامل للموظف</h1>'
            . '<div class="doc-employee">' . self::esc($identity) . '</div>'
            . '</td>'
            . '<td class="mast-meta">'
            . '<div class="meta-row"><span class="meta-strong">التاريخ:</span> ' . self::esc($dates['gregorian']) . '</div>'
            . '<div class="meta-row"><span class="meta-strong">الموافق:</span> ' . self::esc($dates['hijri']) . '</div>'
            . '<div class="meta-row"><span class="meta-strong">اليوم:</span> ' . self::esc($dates['day']) . '</div>'
            . '<div class="meta-div"></div>'
            . '<div class="meta-ref"><span class="ref-hash">#</span> <span class="ref-num">رقم التقرير: ' . self::esc($reportNumber) . '</span></div>'
            . '</td>'
            . '</tr></table>';
    }

    private function buildContentHtml(object $snapshot, array $brand): string
    {
        $data = $snapshot->snapshot_data;
        $employee = $data['employee'] ?? [];
        $report = $data['report'] ?? [];
        $records = $report['records'] ?? [];

        $iconUser = self::inlineFileSvg('user', '#c5a880', '18');
        $iconHash = self::inlineFileSvg('hashtag', '#c5a880', '18');
        $iconBuilding = self::inlineFileSvg('building', '#c5a880', '18');
        $iconSitemap = self::inlineFileSvg('sitemap', '#c5a880', '18');

        $fields = [
            ['الاسم', $employee['full_name'] ?? '', $iconUser],
            ['الرقم الوظيفي', $employee['emp_code'] ?? '', $iconHash],
            ['الفرع', $employee['branch_name'] ?? '', $iconBuilding],
            ['القسم', $employee['department_name'] ?? '', $iconSitemap],
        ];

        $sectionNum = 0;
        $html = '<main class="doc-body">';

        // 1) بيانات الموظف
        $html .= $this->buildSectionHeadHtml(++$sectionNum, 'بيانات الموظف');
        $html .= '<div class="program-card"><table class="prog"><tr>';
        foreach ($fields as [$label, $value, $icon]) {
            $display = trim((string)$value) !== '' ? (string)$value : '—';
            $html .= '<td><span class="cell-icon">' . $icon . '</span><span class="prog-lbl">' . self::esc($label) . '</span><span class="prog-val">' . self::esc($display) . '</span></td>';
        }
        $html .= '</tr></table></div>';

        // 2-6) Sections per type (web order)
        $sectionNum = (int)$sectionNum;
        $rendered = false;
        foreach ($this->groupRecords($records) as $bucket) {
            if ($bucket['records'] === []) {
                continue;
            }
            $rendered = true;
            $sectionNum++;
            $summary = $this->summarizeRecords($bucket['records'], $bucket['summary']);
            $html .= $this->buildSectionHtml($sectionNum, $bucket['title'], $bucket['records'], $summary, $bucket['key']);
        }

        if (!$rendered) {
            $html .= '<div class="empty-state">لا توجد تقييمات مسجلة لهذا الموظف حتى الآن.</div>';
        }

        // 7) التوقيعات
        $html .= $this->buildSectionHeadHtml(++$sectionNum, 'التوقيعات والاعتماد');
        $html .= $this->buildSignaturesHtml();

        $html .= '</main>';
        return $html;
    }

    private function buildSectionHeadHtml(int $number, string $title): string
    {
        return '<div class="section-head"><span class="section-num">' . (int)$number . '</span><span class="section-title">' . self::esc($title) . '</span></div>';
    }

    /**
     * CD-043: إعادة تصنيف الأنواع حسب target_type (مع fallback employee للنسخ القديمة).
     */
    private function normalizeReport(array $report): array
    {
        $records = $report['records'] ?? [];
        if (!is_array($records)) {
            return $report;
        }

        $labels = [
            'employee' => 'تقييم فردي عام',
            'annual'   => 'سنوي',
            'periodic' => 'دوري',
            'course'   => 'دورة',
            'workshop' => 'ورشة',
            'skill'    => 'مهاري',
        ];

        $deriveType = static function ($record) use ($labels): string {
            $target = null;
            $legacy = null;
            if (is_array($record)) {
                $target = $record['target_type'] ?? null;
                $legacy = $record['evaluation_type'] ?? null;
            } elseif (is_object($record)) {
                $target = $record->target_type ?? null;
                $legacy = $record->evaluation_type ?? null;
            }
            if ($target === 'employee') return 'employee';
            if ($target === 'skill') return 'skill';
            if (in_array($target, ['employee_in_course', 'course'], true)) return 'course';
            if (in_array($target, ['employee_in_workshop', 'workshop'], true)) return 'workshop';
            $legacy = is_string($legacy) ? $legacy : '';
            if ($legacy === '' || $legacy === 'annual') {
                return 'employee';
            }
            return $legacy;
        };

        foreach ($records as $index => $record) {
            $type = $deriveType($record);
            if (is_array($record)) {
                $records[$index]['evaluation_type'] = $type;
                $records[$index]['evaluation_type_label'] = $labels[$type] ?? ($record['evaluation_type_label'] ?? 'غير محدد');
            } elseif (is_object($record)) {
                $record->evaluation_type = $type;
                $record->evaluation_type_label = $labels[$type] ?? ($record->evaluation_type_label ?? 'غير محدد');
                $records[$index] = $record;
            }
        }

        // Convert to objects for AveragesEngine.
        $objects = [];
        foreach ($records as $record) {
            $obj = new \stdClass();
            if (is_array($record)) {
                foreach ($record as $key => $value) {
                    $obj->{$key} = $value;
                }
            } elseif (is_object($record)) {
                foreach (get_object_vars($record) as $key => $value) {
                    $obj->{$key} = $value;
                }
            }
            $objects[] = $obj;
        }

        $scale = $this->getCanonicalScale();
        $engine = new \App\Services\AveragesEngine();
        $averages = [];
        foreach (['overall', 'employee', 'annual', 'periodic', 'course', 'workshop', 'skill'] as $type) {
            $matching = $type === 'overall'
                ? $objects
                : array_values(array_filter($objects, static fn($record): bool => $record->evaluation_type === $type));
            $averages[$type] = $engine->summarize($matching, $scale);
        }

        $report['records'] = $records;
        $report['averages'] = $averages;
        $report['yearly'] = $engine->summarizeByYear($objects, $scale);
        return $report;
    }

    private function getCanonicalScale(): array
    {
        static $scale = null;
        if ($scale !== null) {
            return $scale;
        }
        $db = \App\Core\Database::getInstance();
        $db->query("SELECT grade_label, min_value, max_value, numeric_value FROM grading_scales WHERE scale_name = :scale_name AND is_active = 1 ORDER BY sort_order, min_value, id");
        $db->bind(':scale_name', 'السلم الخماسي');
        $scale = $db->fetchAll();
        return $scale;
    }

    /**
     * @return array<string, array{title:string, records:array, summary:?array}>
     */
    private function groupRecords(array $records): array
    {
        // نفس ترتيب وأسماء أقسام الويب: الدورات التدريبية، الورش، التقييمات المهارية، الفردية، السنوية
        $groups = [
            'course'   => ['title' => 'الدورات التدريبية', 'records' => [], 'summary' => null, 'key' => 'course'],
            'workshop' => ['title' => 'ورش العمل',        'records' => [], 'summary' => null, 'key' => 'workshop'],
            'skill'    => ['title' => 'التقييمات المهارية', 'records' => [], 'summary' => null, 'key' => 'skill'],
            'employee' => ['title' => 'التقييمات الفردية', 'records' => [], 'summary' => null, 'key' => 'employee'],
            'annual'   => ['title' => 'التقييمات السنوية', 'records' => [], 'summary' => null, 'key' => 'annual'],
        ];

        foreach ($records as $record) {
            $target = (string)$this->recordValue($record, 'target_type', '');
            if ($target === 'employee') {
                $type = 'employee';
            } elseif ($target === 'employee_in_course' || $target === 'course') {
                $type = 'course';
            } elseif ($target === 'employee_in_workshop' || $target === 'workshop') {
                $type = 'workshop';
            } elseif ($target === 'skill') {
                $type = 'skill';
            } elseif ($target === '') {
                $legacy = (string)$this->recordValue($record, 'evaluation_type', '');
                $type = ($legacy === '' || $legacy === 'annual') ? 'employee' : $legacy;
            } else {
                $type = (string)$this->recordValue($record, 'evaluation_type', '');
            }
            if (array_key_exists($type, $groups)) {
                $groups[$type]['records'][] = $record;
            } else {
                $groups['annual']['records'][] = $record;
            }
        }

        return $groups;
    }

    /**
     * CD-043: تسمية النوع حسب target_type (حتى للنسخ القديمة/الانتقالية).
     */
    private function derivedTypeLabel($record): string
    {
        $target = (string)$this->recordValue($record, 'target_type', '');
        if ($target === 'employee') {
            $type = 'employee';
        } elseif ($target === 'employee_in_course' || $target === 'course') {
            $type = 'course';
        } elseif ($target === 'employee_in_workshop' || $target === 'workshop') {
            $type = 'workshop';
        } elseif ($target === 'skill') {
            $type = 'skill';
        } elseif ($target === '') {
            $legacy = (string)$this->recordValue($record, 'evaluation_type', '');
            $type = ($legacy === '' || $legacy === 'annual') ? 'employee' : $legacy;
        } else {
            $type = (string)$this->recordValue($record, 'evaluation_type', '');
        }

        return match ($type) {
            'employee' => 'تقييم فردي عام',
            'annual' => 'سنوي',
            'periodic' => 'دوري',
            'course' => 'دورة',
            'workshop' => 'ورشة',
            'skill' => 'مهاري',
            default => (string)$this->recordValue($record, 'evaluation_type_label', '—'),
        };
    }

    private function buildSectionHtml(int $number, string $title, array $records, array $summary, string $type): string
    {
        $html = $this->buildSectionHeadHtml($number, $title)
            . '<p class="section-count">عدد السجلات: (' . count($records) . ')</p>';

        $isSimple = ($type === 'course' || $type === 'workshop');

        $headers = $isSimple
            ? ['الاسم', 'التاريخ', 'التقدير']
            : ($type === 'annual' ? ['السنة', 'التقييم', 'الدرجة / 100', 'التقدير'] : ['التاريخ', 'التقييم', 'الدرجة / 100', 'التقدير']);

        $html .= '<table class="data"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . self::esc($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $scoreOf = function (mixed $record) {
            return $this->getRecordScore($record);
        };

        $rowCount = 0;
        foreach (array_values($records) as $record) {
            $rowCount++;
            $score = $scoreOf($record);
            $date = $this->formatDate($this->recordValue($record, 'evaluation_date', ''));
            $label = $this->recordValue($record, 'grade_label', '—');
            $scoreText = $score === null || $score === '' ? '—' : number_format((float)$score, 2);
            $template = $this->recordValue($record, 'template_name', '—');

            if ($isSimple) {
                $html .= '<tr>'
                    . '<td>' . self::esc($type === 'course'
                        ? $this->recordValue($record, 'template_name', $this->recordValue($record, 'program_name', '—'))
                        : $this->recordValue($record, 'template_name', $this->recordValue($record, 'program_name', '—'))) . '</td>'
                    . '<td>' . self::esc($date) . '</td>'
                    . '<td>' . self::esc($label) . '</td>'
                    . '</tr>';
            } else {
                $col1 = $type === 'annual' ? date('Y', strtotime((string)$this->recordValue($record, 'evaluation_date', ''))) : $date;
                $html .= '<tr>'
                    . '<td>' . self::esc($col1) . '</td>'
                    . '<td>' . self::esc($template) . '</td>'
                    . '<td>' . self::esc($scoreText) . '</td>'
                    . '<td>' . self::esc($label) . '</td>'
                    . '</tr>';
            }
        }

        // footer-row
        $average = $summary['average'];
        $grade = $summary['grade_label'] ?? '—';
        $averageText = $average === null ? '—' : number_format((float)$average, 2);
        $colLabel = match ($type) {
            'course'   => 'التقدير العام للدورات التدريبية',
            'workshop' => 'التقدير العام للورش',
            'skill'    => 'التقدير العام للتقييمات المهارية',
            'individual', 'employee' => 'التقدير العام للتقييمات الفردية',
            'annual'   => 'التقدير العام للتقييمات السنوية',
            default    => 'التقدير العام',
        };

        if ($isSimple) {
            $html .= '<tr class="footer-row"><td colspan="2">' . self::esc($colLabel) . '</td>'
                . '<td class="center">' . self::esc($grade) . '</td></tr>';
        } else {
            $html .= '<tr class="footer-row"><td colspan="3">' . self::esc($colLabel) . '</td>'
                . '<td class="center">' . self::esc($grade) . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private function buildSignaturesHtml(): string
    {
        $html = '<table class="sig"><thead><tr>'
            . '<th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th>'
            . '</tr></thead><tbody>';
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $html .= '<tr><td>' . self::esc($line) . '</td><td>' . self::esc($line) . '</td><td>' . self::esc($line) . '</td></tr>';
        }
        return $html . '</tbody></table>';
    }

    private function buildFooterHtml(object $snapshot): string
    {
        $created = (string)($snapshot->created_at ?? date('Y-m-d H:i:s'));
        $userId = (int)($snapshot->created_by_user_id ?? 0);
        $reportNumber = $snapshot->report_number ?? '';
        $date = substr($created, 0, 19);

        return '<div style="font-family:cairo;font-size:8.5pt;color:#6b7280;border-top:0.8px solid #d6d9e0;padding-top:2mm;">'
            . '<table style="width:100%;border-collapse:collapse;direction:rtl"><tr>'
            . '<td style="width:33%;text-align:right;border:0;vertical-align:middle;">'
            . '<span style="display:inline-block;width:4px;height:4px;background:#c5a880;border-radius:50%;vertical-align:middle;margin-left:4px;"></span>'
            . 'وثيقة رسمية — سرّي للاستخدام الداخلي فقط</td>'
            . '<td style="width:34%;text-align:center;border:0;vertical-align:middle;">المستخدم: <strong style="color:#0f2a44;font-weight:800;border-bottom:2px solid #c5a880;">' . (int)$userId . '#</strong></td>'
            . '<td style="width:33%;text-align:left;border:0;vertical-align:middle;direction:ltr;">رقم التقرير: ' . self::esc($reportNumber) . ' · ' . self::esc($date) . '</td>'
            . '</tr></table></div>';
    }

    // ------------------------------------------------------------------
    // Word (DOCX)
    // ------------------------------------------------------------------

    private function addDocxHeader($section, object $snapshot, array $brand): void
    {
        $employee = $snapshot->snapshot_data['employee'] ?? [];
        $dates = self::getDocumentDates($snapshot->created_at ?? '');
        $identity = $this->getEmployeeIdentity($employee);

        $table = $section->addTable([
            'width' => 100,
            'unit' => 'pct',
            'borderSize' => 0,
            'cellMargin' => 30,
            'bidiVisual' => true,
        ]);
        $table->addRow();

        $refCell = $table->addCell(29, ['unit' => 'pct', 'valign' => 'center', 'borderSize' => 0]);
        $refCell->addText('المرجع:', $this->docxFont(false, 8.5, $brand['colors']['gray']), $this->docxParagraph(Jc::RIGHT, 0));
        $refCell->addText((string)($snapshot->report_number ?? ''), $this->docxFont(true, 13, $brand['colors']['primary']), $this->docxParagraph(Jc::RIGHT, 0));

        $titleCell = $table->addCell(42, ['unit' => 'pct', 'valign' => 'center', 'borderSize' => 0]);
        $titleCell->addText('بسم الله الرحمن الرحيم', $this->docxFont(false, 11, $brand['colors']['primary']), $this->docxParagraph(Jc::CENTER, 0));
        $titleCell->addText('تقرير الموظف', $this->docxFont(true, 16, $brand['colors']['primary']), $this->docxParagraph(Jc::CENTER, 0));
        $titleCell->addText($identity, $this->docxFont(true, 22, '#000000'), $this->docxParagraph(Jc::CENTER, 0));

        $datesCell = $table->addCell(29, ['unit' => 'pct', 'valign' => 'center', 'borderSize' => 0]);
        foreach ([
            'التاريخ الميلادي' => $dates['gregorian'],
            'الموافق (الهجري)' => $dates['hijri'],
            'اليوم' => $dates['day'],
        ] as $label => $value) {
            $datesCell->addText($label . ': ' . $value, $this->docxFont(false, 8.5, '#444444'), $this->docxParagraph(Jc::LEFT, 0));
        }

        // الخط الذهبي الفاصل.
        $section->addText('', [], [
            'borderBottomSize' => 18,
            'borderBottomColor' => ltrim($brand['colors']['accent'], '#'),
            'spaceBefore' => 40,
            'spaceAfter' => 120,
        ]);
    }

    private function addDocxEmployeeBox($section, array $employee): void
    {
        $fields = [
            ['الاسم', $employee['full_name'] ?? ''],
            ['الرقم الوظيفي', $employee['emp_code'] ?? ''],
            ['الفرع', $employee['branch_name'] ?? ''],
            ['القسم', $employee['department_name'] ?? ''],
        ];

        $table = $section->addTable([
            'width' => 100,
            'unit' => 'pct',
            'borderSize' => 4,
            'borderColor' => 'e2ded4',
            'cellMargin' => 60,
            'bidiVisual' => true,
        ]);
        $table->addRow();
        $table->addCell(10100, ['bgColor' => 'f5f5f5', 'gridSpan' => 4])->addText(
            'بيانات الموظف',
            $this->docxFont(true, 11, '#0c2823'),
            $this->docxParagraph(Jc::RIGHT, 0)
        );
        foreach (array_chunk($fields, 2) as $pair) {
            $table->addRow();
            foreach ($pair as [$label, $value]) {
                $table->addCell(1400, ['bgColor' => 'f5f5f5'])->addText(
                    $label,
                    $this->docxFont(false, 8.5, '#6c757d'),
                    $this->docxParagraph(Jc::RIGHT, 0)
                );
                $table->addCell(3650, ['bgColor' => 'f5f5f5'])->addText(
                    trim((string)$value) !== '' ? (string)$value : '—',
                    $this->docxFont(true, 10, '#1a1a1a'),
                    $this->docxParagraph(Jc::RIGHT, 0)
                );
            }
        }
    }

    private function addDocxSection($section, string $title, array $records, array $summary): void
    {
        $section->addTextBreak(1);

        $heading = $section->addTable([
            'width' => 100, 'unit' => 'pct', 'borderSize' => 0, 'cellMargin' => 50, 'bidiVisual' => true,
        ]);
        $heading->addRow();
        $heading->addCell(10100, ['bgColor' => '0c2823'])->addText(
            $title,
            $this->docxFont(true, 11, '#ffffff'),
            $this->docxParagraph(Jc::RIGHT, 0)
        );

        $widths = [700, 1800, 3900, 1800, 1900];
        $table = $section->addTable([
            'width' => 100,
            'unit' => 'pct',
            'borderSize' => 4,
            'borderColor' => 'e2ded4',
            'cellMargin' => 50,
            'bidiVisual' => true,
        ]);
        $table->addRow();
        foreach (['#', 'التاريخ', 'البيان', 'الدرجة / 100', 'التقدير'] as $index => $headingText) {
            $table->addCell($widths[$index], ['bgColor' => '0c2823'])->addText(
                $headingText,
                $this->docxFont(true, 9, '#ffffff'),
                $this->docxParagraph($index === 0 || $index >= 3 ? Jc::CENTER : Jc::RIGHT, 0)
            );
        }

        foreach (array_values($records) as $index => $record) {
            $score = $this->getRecordScore($record);
            $rowStyle = $index % 2 === 1 ? ['bgColor' => 'faf8f3'] : [];
            $table->addRow();
            $table->addCell($widths[0], $rowStyle)->addText((string)($index + 1), $this->docxFont(false, 9), $this->docxParagraph(Jc::CENTER, 0));
            $table->addCell($widths[1], $rowStyle)->addText(
                $this->formatDate($this->recordValue($record, 'evaluation_date')),
                $this->docxFont(false, 9),
                $this->docxParagraph(Jc::CENTER, 0)
            );
            $table->addCell($widths[2], $rowStyle)->addText(
                (string)$this->recordValue($record, 'template_name', '—'),
                $this->docxFont(false, 9),
                $this->docxParagraph(Jc::RIGHT, 0)
            );
            $table->addCell($widths[3], $rowStyle)->addText(
                $score === null ? '—' : number_format((float)$score, 2),
                $this->docxFont(false, 9),
                $this->docxParagraph(Jc::CENTER, 0)
            );
            $table->addCell($widths[4], $rowStyle)->addText(
                (string)$this->recordValue($record, 'grade_label', '—'),
                $this->docxFont(false, 9),
                $this->docxParagraph(Jc::CENTER, 0)
            );
        }

        $goldRow = ['bgColor' => 'c8943f'];
        $table->addRow();
        $table->addCell(6400, $goldRow + ['gridSpan' => 3])->addText(
            'التقدير العام',
            $this->docxFont(true, 9.5, '#0c2823'),
            $this->docxParagraph(Jc::RIGHT, 0)
        );
        $table->addCell($widths[3], $goldRow)->addText(
            $summary['average'] === null ? '—' : number_format((float)$summary['average'], 2),
            $this->docxFont(true, 9.5, '#0c2823'),
            $this->docxParagraph(Jc::CENTER, 0)
        );
        $table->addCell($widths[4], $goldRow)->addText(
            (string)($summary['grade_label'] ?? '—'),
            $this->docxFont(true, 9.5, '#0c2823'),
            $this->docxParagraph(Jc::CENTER, 0)
        );
    }

    private function addDocxSignatures($section, array $brand): void
    {
        $section->addTextBreak(2);

        $heading = $section->addTable([
            'width' => 100, 'unit' => 'pct', 'borderSize' => 0, 'cellMargin' => 50, 'bidiVisual' => true,
        ]);
        $heading->addRow();
        $heading->addCell(10100, ['bgColor' => '0c2823'])->addText(
            'التوقيعات',
            $this->docxFont(true, 11, '#ffffff'),
            $this->docxParagraph(Jc::RIGHT, 0)
        );

        $table = $section->addTable([
            'width' => 100, 'unit' => 'pct', 'borderSize' => 4, 'borderColor' => 'c8943f', 'cellMargin' => 60, 'bidiVisual' => true,
        ]);
        $table->addRow();
        foreach (['الموظف', 'المدير المباشر', 'مدير الموارد البشرية'] as $role) {
            $table->addCell(3366, ['bgColor' => 'f5f0e8'])->addText(
                $role,
                $this->docxFont(true, 9.5, $brand['colors']['primary']),
                $this->docxParagraph(Jc::CENTER, 0)
            );
        }
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $table->addRow(500);
            for ($i = 0; $i < 3; $i++) {
                $table->addCell(3366)->addText($line, $this->docxFont(false, 8.5, '#555555'), $this->docxParagraph(Jc::CENTER, 0));
            }
        }
    }

    private function addDocxFooter($section, array $brand): void
    {
        $footer = $section->addFooter();
        $table = $footer->addTable([
            'width' => 100, 'unit' => 'pct', 'borderSize' => 0, 'bidiVisual' => true,
        ]);
        $table->addRow();
        $table->addCell(36, ['unit' => 'pct'])->addPreserveText(
            $brand['confidential'],
            $this->docxFont(false, 8, $brand['colors']['gray']),
            $this->docxParagraph(Jc::RIGHT, 0)
        );
        $table->addCell(28, ['unit' => 'pct'])->addPreserveText(
            'صفحة {PAGE} من {NUMPAGES}',
            $this->docxFont(false, 8, $brand['colors']['gray']),
            $this->docxParagraph(Jc::CENTER, 0)
        );
        $table->addCell(36, ['unit' => 'pct'])->addPreserveText(
            'تاريخ الطباعة: ' . date('Y/m/d'),
            $this->docxFont(false, 8, $brand['colors']['gray']),
            $this->docxParagraph(Jc::LEFT, 0)
        );
    }

    private function docxFont(bool $bold = false, float $size = 10, ?string $color = null): array
    {
        $style = ['name' => 'Cairo', 'size' => $size];
        if ($bold) {
            $style['bold'] = true;
        }
        if ($color !== null && $color !== '') {
            $style['color'] = ltrim($color, '#');
        }
        return $style;
    }

    private function docxParagraph(string $alignment, int $spaceAfter = 80): array
    {
        return ['alignment' => $alignment, 'bidi' => true, 'spaceAfter' => $spaceAfter];
    }

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    private function getEmployeeIdentity(array $employee): string
    {
        $name = trim((string)($employee['full_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $code = trim((string)($employee['emp_code'] ?? ''));
        return $code !== '' ? $code : '—';
    }

    /**
     * @return array{gregorian:string, hijri:string, day:string}
     */
    public static function getDocumentDates(mixed $createdAt): array
    {
        $timestamp = strtotime((string)$createdAt) ?: time();
        $timezone = date_default_timezone_get();
        $hijri = '';
        $day = '';

        if (class_exists(\IntlDateFormatter::class)) {
            try {
                $hijriFormatter = new \IntlDateFormatter(
                    'en_US@calendar=islamic-umalqura',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::NONE,
                    $timezone,
                    \IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
                );
                $hijri = (string)$hijriFormatter->format($timestamp);

                $dayFormatter = new \IntlDateFormatter(
                    'ar_SA',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::NONE,
                    $timezone,
                    \IntlDateFormatter::GREGORIAN,
                    'EEEE'
                );
                $day = (string)$dayFormatter->format($timestamp);
            } catch (\Throwable $exception) {
                $hijri = '';
                $day = '';
            }
        }

        return [
            'gregorian' => date('Y/m/d', $timestamp),
            'hijri' => $hijri !== '' ? $hijri : '—',
            'day' => $day !== '' ? $day : '—',
        ];
    }

    private function summarizeRecords(array $records, ?array $summary): array
    {
        $average = ($summary !== null && ($summary['average'] ?? null) !== null) ? (float)$summary['average'] : null;
        $grade = $summary['grade_label'] ?? null;

        $scores = [];
        foreach ($records as $record) {
            $score = $this->getRecordScore($record);
            if ($score !== null && is_numeric($score)) {
                $scores[] = (float)$score;
            }
        }
        if ($average === null && $scores !== []) {
            $average = array_sum($scores) / count($scores);
        }
        if ($grade === null || $grade === '') {
            $grade = null;
            foreach ($records as $record) {
                $candidate = $this->recordValue($record, 'grade_label');
                if ($candidate !== null && $candidate !== '') {
                    $grade = (string)$candidate;
                    break;
                }
            }
        }

        return ['average' => $average, 'grade_label' => $grade];
    }

    private function formatDate(mixed $value): string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return '—';
        }
        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y/m/d', $timestamp) : $value;
    }

    private function recordValue(mixed $record, string $key, mixed $default = null): mixed
    {
        if (is_array($record)) {
            return array_key_exists($key, $record) ? $record[$key] : $default;
        }
        if (is_object($record)) {
            return property_exists($record, $key) ? $record->{$key} : $default;
        }
        return $default;
    }

    private function recordHasKey(mixed $record, string $key): bool
    {
        if (is_array($record)) {
            return array_key_exists($key, $record);
        }
        if (is_object($record)) {
            return property_exists($record, $key);
        }
        return false;
    }

    /**
     * mPDF attachment document (official-document design identical to the web preview
     * and the main PDF page). Tables only — no flex/grid/FontAwesome.
     */
    private function buildAttachmentHtml(array $attachment, array $brand): string
    {
        $evaluation = isset($attachment['evaluation']) && is_array($attachment['evaluation']) ? (object)$attachment['evaluation'] : null;
        $template = $attachment['template'] ?? [];
        $employee = isset($attachment['employee']) && is_array($attachment['employee']) ? (object)$attachment['employee'] : null;
        $program = isset($attachment['program']) && is_array($attachment['program']) ? (object)$attachment['program'] : null;

        $navy = '#0f2a44';
        $navyLight = '#1e3a5f';
        $gold = '#c5a880';
        $goldLight = '#e8d5b5';
        $zebra = '#fbf9f3';
        $border = '#d6d9e0';
        $borderLight = '#e8eaed';
        $ink = '#1a2332';
        $muted = '#6b7280';

        // Icon per target type
        $targetKey = (string)($template['target_type'] ?? 'employee');
        $emblemIcon = ['employee' => 'user', 'employee_in_course' => 'graduation-cap', 'employee_in_workshop' => 'chalkboard', 'skill' => 'bullseye'][$targetKey] ?? 'user';

        $answersMap = [];
        foreach (($attachment['answers'] ?? []) as $answerRow) {
            if (is_array($answerRow) && isset($answerRow['section_id'])) { $answersMap[$answerRow['section_id']] = (object)$answerRow; }
        }
        $answers = $answersMap;

        $evaluationDate = $evaluation->evaluation_date ?? ($attachment['evaluation']['evaluation_date'] ?? date('Y-m-d'));
        $dates = self::getDocumentDates($evaluationDate);

        // Icons
        $iconUser = self::inlineFileSvg('user', $gold, '16');
        $iconHash = self::inlineFileSvg('hashtag', $gold, '16');
        $iconBuilding = self::inlineFileSvg('building', $gold, '16');
        $iconSitemap = self::inlineFileSvg('sitemap', $gold, '16');
        $iconBook = self::inlineFileSvg('book-open', $gold, '16');
        $iconCalendar = self::inlineFileSvg('calendar', $gold, '16');
        $iconUsers = self::inlineFileSvg('users', $gold, '16');
        $iconUserTie = self::inlineFileSvg('user-tie', $gold, '16');
        $iconTag = self::inlineFileSvg('tag', $gold, '16');
        $iconAward = self::inlineFileSvg('award', $gold, '16');
        $iconListCheck = self::inlineFileSvg('list-check', $gold, '16');
        $emblem = self::inlineFileSvg($emblemIcon, $navy, '46');

        // Badge class for a level label
        $badgeClass = static function (?string $display): string {
            if ($display === null || $display === '' || $display === '—') { return 'badge-na'; }
            $low = mb_strtolower(trim($display));
            if (mb_strpos($low, 'ممتاز') !== false) { return 'badge-excellent'; }
            if (mb_strpos($low, 'جيد') !== false) { return 'badge-good'; }
            return 'badge-average';
        };

        $html = '<style>'
            . '.attach-page{background:#ffffff;border:1px solid ' . $border . ';border-radius:3px;}'
            . '.top-rule{width:100%;border-collapse:collapse;margin-bottom:0}'
            . '.top-rule td{border:0;padding:0;height:4px;line-height:0}'
            . '.rule-navy{background:' . $navy . ';width:38%}'
            . '.rule-gold{background:' . $gold . ';width:24%}'
            . 'table.attachment-head{width:100%;border-collapse:collapse}'
            . 'table.attachment-head td{border:0;padding:1.8mm 4mm;vertical-align:middle}'
            . '.emblem{border:2.5px solid ' . $gold . ';border-radius:50%;width:18mm;height:18mm;background:#ffffff;text-align:center;display:inline-block}'
            . '.emblem-inner{margin-top:3mm;display:inline-block}'
            . 'td.ah-emblem{width:20mm;text-align:center;vertical-align:middle}'
            . 'td.ah-title{width:auto;text-align:center;vertical-align:middle}'
            . '.company{font-size:9px;color:' . $muted . ';margin:0}'
            . '.basmala{font-size:9.5px;color:#2d3a4f;margin:0;font-weight:normal}'
            . '.doc-title{font-size:12.5px;font-weight:800;color:' . $navy . ';margin:0;line-height:1.3;border-bottom:2px solid ' . $gold . ';display:inline-block;padding:0 10pt 2px 10pt}'
            . 'td.ah-meta{width:32mm;text-align:right;background:#f8fafc;border:1px solid ' . $borderLight . ';border-radius:6px;font-size:9pt;line-height:1.5;color:#334155;padding:3mm 2.5mm}'
            . '.meta-strong{color:' . $navy . ';font-weight:700}'
            . '.meta-div{border-top:1px dashed #d6d9e0;margin:2px 0;height:0}'
            . '.attach-section-head{border-bottom:2px solid ' . $navy . ';padding:5px 0 4px 0;margin:6px 0 5px 0;page-break-inside:avoid}'
            . '.attach-section-num{display:inline-block;background:' . $navy . ';color:#ffffff;border-radius:50%;width:13pt;height:13pt;line-height:13pt;text-align:center;font-weight:800;font-size:10pt;margin-left:3mm;vertical-align:middle}'
            . '.attach-section-title{font-size:11px;font-weight:800;color:' . $navy . ';display:inline-block;vertical-align:middle}'
            // برنامج الموظف/البرنامج التدريبي (بطاقات ذهبية)
            . '.program-card{background:#fffbf3;border:2px solid ' . $gold . ';border-radius:10px;overflow:hidden;margin-bottom:8px;page-break-inside:avoid}'
            . '.program-card-head{background:linear-gradient(135deg,' . $navy . ' 0%,' . $navyLight . ' 100%);color:#ffffff;padding:2.5mm 4mm}'
            . '.program-card-head h3{font-size:10pt;font-weight:800;color:#ffffff;margin:0}'
            . 'table.prog{width:100%;border-collapse:collapse}'
            . 'table.prog td{text-align:center;vertical-align:middle;padding:2.5mm 2mm;border-left:1px solid #f1f3f5}'
            . 'table.prog td:last-child{border-left:none}'
            . '.prog-lbl{font-size:8px;font-weight:700;color:' . $muted . ';text-transform:uppercase;letter-spacing:0.4px}'
            . '.prog-val{font-size:10.5px;font-weight:600;color:' . $ink . ';display:block;margin-top:1mm}'
            . '.prog-icon{display:block;margin-bottom:1mm;text-align:center}'
            // بطاقات المعايير
            . '.criterion-card{background:#ffffff;border:1px solid ' . $border . ';border-radius:8px;overflow:hidden;margin-bottom:8px;page-break-inside:avoid}'
            . '.criterion-head{background:' . $navy . ';color:#ffffff;padding:2.5mm 5mm}'
            . '.criterion-head h3{font-size:9.5pt;font-weight:800;color:#ffffff;margin:0;display:inline-block;vertical-align:middle}'
            . '.criterion-head .count{background:#ffffff;color:' . $navy . ';border-radius:12pt;padding:2px 10px;font-size:10pt;font-weight:800;display:inline-block;float:left}'
            . 'table.eval-table{width:100%;border-collapse:collapse;font-size:10pt}'
            . 'table.eval-table th{background:#f8f9fb;color:' . $navy . ';font-weight:800;font-size:10pt;padding:7px 8px;text-align:center;border:1px solid ' . $border . ';border-bottom:2px solid ' . $gold . '}'
            . 'table.eval-table td{padding:5px 6px;border:1px solid #e8eaed;vertical-align:middle;line-height:1.35}'
            . 'table.eval-table tbody tr:nth-child(even){background:' . $zebra . '}'
            . 'table.eval-table td:first-child{font-weight:500}'
            . 'table.eval-table td.center{text-align:center}'
            // أشرطة التقدير (badges)
            . '.badge{display:inline-block;min-width:44px;padding:3px 10px;border-radius:12pt;font-size:9.5pt;font-weight:700;text-align:center;border:1px solid}'
            . '.badge-excellent{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}'
            . '.badge-good{background:#eff6ff;color:#1e40af;border-color:#bfdbfe}'
            . '.badge-average{background:#fef3c7;color:#92400e;border-color:#fde68a}'
            . '.badge-na{background:#f8fafc;color:#94a3b8;border-color:#e2e8f0}'
            . '.notes-cell{color:' . $muted . ';font-size:9pt}'
            . '.level-notes{font-size:7.5pt;color:#64748b;margin-top:2px;border-top:1px dashed #e2e8f0;padding-top:2px;line-height:1.3}'
            . '.evaluator-notes-card{background:#fffbf3;border:2px solid ' . $gold . ';border-radius:10px;padding:6mm 8mm;margin-bottom:6px;page-break-inside:avoid}'
            . '.evaluator-notes-text{font-size:10pt;line-height:1.75;color:#1a2332;white-space:pre-wrap}'
            . '.value-plain{color:#1a2332;font-size:9.5pt;line-height:1.3;display:inline-block;text-align:right;width:100%}'
            // بيانات القائم بالتقييم
            . 'table.evaluator{width:100%;border-collapse:collapse;margin-top:4mm}'
            . 'table.evaluator td{width:33.33%;text-align:center;padding:2mm 2mm;background:#f8fafc;border:1px solid ' . $borderLight . ';border-radius:6px;vertical-align:middle}'
            . '.eval-lbl{font-size:8px;font-weight:700;color:' . $muted . ';letter-spacing:0.4px}'
            . '.eval-val{font-size:11px;font-weight:700;color:' . $navy . ';display:block;margin-top:1px}'
            // التوقيع
            . 'table.signature-area{width:100%;border-collapse:collapse;margin-top:4mm;padding-top:2mm;border-top:1px dashed ' . $border . ';page-break-inside:avoid}'
            . 'table.signature-area td{width:33.33%;text-align:center;vertical-align:bottom;padding:2.5mm 6mm;border:1px dashed ' . $border . ';border-radius:8px;background:#fafbfc;min-height:14mm;page-break-inside:avoid}'
            . '.sig-line{border-top:1px solid ' . $navy . ';width:85%;margin:0 auto;padding-top:2mm;font-size:10pt;color:' . $navy . ';font-weight:700}'
            . '.sig-sub{font-size:8.5pt;color:' . $muted . ';min-height:1em;margin-top:2px}'
            . '</style>'
            . '<div class="attach-page">'
            . '<table class="top-rule"><tr><td class="rule-navy"></td><td class="rule-gold"></td><td class="rule-navy"></td></tr></table>'
            . '<div class="attach-band" style="background:' . $navy . ';color:#ffffff;padding:2.5mm 6mm;text-align:center;border-bottom:2px solid ' . $gold . ';"><span class="attach-title" style="font-size:12px;font-weight:800;color:#ffffff">مرفق ' . $attachment['index'] . ' من ' . $attachment['total'] . '</span></div>';

        // — الترويسة
        $documentTitle = htmlspecialchars($template['template_name'] ?? 'وثيقة التقييم');
        $html .= '<table class="attachment-head"><tr>'
            . '<td class="ah-emblem"><div class="emblem"><span class="emblem-inner">' . $emblem . '</span></div></td>'
            . '<td class="ah-title"><p class="company">اسم الشركة — وحدة الموارد البشرية</p><p class="basmala">بسم الله الرحمن الرحيم</p><h1 class="doc-title">' . $documentTitle . '</h1></td>'
            . '<td class="ah-meta"><div class="meta-row"><span class="meta-strong">التاريخ:</span> ' . self::esc($dates['gregorian']) . '</div>'
            . '<div class="meta-row"><span class="meta-strong">الموافق:</span> ' . self::esc($dates['hijri']) . '</div>'
            . '<div class="meta-row"><span class="meta-strong">اليوم:</span> ' . self::esc($dates['day']) . '</div>'
            . '<div class="meta-div"></div>'
            . '<div class="meta-ref" style="font-size:8.5px;color:#64748b;padding-top:1mm"><span class="ref-hash" style="color:' . $gold . ';font-weight:bold">#</span> <span class="ref-num" style="font-weight:800;color:' . $navy . '">تقرير #' . (int)($evaluation->id ?? 0) . '</span></div>'
            . '</td></tr></table>';

        // 1) بيانات الموظف — بطاقة ذهبية 2×2
        if ($employee) {
            $html .= $this->buildAttachSectionHead(1, 'بيانات الموظف');
            $html .= '<div class="program-card"><table class="prog"><tr>'
                . '<td><span class="prog-icon">' . $iconUser . '</span><span class="prog-lbl">الاسم</span><span class="prog-val">' . self::esc($employee->full_name ?? '—') . '</span></td>'
                . '<td><span class="prog-icon">' . $iconHash . '</span><span class="prog-lbl">الرقم الوظيفي</span><span class="prog-val">' . self::esc($employee->emp_code ?? '—') . '</span></td>'
                . '</tr><tr>'
                . '<td><span class="prog-icon">' . $iconBuilding . '</span><span class="prog-lbl">الفرع</span><span class="prog-val">' . self::esc($employee->branch_name ?? '—') . '</span></td>'
                . '<td><span class="prog-icon">' . $iconSitemap . '</span><span class="prog-lbl">القسم</span><span class="prog-val">' . self::esc($employee->department_name ?? '—') . '</span></td>'
                . '</tr></table></div>';
        }

        // 2) بيانات البرنامج التدريبي (if)
        $sectionNum = 1;
        $hasProgram = !empty($program);
        if ($hasProgram) {
            $sectionNum = 2;
            $progIcon = self::inlineFileSvg(($program->type ?? '') === 'workshop' ? 'chalkboard.svg' : 'graduation-cap.svg', '#ffffff', 16);
            $progTypeLabel = ($program->type ?? '') === 'workshop' ? 'الورشة التدريبية' : 'الدورة التدريبية';
            $html .= $this->buildAttachSectionHead(2, 'بيانات البرنامج التدريبي');
            $html .= '<div class="program-card"><div class="program-card-head"><h3>' . $progIcon . ' ' . $progTypeLabel . '</h3></div>'
                . '<table class="prog"><tr>'
                . '<td><span class="prog-icon">' . $iconBook . '</span><span class="prog-lbl">عنوان البرنامج</span><span class="prog-val" style="font-size:11px">' . self::esc($program->title ?? '—') . '</span></td>'
                . '<td><span class="prog-icon">' . $iconCalendar . '</span><span class="prog-lbl">تاريخ البرنامج</span><span class="prog-val" style="font-size:11px">' . (!empty($program->start_date) ? date('Y/m/d', strtotime($program->start_date)) . ' → ' . (!empty($program->end_date) ? date('Y/m/d', strtotime($program->end_date)) : '—') : '—') . '</span></td>'
                . '</tr><tr>'
                . '<td><span class="prog-icon">' . $iconBuilding . '</span><span class="prog-lbl">الفرع المنفّذ</span><span class="prog-val">' . self::esc($program->branch_name ?? '—') . '</span></td>'
                . '<td><span class="prog-icon">' . $iconUsers . '</span><span class="prog-lbl">القسم المنفّذ</span><span class="prog-val">' . self::esc($program->department_name ?? '—') . '</span></td>'
                . '</tr><tr>'
                . '<td><span class="prog-icon">' . $iconUserTie . '</span><span class="prog-lbl">المسؤول</span><span class="prog-val">' . self::esc($program->responsible_person ?? '—') . '</span></td>'
                . '<td><span class="prog-icon">' . $iconTag . '</span><span class="prog-lbl">نوع البرنامج</span><span class="prog-val">' . (($program->type ?? '') === 'workshop' ? 'ورشة' : 'دورة') . '</span></td>'
                . '</tr></table></div>';
        }

        // 3) معايير التقييم — النمط الوصفي (موظف داخل دورة) يُلغي عمود «ملاحظات» ويوسع «الوصف» للمساحة الكاملة
        $sectionNum = $hasProgram ? 3 : 2;
        $descriptiveCourse = ($template['target_type'] ?? '') === 'employee_in_course' && ($template['evaluation_mode'] ?? '') === 'descriptive';
        $descHeader = $descriptiveCourse ? 'الوصف' : 'المستوى';
        $html .= $this->buildAttachSectionHead($sectionNum, 'معايير التقييم');
        $tableHead = $descriptiveCourse
            ? '<th style="width:32%;text-align:right">عنصر التقييم</th><th style="width:68%;text-align:right">' . $descHeader . '</th>'
            : '<th style="width:42%;text-align:right">عنصر التقييم</th><th style="width:20%">' . $descHeader . '</th><th style="width:38%">ملاحظات</th>';
        foreach (($template['criteria'] ?? []) as $criterion) {
            $criteriaCount = count($criterion['sections']);
            $html .= '<div class="criterion-card"><div class="criterion-head"><h3>' . self::inlineFileSvg('list-check', '#ffffff', 16) . ' ' . self::esc($criterion['criterion_name'] ?? '') . '</h3><span class="count">' . (int)$criteriaCount . ' بنود</span></div>'
                . '<table class="eval-table"><thead><tr>' . $tableHead . '</tr></thead><tbody>';
            foreach (($criterion['sections'] ?? []) as $section) {
                $sid = (int)($section['id'] ?? 0);
                $answer = $answers[$sid] ?? null;
                $display = null;
                $notes = '';
                if ($answer) {
                    $options = null;
                    $raw = (string)$answer->raw_value;
                    if (($section['evaluation_type'] ?? '') === 'grade') {
                        $opts = json_decode((string)($section['evaluation_options'] ?? '[]'), true) ?: [];
                        foreach ($opts as $opt) {
                            if ((string)($opt['value'] ?? '') === $raw || (string)($opt['label'] ?? '') === $raw) { $display = (string)($opt['label'] ?? $raw); break; }
                        }
                        if ($display === null) { $display = $raw; }
                    } else {
                        $display = $raw;
                    }
                    $notes = (string)($answer->notes ?? '');
                }
                $sType = (string)($section['evaluation_type'] ?? '');
                $cls = $badgeClass($display);
                $badgeable = in_array($sType, ['grade', 'overall_grade', 'percentage', 'numeric', 'boolean', 'scale', 'multiple_choice'], true);
                if ($descriptiveCourse) {
                    // موظف داخل دورة (وصفي): عمودان فقط — المستوى يأخذ كامل العرض بما فيها ملاحظاته الصغيرة
                    $notesInner = trim($notes) !== '' ? '<div class="level-notes">' . nl2br(self::esc($notes)) . '</div>' : '';
                    $valueHtml = $display === null || $display === ''
                        ? '<span style="color:#cbd5e1;">—</span>'
                        : ($badgeable
                            ? '<span class="badge ' . $cls . '">' . self::esc($display) . '</span>'
                            : '<div class="value-plain">' . self::esc($display) . '</div>' . $notesInner);
                    $html .= '<tr><td>' . self::esc($section['section_name'] ?? '') . '</td>'
                        . '<td class="text-cell">' . $valueHtml . '</td></tr>';
                } else {
                    $html .= '<tr><td>' . self::esc($section['section_name'] ?? '') . '</td>'
                        . '<td class="center">' . ($display === null || $display === ''
                            ? '<span class="badge badge-na">—</span>'
                            : ($badgeable
                                ? '<span class="badge ' . $cls . '">' . self::esc($display) . '</span>'
                                : '<span class="value-plain">' . self::esc($display) . '</span>')) . '</td>'
                        . '<td class="notes-cell">' . (trim($notes) !== '' ? nl2br(self::esc($notes)) : '<span style="color:#cbd5e1;">—</span>') . '</td></tr>';
                }
            }
            $html .= '</tbody></table></div>';
        }

        // 4) ملاحظات ورأي المقيّم (اختياري — تُعرض فقط عند وجودها)
        $evaluatorNotes = trim((string)($evaluation->notes ?? ''));
        $sectionNumAfterCriteria = $sectionNum;
        if ($evaluatorNotes !== '') {
            $sectionNumAfterCriteria++;
            $html .= $this->buildAttachSectionHead($sectionNumAfterCriteria, 'ملاحظات ورأي المقيّم');
            $html .= '<div class="evaluator-notes-card"><div class="evaluator-notes-text">' . nl2br(self::esc($evaluatorNotes)) . '</div></div>';
        }

        // 5) بيانات القائم بالتقييم
        $sectionNum = $sectionNumAfterCriteria + 1;
        $evaluatorName = (string)($evaluation->evaluator_name ?? '');
        $evaluatorTitle = (string)($evaluation->evaluator_title ?? '');
        $html .= $this->buildAttachSectionHead($sectionNum, 'بيانات القائم بالتقييم');
        $html .= '<table class="evaluator"><tr>'
            . '<td><span class="prog-icon">' . $iconUserTie . '</span><span class="eval-lbl">الاسم</span><span class="eval-val">' . self::esc($evaluatorName !== '' ? $evaluatorName : 'غير محدد') . '</span></td>'
            . '<td><span class="prog-icon">' . $iconAward . '</span><span class="eval-lbl">الصفة</span><span class="eval-val">' . self::esc($evaluatorTitle !== '' ? $evaluatorTitle : '—') . '</span></td>'
            . '<td><span class="prog-icon">' . $iconCalendar . '</span><span class="eval-lbl">تاريخ التقييم</span><span class="eval-val" dir="ltr">' . self::esc($evaluationDate ? date('Y/m/d', strtotime((string)$evaluationDate)) : '—') . '</span></td>'
            . '</tr></table>';
        // — التوقيع
        $html .= '<table class="signature-area"><tr>'
            . '<td><div class="sig-line">توقيع المقيّم</div><div class="sig-sub">' . self::esc($evaluatorName) . '</div></td>'
            . '<td><div class="sig-line">توقيع مسؤول النظام</div><div class="sig-sub"></div></td>'
            . '<td><div class="sig-line">الختم الرسمي</div><div class="sig-sub" style="color:#94a3b8;">—</div></td>'
            . '</tr></table>';

        $html .= '</div>';
        return $html;
    }

    private function buildAttachSectionHead(int $number, string $title): string
    {
        return '<div class="attach-section-head"><span class="attach-section-num">' . (int)$number . '</span><span class="attach-section-title">' . self::esc($title) . '</span></div>';
    }

    private function getRecordScore(mixed $record): mixed
    {
        if ($this->recordHasKey($record, 'final_score')) {
            return $this->recordValue($record, 'final_score');
        }
        return $this->recordValue($record, 'grade_value');
    }

    private static function esc(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function addDocxAttachments(PhpWord $word, object $snapshot, array $brand): void
    {
        $data = $snapshot->snapshot_data;
        $records = $data['report']['records'] ?? [];
        $attachments = [];
        foreach ($records as $record) {
            $attachment = is_array($record) ? ($record['attachment'] ?? null) : ($record->attachment ?? null);
            if (!empty($attachment) && is_array($attachment)) {
                $attachments[] = $attachment;
            }
        }
        if ($attachments === []) {
            return;
        }
        $total = count($attachments);
        foreach (array_values($attachments) as $idx => $attachment) {
            $i = $idx + 1;
            $section = $word->addSection([
                'marginTop' => 900,
                'marginBottom' => 900,
                'marginLeft' => 900,
                'marginRight' => 900,
                'footerHeight' => 500,
            ]);
            $this->addDocxFooter($section, $brand);
            $this->addDocxHeader($section, $snapshot, $brand);

            $heading = $section->addTable([
                'width' => 100, 'unit' => 'pct', 'borderSize' => 0, 'cellMargin' => 50, 'bidiVisual' => true,
            ]);
            $heading->addRow();
            $heading->addCell(10100, ['bgColor' => '0c2823'])->addText(
                'مرفق ' . $i . ' من ' . $total,
                $this->docxFont(true, 12, '#ffffff'),
                $this->docxParagraph(Jc::RIGHT, 0)
            );

            $this->addDocxEmployeeBox($section, $attachment['employee'] ?? []);

            $criteria = $attachment['template']['criteria'] ?? [];
            $answers = [];
            foreach (($attachment['answers'] ?? []) as $answerRow) {
                if (is_array($answerRow) && isset($answerRow['section_id'])) {
                    $answers[$answerRow['section_id']] = $answerRow;
                }
            }
            foreach ($criteria as $criterion) {
                $section->addTextBreak(1);
                $section->addText(
                    (string)($criterion['criterion_name'] ?? ''),
                    $this->docxFont(true, 11, $brand['colors']['primary']),
                    $this->docxParagraph(Jc::RIGHT, 0)
                );
                $table = $section->addTable([
                    'width' => 100, 'unit' => 'pct', 'borderSize' => 4, 'borderColor' => 'e2ded4', 'cellMargin' => 50, 'bidiVisual' => true,
                ]);
                $table->addRow();
                foreach (['#', 'عنصر التقييم', 'المستوى', 'ملاحظات'] as $col => $colTitle) {
                    $table->addCell(($col === 0 ? 600 : ($col === 3 ? 3600 : 2700)), ['bgColor' => '0c2823'])->addText(
                        $colTitle,
                        $this->docxFont(true, 9, '#ffffff'),
                        $this->docxParagraph($col === 0 ? Jc::CENTER : Jc::RIGHT, 0)
                    );
                }
                $sectionsList = $criterion['sections'] ?? [];
                $counter = 0;
                foreach ($sectionsList as $sectionRow) {
                    $counter++;
                    $answer = $answers[$sectionRow['id'] ?? 0] ?? null;
                    $display = '—';
                    if ($answer) {
                        if (($sectionRow['evaluation_type'] ?? '') === 'grade') {
                            $opts = json_decode($sectionRow['evaluation_options'] ?? '[]', true);
                            foreach (($opts ?? []) as $opt) {
                                if ((string)($opt['value'] ?? '') === (string)($answer['raw_value'] ?? '') || (string)($opt['label'] ?? '') === (string)($answer['raw_value'] ?? '')) {
                                    $display = $opt['label'] ?? $answer['raw_value'];
                                    break;
                                }
                            }
                            if ($display === '—') { $display = $answer['raw_value'] ?? '—'; }
                        } else {
                            $display = $answer['raw_value'] ?? '—';
                        }
                    }
                    $notes = ($answer['notes'] ?? '') !== '' ? ($answer['notes'] ?? '') : '—';
                    $table->addRow();
                    $table->addCell(600, ['bgColor' => 'f5f5f5', 'valign' => 'center'])->addText((string)$counter, $this->docxFont(true, 9, '#0c2823'), $this->docxParagraph(Jc::CENTER, 0));
                    $table->addCell(2700, ['bgColor' => 'f5f5f5'])->addText((string)($sectionRow['section_name'] ?? ''), $this->docxFont(false, 9, '#1a1a1a'), $this->docxParagraph(Jc::RIGHT, 0));
                    $table->addCell(2700, ['bgColor' => 'f5f5f5'])->addText((string)$display, $this->docxFont(true, 9, '#0c2823'), $this->docxParagraph(Jc::CENTER, 0));
                    $table->addCell(3600, ['bgColor' => 'f5f5f5'])->addText((string)$notes, $this->docxFont(false, 8, '#444444'), $this->docxParagraph(Jc::RIGHT, 0));
                }
            }

            $this->addDocxSignatures($section, $brand);
        }
    }

    private function getBrand(): array
    {
        return require dirname(__DIR__) . '/Views/reports/styles/brand.php';
    }

    private function getLogoPath(): ?string
    {
        $path = $this->getBrand()['logo'];
        return is_file($path) ? $path : null;
    }

    private function captureOutput(callable $writer): string
    {
        ob_start();
        try { $writer(); return (string)ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }

    private function setText(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        $sheet->setCellValueExplicit($coordinate, (string)$value, DataType::TYPE_STRING);
    }
}
