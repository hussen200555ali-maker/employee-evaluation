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
            'margin_bottom' => 26,
            'margin_header' => 8,
            'margin_footer' => 9,
            'margin_left' => 14,
            'margin_right' => 14,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('تقرير الموظف — ' . $snapshot->report_number);
        $mpdf->SetAuthor('نظام تقييم أداء الموظفين');
        $mpdf->SetCreator('نظام تقييم أداء الموظفين');

        // التذييل يُثبَّت قبل كتابة أول صفحة ليظهر على جميع الصفحات بما فيها الأولى.
        $mpdf->SetHTMLFooter($this->buildFooterHtml($brand));

        $mpdf->WriteHTML($this->wrapDocument($this->buildStyles($brand), $this->buildHtml($snapshot)));

        // Attachments (official documents)
        $data = $snapshot->snapshot_data;
        $report = $data['report'] ?? [];
        $records = $report['records'] ?? [];
        foreach ($records as $record) {
            $attachment = is_array($record) ? ($record['attachment'] ?? null) : ($record->attachment ?? null);
            if (empty($attachment) || !is_array($attachment)) {
                continue;
            }
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
        $colors = $brand['colors'];

        return '<style>'
            . '*{font-family:' . $brand['font']['family'] . ';margin:0;padding:0;box-sizing:border-box}'
            . 'body{direction:rtl;color:#333;font-size:9.5pt;line-height:1.55}'
            // الترويسة الرسمية: ثلاثة أعمدة بلا شعار أو اسم شركة.
            . '.masthead{margin:0 0 5mm}'
            . '.masthead-table{width:100%;border-collapse:collapse}'
            . '.masthead-table td{border:0;padding:0;vertical-align:middle}'
            . '.mh-dates{width:30%;text-align:right;font-size:8.5pt;color:#444;line-height:1.7}'
            . '.mh-dates .lbl{color:' . $colors['gray'] . '}'
            . '.mh-title{width:40%;text-align:center}'
            . '.basmala{font-size:11pt;color:' . $colors['primary'] . ';margin-bottom:1.5mm}'
            . '.doc-title{font-size:16pt;font-weight:bold;color:' . $colors['primary'] . ';line-height:1.35}'
            . '.doc-employee{font-size:22pt;font-weight:bold;color:#000;line-height:1.3;margin-top:1.5mm}'
            . '.mh-ref{width:30%;text-align:left;font-size:8.5pt;color:#444}'
            . '.mh-ref .ref-label{color:' . $colors['gray'] . ';margin-bottom:0.5mm}'
            . '.mh-ref .ref-value{font-size:13pt;font-weight:bold;color:' . $colors['primary'] . ';letter-spacing:0.4px}'
            . '.gold-rule{border-top:2.2px solid ' . $colors['accent'] . ';margin:3mm 0 0;font-size:0;line-height:0}'
            // صندوق بيانات الموظف
            . '.employee-box{background:' . $colors['canvas'] . ';border:1px solid ' . $colors['border']
            . ';border-right:3px solid ' . $colors['accent'] . ';padding:2.5mm 3mm;margin:4mm 0 1mm}'
            . '.employee-box-title{font-size:11pt;font-weight:bold;color:' . $colors['primary'] . ';text-align:right;margin-bottom:1.5mm}'
            . '.employee-grid{width:100%;border-collapse:collapse}'
            . '.employee-grid td{border:0;padding:1mm 1.5mm;text-align:right;vertical-align:top}'
            . '.employee-grid td.lbl{width:16%;color:' . $colors['gray'] . ';font-size:8.5pt}'
            . '.employee-grid td.val{width:34%;color:' . $colors['ink'] . ';font-size:9.5pt;font-weight:bold}'
            // الأقسام والجداول
            . '.section{page-break-inside:avoid;margin-top:4mm}'
            // مرفقات التقييم
            . '.criterion-card{border:1px solid ' . $colors['border'] . ';border-radius:6px;overflow:hidden;margin:2mm 0 3mm}'
            . '.criterion-head{background:' . $colors['primary'] . ';color:' . $colors['white'] . ';padding:1.5mm 3mm;font-weight:bold;font-size:10pt}'
            . 'table.data td,table.data th{border:1px solid ' . $colors['border'] . ';padding:1.6mm 2mm;text-align:right}'
            . 'table.data tr.alt td{background:' . $colors['zebra'] . '}'
            . 'table.data{width:100%;border-collapse:collapse;font-size:9pt}'
            . 'table.data th{background:' . $colors['primary'] . ';color:' . $colors['white']
            . ';border:1px solid ' . $colors['primary'] . ';padding:1.8mm 2mm;text-align:right;font-weight:bold;font-size:9pt}'
            . 'table.data td{border:1px solid ' . $colors['border'] . ';padding:1.6mm 2mm;text-align:right;color:#222}'
            . 'table.data tr.alt td{background:' . $colors['zebra'] . '}'
            . 'table.data td.col-idx,table.data th.col-idx{width:6%;text-align:center}'
            . 'table.data td.col-date,table.data th.col-date{width:16%;text-align:center;white-space:nowrap}'
            . 'table.data td.col-score,table.data th.col-score{width:16%;text-align:center}'
            . 'table.data td.col-grade,table.data th.col-grade{width:18%;text-align:center}'
            . 'table.data tr.gold-row td{background:' . $colors['accent'] . ';color:' . $colors['primary']
            . ';font-weight:bold;border:1px solid ' . $colors['accent'] . ';font-size:9.5pt}'
            // حالات الفراغ والتوقيعات
            . '.confidential-mark{font-size:7.5pt;color:' . $colors['gray'] . ';text-align:center;margin-top:3mm;border-top:0.8px solid ' . $colors['border'] . ';padding-top:1.5mm}'
            . '.empty-state{margin:6mm 0;border:1px dashed ' . $colors['accent'] . ';background:#fdfbf6;'
            . 'color:' . $colors['gray'] . ';text-align:center;padding:8mm 3mm;font-size:11pt}'
            . '.sig-block{page-break-inside:avoid;margin-top:7mm}'
            . 'table.sig-table{width:100%;border-collapse:collapse;margin-top:2mm}'
            . 'table.sig-table th{background:' . $colors['light'] . ';color:' . $colors['primary']
            . ';border:1px solid ' . $colors['accent'] . ';padding:2mm;text-align:center;font-size:9.5pt}'
            . 'table.sig-table td{border:1px solid ' . $colors['border'] . ';height:10mm;text-align:center;'
            . 'color:#555;font-size:8.5pt;padding:1.5mm}'
            . '</style>';
    }

    private function buildHeaderHtml(object $snapshot, array $brand): string
    {
        $employee = $snapshot->snapshot_data['employee'] ?? [];
        $dates = $this->getDocumentDates($snapshot->created_at ?? '');
        $identity = $this->getEmployeeIdentity($employee);

        return '<div class="masthead"><table class="masthead-table"><tr>'
            . '<td class="mh-dates">'
            . '<div><span class="lbl">التاريخ الميلادي:</span> ' . self::esc($dates['gregorian']) . '</div>'
            . '<div><span class="lbl">الموافق (الهجري):</span> ' . self::esc($dates['hijri']) . '</div>'
            . '<div><span class="lbl">اليوم:</span> ' . self::esc($dates['day']) . '</div>'
            . '</td>'
            . '<td class="mh-title">'
            . '<div class="basmala">بسم الله الرحمن الرحيم</div>'
            . '<div class="doc-title">تقرير الموظف</div>'
            . '<div class="doc-employee">' . self::esc($identity) . '</div>'
            . '</td>'
            . '<td class="mh-ref">'
            . '<div class="ref-label">المرجع:</div>'
            . '<div class="ref-value">' . self::esc($snapshot->report_number ?? '') . '</div>'
            . '</td>'
            . '</tr></table><div class="gold-rule"></div></div>';
    }

    private function buildContentHtml(object $snapshot, array $brand): string
    {
        $data = $snapshot->snapshot_data;
        $employee = $data['employee'] ?? [];
        $report = $data['report'] ?? [];
        $records = $report['records'] ?? [];
        $averages = $report['averages'] ?? [];

        $html = $this->buildEmployeeBoxHtml($employee);

        $rendered = false;
        foreach ($this->groupRecords($records) as $bucket) {
            if ($bucket['records'] === []) {
                continue;
            }
            $rendered = true;
            $summary = $this->summarizeRecords($bucket['records'], $bucket['summary']);
            $html .= $this->buildSectionHtml($bucket['title'], $bucket['records'], $summary);
        }

        if (!$rendered) {
            $html .= '<div class="empty-state">لا توجد تقييمات مسجلة لهذا الموظف حتى الآن.</div>';
        }

        // CD-028: إضافة بيانات المقيّم قبل التوقيعات
        $html .= $this->buildEvaluatorInfoHtml($records);

        $html .= $this->buildSignaturesHtml();
        $html .= '<div class="confidential-mark">سرّي — للاستخدام الداخلي فقط | صفحة</div>';

        return $html;
    }

    private function buildEmployeeBoxHtml(array $employee): string
    {
        $fields = [
            'الاسم' => $employee['full_name'] ?? '',
            'الرقم الوظيفي' => $employee['emp_code'] ?? '',
            'الفرع' => $employee['branch_name'] ?? '',
            'القسم' => $employee['department_name'] ?? '',
        ];

        $html = '<div class="employee-box"><div class="employee-box-title">بيانات الموظف</div>'
            . '<table class="employee-grid"><tbody>';
        foreach (array_chunk($fields, 2, true) as $rowFields) {
            $html .= '<tr>';
            foreach ($rowFields as $label => $value) {
                $display = trim((string)$value) !== '' ? (string)$value : '—';
                $html .= '<td class="lbl">' . self::esc($label) . '</td>'
                    . '<td class="val">' . self::esc($display) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div>';
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
        $groups = [
            'course' => ['title' => 'الدورات التدريبية', 'records' => [], 'summary' => null],
            'workshop' => ['title' => 'الورش', 'records' => [], 'summary' => null],
            'employee' => ['title' => 'التقييمات الفردية', 'records' => [], 'summary' => null],
            'annual' => ['title' => 'التقييمات السنوية', 'records' => [], 'summary' => null],
            'descriptive' => ['title' => 'التقييمات الوصفية', 'records' => [], 'summary' => null],
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
            if ($this->getRecordScore($record) === null) {
                $groups['descriptive']['records'][] = $record;
                continue;
            }
            if ($type === 'course') {
                $groups['course']['records'][] = $record;
            } elseif ($type === 'workshop') {
                $groups['workshop']['records'][] = $record;
            } elseif ($type === 'employee') {
                $groups['employee']['records'][] = $record;
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

    private function buildSectionHtml(string $title, array $records, array $summary): string
    {
        $html = '<div class="section"><div class="section-title">' . self::esc($title) . '</div>'
            . '<table class="data"><thead><tr>'
            . '<th class="col-idx">#</th>'
            . '<th class="col-date">التاريخ</th>'
            . '<th>البيان</th>'
            . '<th class="col-score">الدرجة / 100</th>'
            . '<th class="col-grade">التقدير</th>'
            . '</tr></thead><tbody>';

        foreach (array_values($records) as $index => $record) {
            $score = $this->getRecordScore($record);
            $altClass = $index % 2 === 1 ? ' class="alt"' : '';
            $html .= '<tr' . $altClass . '>'
                . '<td class="col-idx">' . ($index + 1) . '</td>'
                . '<td class="col-date">' . self::esc($this->formatDate($this->recordValue($record, 'evaluation_date'))) . '</td>'
                . '<td>' . self::esc($this->recordValue($record, 'template_name', '—')) . '</td>'
                . '<td class="col-score">' . ($score === null ? '—' : self::esc(number_format((float)$score, 2))) . '</td>'
                . '<td class="col-grade">' . self::esc($this->recordValue($record, 'grade_label', '—')) . '</td>'
                . '</tr>';
        }

        $html .= '<tr class="gold-row">'
            . '<td colspan="3">التقدير العام</td>'
            . '<td class="col-score">' . ($summary['average'] === null ? '—' : self::esc(number_format((float)$summary['average'], 2))) . '</td>'
            . '<td class="col-grade">' . self::esc($summary['grade_label'] ?? '—') . '</td>'
            . '</tr>';

        return $html . '</tbody></table></div>';
    }

    /**
     * CD-028: بناء كتلة بيانات المقيّم للعرض في PDF/HTML.
     * يأخذ أول تقييم منفصل (الأحدث) ويستخرج اسم/صفة المقيّم وتاريخ التقييم.
     */
    private function buildEvaluatorInfoHtml(array $records): string
    {
        $evaluatorName = '';
        $evaluatorTitle = '';
        $evaluationDate = '';

        // ابحث في السجلات عن أول تقييم يحوي بيانات مقيّم
        foreach ($records as $record) {
            $name = $this->recordValue($record, 'evaluator_name', '');
            $title = $this->recordValue($record, 'evaluator_title', '');
            $date = $this->recordValue($record, 'evaluation_date', '');

            if ($name || $title || $date) {
                $evaluatorName = $name;
                $evaluatorTitle = $title;
                $evaluationDate = $date;
                break;
            }
        }

        if (!$evaluatorName && !$evaluatorTitle && !$evaluationDate) {
            return '';
        }

        $html = '<div class="evaluator-info-block" style="margin-top:6mm;padding:3mm 4mm;border:0.8px solid #c8943f;border-radius:3mm;background:#fffbf3;">'
            . '<div class="section-title" style="color:#c8943f;font-weight:bold;font-size:9pt;margin-bottom:2mm;">بيانات المقيّم</div>'
            . '<table style="width:100%;font-size:8.5pt;">';
        if ($evaluatorName) {
            $html .= '<tr><td style="width:25%;color:#6c757d;">اسم المقيّم:</td><td style="font-weight:bold;">' . $this->esc($evaluatorName) . '</td></tr>';
        }
        if ($evaluatorTitle) {
            $html .= '<tr><td style="color:#6c757d;">صفة المقيّم:</td><td>' . $this->esc($evaluatorTitle) . '</td></tr>';
        }
        if ($evaluationDate) {
            $html .= '<tr><td style="color:#6c757d;">تاريخ التقييم:</td><td>' . $this->esc($evaluationDate) . '</td></tr>';
        }
        $html .= '</table></div>';
        return $html;
    }

    private function buildSignaturesHtml(): string
    {
        $html = '<div class="sig-block"><div class="section-title">التوقيعات</div>'
            . '<table class="sig-table"><thead><tr>'
            . '<th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th>'
            . '</tr></thead><tbody>';
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $html .= '<tr><td>' . $line . '</td><td>' . $line . '</td><td>' . $line . '</td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

    private function buildFooterHtml(array $brand): string
    {
        $printDate = date('Y/m/d');

        return '<div style="font-family:' . $brand['font']['family'] . ';font-size:7.5pt;color:' . $brand['colors']['gray']
            . ';border-top:0.8px solid ' . $brand['colors']['accent'] . ';padding-top:1.5mm;">'
            . '<table style="width:100%;border:0;direction:ltr"><tr>'
            . '<td style="width:36%;text-align:left;border:0;">تاريخ الطباعة: ' . self::esc($printDate) . '</td>'
            . '<td style="width:28%;text-align:center;border:0;">صفحة {PAGENO} من {nbpg}</td>'
            . '<td style="width:36%;text-align:right;border:0;color:' . $brand['colors']['accent'] . ';">'
            . self::esc($brand['confidential']) . '</td>'
            . '</tr></table></div>';
    }

    // ------------------------------------------------------------------
    // Word (DOCX)
    // ------------------------------------------------------------------

    private function addDocxHeader($section, object $snapshot, array $brand): void
    {
        $employee = $snapshot->snapshot_data['employee'] ?? [];
        $dates = $this->getDocumentDates($snapshot->created_at ?? '');
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
    private function getDocumentDates(mixed $createdAt): array
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
     * HTML fragment for a single evaluation attachment (official document).
     * Reuses the shared _document partial to match the report view exactly.
     */
    private function buildAttachmentHtml(array $attachment, array $brand): string
    {
        $evaluation = isset($attachment['evaluation']) && is_array($attachment['evaluation']) ? (object)$attachment['evaluation'] : null;
        $template = $attachment['template'] ?? [];
        $employee = isset($attachment['employee']) && is_array($attachment['employee']) ? (object)$attachment['employee'] : null;
        $program = isset($attachment['program']) && is_array($attachment['program']) ? (object)$attachment['program'] : null;
        $evaluator = $evaluation ? (object)[
            'full_name' => $evaluation->evaluator_name ?? '',
            'position_title' => $evaluation->evaluator_title ?? '',
        ] : null;
        $hijriDate = null;

        // Build answers map by section_id.
        $answersMap = [];
        foreach (($attachment['answers'] ?? []) as $answerRow) {
            if (is_array($answerRow) && isset($answerRow['section_id'])) {
                $answersMap[$answerRow['section_id']] = (object)$answerRow;
            }
        }
        $answers = $answersMap;

        ob_start();
        require APP_ROOT . '/app/Views/evaluations/_document_styles.php';
        $styles = ob_get_clean();

        // mPDF: إزالة الاستيرادات الخارجية واستبدال المتغيرات CSS غير المدعومة، وتحسين التخطيط لمحرك mPDF.
        $styles = (string)preg_replace('/@import[^;]+;/', '', (string)$styles);
        $styles = str_replace('min-height:285mm', 'min-height:auto', $styles);
        $styles = preg_replace('/box-shadow:[^;]+;/', '', $styles);
        $styles = str_replace('overflow:hidden;', '', $styles);
        $brandFamily = (string)($brand['font']['family'] ?? 'cairo');
        $styles .= '<style>'
            . 'body{direction:rtl;font-family:' . $brandFamily . ';font-size:9.5pt;line-height:1.55;}'
            . '.a4-page{display:block;width:100%;min-height:0;margin:0 auto;background:#ffffff;overflow:visible;border:0;border-radius:0;box-shadow:none;position:relative;}'
            . '.a4-page{border-top:3px solid #c8943f;}'
            . '.doc-body{display:block;flex:0 0 auto;}'
            . '.doc-footer{margin-top:0;display:block;background:#f8f9fb;border-top:1px solid #e2ded4;padding:10px 22px;font-size:9pt;color:#6c757d;}'
            . '.doc-footer .left{display:block;}'
            . '.doc-header{display:block;padding:10mm 0 6mm;}'
            . '.header-emblem{display:block;float:left;width:12mm;height:12mm;border:1px solid #c8943f;border-radius:50%;background:#ffffff;text-align:center;}'
            . '.header-titles{display:block;text-align:center;padding:0 12px;}'
            . '.header-meta{display:block;float:right;min-width:155px;text-align:right;font-size:10.5pt;line-height:1.7;color:#334155;background:#f8f9fb;border:1px solid #e8eaed;border-radius:8px;padding:8px 10px;}'
            . '.section{display:block;margin-bottom:16px;}'
            . '.section-head{display:block;padding-bottom:7px;border-bottom:2px solid #0c2823;}'
            . '.section-head .line{display:none;}'
            . '.section-num{display:block;width:28px;height:28px;background:#0c2823;color:#ffffff;border-radius:50%;font-size:12pt;font-weight:800;text-align:center;line-height:28px;}'
            . '.criterion-card{display:block;background:#ffffff;border:1px solid #e2ded4;border-radius:0;overflow:visible;margin-bottom:12px;box-shadow:none;}'
            . '.criterion-head{display:block;background:#0c2823;color:#ffffff;padding:8mm 0;border-bottom:2px solid #c8943f;}'
            . '.criterion-head h3{display:block;float:left;font-size:13pt;font-weight:800;color:#ffffff;margin:0;}'
            . '.criterion-head .count{display:block;float:right;background:#ffffff;color:#0c2823;border:none;border-radius:20px;padding:3px 10px;font-size:11pt;font-weight:800;}'
            . '.eval-table{width:100%;border-collapse:collapse;font-size:11pt;}'
            . '.eval-table thead th{background:#f8f9fb;color:#0c2823;font-weight:700;font-size:11pt;padding:6pt 8pt;text-align:center;border:1px solid #e2ded4;}'
            . '.eval-table thead th:first-child{text-align:right;padding-right:12pt;}'
            . '.eval-table tbody td{padding:7pt 8pt;border:1px solid #e8eaed;vertical-align:middle;}'
            . '.eval-table tbody td:first-child{color:#1a1a1a;font-weight:500;font-size:11pt;}'
            . '.eval-table tbody td:nth-child(2){text-align:center;}'
            . '.eval-table tbody td:last-child{color:#6c757d;font-size:10pt;text-align:center;}'
            . '.badge{display:inline-block;}'
            . '.program-card-head{display:block;padding:8mm 0;}'
            . '.program-card-head h3{display:block;font-size:13pt;font-weight:800;color:#ffffff;}'
            . '.program-grid{display:table;width:100%;border-collapse:collapse;}'
            . '.program-cell{display:table-cell;vertical-align:top;padding:8mm 14mm;border-bottom:1px solid #f1f3f5;border-left:1px solid #f1f3f5;}'
            . '.program-cell label{display:block;font-size:9pt;font-weight:700;color:#6c757d;text-align:center;}'
            . '.program-cell span{display:block;font-size:12pt;font-weight:600;color:#1a1a1a;text-align:center;}'
            . '.evaluator-card{display:table;width:100%;background:#f8f9fb;border:1px solid #e2ded4;border-radius:10px;padding:16px;}'
            . '.eval-person{display:table-cell;vertical-align:top;text-align:center;padding:10mm 8mm;background:#ffffff;border:1px solid #e8eaed;border-radius:8px;}'
            . '.eval-person .lbl{display:block;font-size:10pt;font-weight:700;color:#6c757d;text-align:center;}'
            . '.eval-person .val{display:block;font-size:13pt;font-weight:700;color:#0c2823;text-align:center;}'
            . '.signature-area{display:table;width:100%;border-collapse:collapse;margin-top:18px;padding-top:14px;border-top:1px dashed #d6d9e0;}'
            . '.sig-box{display:table-cell;vertical-align:bottom;width:33.33%;text-align:center;padding:10mm 6mm;border:1px dashed #d6d9e0;border-radius:8px;background:#fafbfc;min-height:78px;}'
            . '.sig-line{width:100%;height:1px;background:#0c2823;margin:14px auto 6px;position:relative;}'
            . '.sig-label{font-size:10.5pt;font-weight:700;color:#0c2823;}'
            . '.sig-sub{font-size:10pt;color:#6c757d;min-height:1.2em;}'
            . '</style>';

        ob_start();
        require APP_ROOT . '/app/Views/evaluations/_document.php';
        $body = ob_get_clean();

        return $styles . $body;
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
