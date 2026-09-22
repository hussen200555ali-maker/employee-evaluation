/**
 * =================================================================
 *          ملف JavaScript خاص بمنطق إعداد وطباعة التقارير
 *                      (النسخة النهائية المبسطة)
 * =================================================================
 */

/**
 * تفتح نافذة منبثقة تشير إلى صفحة الطباعة التي يتم إنشاؤها من الخادم.
 * @param {string} level - مستوى التقرير ('company', 'branch', 'department').
 * @param {number|null} id - معرف الفرع أو القسم (إذا لزم الأمر).
 *//*
function fetchAndPrintReport(level, id = null) {
    // بناء الرابط لصفحة الطباعة الجديدة على الخادم
    let url = `${BASE_URL}/reports/print?level=${level}`;
    if (id) {
        url += `&id=${id}`;
    }

    // فتح الرابط في نافذة جديدة
    const printWindow = window.open(url, '_blank', 'height=800,width=1000,scrollbars=yes');

    if (!printWindow) {
        // استخدام SweetAlert2 لتنبيه المستخدم في حالة حظر النوافذ المنبثقة
        Swal.fire(
            'خطأ في الطباعة',
            'فشل فتح نافذة الطباعة. قد يكون متصفحك يمنع النوافذ المنبثقة. الرجاء السماح بها لهذا الموقع والمحاولة مرة أخرى.',
            'error'
        );
    } else {
        printWindow.focus();
    }
}
*/

function fetchAndPrintReport(level, id = null) {
    // بناء الرابط لصفحة الطباعة الجديدة على الخادم
    let url = `${BASE_URL}/reports/print?level=${level}`;
    if (id) {
        url += `&id=${id}`;
    }

    // إضافة معلمة عشوائية لتجنب التخزين المؤقت
    url += `&t=${new Date().getTime()}`;

    // فتح الرابط في نافذة جديدة
    const printWindow = window.open(url, '_blank', 'height=800,width=1000,scrollbars=yes');

    if (!printWindow) {
        // استخدام SweetAlert2 لتنبيه المستخدم في حالة حظر النوافذ المنبثقة
        Swal.fire(
            'خطأ في الطباعة',
            'فشل فتح نافذة الطباعة. قد يكون متصفحك يمنع النوافذ المنبثقة. الرجاء السماح بها لهذا الموقع والمحاولة مرة أخرى.',
            'error'
        );
    } else {
        printWindow.focus();
    }
}