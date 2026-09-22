<?php
namespace App\Core;

use App\Core\Auth;

// ... (فئة CustomPdf تبقى كما هي تمامًا) ...
class CustomPdf extends \TCPDF {
    public $reportData;

    public function Header() {
        if (empty($this->reportData['entityInfo'])) return;
        $entityInfo = $this->reportData['entityInfo'];
        $this->SetFont('cairo', 'B', 16);
        $this->Cell(0, 10, 'بسم الله الرحمن الرحيم', 0, 1, 'C');
        $this->Ln(5);
        $this->SetFont('cairo', '', 10);
        $logoPath = 'public/assets/images/logo.png';
        $headerTbl = '...'; // هذا الجزء يعمل بشكل جيد، لذا سنبقيه كما هو
        $this->writeHTML($headerTbl, true, false, true, false, '');
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->Ln(5);
    }

    public function Footer() {
        // ... (هذا الجزء يعمل بشكل جيد، لذا سنبقيه كما هو) ...
    }

    private function getArabicDay($dayIndex) {
        $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        return $days[$dayIndex];
    }
}


class PdfReportBuilder {

    private $pdf;
    private $entityInfo;
    private $analysis;

    public function generateGroupAnalysisReport($reportData) {
        // ... (جزء تهيئة الخطوط والـ PDF يبقى كما هو) ...
        $fontPathRegular = APP_ROOT . '/public/fonts/Cairo-Regular.ttf';
        $fontPathBold = APP_ROOT . '/public/fonts/Cairo-Bold.ttf';
        \TCPDF_FONTS::addTTFfont($fontPathRegular, 'TrueTypeUnicode', '', 32);
        \TCPDF_FONTS::addTTFfont($fontPathBold, 'TrueTypeUnicode', '', 32);

        $this->pdf = new CustomPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->pdf->reportData = $reportData;
        $this->entityInfo = $reportData['entityInfo'];
        $this->analysis = $reportData['employee_analysis'];

        $this->pdf->SetCreator('نظام تقييم الأداء');
        $this->pdf->SetAuthor(Auth::user()->username ?? 'System');
        $this->pdf->SetTitle($this->entityInfo['reportTitle']);
        $this->pdf->SetMargins(15, 45, 15);
        $this->pdf->SetAutoPageBreak(TRUE, 20);
        $this->pdf->setFontSubsetting(true);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->setRTL(true);

        $this->pdf->AddPage();
        $this->buildExecutiveSummary();
        $this->buildCompetencyTables();
        $this->buildEmployeeAnalysisTable();

        $filename = 'report_' . date('Ymd_His') . '.pdf';
        $this->pdf->Output($filename, 'I');
        exit;
    }

    // ▼▼▼ الدالة المحدثة بالكامل ▼▼▼
    private function buildExecutiveSummary() {
        $stats = $this->analysis['stats'];
        $this->pdf->SetFont('cairo', 'B', 12);
        $this->pdf->Cell(0, 10, '1. الملخص التنفيذي', 0, 1, 'R');
        $this->pdf->SetDrawColor(150, 150, 150);
        $this->pdf->SetLineWidth(0.1);

        $colWidth1 = 45;
        $colWidth2 = 45;
        $rowHeight = 8;

        // الصف الأول
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->SetFillColor(242, 242, 242);
        $this->pdf->Cell($colWidth1, $rowHeight, 'الكيان المحلل', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->Cell($colWidth2, $rowHeight, $this->entityInfo['name'] . ' (' . $this->entityInfo['type'] . ')', 1, 0, 'R', 0);
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->Cell($colWidth1, $rowHeight, 'متوسط الأداء العام', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', 'B', 12);
        $this->pdf->Cell($colWidth2, $rowHeight, ($stats['average_performance'] ?? 0) . '%', 1, 1, 'C', 0);

        // الصف الثاني
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->Cell($colWidth1, $rowHeight, 'المدير المسؤول', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->Cell($colWidth2, $rowHeight, $this->entityInfo['manager'], 1, 0, 'R', 0);
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->Cell($colWidth1, $rowHeight, 'الكفاءات القوية', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->Cell($colWidth2, $rowHeight, ($stats['strong_competencies_count'] ?? 0), 1, 1, 'C', 0);

        // الصف الثالث
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->Cell($colWidth1, $rowHeight, 'عدد الموظفين', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->Cell($colWidth2, $rowHeight, ($this->entityInfo['employee_count'] ?? 0), 1, 0, 'C', 0);
        $this->pdf->SetFont('cairo', 'B', 10);
        $this->pdf->Cell($colWidth1, $rowHeight, 'كفاءات للتطوير', 1, 0, 'R', 1);
        $this->pdf->SetFont('cairo', '', 10);
        $this->pdf->Cell($colWidth2, $rowHeight, ($stats['weak_competencies_count'] ?? 0), 1, 1, 'C', 0);

        $this->pdf->Ln(8);
    }

    // ▼▼▼ الدالة المحدثة بالكامل ▼▼▼
    private function buildCompetencyTables() {
        $performance = $this->analysis['classified_performance'];
        $this->pdf->SetFont('cairo', 'B', 12);
        $this->pdf->Cell(0, 10, '2. تحليل الكفاءات التفصيلي', 0, 1, 'R');

        $configs = [
            'strong' => ['title' => 'نقاط القوة الجماعية (≥ 85%)', 'color' => [209, 231, 221]],
            'medium' => ['title' => 'الأداء المتوسط (50% - 84.9%)', 'color' => [204, 229, 255]],
            'weak' => ['title' => 'جوانب التحسين الجماعية (< 50%)', 'color' => [248, 215, 218]]
        ];

        foreach ($configs as $key => $config) {
            if (!empty($performance[$key])) {
                $this->pdf->SetFont('cairo', 'B', 11);
                $this->pdf->Cell(0, 8, $config['title'], 0, 1, 'R');
                
                // Header
                $this->pdf->SetFillColor($config['color'][0], $config['color'][1], $config['color'][2]);
                $this->pdf->SetFont('cairo', 'B', 10);
                $this->pdf->Cell(80, 7, 'الكفاءة (المعيار)', 1, 0, 'R', 1);
                $this->pdf->Cell(40, 7, 'متوسط الأداء', 1, 0, 'C', 1);
                $this->pdf->Cell(60, 7, 'توزيع الموظفين (ضعيف/متوسط/قوي)', 1, 1, 'C', 1);

                // Body
                $this->pdf->SetFont('cairo', '', 9);
                foreach ($performance[$key] as $item) {
                    $this->pdf->MultiCell(80, 10, $item['section_name'] . "\n(" . $item['criterion_name'] . ")", 1, 'R', 0, 0);
                    $this->pdf->SetFont('cairo', 'B', 11);
                    $this->pdf->Cell(40, 10, $item['average_score'] . '%', 1, 0, 'C', 0);
                    $this->pdf->SetFont('cairo', '', 10);
                    $this->pdf->Cell(60, 10, $item['weak_count'] . ' / ' . $item['medium_count'] . ' / ' . $item['strong_count'], 1, 1, 'C', 0);
                }
                $this->pdf->Ln(5);
            }
        }
    }

    // ▼▼▼ الدالة المحدثة بالكامل ▼▼▼
    private function buildEmployeeAnalysisTable() {
        // ... (منطق تجميع وتحليل الموظفين يبقى كما هو) ...
        $all_employees = [];
        // ...
        $employeeAnalysis = [];
        // ...

        if (empty($employeeAnalysis)) return;

        $this->pdf->AddPage();
        $this->pdf->SetFont('cairo', 'B', 12);
        $this->pdf->Cell(0, 10, '3. التحليل الفردي للموظفين', 0, 1, 'R');

        // Header
        $this->pdf->SetFillColor(233, 236, 239);
        $this->pdf->SetFont('cairo', 'B', 9);
        $this->pdf->Cell(10, 12, 'م', 1, 0, 'C', 1);
        $this->pdf->Cell(40, 12, 'اسم الموظف (المنصب)', 1, 0, 'R', 1);
        $this->pdf->Cell(20, 12, 'متوسط الأداء', 1, 0, 'C', 1);
        $this->pdf->Cell(20, 12, 'فجوة الأداء', 1, 0, 'C', 1);
        $this->pdf->Cell(45, 12, '▲ أقوى أداء (الدرجة)', 1, 0, 'R', 1);
        $this->pdf->Cell(45, 12, '▼ الأضعف أداءً (الدرجة)', 1, 1, 'R', 1);

        // Body
        $this->pdf->SetFont('cairo', '', 8);
        $counter = 1;
        foreach ($employeeAnalysis as $emp) {
            $topComp = $emp['top_competencies'][0] ?? null;
            $bottomComp = $emp['bottom_competencies'][0] ?? null;
            
            $rowHeight = 14; // ارتفاع كاف لسطرين
            $this->pdf->Cell(10, $rowHeight, $counter++, 1, 0, 'C');
            $this->pdf->MultiCell(40, $rowHeight, "<b>" . $emp['name'] . "</b>\n" . $emp['position'], 1, 'R', 0, 0, '', '', true, 0, true);
            $this->pdf->SetFont('cairo', 'B', 10);
            $this->pdf->Cell(20, $rowHeight, $emp['average'] . '%', 1, 0, 'C');
            $this->pdf->SetFont('cairo', '', 9);
            $this->pdf->Cell(20, $rowHeight, $emp['gap'] . ' نقطة', 1, 0, 'C');
            $this->pdf->SetFont('cairo', '', 8);
            $topText = $topComp ? $topComp['section_name'] . ' (' . $topComp['score'] . "%)\n<small>من: " . $topComp['criterion_name'] . '</small>' : 'N/A';
            $bottomText = $bottomComp ? $bottomComp['section_name'] . ' (' . $bottomComp['score'] . "%)\n<small>من: " . $bottomComp['criterion_name'] . '</small>' : 'N/A';
            $this->pdf->MultiCell(45, $rowHeight, $topText, 1, 'R', 0, 0, '', '', true, 0, true);
            $this->pdf->MultiCell(45, $rowHeight, $bottomText, 1, 'R', 0, 1, '', '', true, 0, true);
        }
    }
}
