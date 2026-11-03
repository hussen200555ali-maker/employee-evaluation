<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\GradingScale;

class GradingScaleController extends Controller {

    public function __construct() {
        Auth::enforce('grading_scales.manage');
    }

    public function index(): void {
        $this->view('grading-scales/index', [
            'pageTitle' => 'إدارة سلم التقديرات',
            'scales' => (new GradingScale())->getAll(),
        ]);
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/grading-scales');
        }

        $data = $this->validatedInput();
        if ($data === null) {
            $this->redirect('/grading-scales');
        }

        $model = new GradingScale();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && $id !== null && $id > 0) {
            if (!$model->findById($id)) {
                Flash::set('درجة التقدير المطلوبة غير موجودة.', 'danger');
                $this->redirect('/grading-scales');
            }
            if ($model->gradeLabelExists($data['scale_name'], $data['grade_label'], $id)) {
                Flash::set('يوجد تقدير بالاسم نفسه في هذا السلم.', 'danger');
                $this->redirect('/grading-scales');
            }
            if ($model->hasOverlappingRange($data['scale_name'], $data['min_value'], $data['max_value'], $id)) {
                Flash::set('يتداخل نطاق الدرجة مع درجة أخرى في السلم نفسه.', 'danger');
                $this->redirect('/grading-scales');
            }
            if (!$model->update($id, $data)) {
                Flash::set('تعذر تحديث درجة التقدير.', 'danger');
                $this->redirect('/grading-scales');
                return;
            }
            Flash::set('تم تحديث درجة التقدير.', 'success');
        } else {
            if ($model->gradeLabelExists($data['scale_name'], $data['grade_label'])) {
                Flash::set('يوجد تقدير بالاسم نفسه في هذا السلم.', 'danger');
                $this->redirect('/grading-scales');
            }
            if ($model->hasOverlappingRange($data['scale_name'], $data['min_value'], $data['max_value'])) {
                Flash::set('يتداخل نطاق الدرجة مع درجة أخرى في السلم نفسه.', 'danger');
                $this->redirect('/grading-scales');
            }
            $model->create($data);
            Flash::set('تمت إضافة درجة التقدير.', 'success');
        }

        $this->redirect('/grading-scales');
    }

    public function destroy(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/grading-scales');
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            Flash::set('معرّف درجة التقدير غير صالح.', 'danger');
            $this->redirect('/grading-scales');
        }

        $model = new GradingScale();
        if (!$model->findById($id)) {
            Flash::set('درجة التقدير المطلوبة غير موجودة.', 'danger');
            $this->redirect('/grading-scales');
        }

        if (!$model->delete($id)) {
            Flash::set('تعذر حذف درجة التقدير.', 'danger');
            $this->redirect('/grading-scales');
            return;
        }
        Flash::set('تم حذف درجة التقدير.', 'success');
        $this->redirect('/grading-scales');
    }

    private function validatedInput(): ?array {
        $scaleName = trim((string)($_POST['scale_name'] ?? ''));
        $gradeLabel = trim((string)($_POST['grade_label'] ?? ''));
        $minValue = $_POST['min_value'] ?? null;
        $maxValue = $_POST['max_value'] ?? null;
        $numericValue = $_POST['numeric_value'] ?? null;
        $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);

        if (
            $scaleName === ''
            || mb_strlen($scaleName, 'UTF-8') > 100
            || $gradeLabel === ''
            || mb_strlen($gradeLabel, 'UTF-8') > 50
            || !is_numeric($minValue)
            || !is_numeric($maxValue)
            || !is_numeric($numericValue)
            || $sortOrder === false
        ) {
            Flash::set('تحقق من اسم السلم والتقدير والحدود والقيمة الرقمية والترتيب.', 'danger');
            return null;
        }

        $minValue = (float)$minValue;
        $maxValue = (float)$maxValue;
        $numericValue = (float)$numericValue;
        if (
            !is_finite($minValue)
            || !is_finite($maxValue)
            || !is_finite($numericValue)
            || $minValue < 0
            || $maxValue > 100
            || $minValue > $maxValue
            || $numericValue < 0
            || $numericValue > 9.99
            || $sortOrder < 0
        ) {
            Flash::set('يجب أن تكون الحدود بين 0 و100، وأن يكون الحد الأدنى أقل من الأعلى.', 'danger');
            return null;
        }

        return [
            'scale_name' => $scaleName,
            'grade_label' => $gradeLabel,
            'min_value' => $minValue,
            'max_value' => $maxValue,
            'numeric_value' => $numericValue,
            'sort_order' => $sortOrder,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }
}
