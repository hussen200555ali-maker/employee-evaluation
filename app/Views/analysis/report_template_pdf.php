<?php
// ملف: app/Views/analysis/report_template_pdf.php
// هذا هو القالب النهائي المصمم خصيصًا للطباعة الاحترافية
?>
  <!DOCTYPE html>
  <html lang="ar" dir="rtl">
  <head>
      <meta charset="UTF-8">
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <title><?php echo htmlspecialchars($reportTitle ?? 'تقرير الأداء' ); ?></title>
      
      <!-- ▼▼▼ الخطوة 1: تضمين كل أكواد CSS مباشرة داخل القالب ▼▼▼ -->
      <style>
          /* تحديد الخط الأساسي الذي يدعم العربية (مهم جدًا) */
          body {
              font-family: 'dejavu sans', sans-serif;
              font-size: 12px;
              direction: rtl; /* تحديد اتجاه الصفحة بالكامل */
              text-align: right;
          }

          /* تنسيق الترويسة */
          .header-table {
              width: 100%;
              border-bottom: 1px solid #000;
              padding-bottom: 10px;
              margin-bottom: 20px;
          }
          .header-table td {
              vertical-align: middle;
              text-align: center;
          }
          .header-right, .header-left {
              width: 30%;
              font-size: 11px;
              line-height: 1.5;
          }
          .header-center {
              width: 40%;
          }
          .company-logo {
              max-width: 80px; /* يمكنك تعديل حجم الشعار */
              max-height: 80px;
          }
          .report-main-title {
              font-size: 16px;
              font-weight: bold;
              margin-top: 5px;
          }

          /* تنسيق الجداول لتكون شبيهة بـ Bootstrap */
          .table {
              width: 100%;
              margin-bottom: 1rem;
              color: #212529;
              border-collapse: collapse; /* دمج حدود الخلايا */
          }
          .table th, .table td {
              padding: 0.75rem;
              vertical-align: top;
              border-top: 1px solid #dee2e6;
              text-align: right; /* التأكد من محاذاة النص لليمين */
          }
          .table thead th {
              vertical-align: bottom;
              border-bottom: 2px solid #dee2e6;
              background-color: #f8f9fa; /* لون خلفية رأس الجدول */
          }
          .table-bordered, .table-bordered th, .table-bordered td {
              border: 1px solid #dee2e6;
          }
          .table-striped tbody tr:nth-of-type(odd) {
              background-color: rgba(0, 0, 0, 0.05);
          }

          /* تنسيق عناوين الأقسام (نقاط القوة والضعف) */
          .section-header {
              background-color: #e9ecef;
              padding: 10px;
              font-size: 14px;
              font-weight: bold;
              margin-top: 20px;
              margin-bottom: 10px;
              border-right: 5px solid #007bff; /* شريط جانبي للتمييز */
          }
          .section-header.strong { border-color: #28a745; }
          .section-header.weak { border-color: #dc3545; }

          /* تنسيق شارات الدرجات */
          .badge {
              display: inline-block;
              padding: 0.35em 0.65em;
              font-size: .75em;
              font-weight: 700;
              line-height: 1;
              text-align: center;
              white-space: nowrap;
              vertical-align: baseline;
              border-radius: 0.25rem;
              color: #fff;
          }
          .badge-success { background-color: #28a745; }
          .badge-danger { background-color: #dc3545; }
          .badge-primary { background-color: #007bff; }

          /* تنسيق التذييل */
          .footer {
              position: fixed;
              bottom: 0;
              left: 0;
              right: 0;
              text-align: center;
              font-size: 10px;
              color: #777;
              border-top: 1px solid #ccc;
              padding-top: 5px;
          }
      </style>
  </head>
  <body>

      <!-- ▼▼▼ الخطوة 2: إضافة هيكل الترويسة الجديد ▼▼▼ -->
      <table class="header-table">
          <tr>
              <td class="header-right">
                  المملكة العربية السعودية  

                  مدينة الرياض
              </td>
              <td class="header-center">
                  بسم الله الرحمن الرحيم  

                  <!-- ملاحظة: يجب أن يكون مسار الشعار مطلقًا أو Base64 -->
                  <!-- مثال لمسار مطلق: <img src="C:/xamppp/htdocs/employee-evaluation/public/images/logo.png" class="company-logo"> -->
                  <!-- مثال لـ Base64: <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUg..." class="company-logo"> -->
                  <div class="report-main-title"><?php echo htmlspecialchars($reportTitle); ?></div>
              </td>
              <td class="header-left">
                  التاريخ: <?php echo date('Y-m-d'); ?>  

                  الموافق: <?php echo date('d/m/Y'); ?>  

                  اليوم: <?php 
                      $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                      echo $days[date('w')];
                  ?>
              </td>
          </tr>
      </table>

      <!-- ▼▼▼ الخطوة 3: عرض محتوى التقرير باستخدام التنسيقات الجديدة ▼▼▼ -->
      <main>
          <?php if (isset($analysis['classified_performance']) && !empty($analysis['classified_performance'])): ?>
              
              <!-- عرض نقاط القوة -->
              <?php if (!empty($analysis['classified_performance']['strong'])): ?>
                  <div class="section-header strong">نقاط القوة (أداء ≥ 85%)</div>
                  <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>المعيار الرئيسي</th>
                              <th>الكفاءة (القسم الفرعي)</th>
                              <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($analysis['classified_performance']['strong'] as $item): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                  <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                  <td style="text-align: center;"><span class="badge badge-success"><?php echo $item['score']; ?>%</span></td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              <?php endif; ?>

              <!-- عرض الأداء الجيد -->
              <?php if (!empty($analysis['classified_performance']['medium'])): ?>
                  <div class="section-header">الأداء الجيد (50% - 84.9%)</div>
                  <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>المعيار الرئيسي</th>
                              <th>الكفاءة (القسم الفرعي)</th>
                              <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($analysis['classified_performance']['medium'] as $item): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                  <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                  <td style="text-align: center;"><span class="badge badge-primary"><?php echo $item['score']; ?>%</span></td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              <?php endif; ?>

              <!-- عرض جوانب التطوير -->
              <?php if (!empty($analysis['classified_performance']['weak'])): ?>
                  <div class="section-header weak">جوانب تحتاج إلى تطوير (أداء < 50%)</div>
                  <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>المعيار الرئيسي</th>
                              <th>الكفاءة (القسم الفرعي)</th>
                              <th style="width: 20%; text-align: center;">متوسط الدرجة</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($analysis['classified_performance']['weak'] as $item): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($item['criterion_name']); ?></td>
                                  <td><?php echo htmlspecialchars($item['section_name']); ?></td>
                                  <td style="text-align: center;"><span class="badge badge-danger"><?php echo $item['score']; ?>%</span></td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              <?php endif; ?>

          <?php else: ?>
              <p>لا توجد بيانات تحليلية لعرضها في هذا التقرير.</p>
          <?php endif; ?>
      </main>

      <!-- ▼▼▼ الخطوة 4: إضافة تذييل للصفحة (اختياري) ▼▼▼ -->
      <div class="footer">
          صفحة <span class="page-number"></span> | <?php echo htmlspecialchars($companyName); ?> &copy; <?php echo date('Y'); ?>
      </div>

  </body>
  </html>
