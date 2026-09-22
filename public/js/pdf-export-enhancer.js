/**
 * ملف: public/js/pdf-export-enhancer.js
 * هذا الملف يحسن تجربة تصدير التقارير إلى PDF
 */

document.addEventListener('DOMContentLoaded', function() {
    // البحث عن أزرار تصدير PDF
    const pdfExportButtons = document.querySelectorAll('a[href*="analysis/exportPdf"], a[href*="analysis/exportPdfWithCharts"]');

    pdfExportButtons.forEach(button => {
        // إضافة أيقونة جديدة للزر
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-file-pdf"></i> ' + originalContent;

        // إضافة تأثير عند المرور بالماوس
        button.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#dc3545';
            this.style.color = 'white';
        });

        button.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.color = '';
        });

        // إضافة تأثير النقر
        button.addEventListener('click', function(e) {
            // إظهار رسالة مؤقتة
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.left = '50%';
            notification.style.transform = 'translateX(-50%)';
            notification.style.backgroundColor = '#0d6efd';
            notification.style.color = 'white';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '4px';
            notification.style.zIndex = '9999';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            notification.style.direction = 'rtl';
            notification.textContent = 'جاري إنشاء التقرير...';

            document.body.appendChild(notification);

            // إزالة الرسالة بعد 3 ثوانٍ
            setTimeout(() => {
                notification.remove();
            }, 3000);
        });
    });

    // تحسين واجهة طباعة التقرير المباشرة
    const printView = document.querySelector('.print-view');
    if (printView) {
        // إضافة أزرار للتحكم في الطباعة
        const printControls = document.createElement('div');
        printControls.className = 'print-controls no-print';
        printControls.style.cssText = 'margin-bottom: 20px; text-align: center;';

        const backButton = document.createElement('button');
        backButton.textContent = 'العودة';
        backButton.style.cssText = 'background-color: #6c757d; color: white; border: none; padding: 8px 16px; margin: 0 5px; border-radius: 4px; cursor: pointer;';
        backButton.onclick = () => window.history.back();

        const printButton = document.createElement('button');
        printButton.textContent = 'طباعة التقرير';
        printButton.style.cssText = 'background-color: #0d6efd; color: white; border: none; padding: 8px 16px; margin: 0 5px; border-radius: 4px; cursor: pointer;';
        printButton.onclick = () => window.print();

        const pdfButton = document.createElement('button');
        pdfButton.textContent = 'حفظ كـ PDF';
        pdfButton.style.cssText = 'background-color: #dc3545; color: white; border: none; padding: 8px 16px; margin: 0 5px; border-radius: 4px; cursor: pointer;';
        pdfButton.onclick = () => {
            // الحصول على رابط تصدير PDF الحالي
            const currentUrl = new URL(window.location.href);
            const baseUrl = window.BASE_URL || window.location.origin;
            const pdfUrl = new URL(baseUrl + '/analysis/exportPdf');

            // نسخ بارامترات الرابط الحالي
            pdfUrl.searchParams.set('level', currentUrl.searchParams.get('level'));
            pdfUrl.searchParams.set('target_id', currentUrl.searchParams.get('target_id'));
            pdfUrl.searchParams.set('scope', currentUrl.searchParams.get('scope'));

            // الانتقال لصفحة تصدير PDF
            window.location.href = pdfUrl.toString();
        };

        printControls.appendChild(backButton);
        printControls.appendChild(printButton);
        printControls.appendChild(pdfButton);

        // إضافة أزرار التحكم إلى أعلى الصفحة
        const reportHeader = document.querySelector('.report-header');
        if (reportHeader) {
            reportHeader.parentNode.insertBefore(printControls, reportHeader.nextSibling);
        }
    }

    // تحسين عرض المؤشرات الرئيسية
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach(card => {
        card.style.transition = 'transform 0.3s ease';

        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        });
    });
});
