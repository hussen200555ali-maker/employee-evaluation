<?php
// المسار: app/Controllers/BuilderController.php (النسخة النهائية والموحدة)

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\Template;
use App\Models\Position; // لا تنس استيراد مودل المناصب
use App\Core\Audit;

/**
 * =================================================================
 * وحدة تحكم باني النماذج (BuilderController) - النسخة النهائية
 * =================================================================
 * هذا المتحكم هو المسؤول الوحيد عن عرض وحفظ وتصميم قوالب التقييم.
 */
class BuilderController extends Controller {

    public function __construct() {
        // حماية الوحدة بأكملها. لا يمكن الوصول لأي دالة هنا بدون هذه الصلاحية.
        Auth::enforce('templates.manage');
    }

    /**
     * =================================================================
     * الدالة الرئيسية: عرض واجهة الباني (لإنشاء جديد أو تعديل)
     * =================================================================
     * @param int|null $id - معرّف القالب (في حالة التعديل).
     */
    public function index($id = null) {
        $templateModel = new Template();
        $positionModel = new Position();
        
        // 1. تهيئة المتغيرات
        $isEditing = false;
        $templateData = null;
        $formStructureJson = 'null';
        $allPositions = $positionModel->getAll(); // جلب كل المناصب لعرضها في القائمة

        // 2. التحقق إذا كنا في وضع التعديل
        if ($id) {
            $isEditing = true;
            $templateData = $templateModel->getTemplateWithStructure($id);

            if (!$templateData) {
                return (new \App\Controllers\ErrorController())->notFound();
            }
            
            // تمرير الهيكل الكامل إلى JavaScript
            $formStructureJson = json_encode($templateData, JSON_UNESCAPED_UNICODE);
        }

        // 3. عرض الواجهة مع تمرير كل البيانات اللازمة
        $this->view('builder/index', [
            'pageTitle' => $isEditing ? 'تعديل القالب: ' . htmlspecialchars($templateData['template_name']) : 'إنشاء قالب جديد',
            'isEditing' => $isEditing,
            'template' => $templateData,
            'formStructureJson' => $formStructureJson,
            'positions' => $allPositions // تمرير قائمة المناصب
        ]);
    }

    /**
     * =================================================================
     * دالة الحفظ المركزية (مع منطق التحقق الذكي من الأوزان)
     * =================================================================
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/templates');
        }

        // 1. تجميع البيانات الأساسية للقالب - 7 أنواع عامة
        $allowedTypes = ['employee','employee_in_course','employee_in_workshop','course','workshop','unit','admin'];
        $ttype = $_POST['target_type'] ?? 'employee';
        if (!in_array($ttype, $allowedTypes)) $ttype='employee';
        $uscope = null;
        if ($ttype==='unit') {
            $us = $_POST['unit_scope'] ?? '';
            if (in_array($us, ['branch','department'])) $uscope=$us;
        }
        $templateData = [
            'template_name'        => trim($_POST['template_name'] ?? 'قالب بدون اسم'),
            'template_description' => trim($_POST['template_description'] ?? ''),
            'target_position_id'   => !empty($_POST['target_position_id']) ? (int)$_POST['target_position_id'] : null,
            'target_type'          => $ttype,
            'target_entity_id'     => null,
            'unit_scope'           => $uscope,
            'is_active'            => isset($_POST['is_active']) ? 1 : 0,
            'user_id'              => Auth::id()
        ];

        $templateModel = new Template();
        $criteriaStructure = $templateModel->buildStructureFromRequest($_POST);

        // 2. التحقق الذكي من صحة الأوزان
        $validationError = $this->validateWeights($criteriaStructure);
        if ($validationError) {
            Flash::set($validationError, 'danger');
            // إعادة المستخدم إلى نفس الصفحة التي كان عليها (إنشاء أو تعديل)
            $redirectUrl = isset($_POST['template_id']) ? '/builder/edit/' . $_POST['template_id'] : '/builder/create';
            $this->redirect($redirectUrl);
        }

        // 3. تحديد ما إذا كانت العملية "تحديث" أم "إنشاء"
        if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
            // --- حالة التحديث ---
            $templateId = (int)$_POST['template_id'];
            if ($templateModel->updateTemplateWithStructure($templateId, $templateData, $criteriaStructure)) {
                Audit::log('TEMPLATE_UPDATE', $templateId, 'form_templates');
                Flash::set('تم تحديث القالب بنجاح!', 'success');
            } else {
                Flash::set('حدث خطأ أثناء تحديث القالب.', 'danger');
            }
            $this->redirect('/templates');

        } else {
            // --- حالة الإنشاء ---
            $newId = $templateModel->createTemplateWithStructure($templateData, $criteriaStructure);
            if ($newId) {
                Audit::log('TEMPLATE_CREATE', $newId, 'form_templates');
                Flash::set('تم إنشاء القالب بنجاح.', 'success');
            } else {
                Flash::set('حدث خطأ أثناء إنشاء القالب.', 'danger');
            }
            $this->redirect('/templates');
        }
    }

    /**
     * =================================================================
     * دالة مساعدة للتحقق من الأوزان بهامش خطأ
     * =================================================================
     * @param array $criteriaStructure
     * @return string|null رسالة الخطأ أو null إذا كانت الأوزان صحيحة
     */
    private function validateWeights($criteriaStructure) {
        $totalCriteriaWeight = 0;
        $tolerance = 0.01; // هامش الخطأ المسموح به

        if (empty($criteriaStructure)) {
            return null; // لا يوجد شيء للتحقق منه
        }

        foreach ($criteriaStructure as $criterion) {
            $totalCriteriaWeight += (float)$criterion['weight'];
            
            $totalSectionWeight = 0;
            if (!empty($criterion['sections'])) {
                foreach ($criterion['sections'] as $section) {
                    $totalSectionWeight += (float)$section['weight'];
                }
                // التحقق من مجموع أوزان الأقسام داخل المعيار
                if (abs(100 - $totalSectionWeight) > $tolerance) {
                    return "خطأ: مجموع أوزان الأقسام في معيار '" . htmlspecialchars($criterion['name']) . "' يجب أن يكون 100%. المجموع الحالي هو " . round($totalSectionWeight, 2) . "%.";
                }
            }
        }

        // التحقق من المجموع الكلي لأوزان المعايير
        if (abs(100 - $totalCriteriaWeight) > $tolerance) {
            return "خطأ: المجموع الكلي لأوزان المعايير يجب أن يكون 100%. المجموع الحالي هو " . round($totalCriteriaWeight, 2) . "%.";
        }

        return null; // لا توجد أخطاء
    }

    /**
     * يعرض صفحة المعاينة الفارغة.
     */
    public function preview() {
        $this->view('builder/print_preview', [
            'pageTitle' => 'معاينة الطباعة' 
        ]);
    }
}

