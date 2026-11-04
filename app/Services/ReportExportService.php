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
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans', 'tempDir' => sys_get_temp_dir()]);
        $mpdf->SetDirectionality('rtl');
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
        foreach (['التاريخ', 'النوع', 'القالب', 'الدرجة', 'التقدير'] as $column => $heading) {
            $sheet->setCellValue([$column + 1, $row], $heading);
        }
        $row++;
        foreach ($report['records'] as $record) {
            $values = [['text', $record['evaluation_date'] ?? ''], ['text', $record['evaluation_type_label'] ?? ''], ['text', $record['template_name'] ?? ''], ['number', $record['grade_value']], ['text', $record['grade_label'] ?? '']];
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
        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(11);
        $section = $word->addSection();

        // Logo
        $logoPath = $this->getLogoPath();
        if ($logoPath !== null) {
            $section->addImage($logoPath, ['alignment' => Jc::CENTER, 'height' => 60]);
        } else {
            $section->addText('شعار الشركة', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        }

        $section->addText('تقرير تقييم الموظف', ['bold' => true, 'size' => 18], ['alignment' => Jc::END]);
        $section->addText('رقم التقرير: ' . $snapshot->report_number, [], ['alignment' => Jc::END]);
        $section->addText('تاريخ الإنشاء: ' . $snapshot->created_at, [], ['alignment' => Jc::END]);

        $employee = $data['employee'];
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
        $employee = $data['employee'];
        $html = '<html lang="ar"><meta charset="UTF-8"><style>body{font-family:dejavusans;direction:rtl}table{width:100%;border-collapse:collapse;margin:12px 0}th,td{border:1px solid #888;padding:6px;text-align:right}h1{font-size:20px}.logo{text-align:center;margin-bottom:12px}.sig-table td{height:40px;text-align:center}</style><body>';

        // Logo
        $logoPath = $this->getLogoPath();
        if ($logoPath !== null) {
            $html .= '<div class="logo"><img src="' . $escape($logoPath) . '" height="80"></div>';
        } else {
            $html .= '<div class="logo" style="font-size:16px;color:#888;">شعار الشركة</div>';
        }

        $html .= '<h1>تقرير تقييم الموظف</h1><p>الاسم: ' . $escape($employee['full_name'] ?? '') . '</p>';
        $html .= '<p>رقم التقرير: ' . $escape($snapshot->report_number) . ' | تاريخ الإنشاء: ' . $escape($snapshot->created_at) . '</p>';
        $html .= '<p>الرقم الوظيفي: ' . $escape($employee['emp_code'] ?? '') . ' | القسم: ' . $escape($employee['department_name'] ?? '') . ' | الفرع: ' . $escape($employee['branch_name'] ?? '') . '</p>';
        $html .= '<p>البريد الإلكتروني: ' . $escape($employee['email'] ?? '') . ' | الهاتف: ' . $escape($employee['phone'] ?? '') . '</p>';
        $html .= '<p>المنصب: ' . $escape($employee['position_title'] ?? '') . ' | المدير المباشر: ' . $escape($employee['manager_name'] ?? '') . '</p>';
        $html .= '<p>تاريخ الالتحاق: ' . $escape($employee['hire_date'] ?? '') . ' | الحالة: ' . $escape($employee['status'] ?? '') . '</p>';
        $html .= '<h2>المتوسطات</h2><table><thead><tr><th>المؤشر</th><th>المتوسط</th><th>التقدير</th></tr></thead><tbody>';
        $labels = ['overall' => 'المتوسط العام', 'annual' => 'التقييم السنوي', 'periodic' => 'التقييم الدوري', 'course' => 'تقييمات الدورات', 'workshop' => 'تقييمات الورش'];
        foreach ($labels as $key => $label) {
            $summary = $data['report']['averages'][$key];
            $html .= '<tr><td>' . $label . '</td><td>' . $escape($summary['average'] ?? '—') . '</td><td>' . $escape($summary['grade_label'] ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table><h2>التقييمات</h2><table><thead><tr><th>التاريخ</th><th>النوع</th><th>القالب</th><th>الدرجة</th><th>التقدير</th></tr></thead><tbody>';
        foreach ($data['report']['records'] as $record) {
            $html .= '<tr><td>' . $escape($record['evaluation_date'] ?? '') . '</td><td>' . $escape($record['evaluation_type_label'] ?? '') . '</td><td>' . $escape($record['template_name'] ?? '') . '</td><td>' . $escape($record['grade_value'] ?? '—') . '</td><td>' . $escape($record['grade_label'] ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        // Signatures
        $html .= '<h2>التوقيعات</h2><table class="sig-table"><thead><tr><th>الموظف</th><th>المدير المباشر</th><th>مدير الموارد البشرية</th></tr></thead><tbody>';
        foreach (['الاسم: ___________', 'التوقيع: ___________', 'التاريخ: ____/____/____'] as $line) {
            $html .= '<tr><td>' . $line . '</td><td>' . $line . '</td><td>' . $line . '</td></tr>';
        }
        return $html . '</tbody></table></body></html>';
    }

    private function getLogoPath(): ?string
    {
        $path = dirname(__DIR__, 2) . '/public/assets/images/logo.png';
        return file_exists($path) ? $path : null;
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
