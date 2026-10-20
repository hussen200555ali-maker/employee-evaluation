<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
// استيراد المودلز التي سنحتاجها
use App\Models\Criterion;
//استيراد مودل القالب
use App\Models\Template; 

/**
 * CriterionController
 * مسؤول عن إدارة المعايير داخل القوالب.
 */
class CriterionController extends Controller {

    /**
     * دالة البناء للتحقق من الصلاحية.
     */
    public function __construct() {
        // استخدام enforce هنا مقبول لأن هذا الـ Controller يجب ألا يتم الوصول إليه مباشرة
        // إلا من خلال طلبات AJAX أو إرسال نماذج محمية.
        Auth::enforce('templates.manage');
    }

    /**
     * يخزن معياراً جديداً.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'template_id' => (int)$_POST['template_id'],
                'name' => trim($_POST['name']),
                'weight_percent' => (float)$_POST['weight_percent']
            ];

            // (لاحقاً سنتحقق من أن مجموع الأوزان لا يتجاوز 100)

            $criterionModel = new Criterion();
            $newId = $criterionModel->create($data);

            if ($newId) {
                Audit::log('CRITERION_CREATE', $newId, 'criteria', $data);
                Flash::set('تم إضافة المعيار "' . $data['name'] . '" بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء إضافة المعيار.', 'error');
            }
            
            // أعد التوجيه إلى صفحة إدارة المعايير الخاصة بنفس القالب.
            header('Location: ' . APP_URL . '/templates/' . $data['template_id']);
            exit();
        }
    }
    /**
     * يحدّث معياراً موجوداً.
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => (int)$_POST['id'],
                'name' => trim($_POST['name']),
                'weight_percent' => (float)$_POST['weight_percent'],
                'template_id' => (int)$_POST['template_id'] // نحتاجه لإعادة التوجيه
            ];

            $criterionModel = new Criterion();
            if ($criterionModel->update($data)) {
                Audit::log('CRITERION_UPDATE', $data['id'], 'criteria', $data);
                Flash::set('تم تحديث المعيار بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء تحديث المعيار.', 'error');
            }
            
            header('Location: ' . APP_URL . '/templates/' . $data['template_id']);
            exit();
        }
    }

    /**
     * يحذف معياراً.
     */
    public function destroy() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            $criterionModel = new Criterion();
            $criterionData = $criterionModel->findById($id);

            if ($criterionData && $criterionModel->delete($id)) {
                Audit::log('CRITERION_DELETE', $id, 'criteria', (array)$criterionData);
                Flash::set('تم حذف المعيار بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء حذف المعيار.', 'error');
            }
            
            // أعد التوجيه إلى صفحة القالب التي كان فيها.
            // نفترض أن ID القالب سيتم إرساله مع طلب الحذف.
            $template_id = (int)$_POST['template_id'];
            header('Location: ' . APP_URL . '/templates/' . $template_id);
            exit();
        }
    }

     /**
     * يعرض صفحة إدارة الأقسام لمعيار معين.
     */
    public function showSections($template_id, $criterion_id) {
        $templateModel = new Template();
        $criterionModel = new Criterion();

        $template = $templateModel->findById($template_id);
        $criterion = $criterionModel->findById($criterion_id);

        // التأكد من أن كل شيء موجود وأن المعيار يتبع للقالب الصحيح.
        if (!$template || !$criterion || $criterion->template_id != $template->id) {
            return (new ErrorController())->notFound();
        }

        $data = [
            'pageTitle' => 'إدارة أقسام المعيار',
            'template' => $template,
            'criterion' => $criterion,
            'sections' => $criterionModel->getSections($criterion_id)
        ];

        $this->view('templates/sections', $data);
    }

    /**
     * يخزن قسماً جديداً.
     */
    public function storeSection() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // تحويل الخيارات من textarea إلى JSON
            $options_json = null;
            if (in_array($_POST['input_type'], ['single_choice', 'multi_choice']) && !empty($_POST['options'])) {
                $options_array = [];
                $lines = explode("\n", trim($_POST['options']));
                foreach ($lines as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $options_array[] = ['label' => trim($parts[0]), 'value' => (int)trim($parts[1])];
                    }
                }
                $options_json = json_encode(['options' => $options_array], JSON_UNESCAPED_UNICODE);
            }

            $data = [
                'criterion_id' => (int)$_POST['criterion_id'],
                'name' => trim($_POST['name']),
                'input_type' => trim($_POST['input_type']),
                'options_json' => $options_json,
                'max_value' => ($_POST['input_type'] === 'numeric') ? (int)$_POST['max_value'] : 100,
                'weight_percent' => (float)$_POST['weight_percent'],
                'has_notes' => isset($_POST['has_notes']) ? 1 : 0,
                'notes_required' => isset($_POST['notes_required']) ? 1 : 0,
            ];

            $criterionModel = new Criterion();
            $newId = $criterionModel->addSection($data);

            if ($newId) {
                Audit::log('SECTION_CREATE', $newId, 'criteria_sections', $data);
                Flash::set('تم إضافة القسم بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء إضافة القسم.', 'error');
            }
            
            // أعد التوجيه إلى نفس الصفحة.
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }

    /**
     * يحذف قسماً.
     */
    public function destroySection() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $section_id = (int)$_POST['id'];
            
            $criterionModel = new Criterion();
            $sectionData = $criterionModel->findSectionById($section_id);

            if ($sectionData && $criterionModel->deleteSection($section_id)) {
                Audit::log('SECTION_DELETE', $section_id, 'criteria_sections', (array)$sectionData);
                Flash::set('تم حذف القسم بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء حذف القسم.', 'error');
            }

            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }
      /**
     * يحدّث قسماً موجوداً.
     */
    public function updateSection() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // نفس منطق التحويل إلى JSON المستخدم في دالة storeSection
            $options_json = null;
            if (in_array($_POST['input_type'], ['single_choice', 'multi_choice']) && !empty($_POST['options'])) {
                $options_array = [];
                $lines = explode("\n", trim($_POST['options']));
                foreach ($lines as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $options_array[] = ['label' => trim($parts[0]), 'value' => (int)trim($parts[1])];
                    }
                }
                $options_json = json_encode(['options' => $options_array], JSON_UNESCAPED_UNICODE);
            }

            $data = [
                'id' => (int)$_POST['id'],
                'name' => trim($_POST['name']),
                'input_type' => trim($_POST['input_type']),
                'options_json' => $options_json,
                'max_value' => ($_POST['input_type'] === 'numeric') ? (int)$_POST['max_value'] : 100,
                'weight_percent' => (float)$_POST['weight_percent'],
                'has_notes' => isset($_POST['has_notes']) ? 1 : 0,
                'notes_required' => isset($_POST['notes_required']) ? 1 : 0,
            ];

            $criterionModel = new Criterion();
            if ($criterionModel->updateSection($data)) {
                Audit::log('SECTION_UPDATE', $data['id'], 'criteria_sections', $data);
                Flash::set('تم تحديث القسم بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء تحديث القسم.', 'error');
            }
            
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }
}
