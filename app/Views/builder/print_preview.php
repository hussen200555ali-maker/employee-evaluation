<?php
// المسار: print_preview.php (في المجلد الرئيسي)
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>معاينة الطباعة</title>
    <link href="<?php echo APP_URL; ?>/public/libs/bootstrap-5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/local-fonts.css">
    <style>
        /* أنماط خاصة بصفحة الطباعة فقط */
        body {
            background-color: #e9ecef; /* خلفية رمادية للشاشة فقط */
            font-family: 'Cairo', sans-serif;
        }
        .page-container {
            background-color: white;
            max-width: 800px; /* A4-like width */
            margin: 2rem auto;
            padding: 2.5cm 2cm; /* هوامش تشبه Word */
            box-shadow: 0 5px 20px rgba(0,0,0,0.15 );
            border: 1px solid #ccc;
        }
        .no-print {
            padding: 1rem;
            text-align: center;
            background-color: #343a40;
            color: white;
        }
        .preview-header h3 { font-weight: bold; }
        .table { font-size: 0.9rem; }
        .table thead th { background-color: #f8f9fa !important; }

        /* أنماط الطباعة الفعلية */
        @media print {
            body { background-color: white; }
            .no-print { display: none !important; }
            .page-container {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                max-width: 100% !important;
            }
            @page {
                size: A4;
                margin: 2cm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <p class="mb-2">هذه معاينة للطباعة. اضغط على Ctrl+P أو الزر أدناه للطباعة.</p>
        <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> طباعة</button>
        <button class="btn btn-secondary" onclick="window.close();">إغلاق</button>
    </div>

    <div class="page-container" id="print-content-area">
        <!-- سيتم حقن محتوى التقرير هنا بواسطة الجافاسكريبت -->
        <p class="text-center p-5">جاري تحميل البيانات...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // 1. قراءة البيانات من التخزين المؤقت للمتصفح
                const formDataJson = localStorage.getItem('printPreviewData');
                if (!formDataJson) {
                    throw new Error('لم يتم العثور على بيانات المعاينة. يرجى إغلاق هذه النافذة والمحاولة مرة أخرى.');
                }

                // 2. تحويل النص إلى كائن جافاسكريبت
                const data = JSON.parse(formDataJson);

                // 3. استدعاء دالة بناء HTML (سنقوم بنقلها هنا)
                const contentHTML = generatePreviewHTML(data);
                
                // 4. حقن المحتوى في الصفحة
                document.getElementById('print-content-area').innerHTML = contentHTML;

                // 5. تنظيف التخزين المؤقت (اختياري، لكنه جيد)
                localStorage.removeItem('printPreviewData');

            } catch (e) {
                document.getElementById('print-content-area').innerHTML = `<div class="alert alert-danger"><strong>خطأ:</strong> ${e.message}</div>`;
            }
        });

        // دالة مساعدة لتحويل نوع القسم إلى نص عربي
        function getSectionTypeName(type) {
            const names = { percentage: 'نسبة مئوية', numeric: 'رقمي', grade: 'تقدير', boolean: 'نعم/لا' };
            return names[type] || 'قسم';
        }

        // دالة بناء HTML (نفس الدالة من builder.js)
        function generatePreviewHTML(data) {
            let html = `<div class="preview-header text-center mb-4">
                            <h3>${data.template_name || 'اسم الاستمارة'}</h3>
                            <p class="text-muted">${data.template_description || 'لا يوجد وصف'}</p>
                        </div>`;

            if (!data.criteria || data.criteria.length === 0) {
                return html + '<p class="text-center">لم يتم إضافة أي معايير بعد.</p>';
            }

            html += '<table class="table table-bordered">';
            html += `<thead><tr><th>المعيار (الوزن)</th><th>القسم (الوزن)</th><th>نوع التقييم</th></tr></thead><tbody>`;

            data.criteria.forEach(criterion => {
                const sections = criterion.sections || [];
                const rowspan = sections.length > 0 ? `rowspan="${sections.length}"` : '';

                if (sections.length > 0) {
                    sections.forEach((section, index) => {
                        html += '<tr>';
                        if (index === 0) {
                            html += `<td ${rowspan} class="align-middle"><strong>${criterion.name}</strong> (${parseFloat(criterion.weight).toFixed(1)}%)</td>`;
                        }
                        html += `<td class="align-middle">${section.name} (${parseFloat(section.weight).toFixed(1)}%)</td>`;
                        html += `<td class="align-middle">${getSectionTypeName(section.sectionType)}</td>`;
                        html += '</tr>';
                    });
                } else {
                    html += `<tr>
                                <td class="align-middle"><strong>${criterion.name}</strong> (${parseFloat(criterion.weight).toFixed(1)}%)</td>
                                <td colspan="2" class="text-center text-muted">لا توجد أقسام في هذا المعيار</td>
                             </tr>`;
                }
            });

            html += '</tbody></table>';
            return html;
        }
    </script>

</body>
</html>
