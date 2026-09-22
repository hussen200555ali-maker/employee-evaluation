<?php
// المسار: app/Controllers/TemplateController.php (النسخة النظيفة)

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Template;
use App\Models\Evaluation;
use App\Core\Flash;
use App\Core\Audit;

class TemplateController extends Controller {

    public function __construct() {
        // حماية الوحدة بأكملها
        Auth::can('templates.manage');
    }

    /**
     * يعرض صفحة قائمة القوالب.
     */
    public function index() {
        $templateModel = new Template();
        $this->view('templates/index', [
            'pageTitle' => 'إدارة قوالب التقييم',
            'templates' => $templateModel->getAll()
        ]);
    }

    /**
     * يحذف قالبًا.
     */
    
public function destroy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::can('templates.manage', false)) {
        Flash::set('طلب غير مصرح به.', 'danger');
        $this->redirect('/templates');
    }

    $id = $_POST['template_id'] ?? $_POST['id'] ?? null;

    if (!$id) {
        Flash::set('لم يتم تحديد معرّف القالب للحذف.', 'warning');
        $this->redirect('/templates');
    }

    // --- ▼▼▼ بداية المنطق الجديد والذكي ▼▼▼ ---

    // 1. إنشاء كائنات من المودلز التي نحتاجها
    $templateModel = new Template();
    $evaluationModel = new Evaluation(); // <-- المودل الجديد

    // 2. التحقق أولاً: هل القالب مستخدم؟
    if ($evaluationModel->isTemplateInUse($id)) {
        // إذا كان مستخدماً، لا تحاول الحذف! فقط أظهر رسالة خطأ واضحة.
        Flash::set('لا يمكن حذف هذا القالب لأنه مرتبط بتقييمات حالية أو سابقة. يمكنك إلغاء تنشيطه بدلاً من ذلك.', 'warning');
        $this->redirect('/templates');
    }

    // 3. إذا لم يكن مستخدماً، قم بعملية الحذف بأمان
    $templateToDelete = $templateModel->findById($id); 

    if ($templateModel->delete($id)) {
        Audit::log('TEMPLATE_DELETE', $id, 'form_templates', json_encode($templateToDelete));
        Flash::set('تم حذف القالب بنجاح!', 'success');
    } else {
        // هذه الرسالة ستظهر فقط في حالة حدوث خطأ غير متوقع في قاعدة البيانات
        Flash::set('حدث خطأ غير متوقع أثناء محاولة حذف القالب.', 'danger');
    }

    // --- ▲▲▲ نهاية المنطق الجديد ▲▲▲ ---

    $this->redirect('/templates');
}
}
