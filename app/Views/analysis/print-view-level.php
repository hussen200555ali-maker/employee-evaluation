<?php
// بداية الجلسة إذا لم تكن بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// بيانات افتراضية للسلامة
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');
$currentDay = date('l');

// تحويل اليوم العربي
$daysMap = [
    'Monday' => 'الإثنين',
    'Tuesday' => 'الثلاثاء', 
    'Wednesday' => 'الأربعاء',
    'Thursday' => 'الخميس',
    'Friday' => 'الجمعة',
    'Saturday' => 'السبت',
    'Sunday' => 'الأحد'
];
$currentDayArabic = $daysMap[$currentDay] ?? $currentDay;

// دالة تحويل التاريخ الهجري البسيطة
function toHijri($gregorianDate) {
    $timestamp = strtotime($gregorianDate);
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    
    // تحويل بسيط (هذا مثال - يمكن استبداله بمكتبة متخصصة)
    $hijriYear = $year - 622;
    $hijriMonth = $month;
    $hijriDay = $day;
    
    return $hijriYear . '-' . $hijriMonth . '-' . $hijriDay;
}

// دالة لاستخراج جميع الموظفين من البيانات الهرمية
function getAllEmployeesFromHierarchy($hierarchicalData) {
    $employees = [];
    
    if (isset($hierarchicalData['entities'])) {
        foreach ($hierarchicalData['entities'] as $entity) {
            if (isset($entity['type']) && $entity['type'] === 'employee') {
                $employees[] = $entity;
            } elseif (isset($entity['entities'])) {
                $employees = array_merge($employees, getAllEmployeesFromHierarchy($entity));
            }
        }
    }
    
    return $employees;
}

$currentHijri = toHijri($currentDate);
$userName = $_SESSION['user_name'] ?? 'مستخدم النظام';

// بيانات افتراضية للسلامة
$entityInfo = $entityInfo ?? [
    'type' => 'غير محدد',
    'name' => 'غير معروف', 
    'manager' => 'غير محدد',
    'employee_count' => 0,
    'icon' => 'fa-question'
];

$analysis = $analysis ?? [
    'stats' => [
        'average_performance' => 0,
        'strong_competencies_count' => 0,
        'weak_competencies_count' => 0
    ],
    'classified_performance' => [
        'strong' => [],
        'medium' => [],
        'weak' => []
    ]
];

$hierarchicalData = $hierarchicalData ?? ['entities' => []];
$filters = $filters ?? [];
$level = $filters['level'] ?? 'company';

// تحديد نوع التقرير
$reportTypes = [
    'department' => 'تقرير أداء القسم',
    'branch' => 'تقرير أداء الفرع', 
    'company' => 'تقرير أداء الشركة'
];
$reportType = $reportTypes[$level] ?? 'تقرير الأداء';

// الحصول على جميع الموظفين من البيانات الهرمية
$allEmployees = getAllEmployeesFromHierarchy($hierarchicalData);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $reportType . ' - ' . $entityInfo['name']; ?></title>
    <style>
        /* أنماط الطباعة */
        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }
            
            body {
                font-family: 'Arial', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #000;
                background: #fff;
                font-size: 12pt;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .page-break-before {
                page-break-before: always;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
                font-size: 10pt;
            }
            
            th, td {
                border: 1px solid #333;
                padding: 6px 8px;
                text-align: center;
            }
            
            th {
                background-color: #f5f5f5;
                font-weight: bold;
                color: #000;
            }
            
            .header, .footer {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .header table, .footer table {
                border: none;
            }
            
            .header td, .footer td {
                border: none;
                padding: 5px;
                vertical-align: top;
            }
            
            .company-name {
                font-size: 16pt;
                font-weight: bold;
                color: #2c3e50;
            }
            
            .bismillah {
                font-size: 14pt;
                font-weight: bold;
                color: #27ae60;
                text-align: center;
            }
            
            .report-title {
                text-align: center;
                margin: 20px 0;
                padding: 10px;
                border: 2px solid #3498db;
                border-radius: 8px;
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
            }
            
            .report-title h1 {
                margin: 0;
                font-size: 18pt;
            }
            
            .entity-summary {
                background: #ecf0f1;
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
                border-right: 4px solid #3498db;
            }
            
            .summary-stats {
                display: flex;
                justify-content: space-around;
                text-align: center;
                margin: 15px 0;
            }
            
            .stat-item {
                flex: 1;
                padding: 10px;
            }
            
            .stat-value {
                font-size: 20pt;
                font-weight: bold;
                color: #2c3e50;
            }
            
            .stat-label {
                font-size: 10pt;
                color: #7f8c8d;
            }
            
            .table-title {
                background: #34495e;
                color: white;
                padding: 8px 12px;
                margin: 20px 0 10px 0;
                border-radius: 4px;
                font-weight: bold;
            }
            
            .template-header {
                background: #95a5a6 !important;
                color: white !important;
                font-weight: bold;
                font-size: 11pt;
            }
            
            .criterion-row {
                background: #f8f9fa !important;
                font-weight: bold;
            }
            
            .section-row {
                background: #ffffff !important;
            }
            
            .strong-col { background: rgba(39, 174, 96, 0.1); }
            .medium-col { background: rgba(243, 156, 18, 0.1); }
            .weak-col { background: rgba(231, 76, 60, 0.1); }
            
            .performance-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 9pt;
            }
            
            .badge-success { background: #27ae60; color: white; }
            .badge-warning { background: #f39c12; color: white; }
            .badge-danger { background: #e74c3c; color: white; }
            .badge-info { background: #3498db; color: white; }
            
            .employee-section {
                margin: 20px 0;
                padding: 15px;
                border: 1px solid #bdc3c7;
                border-radius: 8px;
                background: #f8f9fa;
            }
            
            .employee-header {
                background: #2c3e50;
                color: white;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 10px;
            }
            
            .footer {
                margin-top: 30px;
                padding-top: 10px;
                border-top: 1px solid #bdc3c7;
                font-size: 9pt;
                color: #7f8c8d;
            }
        }
        /* تحسينات إضافية للجداول */
.performance-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 9pt;
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

.badge-success { 
    background: #27ae60 !important; 
    color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.badge-warning { 
    background: #f39c12 !important; 
    color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.badge-danger { 
    background: #e74c3c !important; 
    color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.badge-info { 
    background: #3498db !important; 
    color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* منع تكسر الصفوف في الجداول */
table {
    page-break-inside: auto;
}

tr {
    page-break-inside: avoid;
    page-break-after: auto;
}

/* تحسين عرض الأرقام */
td, th {
    font-family: 'Arial', 'Segoe UI', sans-serif;
}

.text-center {
    text-align: center !important;
}
  /* أنماط الطباعة المحسنة */
@media print {
    @page {
        margin: 0.5cm;
        size: A4;
        marks: crop;
    }
    
    body {
        font-family: 'Segoe UI', 'Arial', 'Tahoma', sans-serif;
        line-height: 1.4;
        color: #2c3e50;
        background: #fff;
        font-size: 10pt;
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .no-print {
        display: none !important;
    }
    
    .print-only {
        display: block !important;
    }
    
    .page-break {
        page-break-after: always;
    }
    
    .page-break-before {
        page-break-before: always;
    }
    
    /* الترويسة */
    .header {
        width: 100%;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    
    .header table {
        border: none;
    }
    
    .header td {
        border: none;
        padding: 3px;
        vertical-align: top;
    }
    
    .company-name {
        font-size: 14pt;
        font-weight: bold;
        color: #2c3e50;
    }
    
    .bismillah {
        font-size: 12pt;
        font-weight: bold;
        color: #27ae60;
        text-align: center;
    }
    
    .report-title {
        text-align: center;
        margin: 15px 0;
        padding: 8px;
        border: 1px solid #3498db;
        border-radius: 6px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        page-break-after: avoid;
    }
    
    .report-title h1 {
        margin: 0;
        font-size: 16pt;
    }
    
    /* بطاقة توزيع الكفاءات */
    .competency-distribution-card {
        background: #fff;
        border: 1px solid #bdc3c7;
        border-radius: 8px;
        margin: 15px 0;
        page-break-inside: avoid;
    }
    
    .distribution-header {
        background: #34495e;
        color: white;
        padding: 8px 12px;
        border-radius: 6px 6px 0 0;
    }
    
    .distribution-header h3 {
        margin: 0;
        font-size: 12pt;
    }
    
    .distribution-stats {
        display: flex;
        justify-content: space-around;
        padding: 15px 5px;
    }
    
    .distribution-item {
        text-align: center;
        flex: 1;
        padding: 8px;
    }
    
    .distribution-item.strong .distribution-value { color: #27ae60; }
    .distribution-item.medium .distribution-value { color: #f39c12; }
    .distribution-item.weak .distribution-value { color: #e74c3c; }
    .distribution-item.total .distribution-value { color: #3498db; }
    
    .distribution-value {
        font-size: 16pt;
        font-weight: bold;
        margin-bottom: 3px;
    }
    
    .distribution-label {
        font-size: 9pt;
        font-weight: bold;
        margin-bottom: 2px;
    }
    
    .distribution-percentage {
        font-size: 10pt;
        font-weight: bold;
        color: #7f8c8d;
    }
    
    .distribution-range {
        font-size: 8pt;
        color: #95a5a6;
    }
    
    /* الجداول المحسنة */
    .professional-table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
        font-size: 8pt;
        page-break-inside: auto;
    }
    
    .professional-table th,
    .professional-table td {
        border: 1px solid #95a5a6;
        padding: 5px 6px;
        text-align: center;
        page-break-inside: avoid;
    }
    
    .professional-table th {
        background: #34495e !important;
        color: white !important;
        font-weight: bold;
        font-size: 8pt;
    }
    
    .professional-table .text-right {
        text-align: right;
    }
    
    /* ألوان الخلفية */
    .strong-col { 
        background: rgba(39, 174, 96, 0.1) !important; 
    }
    .medium-col { 
        background: rgba(243, 156, 18, 0.1) !important; 
    }
    .weak-col { 
        background: rgba(231, 76, 60, 0.1) !important; 
    }
    
    /* badges محسنة */
    .performance-badge {
        padding: 3px 6px;
        border-radius: 10px;
        font-weight: bold;
        font-size: 7pt;
        display: inline-block;
        min-width: 45px;
        text-align: center;
        white-space: nowrap;
    }
    
    .badge-success { 
        background: #27ae60 !important; 
        color: white !important;
    }
    .badge-warning { 
        background: #f39c12 !important; 
        color: white !important;
    }
    .badge-danger { 
        background: #e74c3c !important; 
        color: white !important;
    }
    .badge-info { 
        background: #3498db !important; 
        color: white !important;
    }
    
    .count-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 7pt;
        font-weight: bold;
        background: #ecf0f1;
        color: #2c3e50;
    }
    
    .strong-badge { background: #d5f4e6; color: #27ae60; }
    .medium-badge { background: #fdebd0; color: #f39c12; }
    
    /* العناوين */
    .table-title {
        background: #2c3e50;
        color: white;
        padding: 6px 10px;
        margin: 12px 0 8px 0;
        border-radius: 4px;
        font-weight: bold;
        font-size: 10pt;
        page-break-after: avoid;
    }
    
    .template-section {
        margin-bottom: 15px;
        page-break-inside: avoid;
    }
    
    .template-header {
        background: #7f8c8d !important;
        color: white !important;
        padding: 5px 8px;
        font-weight: bold;
        font-size: 9pt;
        border-radius: 3px;
        margin-bottom: 5px;
    }
    
    /* بطاقات الموظفين */
    .employee-card {
        border: 1px solid #bdc3c7;
        border-radius: 6px;
        margin: 10px 0;
        padding: 8px;
        background: #f8f9fa;
        page-break-inside: avoid;
    }
    
    .employee-header {
        background: #2c3e50;
        color: white;
        padding: 6px 8px;
        border-radius: 4px;
        margin-bottom: 8px;
    }
    
    .employee-info h4 {
        margin: 0;
        font-size: 10pt;
    }
    
    .employee-info small {
        font-size: 8pt;
        opacity: 0.9;
    }
    
    .competencies-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-top: 8px;
    }
    
    .competency-category {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px;
    }
    
    .competency-category.strong { border-left: 3px solid #27ae60; }
    .competency-category.medium { border-left: 3px solid #f39c12; }
    .competency-category.weak { border-left: 3px solid #e74c3c; }
    
    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
        padding-bottom: 3px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .category-header h5 {
        margin: 0;
        font-size: 8pt;
        color: #2c3e50;
    }
    
    .category-range {
        font-size: 7pt;
        color: #7f8c8d;
    }
    
    .competencies-list {
        max-height: 120px;
        overflow-y: auto;
    }
    
    .competency-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 3px 0;
        border-bottom: 1px dotted #ecf0f1;
    }
    
    .competency-item:last-child {
        border-bottom: none;
    }
    
    .competency-details {
        flex: 1;
    }
    
    .competency-details strong {
        font-size: 8pt;
        display: block;
    }
    
    .criterion-name {
        font-size: 7pt;
        color: #7f8c8d;
    }
    
    /* الصفوف الخاصة */
    .summary-row {
        background: #ecf0f1 !important;
        font-weight: bold;
    }
    
    .criterion-row {
        background: #f8f9fa !important;
    }
    
    .criterion-name {
        font-weight: bold;
        text-align: right !important;
    }
    
    /* حالة عدم وجود بيانات */
    .no-data {
        text-align: center;
        padding: 20px;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* التذييل */
    .footer {
        margin-top: 20px;
        padding-top: 8px;
        border-top: 1px solid #bdc3c7;
        font-size: 8pt;
        color: #7f8c8d;
    }
}

/* أنماط الشاشة */
@media screen {
    body {
        background: #f8f9fa;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .print-container {
        max-width: 21cm;
        margin: 0 auto;
        background: white;
        padding: 1.5cm;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    
    .print-actions {
        text-align: center;
        margin: 20px 0;
        padding: 20px;
        background: #ecf0f1;
        border-radius: 8px;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14pt;
        cursor: pointer;
        margin: 0 10px;
        transition: all 0.3s ease;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
}

/* تحسينات عامة */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-left { text-align: left; }
.mb-20 { margin-bottom: 20px; }

/* أيقونات */
.fas, .fa {
    margin-left: 5px;
}  
/* بطاقة ملخص الكفاءات */
.competency-summary-card {
    background: #fff;
    border: 1px solid #bdc3c7;
    border-radius: 8px;
    margin: 15px 0;
    page-break-inside: avoid;
}

.summary-header {
    background: #34495e;
    color: white;
    padding: 8px 12px;
    border-radius: 6px 6px 0 0;
}

.summary-header h3 {
    margin: 0;
    font-size: 12pt;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding: 15px;
}

.summary-item {
    text-align: center;
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
}

.summary-item.strong { border-top: 3px solid #27ae60; }
.summary-item.medium { border-top: 3px solid #f39c12; }
.summary-item.weak { border-top: 3px solid #e74c3c; }
.summary-item.total { border-top: 3px solid #3498db; }

.summary-item.strong .summary-value { color: #27ae60; }
.summary-item.medium .summary-value { color: #f39c12; }
.summary-item.weak .summary-value { color: #e74c3c; }
.summary-item.total .summary-value { color: #3498db; }

.summary-value {
    font-size: 18pt;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 9pt;
    font-weight: bold;
    margin-bottom: 3px;
}

.summary-percentage {
    font-size: 10pt;
    font-weight: bold;
    color: #7f8c8d;
}

.summary-range {
    font-size: 8pt;
    color: #95a5a6;
}

/* الجداول المحسنة */
.professional-table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0;
    font-size: 8pt;
    page-break-inside: auto;
}

.professional-table th,
.professional-table td {
    border: 1px solid #95a5a6;
    padding: 5px 6px;
    text-align: center;
    page-break-inside: avoid;
}

.professional-table th {
    background: #34495e !important;
    color: white !important;
    font-weight: bold;
    font-size: 8pt;
}

.professional-table .text-right {
    text-align: right;
}

/* ألوان الخلفية */
.strong-col { 
    background: rgba(39, 174, 96, 0.1) !important; 
}
.medium-col { 
    background: rgba(243, 156, 18, 0.1) !important; 
}
.weak-col { 
    background: rgba(231, 76, 60, 0.1) !important; 
}

/* badges محسنة */
.performance-badge {
    padding: 3px 6px;
    border-radius: 10px;
    font-weight: bold;
    font-size: 7pt;
    display: inline-block;
    min-width: 45px;
    text-align: center;
    white-space: nowrap;
}

.badge-success { 
    background: #27ae60 !important; 
    color: white !important;
}
.badge-warning { 
    background: #f39c12 !important; 
    color: white !important;
}
.badge-danger { 
    background: #e74c3c !important; 
    color: white !important;
}
.badge-info { 
    background: #3498db !important; 
    color: white !important;
}

.count-badge {
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 7pt;
    font-weight: bold;
    background: #ecf0f1;
    color: #2c3e50;
}

.strong-badge { background: #d5f4e6; color: #27ae60; }
.medium-badge { background: #fdebd0; color: #f39c12; }

/* العناوين */
.table-title {
    background: #2c3e50;
    color: white;
    padding: 6px 10px;
    margin: 12px 0 8px 0;
    border-radius: 4px;
    font-weight: bold;
    font-size: 10pt;
    page-break-after: avoid;
}

.template-section {
    margin-bottom: 15px;
    page-break-inside: avoid;
}

.template-header {
    background: #7f8c8d !important;
    color: white !important;
    padding: 5px 8px;
    font-weight: bold;
    font-size: 9pt;
    border-radius: 3px;
    margin-bottom: 5px;
}

/* بطاقات الموظفين */
.employee-card {
    border: 1px solid #bdc3c7;
    border-radius: 6px;
    margin: 10px 0;
    padding: 8px;
    background: #f8f9fa;
    page-break-inside: avoid;
}

.employee-header {
    background: #2c3e50;
    color: white;
    padding: 6px 8px;
    border-radius: 4px;
    margin-bottom: 8px;
}

.employee-info h4 {
    margin: 0;
    font-size: 10pt;
}

.employee-info small {
    font-size: 8pt;
    opacity: 0.9;
}

.competencies-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
}

.competency-category {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px;
}

.competency-category.strong { border-left: 3px solid #27ae60; }
.competency-category.medium { border-left: 3px solid #f39c12; }
.competency-category.weak { border-left: 3px solid #e74c3c; }

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    padding-bottom: 3px;
    border-bottom: 1px solid #ecf0f1;
}

.category-header h5 {
    margin: 0;
    font-size: 8pt;
    color: #2c3e50;
}

.category-range {
    font-size: 7pt;
    color: #7f8c8d;
}

.competencies-list {
    max-height: 120px;
    overflow-y: auto;
}

.competency-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
    border-bottom: 1px dotted #ecf0f1;
}

.competency-item:last-child {
    border-bottom: none;
}

.competency-details {
    flex: 1;
}

.competency-details strong {
    font-size: 8pt;
    display: block;
}

.criterion-name {
    font-size: 7pt;
    color: #7f8c8d;
}

/* الصفوف الخاصة */
.summary-row {
    background: #ecf0f1 !important;
    font-weight: bold;
}

.criterion-row {
    background: #f8f9fa !important;
}

.criterion-name {
    font-weight: bold;
    text-align: right !important;
}

/* حالة عدم وجود بيانات */
.no-data {
    text-align: center;
    padding: 20px;
    color: #7f8c8d;
    font-style: italic;
}
/* تحسينات للطباعة بدون سكرول */
.competencies-list {
    /* إزالة أي حدود للارتفاع أو تمرير */
    max-height: none !important;
    overflow: visible !important;
    height: auto !important;
}

.employees-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.employee-card {
    height: auto;
    min-height: 300px;
    page-break-inside: avoid;
}

.competencies-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.competency-category {
    flex: 1;
}

/* تحسينات للبيانات الحقيقية */
.count-badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 8pt;
    font-weight: bold;
    background: #ecf0f1;
    color: #2c3e50;
    display: inline-block;
    min-width: 30px;
    text-align: center;
}

.strong-badge { 
    background: #d5f4e6 !important; 
    color: #27ae60 !important; 
    border: 1px solid #27ae60;
}

.medium-badge { 
    background: #fdebd0 !important; 
    color: #f39c12 !important; 
    border: 1px solid #f39c12;
}

/* منع تكسر الجداول */
.professional-table {
    page-break-inside: avoid;
}

/* تحسين التباعد للطباعة */
.employee-card {
    padding: 10px;
    margin-bottom: 10px;
}

.competency-item {
    padding: 4px 0;
    border-bottom: 1px dashed #ecf0f1;
}
/* تذييل الصفحة في كل صفحة */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    padding: 8px 0;
    border-top: 2px solid #3498db;
    font-size: 8pt;
    color: #7f8c8d;
    margin: 0;
    z-index: 1000;
}

.footer table {
    width: 100%;
    border: none;
    margin: 0;
}

.footer td {
    border: none;
    padding: 2px 10px;
    vertical-align: top;
}

/* تعديل الهوامش لاستيعاب التذييل */
body {
    margin-bottom: 60px !important;
}

.print-container {
    padding-bottom: 70px;
}

/* تحديث أرقام الصفحات */
@page {
    margin-bottom: 60px;
}/* صفحة الموظف المستقلة */
.employee-page {
    page-break-before: always;
    margin-bottom: 30px;
}

.employee-page:first-child {
    page-break-before: avoid;
}

.employee-page-header {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    page-break-after: avoid;
}

.employee-main-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.employee-avatar {
    font-size: 40pt;
    margin-left: 20px;
    color: #3498db;
}

.employee-details h3 {
    margin: 0 0 5px 0;
    font-size: 16pt;
    color: white;
}

.employee-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.emp-code {
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 9pt;
}

.employee-additional-info {
    display: flex;
    gap: 20px;
    border-top: 1px solid rgba(255,255,255,0.3);
    padding-top: 10px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 9pt;
    opacity: 0.9;
}

/* حاوية الكفاءات */
.competencies-container {
    margin-bottom: 20px;
}

.competency-section {
    margin-bottom: 25px;
    page-break-inside: avoid;
}

.competency-section.strong {
    border-right: 4px solid #27ae60;
}

.competency-section.medium {
    border-right: 4px solid #f39c12;
}

.competency-section.weak {
    border-right: 4px solid #e74c3c;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.section-title {
    font-size: 12pt;
    font-weight: bold;
    color: #2c3e50;
}

.section-stats {
    display: flex;
    gap: 15px;
    font-size: 9pt;
    color: #7f8c8d;
}

/* بطاقات الكفاءات */
.competencies-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.competency-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    page-break-inside: avoid;
}

.competency-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.competency-header h4 {
    margin: 0;
    font-size: 10pt;
    color: #2c3e50;
    flex: 1;
    margin-left: 10px;
}

.competency-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
}

.template, .criterion {
    font-size: 8pt;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.competency-description {
    padding: 10px;
    border-radius: 4px;
    font-size: 9pt;
}

.feedback.positive {
    background: #d5f4e6;
    color: #27ae60;
    border-right: 3px solid #27ae60;
}

.feedback.neutral {
    background: #fdebd0;
    color: #f39c12;
    border-right: 3px solid #f39c12;
}

.feedback.negative {
    background: #fadbd8;
    color: #e74c3c;
    border-right: 3px solid #e74c3c;
}

/* ملخص الموظف */
.employee-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 25px;
    page-break-before: avoid;
}

.summary-card, .recommendations-card {
    border: 1px solid #bdc3c7;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}

.summary-card h4, .recommendations-card h4 {
    margin: 0 0 15px 0;
    font-size: 11pt;
    color: #2c3e50;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dotted #ecf0f1;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    font-weight: bold;
    border-top: 2px solid #3498db;
    margin-top: 5px;
    padding-top: 8px;
}

.summary-item .value.strong { color: #27ae60; font-weight: bold; }
.summary-item .value.medium { color: #f39c12; font-weight: bold; }
.summary-item .value.weak { color: #e74c3c; font-weight: bold; }

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recommendation {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 4px;
    font-size: 9pt;
}

.recommendation.positive {
    background: #d5f4e6;
    color: #27ae60;
    border-right: 3px solid #27ae60;
}

.recommendation.neutral {
    background: #fdebd0;
    color: #f39c12;
    border-right: 3px solid #f39c12;
}

.recommendation.negative {
    background: #fadbd8;
    color: #e74c3c;
    border-right: 3px solid #e74c3c;
}

/* حالة عدم وجود بيانات */
.no-competencies {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.no-competencies h4 {
    margin: 15px 0 10px 0;
    font-size: 12pt;
}

.no-competencies p {
    margin: 0;
    font-size: 10pt;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.no-data h4 {
    margin: 15px 0 10px 0;
    font-size: 14pt;
}

.no-data p {
    margin: 0;
    font-size: 11pt;
}

/* تحسينات للطباعة */
@media print {
    .employee-page {
        margin-bottom: 0;
    }
    
    .competencies-grid {
        grid-template-columns: 1fr;
    }
    
    .employee-summary {
        grid-template-columns: 1fr;
    }
    
    /* منع تكسر العناصر المهمة */
    .employee-page-header,
    .section-header,
    .competency-card,
    .summary-card,
    .recommendations-card {
        page-break-inside: avoid;
    }
}
/* أنماط الشاشة */
        @media screen {
            body {
                background: #f8f9fa;
                padding: 20px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .print-container {
                max-width: 21cm;
                margin: 0 auto;
                background: white;
                padding: 2cm;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
            
            .print-actions {
                text-align: center;
                margin: 20px 0;
                padding: 20px;
                background: #ecf0f1;
                border-radius: 8px;
            }
            
            .btn-print {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-size: 14pt;
                cursor: pointer;
                margin: 0 10px;
                transition: all 0.3s ease;
            }
            
            .btn-print:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
        }
        /* تذييل الصفحة في كل صفحة */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    padding: 8px 0;
    border-top: 2px solid #3498db;
    font-size: 8pt;
    color: #7f8c8d;
    margin: 0;
    z-index: 1000;
}

.footer table {
    width: 100%;
    border: none;
    margin: 0;
}

.footer td {
    border: none;
    padding: 2px 10px;
    vertical-align: top;
}

/* تعديل الهوامش لاستيعاب التذييل */
body {
    margin-bottom: 60px !important;
}

.print-container {
    padding-bottom: 70px;
}

/* تحديث أرقام الصفحات */
@page {
    margin-bottom: 60px;
}/* صفحة الموظف المستقلة */
.employee-page {
    page-break-before: always;
    margin-bottom: 30px;
}

.employee-page:first-child {
    page-break-before: avoid;
}

.employee-page-header {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    page-break-after: avoid;
}

.employee-main-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.employee-avatar {
    font-size: 40pt;
    margin-left: 20px;
    color: #3498db;
}

.employee-details h3 {
    margin: 0 0 5px 0;
    font-size: 16pt;
    color: white;
}

.employee-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.emp-code {
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 9pt;
}

.employee-additional-info {
    display: flex;
    gap: 20px;
    border-top: 1px solid rgba(255,255,255,0.3);
    padding-top: 10px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 9pt;
    opacity: 0.9;
}

/* حاوية الكفاءات */
.competencies-container {
    margin-bottom: 20px;
}

.competency-section {
    margin-bottom: 25px;
    page-break-inside: avoid;
}

.competency-section.strong {
    border-right: 4px solid #27ae60;
}

.competency-section.medium {
    border-right: 4px solid #f39c12;
}

.competency-section.weak {
    border-right: 4px solid #e74c3c;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.section-title {
    font-size: 12pt;
    font-weight: bold;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-stats {
    display: flex;
    gap: 15px;
    font-size: 9pt;
    color: #7f8c8d;
}

/* بطاقات الكفاءات */
.competencies-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.competency-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    page-break-inside: avoid;
}

.competency-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.competency-header h4 {
    margin: 0;
    font-size: 10pt;
    color: #2c3e50;
    flex: 1;
    margin-left: 10px;
}

.competency-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
}

.template, .criterion {
    font-size: 8pt;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.competency-description {
    padding: 10px;
    border-radius: 4px;
    font-size: 9pt;
}

.feedback.positive {
    background: #d5f4e6;
    color: #27ae60;
    border-right: 3px solid #27ae60;
}

.feedback.neutral {
    background: #fdebd0;
    color: #f39c12;
    border-right: 3px solid #f39c12;
}

.feedback.negative {
    background: #fadbd8;
    color: #e74c3c;
    border-right: 3px solid #e74c3c;
}

/* ملخص الموظف */
.employee-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 25px;
    page-break-before: avoid;
}

.summary-card, .recommendations-card {
    border: 1px solid #bdc3c7;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}

.summary-card h4, .recommendations-card h4 {
    margin: 0 0 15px 0;
    font-size: 11pt;
    color: #2c3e50;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dotted #ecf0f1;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    font-weight: bold;
    border-top: 2px solid #3498db;
    margin-top: 5px;
    padding-top: 8px;
}

.summary-item .value.strong { color: #27ae60; font-weight: bold; }
.summary-item .value.medium { color: #f39c12; font-weight: bold; }
.summary-item .value.weak { color: #e74c3c; font-weight: bold; }

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recommendation {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 4px;
    font-size: 9pt;
}

.recommendation.positive {
    background: #d5f4e6;
    color: #27ae60;
    border-right: 3px solid #27ae60;
}

.recommendation.neutral {
    background: #fdebd0;
    color: #f39c12;
    border-right: 3px solid #f39c12;
}

.recommendation.negative {
    background: #fadbd8;
    color: #e74c3c;
    border-right: 3px solid #e74c3c;
}

/* حالة عدم وجود بيانات */
.no-competencies {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.no-competencies h4 {
    margin: 15px 0 10px 0;
    font-size: 12pt;
}

.no-competencies p {
    margin: 0;
    font-size: 10pt;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.no-data h4 {
    margin: 15px 0 10px 0;
    font-size: 14pt;
}

.no-data p {
    margin: 0;
    font-size: 11pt;
}

/* تحسينات للطباعة */
@media print {
    .employee-page {
        margin-bottom: 0;
    }
    
    .competencies-grid {
        grid-template-columns: 1fr;
    }
    
    .employee-summary {
        grid-template-columns: 1fr;
    }
    
    /* منع تكسر العناصر المهمة */
    .employee-page-header,
    .section-header,
    .competency-card,
    .summary-card,
    .recommendations-card {
        page-break-inside: avoid;
    }
}
        /* تحسينات عامة */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .mb-20 { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- ترويسة الصفحة -->
        <div class="header">
            <table>
                <tr>
                    <td width="33%" align="right">
                        <div class="company-name">شركة التقنية المتطورة</div>
                    </td>
                    <td width="34%" align="center">
                        <div class="bismillah">بسم الله الرحمن الرحيم</div>
                    </td>
                    <td width="33%" align="left">
                        <div><strong>التاريخ الميلادي:</strong> <?php echo $currentDate; ?></div>
                        <div><strong>التاريخ الهجري:</strong> <?php echo $currentHijri; ?></div>
                        <div><strong>اليوم:</strong> <?php echo $currentDayArabic; ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- عنوان التقرير -->
        <div class="report-title">
            <h1><?php echo $reportType . ' - ' . $entityInfo['name']; ?></h1>
        </div>

        <!-- ملخص الكيان -->
        <div class="entity-summary">
            <h3>ملخص <?php echo $entityInfo['type']; ?></h3>
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $entityInfo['employee_count']; ?></div>
                    <div class="stat-label">عدد الموظفين</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $analysis['stats']['average_performance']; ?>%</div>
                    <div class="stat-label">متوسط الأداء العام</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $analysis['stats']['strong_competencies_count']; ?></div>
                    <div class="stat-label">كفاءات قوية</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $analysis['stats']['weak_competencies_count']; ?></div>
                    <div class="stat-label">كفاءات للتطوير</div>
                </div>
            </div>
        </div>

        <!-- محتوى التقرير حسب النوع -->
        <?php if ($level === 'department'): ?>
            <!-- ========== تقرير القسم ========== -->
            <?php include 'partials/print_department_content.php'; ?>
            
        <?php elseif ($level === 'branch'): ?>
            <!-- ========== تقرير الفرع ========== -->
            <?php include 'partials/print_branch_content.php'; ?>
            
        <?php else: ?>
            <!-- ========== تقرير الشركة ========== -->
            <?php include 'partials/print_company_content.php'; ?>
            
        <?php endif; ?>

        <!-- تذييل الصفحة -->
        <!-- تذييل الصفحة (سيظهر في كل صفحة) -->
<div class="footer">
    <table>
        <tr>
            <td width="33%" align="right">
                <div><strong>المستخدم:</strong> <?php echo $userName; ?></div>
            </td>
            <td width="34%" align="center">
                <div>الصفحة <span class="page-number"></span> من <span class="total-pages"></span></div>
            </td>
            <td width="33%" align="left">
                <div><strong>تاريخ ووقت الطباعة:</strong><br>
                    <?php echo $currentDate . ' - ' . $currentTime; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

    <!-- أزرار التحكم في الطباعة (تظهر فقط على الشاشة) -->
    <div class="print-actions no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ طباعة التقرير
        </button>
        <button class="btn-print" onclick="window.close()">
            ❌ إغلاق النافذة
        </button>
    </div>

  <script>
// تحديث أرقام الصفحات تلقائياً
document.addEventListener('DOMContentLoaded', function() {
    // هذه الدالة ستتم معالجتها من قبل المتصفح أثناء الطباعة
    // نضيف أرقام الصفحات يدوياً للعرض على الشاشة
    const pageNumbers = document.querySelectorAll('.page-number');
    const totalPages = document.querySelectorAll('.employee-page').length + 2; // +2 للصفحات الأخرى
    
    pageNumbers.forEach((page, index) => {
        page.textContent = (index + 1);
    });
    
    const totalPagesElements = document.querySelectorAll('.total-pages');
    totalPagesElements.forEach(el => {
        el.textContent = totalPages;
    });
});

// الطباعة التلقائية (اختياري)
window.onload = function() {
    // يمكن تفعيل هذا السطر للطباعة التلقائية
    // setTimeout(() => { window.print(); }, 1000);
};
</script>
    
</body>
</html>