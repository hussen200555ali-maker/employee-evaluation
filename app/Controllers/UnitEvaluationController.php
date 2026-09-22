<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
use App\Models\Evaluation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Template;
use App\Models\EvaluationSchedule;

class UnitEvaluationController extends Controller {

    private function checkScope($entityType, $entityId) {
        if (Auth::userHasPermission('reports.view.all')) return true;
        $user = Auth::user();
        $empId = $user->employee_id ?? 0;
        if (!$empId) return false;
        $empModel = new \App\Models\Employee();
        $emp = $empModel->findById($empId);
        if (!$emp) return false;
        if ($entityType==='branch') return (int)$emp->branch_id === (int)$entityId;
        if ($entityType==='department') return (int)$emp->department_id === (int)$entityId;
        return false;
    }

    public function create() {
        Auth::can('units.create');
        $branchModel = new Branch();
        $deptModel = new Department();
        $schedModel = new EvaluationSchedule();
        $this->view('units/create', [
            'pageTitle'=>'بدء تقييم وحدة',
            'branches'=>$branchModel->getAll(),
            'departments'=>$deptModel->getAll(),
            'schedules'=>$schedModel->getAll()
        ]);
    }

    public function store() {
        Auth::can('units.create');
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/units/evaluations/create');
        $entityType = $_POST['entity_type'] ?? 'branch';
        $entityId = (int)($_POST['entity_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);
        $scheduleId = !empty($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : null;
        $date = $_POST['evaluation_date'] ?? date('Y-m-d');

        if (!in_array($entityType, ['branch','department']) || !$entityId || !$templateId) {
            Flash::set('بيانات الوحدة غير مكتملة.', 'danger');
            $this->redirect('/units/evaluations/create');
        }
        if (!$this->checkScope($entityType, $entityId)) {
            return (new ErrorController())->forbidden('units.create');
        }
        // Validate schedule belongs to unit
        if ($scheduleId) {
            $sched = (new EvaluationSchedule())->findById($scheduleId);
            if (!$sched || (int)$sched->unit_id !== $entityId || $sched->entity_type !== $entityType) {
                Flash::set('الجدولة غير صالحة.', 'danger');
                $this->redirect('/units/evaluations/create');
            }
        }
        // Unique check
        $db = \App\Core\Database::getInstance();
        $db->query("SELECT id FROM evaluations WHERE entity_type=:et AND entity_id=:eid AND schedule_id <=> :sid AND template_id=:tid AND status IN ('draft','pending_review','approved') LIMIT 1");
        $db->bind(':et', $entityType);
        $db->bind(':eid', $entityId);
        $db->bind(':sid', $scheduleId);
        $db->bind(':tid', $templateId);
        if ($db->fetch()) {
            Flash::set('يوجد تقييم مكتمل لهذه الوحدة بنفس الدورية والقالب.', 'danger');
            $this->redirect('/units/evaluations/create');
        }
        // Build snapshot
        if ($entityType==='branch') {
            $unit = (new Branch())->findById($entityId);
            $name = $unit->name ?? "فرع $entityId";
            $branchName=$name; $deptName='';
        } else {
            $unit = (new Department())->findById($entityId);
            $name = $unit->name ?? "قسم $entityId";
            $branchName=''; $deptName=$name;
        }
        $data = [
            'template_id'=>$templateId,
            'entity_type'=>$entityType,
            'entity_id'=>$entityId,
            'schedule_id'=>$scheduleId,
            'evaluated_employee_id'=>null,
            'evaluator_employee_id'=> Auth::user()->employee_id ?? 0,
            'evaluator_name'=> Auth::user()->username ?? 'system',
            'evaluation_date'=>$date,
            'created_by_user_id'=>Auth::id(),
            'employee_name'=>$name,
            'employee_code'=> $entityType.'-'.$entityId,
            'branch_name'=>$branchName,
            'department_name'=>$deptName,
            'position_name'=>'وحدة',
            'entity_snapshot'=>['unit_name'=>$name,'entity_type'=>$entityType],
            'status'=>'draft'
        ];
        $evalModel = new Evaluation();
        $newId = $evalModel->create($data);
        if ($newId) {
            Audit::log('UNIT_EVALUATION_STARTED', $newId, 'evaluations', ['entity_type'=>$entityType,'entity_id'=>$entityId]);
            $this->redirect("/evaluations/{$newId}/fill");
        } else {
            Flash::set('فشل إنشاء تقييم الوحدة.', 'danger');
            $this->redirect('/units/evaluations/create');
        }
    }

    public function review() {
        Auth::can('units.review');
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/');
        $id = (int)($_POST['evaluation_id'] ?? 0);
        $action = $_POST['action'] ?? 'approve';
        $evalModel = new Evaluation();
        $eval = $evalModel->findById($id);
        if (!$eval || !in_array($eval->entity_type, ['branch','department'])) {
            Flash::set('التقييم غير موجود.', 'danger');
            $this->redirect('/');
        }
        if ($action==='approve') {
            $evalModel->updateScoreAndStatus($id, $eval->final_score, 'approved');
            Audit::log('UNIT_EVALUATION_APPROVED', $id, 'evaluations');
            Flash::set('تم اعتماد تقييم الوحدة.', 'success');
        } elseif ($action==='reject') {
            $evalModel->updateScoreAndStatus($id, null, 'draft');
            Audit::log('UNIT_EVALUATION_REJECTED', $id, 'evaluations');
            Flash::set('أُعيد التقييم للمسودة.', 'info');
        }
        $this->redirect("/evaluations/{$id}/fill");
    }
}
