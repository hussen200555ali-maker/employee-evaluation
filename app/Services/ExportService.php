<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    private static array $headerStyle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    private static array $cellStyle = [
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    /**
     * Export evaluations data to XLSX
     */
    public static function exportEvaluations(array $evaluations, string $title = 'تقرير التقييمات'): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle(mb_substr($title, 0, 31));

        $headers = ['م', 'النوع', 'الكيان', 'الكود', 'القسم', 'الفرع', 'القالب', 'تاريخ التقييم', 'الدرجة النهائية', 'الحالة'];

        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray(self::$headerStyle);
        }
        $sheet->getRowDimension('1')->setRowHeight(30);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(12);

        $typeLabels = ['employee'=>'موظف','course'=>'دورة','workshop'=>'ورشة','branch'=>'فرع','department'=>'قسم','employee_in_course'=>'موظف في دورة','employee_in_workshop'=>'موظف في ورشة','admin'=>'إداري'];
        $row = 2;
        foreach ($evaluations as $i => $ev) {
            $score = $ev->final_score ?? $ev->average_score ?? 0;
            $statusCode = $ev->status ?? '';
            $statusLabel = match($statusCode) { 'completed'=>'مكتمل','draft'=>'مسودة','approved'=>'معتمد','pending_review'=>'قيد المراجعة','expired'=>'منتهي', default=>$statusCode };
            $entityType = $ev->entity_type ?? 'employee';
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $typeLabels[$entityType] ?? $entityType);
            $sheet->setCellValue('C' . $row, $ev->employee_name ?? $ev->full_name ?? '-');
            $sheet->setCellValue('D' . $row, $ev->employee_code ?? $ev->emp_code ?? '-');
            $sheet->setCellValue('E' . $row, $ev->department_name ?? '-');
            $sheet->setCellValue('F' . $row, $ev->branch_name ?? '-');
            $sheet->setCellValue('G' . $row, $ev->template_name ?? '-');
            $sheet->setCellValue('H' . $row, $ev->evaluation_date ?? '-');
            $sheet->setCellValue('I' . $row, $score);
            $sheet->setCellValue('J' . $row, $statusLabel);
            foreach (['A','B','C','D','E','F','G','H','I','J'] as $c) {
                $sheet->getStyle($c . $row)->applyFromArray(self::$cellStyle);
            }
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $sheet->setCellValue('A' . ($row + 1), 'تاريخ التقرير: ' . date('Y-m-d H:i'));
        $sheet->mergeCells('A' . ($row + 1) . ':J' . ($row + 1));

        self::outputXlsx($spreadsheet, $title . '_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * Export employees data to XLSX
     */
    public static function exportEmployees(array $employees, string $title = 'قائمة الموظفين'): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle(mb_substr($title, 0, 31));

        $headers = ['م', 'الاسم', 'الكود', 'البريد الإلكتروني', 'الهاتف', 'القسم', 'الفرع', 'المنصب', 'الحالة'];

        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->applyFromArray(self::$headerStyle);
        }
        $sheet->getRowDimension('1')->setRowHeight(30);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(10);

        $row = 2;
        foreach ($employees as $i => $emp) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $emp->full_name ?? '-');
            $sheet->setCellValue('C' . $row, $emp->emp_code ?? '-');
            $sheet->setCellValue('D' . $row, $emp->personal_email ?? '-');
            $sheet->setCellValue('E' . $row, $emp->phone ?? '-');
            $sheet->setCellValue('F' . $row, $emp->department_name ?? '-');
            $sheet->setCellValue('G' . $row, $emp->branch_name ?? '-');
            $sheet->setCellValue('H' . $row, $emp->position_title ?? '-');
            $sheet->setCellValue('I' . $row, ($emp->is_active ?? 1) ? 'نشط' : 'غير نشط');
            foreach (['A','B','C','D','E','F','G','H','I'] as $c) {
                $sheet->getStyle($c . $row)->applyFromArray(self::$cellStyle);
            }
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $sheet->setCellValue('A' . ($row + 1), 'تاريخ التقرير: ' . date('Y-m-d H:i'));
        $sheet->mergeCells('A' . ($row + 1) . ':I' . ($row + 1));

        self::outputXlsx($spreadsheet, $title . '_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * Export to CSV with UTF-8 BOM for Arabic support
     */
    public static function exportCsv(array $data, array $headers, string $filename = 'export.csv'): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($data as $row) {
            fputcsv($output, (array) $row);
        }
        fclose($output);
        exit;
    }

    private static function outputXlsx(Spreadsheet $spreadsheet, string $filename): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
