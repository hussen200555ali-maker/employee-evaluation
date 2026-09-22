<?php
// ملف: app/Libraries/ReportPDF.php

namespace App\Libraries;

// تأكد من أن Composer Autoloader قد تم تضمينه في نقطة بداية تطبيقك
// عادة في public/index.php

class ReportPDF extends \TCPDF {

    private $reportTitle = 'تقرير الأداء';
    private $companyName = 'اسم الشركة';

    // يمكنك تمرير البيانات إلى الكلاس عند إنشائه
    public function setReportTitle($title) {
        $this->reportTitle = $title;
    }

    public function setCompanyName($name) {
        $this->companyName = $name;
    }

    // --- هذه هي الدالة التي تنشئ الترويسة المخصصة لكل صفحة ---
    public function Header() {
        // تحديد الخط
        $this->SetFont('aealarabiya', 'B', 16);
        
        // تفعيل وضع RTL للترويسة
        $this->setRTL(true);

        // --- بناء الترويسة ---
        // 1. العنوان الرئيسي
        $this->Cell(0, 10, 'بسم الله الرحمن الرحيم', 0, 1, 'C');
        $this->SetFont('aealarabiya', 'B', 14);
        $this->Cell(0, 10, $this->reportTitle, 0, 1, 'C');
        
        // 2. خط فاصل تحت العنوان
        $this->Line(15, $this->GetY() + 2, $this->getPageWidth() - 15, $this->GetY() + 2);
        
        // إضافة مسافة بعد الترويسة
        $this->Ln(10);
    }

    // --- هذه هي الدالة التي تنشئ التذييل المخصص لكل صفحة ---
    public function Footer() {
        // تحديد موضع التذييل (15mm من الأسفل)
        $this->SetY(-15);
        
        // تحديد الخط
        $this->SetFont('aealarabiya', 'I', 8);
        
        // تفعيل وضع RTL للتذييل
        $this->setRTL(true);
        
        // بناء نص التذييل
        $footerText = $this->companyName . ' © ' . date('Y');
        $pageNumberText = 'صفحة ' . $this->getAliasNumPage() . ' من ' . $this->getAliasNbPages();
        
        // رسم خط فاصل فوق التذييل
        $this->Line(15, $this->GetY() - 2, $this->getPageWidth() - 15, $this->GetY() - 2);

        // كتابة اسم الشركة على اليمين ورقم الصفحة على اليسار
        $this->Cell($this->getPageWidth() / 3, 10, $footerText, 0, 0, 'R');
        $this->Cell($this->getPageWidth() / 3, 10, '', 0, 0, 'C'); // خلية فارغة في المنتصف
        $this->Cell($this->getPageWidth() / 3 - 30, 10, $pageNumberText, 0, 0, 'L');
    }
}
