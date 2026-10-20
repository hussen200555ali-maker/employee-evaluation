<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\WorkshopEvaluation;
use App\Models\TrainingWorkshop;
use App\Core\Flash;

class WorkshopEvaluationController extends Controller {

/*    public function __construct() {
        Auth::enforce('training.manage'); // أو صلاحية خاصة بالتقييم
    }*/

    // عرض نموذج التقييم لورشة معينة
    public function create($workshop_id) {
      Auth::enforce('training.manage');
        $twModel = new TrainingWorkshop();
        $workshop = $twModel->findById($workshop_id);
        if (!$workshop) {
            Flash::set('الورشة غير موجودة.', 'danger');
            $this->redirect('/training/workshops');
            return;
        }

        $data = [
            'pageTitle' => 'تقييم ورشة',
            'workshop' => $workshop
        ];
        $this->view('training/workshops/evaluations/create', $data);
    }

    // حفظ التقييم
    public function store() {
      Auth::enforce('training.manage');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/training/workshops');
            return;
        }

        $model = new WorkshopEvaluation();
        $payload = [
            'workshop_id' => $_POST['workshop_id'],
            'expected_attendees' => $_POST['expected_attendees'] ?? 0,
            'actual_attendees' => $_POST['actual_attendees'] ?? 0,
            'program_regularity_weighted_score' => $_POST['program_regularity_weighted_score'] ?? 0,
            'notes' => $_POST['notes'] ?? null,
            'evaluated_by' => Auth::id()
        ];

        $newId = $model->create($payload);
        if ($newId) {
            Flash::set('تم حفظ التقييم بنجاح.', 'success');
        } else {
            Flash::set('حدث خطأ أثناء حفظ التقييم.', 'danger');
        }

        $this->redirect('/training/workshops');
    }

    // عرض تقييم محفوظ
public function show($id) {
  Auth::enforce('training.manage');
    $evaluationModel = new \App\Models\WorkshopEvaluation();
    $evaluation = $evaluationModel->findById($id);

    if (!$evaluation) {
        \App\Core\Flash::set('التقييم غير موجود', 'danger');
        $this->redirect('/training/workshops');
        return;
    }

    $data = [
        'pageTitle'  => 'عرض تقييم ورشة',
        'evaluation' => $evaluation,
        'printedBy'  => Auth::user()->username
      ];

    $this->view('training/workshops/evaluations/show', $data);
}


}

