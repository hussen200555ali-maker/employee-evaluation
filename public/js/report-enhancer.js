/**
 * ملف: public/js/report-enhancer.js
 * هذا الملف يحسن عرض التقارير على الشاشة ويدعم الطباعة
 */

document.addEventListener('DOMContentLoaded', function() {
    // تحسين عرض التقارير
    const reportContainers = document.querySelectorAll('.report-container');

    reportContainers.forEach(container => {
        // إضافة تأثيرات بصرية عند التمرير
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        observer.observe(container);

        // إ-initialization للعناصر
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        container.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });

    // تحسين أزرار التصدير
    const exportButtons = document.querySelectorAll('a[href*="exportPdf"], button');

    exportButtons.forEach(button => {
        // إضافة تأثير عند التمرير
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });

        // إضافة أيقونات للأزرار
        if (button.textContent.includes('PDF') || button.textContent.includes('تصدير')) {
            button.innerHTML = '<i class="fas fa-file-pdf"></i> ' + button.textContent;
        }
    });

    // تحسين الجداول
    const tables = document.querySelectorAll('.table');

    tables.forEach(table => {
        // إضافة تأثير عند التمرير
        table.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            this.style.transition = 'box-shadow 0.3s ease';
        });

        table.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });

        // إضافة دعم للتمرير الأفقي في الجداول الكبيرة
        if (table.scrollWidth > table.clientWidth) {
            table.style.overflowX = 'auto';
            table.style.whiteSpace = 'nowrap';
        }
    });

    // تحسين بطاقات المؤشرات
    const metricCards = document.querySelectorAll('.metric-card');

    metricCards.forEach(card => {
        // إضافة تأثير عند التمرير
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        });
    });

    // تحسين أقسام التقرير
    const sections = document.querySelectorAll('.section');

    sections.forEach(section => {
        // إضافة تأثير عند التمرير
        section.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            this.style.transition = 'box-shadow 0.3s ease';
        });

        section.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });

    // تحسين طباعة التقرير
    const printButton = document.querySelector('.btn-print');
    if (printButton) {
        printButton.addEventListener('click', function() {
            // إظهار شاشة التحميل
            const loadingSpinner = document.createElement('div');
            loadingSpinner.className = 'loading-spinner';
            loadingSpinner.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingSpinner);

            // إخفاء العناصر غير المرغوب في الطباعة
            const noPrintElements = document.querySelectorAll('.no-print');
            noPrintElements.forEach(el => {
                el.style.display = 'none';
            });

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
            notification.textContent = 'جاري تجهيز التقرير للطباعة...';

            document.body.appendChild(notification);

            // إزالة الرسالة بعد 3 ثوانٍ
            setTimeout(() => {
                notification.remove();
                loadingSpinner.remove();

                // استعادة العناصر المخفية
                noPrintElements.forEach(el => {
                    el.style.display = '';
                });

                // طباعة الصفحة
                window.print();
            }, 3000);
        });
    }

    // تحسين تصدير PDF
    const pdfButton = document.querySelector('.btn-pdf');
    if (pdfButton) {
        pdfButton.addEventListener('click', function() {
            // إظهار شاشة التحميل
            const loadingSpinner = document.createElement('div');
            loadingSpinner.className = 'loading-spinner';
            loadingSpinner.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingSpinner);

            // إظهار رسالة مؤقتة
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.left = '50%';
            notification.style.transform = 'translateX(-50%)';
            notification.style.backgroundColor = '#dc3545';
            notification.style.color = 'white';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '4px';
            notification.style.zIndex = '9999';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            notification.style.direction = 'rtl';
            notification.textContent = 'جاري إنشاء ملف PDF...';

            document.body.appendChild(notification);

            // الانتقال لصفحة تصدير PDF
            setTimeout(() => {
                window.location.href = this.href;
                notification.remove();
                loadingSpinner.remove();
            }, 2000);
        });
    }

    // تحسين عرض التوصيات
    const recommendations = document.querySelectorAll('.recommendation-item');

    recommendations.forEach(item => {
        // إضافة تأثير عند التمرير
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
            this.style.padding = '15px';
            this.style.borderRadius = '4px';
            this.style.transition = 'background-color 0.3s ease, padding 0.3s ease';
        });

        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.padding = '';
        });
    });
});
