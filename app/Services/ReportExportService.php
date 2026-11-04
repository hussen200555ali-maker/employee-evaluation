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
            'margin_top' => 35,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('مسند تقييم — ' . $snapshot->report_number);
        $mpdf->SetHTMLFooter(
            '<div style="font-family:' . $brand['font']['family'] . ';font-size:8px;color:' . $brand['colors']['gray']
            . ';border-top:1px solid ' . $brand['colors']['accent'] . ';padding-top:3px;">'
            . '<table style="width:100%;border:0"><tr>'
            . '<td style="text-align:right;color:' . $brand['colors']['accent'] . ';border:0;">'
            . htmlspecialchars($brand['confidential'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</td><td style="text-align:left;border:0;">صفحة {PAGENO} من {nbpg}</td>'
            . '</tr></table></div>'
        );
        $mpdf->WriteHTML($this->buildHtml($snapshot));
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
        $labels = ['overall' => 'المتوسط العام', 'annual' => 'التقييم السنوي', 'periodic' => 'التقييم الدوري', 'course' => 'تقييمات الدورات', 'workshop' => 'تقييمات الورش'];
        $row++;
        foreach ($labels as $key => $label) {
            $this->setText($sheet, "A{$row}", $label);
            $sheet->setCellValue("B{$row}", $averages[$key]['average']);
            $this->setText($sheet, "C{$row}", $averages[$key]['grade_label'] ?? '');
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
                ['text', $record['evaluation_date'] ?? ''],
                ['text', $record['evaluation_type_label'] ?? ''],
                ['text', $record['template_name'] ?? ''],
                ['number', $this->getRecordScore($record)],
                ['text', $record['grade_label'] ?? ''],
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
        return $this->captureOutput(static function () use ($spreadsheet): void { (new Xlsx($spreadsheet))->save('php://output'); });
    }

    private function renderDocx(object $snapshot): string
    {
        $data = $snapshot->snapshot_data;
        $brand = $this->getBrand();
        $word = new PhpWord();
        $word->setDefaultFontName('Cairo');
        $word->setDefaultFontSize(11);
        $section = $word->addSection();
        $footer = $section->addFooter();
        $footer->addText(
            $brand['confidential'] . '  |  ' . $brand['footer_text'],
            ['name' => $brand['font']['family'], 'size' => 8, 'color' => ltrim($brand['colors']['gray'], '#')],
            ['alignment' => Jc::CENTER]
        );

        $employee = $data['employee'];
        $logoPath = $this->getLogoPath();
        if ($logoPath !== null) {
            $section->addImage($logoPath, ['alignment' => Jc::CENTER, 'height' => 70]);
        }
        $section->addText(
            $brand['company']['name'],
            ['name' => $brand['font']['family'], 'bold' => true, 'size' => 18, 'color' => ltrim($brand['colors']['primary'], '#')],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            $brand['company']['tagline'],
            ['name' => $brand['font']['family'], 'size' => 14, 'color' => ltrim($brand['colors']['accent'], '#')],
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(2);
        $section->addText(
            'مسند تقييم أداء الموظف',
            ['name' => $brand['font']['family'], 'bold' => true, 'size' => 24, 'color' => ltrim($brand['colors']['primary'], '#')],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            $employee['full_name'] ?? '',
            ['name' => $brand['font']['family'], 'bold' => true, 'size' => 20],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            'رقم المسند: ' . $snapshot->report_number,
            ['name' => $brand['font']['family'], 'size' => 12],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            $brand['confidential'],
            ['name' => $brand['font']['family'], 'bold' => true, 'size' => 10, 'color' => ltrim($brand['colors']['accent'], '#')],
            ['alignment' => Jc::CENTER]
        );
        $section->addPageBreak();

        // Document body
        $section->addText('تقرير تقييم الموظف', ['bold' => true, 'size' => 18], ['alignment' => Jc::END]);
        $section->addText('رقم التقرير: ' . $snapshot->report_number, [], ['alignment' => Jc::END]);
        $section->addText('تاريخ الإنشاء: ' . $snapshot->created_at, [], ['alignment' => Jc::END]);

        foreach ([
            'الاسم' => $employee['full_name'] ?? '',
            'الرقم الوظيفي' => $employee['emp_code'] ?? '',
            'البريد الإلكتروني' => $employee['email'] ?? '',
            'الهاتف' => $employee['phone'] ?? '',
            'القسم' => $employee['department_name'] ?? '',
            'الفرع' => $employee['branch_name'] ?? '',
            'المنصب' => $employee['position_title'] ?? '',
            'المدير المباشر' => $employee['manager_name'] ?? '',
            'تاريخ الالتحاق' => $employee['hire_date'] ?? '',
            'الحالة' => $employee['status'] ?? '',
        ] as $label => $value) {
            $section->addText($label . ': ' . $value, [], ['alignment' => Jc::END]);
        }

        // Averages table
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $table->addRow();
        foreach (['المؤشر', 'المتوسط', 'التقدير'] as $heading) { $table->addCell(2600)->addText($heading, ['bold' => true], ['alignment' => Jc::END]); }
        $labels = ['overall' => 'المتوسط العام', 'annual' => 'التقييم السنوي', 'periodic' => 'التقييم الدوري', 'course' => 'تقييمات الدورات', 'workshop' => 'تقييمات الورش'];
        foreach ($labels as $key => $label) {
            $table->addRow();
            $summary = $data['report']['averages'][$key];
            $table->addCell(2600)->addText($label, [], ['alignment' => Jc::END]);
            $table->addCell(1800)->addText($summary['average'] === null ? '—' : (string)$summary['average']);
            $table->addCell(2600)->addText($summary['grade_label'] ?? '', [], ['alignment' => Jc::END]);
        }

        // Signatures table
        $section->addTextBreak(2);
        $section->addText('التوقيعات', ['bold' => true, 'size' => 14], ['alignment' => Jc::END]);
        $sigTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $sigTable->addRow();
        foreach (['الموظف', 'المدير المباشر', 'مدير الموارد البشرية'] as $role) { $sigTable->addCell(3000)->addText($role, ['bold' => true], ['alignment' => Jc::CENTER]); }
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $sigTable->addRow();
            for ($i = 0; $i < 3; $i++) { $sigTable->addCell(3000)->addText($line, [], ['alignment' => Jc::END]); }
        }

        return $this->captureOutput(static function () use ($word): void { IOFactory::createWriter($word, 'Word2007')->save('php://output'); });
    }

    protected function buildHtml(object $snapshot): string
    {
        $data = $snapshot->snapshot_data;
        $escape = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $brand = $this->getBrand();
        $employee = $data['employee'] ?? [];
        $report = $data['report'] ?? [];
        $averages = $report['averages'] ?? [];
        $records = $report['records'] ?? [];
        $colors = $brand['colors'];
        $html = '<!doctype html><html lang="ar" dir="rtl"><meta charset="UTF-8"><style>'
            . '*{font-family:' . $brand['font']['family'] . ';margin:0;padding:0;box-sizing:border-box}'
            . 'body{direction:rtl;color:#333;font-size:11px;line-height:1.6}'
            . '.cover{background:' . $colors['primary'] . ';color:' . $colors['accent']
            . ';padding:38px 30px;text-align:center;height:225mm;page-break-after:always}'
            . '.cover .logo{margin:0 auto 20px}.cover .logo img{max-height:58px;max-width:180px}'
            . '.cover h1{color:' . $colors['accent'] . ';font-size:26px;margin:20px 0 8px}'
            . '.cover .tagline{font-size:14px}.cover .emp-name{color:' . $colors['white']
            . ';font-size:22px;margin:34px 0 8px}.cover .emp-meta{color:' . $colors['white'] . ';font-size:12px}'
            . '.cover .report-number{border-top:1px solid ' . $colors['accent'] . ';margin:28px auto 0;padding-top:12px;width:70%}'
            . '.cover .confidential{border:1px solid ' . $colors['accent'] . ';color:' . $colors['accent']
            . ';padding:8px 20px;display:inline-block;margin-top:35px;font-size:10px}'
            . '.section-title{background:' . $colors['primary'] . ';color:' . $colors['accent']
            . ';padding:6px 12px;font-weight:bold;font-size:13px;margin:20px 0 8px}'
            . 'table{width:100%;border-collapse:collapse;margin:8px 0 14px}'
            . 'table.data th{background:' . $colors['primary'] . ';color:' . $colors['accent']
            . ';padding:6px;text-align:right;border:1px solid ' . $colors['primary'] . '}'
            . 'table.data td{border:1px solid ' . $colors['gray_light'] . ';padding:6px;text-align:right}'
            . 'table.data tr:nth-child(even){background:' . $colors['light'] . '}'
            . '.employee-grid td{width:50%;border:1px solid ' . $colors['gray_light'] . ';padding:7px}'
            . '.field-label{color:' . $colors['gray'] . ';font-size:9px}.avg-cards td{width:20%;text-align:center;padding:9px 4px;border:1px solid ' . $colors['gray_light'] . '}'
            . '.avg-card-title{color:' . $colors['gray'] . ';font-size:9px}.avg-card-value{font-size:15px;font-weight:bold;color:' . $colors['primary'] . '}'
            . '.avg-card.highlight{background:' . $colors['primary'] . ';color:' . $colors['white'] . ';border-color:' . $colors['accent'] . '}'
            . '.avg-card.highlight .avg-card-value,.avg-card.highlight .avg-card-title{color:' . $colors['accent'] . '}'
            . '.sig-table th{background:' . $colors['light'] . ';color:' . $colors['primary'] . ';border:1px solid ' . $colors['accent'] . ';padding:8px}'
            . '.sig-table td{height:38px;text-align:center;border:1px solid ' . $colors['gray_light'] . ';padding:6px}'
            . '.empty-state{text-align:center;color:' . $colors['gray'] . ';padding:16px}'
            . '.page-reference{display:none}</style><body>';

        $logoPath = $this->getLogoPath();
        $html .= '<div class="cover"><div class="logo">';
        $html .= $logoPath === null
            ? '<span>' . $escape($brand['company']['name']) . '</span>'
            : '<img src="' . $escape($logoPath) . '" alt="' . $escape($brand['company']['name']) . '">';
        $html .= '</div><div class="tagline">' . $escape($brand['company']['name']) . '</div>'
            . '<h1>' . $escape($brand['company']['tagline']) . '</h1>'
            . '<div class="emp-name">' . $escape($employee['full_name'] ?? '') . '</div>'
            . '<div class="emp-meta">' . $escape($employee['emp_code'] ?? '')
            . ' &nbsp;|&nbsp; ' . $escape($employee['department_name'] ?? '') . '</div>'
            . '<div class="report-number">رقم المسند: ' . $escape($snapshot->report_number)
            . '<br>تاريخ الإصدار: ' . $escape($snapshot->created_at) . '</div>'
            . '<div class="confidential">' . $escape($brand['confidential']) . '</div></div>';

        $html .= '<h2 class="section-title">بيانات الموظف</h2><table class="employee-grid"><tbody>';
        $employeeFields = [
            'الاسم' => $employee['full_name'] ?? '',
            'الرقم الوظيفي' => $employee['emp_code'] ?? '',
            'القسم' => $employee['department_name'] ?? '',
            'الفرع' => $employee['branch_name'] ?? '',
            'المسمى الوظيفي' => $employee['position_title'] ?? '',
            'المدير المباشر' => $employee['manager_name'] ?? '',
            'البريد الإلكتروني' => $employee['email'] ?? '',
            'الهاتف' => $employee['phone'] ?? '',
            'تاريخ الالتحاق' => $employee['hire_date'] ?? '',
            'الحالة' => $employee['status'] ?? '',
        ];
        foreach (array_chunk($employeeFields, 2, true) as $rowFields) {
            $html .= '<tr>';
            foreach ($rowFields as $label => $value) {
                $html .= '<td><span class="field-label">' . $escape($label) . '</span><br>' . $escape($value) . '</td>';
            }
            if (count($rowFields) === 1) {
                $html .= '<td></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $labels = [
            'overall' => 'المتوسط العام',
            'annual' => 'التقييم السنوي',
            'periodic' => 'التقييم الدوري',
            'course' => 'تقييمات الدورات',
            'workshop' => 'تقييمات الورش',
        ];
        $html .= '<h2 class="section-title">ملخص الأداء</h2><table class="avg-cards"><tbody><tr>';
        foreach ($labels as $key => $label) {
            $summary = $averages[$key] ?? [];
            $average = $summary['average'] ?? null;
            $highlight = $key === 'overall' ? ' highlight' : '';
            $html .= '<td class="avg-card' . $highlight . '"><div class="avg-card-title">' . $escape($label)
                . '</div><div class="avg-card-value">' . ($average === null ? '—' : $escape(number_format((float)$average, 2) . ' / 100'))
                . '</div><div>' . $escape($summary['grade_label'] ?? '') . '</div></td>';
        }
        $html .= '</tr></tbody></table>';

        $html .= '<h2 class="section-title">سجل التقييمات</h2><table class="data"><thead><tr>'
            . '<th>التاريخ</th><th>النوع</th><th>القالب</th><th>الدرجة / 100</th><th>التقدير</th>'
            . '</tr></thead><tbody>';
        if ($records === []) {
            $html .= '<tr><td class="empty-state" colspan="5">لا توجد تقييمات مسجلة.</td></tr>';
        }
        foreach ($records as $record) {
            $score = $this->getRecordScore($record);
            $scoreText = $score === null ? '—' : number_format((float)$score, 2);
            $html .= '<tr><td>' . $escape($record['evaluation_date'] ?? '')
                . '</td><td>' . $escape($record['evaluation_type_label'] ?? '')
                . '</td><td>' . $escape($record['template_name'] ?? '')
                . '</td><td>' . $escape($scoreText)
                . '</td><td>' . $escape($record['grade_label'] ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2 class="section-title">التوقيعات</h2><table class="sig-table"><thead><tr>'
            . '<th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th></tr></thead><tbody>';
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $html .= '<tr><td>' . $line . '</td><td>' . $line . '</td><td>' . $line . '</td></tr>';
        }
        return $html . '</tbody></table><div class="page-reference">صفحة {PAGENO} من {nbpg}</div>'
            . '<p style="text-align:center;color:' . $escape($colors['gray']) . ';font-size:8px;margin-top:16px;">'
            . $escape($brand['footer_text']) . '</p></body></html>';
    }

    private function getBrand(): array
    {
        return require dirname(__DIR__) . '/Views/reports/styles/brand.php';
    }

    private function getRecordScore(array $record): mixed
    {
        return array_key_exists('final_score', $record)
            ? $record['final_score']
            : ($record['grade_value'] ?? null);
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
