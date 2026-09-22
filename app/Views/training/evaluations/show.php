<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<!-- ================================================== -->
<!--           1. أنماط العرض والطباعة                 -->
<!-- ================================================== -->
<style>
    /* إخفاء العناصر غير المرغوب فيها في الطباعة */
    @media print {
        .no-print, .main-header, .main-footer {
            display: none !important;
        }

        /* إجبار المتصفح على طباعة الألوان والخلفيات */
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background-color: #fff !important;
        }

        /* إعدادات الصفحة المطبوعة */
        @page {
            size: A4;
            margin: 0.8cm;
        }

        .page-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .card-for-print {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }

        /* === الحل لمشكلة فاصل الصفحات === */
        .report-header, .card-for-print {
            page-break-inside: avoid; /* منع المتصفح من قطع هذه العناصر */
        }
        /* ================================= */

        .print-footer {
            display: block !important;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #666;
            padding: 5px;
            background-color: #fff;
            border-top: 1px solid #ccc;
        }
    }
</style>

<!-- ================================================== -->
<!--           2. حاوية المحتوى الرئيسية               -->
<!-- ================================================== -->
<div class="container page-container" style="max-width: 950px;">

    <!-- أزرار الإجراءات (لا تطبع) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2><i class="fas fa-chart-bar"></i> تفاصيل تقييم الدورة</h2>
        <div>
            <button onclick="window.print();" class="btn btn-primary"><i class="fas fa-print me-2"></i> طباعة</button>
            <a href="<?php echo APP_URL; ?>/reports/training-dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> العودة للتقارير
            </a>
        </div>
    </div>

    <!-- الترويسة الرسمية (بتنسيق جديد) -->
    <div class="report-header" style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;">
        <div style="text-align: center; min-width: 150px;">
            <span style="display:block">المملكة العربية السعودية</span>  

            <span>مدينة الرياض</span>
        </div>
        <div style="text-align: center; flex-grow: 1;">
            <p style="font-size: 1.2rem; font-weight: bold; margin: 0;">بسم الله الرحمن الرحيم</p>
            <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="شعار" style="max-width: 70px; margin: 5px auto;">
            <h1 style="font-size: 1.4rem; margin: 0; font-weight: 700;">تقييم دورة تدريبية</h1>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <span style="display:block">التاريخ: <?php echo date('Y-m-d'); ?></span>  

            <span>اليوم: <?php echo $data['today_in_arabic']; ?></span>
        </div>
    </div>

    <!-- ================================================== -->
    <!--      3. المحتوى الفعلي (موحد للعرض والطباعة)      -->
    <!-- ================================================== -->
    <div class="card shadow-lg border-0 card-for-print">
        <div class="card-header bg-dark text-white p-3"> <!-- تقليل الـ padding -->
            <div class="d-flex align-items-center">
                <i class="fas fa-chalkboard-teacher fa-2x me-3"></i> <!-- تصغير الأيقونة والهامش -->
                <div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($data['eval']->course_title); ?></h4> <!-- تصغير الخط -->
                    <p class="mb-0 opacity-75 small">
                        <?php echo htmlspecialchars($data['eval']->branch_name ?? 'فرع غير محدد'); ?> / 
                        <?php echo htmlspecialchars($data['eval']->department_name ?? 'قسم غير محدد'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="card-body p-3"> <!-- تقليل الـ padding -->
            <!-- قسم المعلومات الأساسية -->
            <h5 class="card-title mb-2"><i class="fas fa-info-circle text-muted me-2"></i>معلومات أساسية</h5>
            <div class="border rounded p-3 mb-3">
                <div class="row gy-2">
                    <div class="col-lg-4 col-md-6"><strong>تاريخ البدء:</strong> <span><?php echo $data['eval']->start_date; ?></span></div>
                    <div class="col-lg-4 col-md-6"><strong>تاريخ الانتهاء:</strong> <span><?php echo $data['eval']->end_date; ?></span></div>
                    <div class="col-lg-4 col-md-6"><strong>تم التقييم بواسطة:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($data['eval']->evaluator_name); ?></span></div>
                    <div class="col-lg-4 col-md-6"><strong>الحضور الفعلي:</strong> <span><?php echo $data['eval']->actual_attendees; ?> مشارك</span></div>
                    <div class="col-lg-4 col-md-6"><strong>الحضور المفترض:</strong> <span><?php echo $data['eval']->expected_attendees; ?> مشارك</span></div>
                    <div class="col-lg-4 col-md-6"><strong>تاريخ التقييم:</strong> <span><?php echo date('Y-m-d', strtotime($data['eval']->created_at)); ?></span></div>
                </div>
            </div>

            <!-- قسم نتائج التقييم -->
            <h5 class="card-title mb-2"><i class="fas fa-poll-h text-muted me-2"></i>نتائج التقييم المفصلة</h5>
            <div class="row g-2 text-center mb-3"> <!-- تقليل المسافات بين البطاقات -->
                <div class="col-4"> 
                    <div class="card h-100 border-success">
                        <div class="card-header bg-success text-white small py-1">الحضور (50%)</div>
                        <div class="card-body d-flex flex-column justify-content-center py-2">
                            <h5 class="card-title text-success mb-0 fw-bold"><?php echo number_format($data['eval']->attendance_score, 2); ?>%</h5>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white small py-1">انتظام البرنامج (30%)</div>
                        <div class="card-body d-flex flex-column justify-content-center py-2">
                            <p class="card-text text-muted" style="font-size: 0.7rem; margin-bottom: 2px;">الدرجة الموزونة</p>
                            <h5 class="card-title text-primary mb-0 fw-bold"><?php echo number_format($data['eval']->program_regularity_weighted_score, 2); ?>%</h5>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark small py-1">استيعاب المشاركين (20%)</div>
                        <div class="card-body d-flex flex-column justify-content-center py-2">
                            <p class="card-text text-muted" style="font-size: 0.7rem; margin-bottom: 2px;">الدرجة الموزونة</p>
                            <h5 class="card-title text-warning mb-0 fw-bold"><?php echo number_format($data['eval']->participant_comprehension_weighted_score, 2); ?>%</h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- بطاقة النتيجة الإجمالية -->
            <div class="alert alert-dark text-center p-2" role="alert">
                <h5 class="alert-heading mb-1">النتيجة الإجمالية للدورة</h5>
                <hr class="my-1">
                <p class="display-6 fw-bold mb-0"><?php echo number_format($data['eval']->total_score, 2); ?>%</p>
            </div>
        </div>
    </div>
</div>

<!-- تذييل الطباعة -->
<div class="print-footer" style="display: none;">
    تمت الطباعة بواسطة: <?php echo htmlspecialchars($data['printed_by']); ?> | تاريخ الطباعة: <?php echo date('Y-m-d H:i:s'); ?>
</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
