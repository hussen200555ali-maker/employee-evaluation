<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\EvaluationSchedule;
use App\Models\Template;
use App\Models\Branch;
use App\Models\Department;
use App\Core\Audit;

class ScheduleController extends Controller {

    public function __construct() {
        Auth::can('units.manage');
    }

    public function index() {
        $model = new EvaluationSchedule();
        $schedules = $model->getAll();
        $templateModel = new Template();
        $templates = $templateModel->findByScope('unit');
        $branchModel = new Branch();
        $branches = $branchModel->getAll();
        $deptModel = new Department();
        $departments = $deptModel->getAll();
        $this->view('schedules/index', [
            'pageTitle'=>'إدارة جدولة تقييم الوحدات',
            'schedules'=>$schedules,
            'templates'=>$templates,
            'branches'=>$branches,
            'departments'=>$departments
        ]);
    }

    public function store() {
        Auth::can('units.manage');
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/schedules');
        $data = [
            'template_id'=> (int)($_POST['template_id'] ?? 0),
            'entity_type'=> $_POST['entity_type'] ?? 'branch',
            'unit_id'=> (int)($_POST['unit_id'] ?? 0),
            'periodicity'=> $_POST['periodicity'] ?? 'monthly',
            'title'=> trim($_POST['title'] ?? ''),
            'start_date'=> $_POST['start_date'] ?? date('Y-m-d'),
            'end_date'=> !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'is_active'=> isset($_POST['is_active']) ? 1 : 0,
            'created_by'=> Auth::id()
        ];
        $allowedE = ['branch','department'];
        $allowedP = ['daily','weekly','monthly','quarterly','semiannual','annual','custom'];
        if (!in_array($data['entity_type'], $allowedE) || !in_array($data['periodicity'], $allowedP) || !$data['template_id'] || !$data['unit_id']) {
            Flash::set('بيانات الجدولة غير مكتملة.', 'danger');
            $this->redirect('/schedules');
        }
        $model = new EvaluationSchedule();
        $id = $model->create($data);
        if ($id) {
            Audit::log('SCHEDULE_CREATE', $id, 'evaluation_schedules');
            Flash::set('تم إنشاء جدولة الوحدة بنجاح.', 'success');
        } else Flash::set('فشل إنشاء الجدولة.', 'danger');
        $this->redirect('/schedules');
    }

    public function update() {
        Auth::can('units.manage');
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/schedules');
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'template_id'=> (int)($_POST['template_id'] ?? 0),
            'entity_type'=> $_POST['entity_type'] ?? 'branch',
            'unit_id'=> (int)($_POST['unit_id'] ?? 0),
            'periodicity'=> $_POST['periodicity'] ?? 'monthly',
            'title'=> trim($_POST['title'] ?? ''),
            'start_date'=> $_POST['start_date'] ?? date('Y-m-d'),
            'end_date'=> !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'is_active'=> isset($_POST['is_active']) ? 1 : 0,
        ];
        $model = new EvaluationSchedule();
        if ($model->update($id, $data)) {
            Audit::log('SCHEDULE_UPDATE', $id, 'evaluation_schedules');
            Flash::set('تم تحديث الجدولة.', 'success');
        } else Flash::set('فشل التحديث.', 'danger');
        $this->redirect('/schedules');
    }

    public function destroy() {
        Auth::can('units.manage');
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/schedules');
        $id = (int)($_POST['id'] ?? 0);
        $model = new EvaluationSchedule();
        if ($model->hasEvaluations($id)) {
            Flash::set('لا يمكن حذف جدولة مرتبطة بتقييمات.', 'danger');
            $this->redirect('/schedules');
        }
        if ($model->delete($id)) {
            Audit::log('SCHEDULE_DELETE', $id, 'evaluation_schedules');
            Flash::set('تم الحذف.', 'success');
        }
        $this->redirect('/schedules');
    }
}
