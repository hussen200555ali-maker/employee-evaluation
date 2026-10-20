<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Audit;
use App\Models\Evaluation;
use App\Models\Template;
use App\Models\TrainingCourse;
use App\Models\TrainingWorkshop;
use App\Models\Employee;

class FlexibleEvaluationController extends Controller {

    private function checkCourseScope($courseId){
        if (Auth::userHasPermission('reports.view.all')) return true;
        $user=Auth::user(); $empId=$user->employee_id??0;
        if(!$empId) return false;
        $emp=(new Employee())->findById($empId);
        if(!$emp) return false;
        $course=(new TrainingCourse())->findById($courseId);
        if(!$course) return false;
        return (int)$emp->branch_id === (int)$course->branch_id;
    }

    public function createCourse() {
        Auth::enforce('evaluations.create');
        $courses=(new TrainingCourse())->getAll();
        $templates=(new Template())->findByScope('course');
        $this->view('flexible/create_course', ['pageTitle'=>'تقييم دورة ككيان (مرن)','courses'=>$courses,'templates'=>$templates]);
    }

    public function createWorkshop() {
        Auth::enforce('evaluations.create');
        $workshops=(new TrainingWorkshop())->getAll();
        $templates=(new Template())->findByScope('workshop');
        $this->view('flexible/create_workshop', ['pageTitle'=>'تقييم ورشة ككيان (مرن)','workshops'=>$workshops,'templates'=>$templates]);
    }

    public function createEmployeeInCourse() {
        Auth::enforce('evaluations.create');
        $courses=(new TrainingCourse())->getAll();
        $templates=(new Template())->findByScope('employee_in_course');
        $this->view('flexible/create_employee_in_course', ['pageTitle'=>'تقييم موظف داخل دورة (مرن)','courses'=>$courses,'templates'=>$templates]);
    }

    public function createEmployeeInWorkshop() {
        Auth::enforce('evaluations.create');
        $workshops=(new TrainingWorkshop())->getAll();
        $templates=(new Template())->findByScope('employee_in_workshop');
        $this->view('flexible/create_employee_in_workshop', ['pageTitle'=>'تقييم موظف داخل ورشة (مرن)','workshops'=>$workshops,'templates'=>$templates]);
    }

    public function storeCourse() {
        Auth::enforce('evaluations.create');
        if($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/flexible/evaluations/create/course');
        $courseId=(int)($_POST['course_id']??0); $templateId=(int)($_POST['template_id']??0);
        if(!$courseId||!$templateId){ Flash::set('بيانات غير مكتملة','danger'); $this->redirect('/flexible/evaluations/create/course'); }
        $course=(new TrainingCourse())->findById($courseId);
        if(!$course){ Flash::set('الدورة غير موجودة','danger'); $this->redirect('/flexible/evaluations/create/course'); }
        if(!$this->checkCourseScope($courseId)) return (new ErrorController())->forbidden('evaluations.create');
        $exists=$this->exists('course',$courseId,null,null,$templateId);
        if($exists){ Flash::set('يوجد تقييم لهذه الدورة بنفس القالب','danger'); $this->redirect('/flexible/evaluations/create/course'); }
        $data=[
            'template_id'=>$templateId,'entity_type'=>'course','entity_id'=>$courseId,'context_type'=>null,'context_id'=>null,'schedule_id'=>null,
            'evaluated_employee_id'=>null,'evaluator_employee_id'=>Auth::user()->employee_id??0,
            'evaluation_date'=>$_POST['evaluation_date']??date('Y-m-d'),'created_by_user_id'=>Auth::id(),
            'employee_name'=>$course->course_title,'employee_code'=>'course-'.$courseId,'branch_name'=>$course->branch_name??'','department_name'=>$course->dept_name??'','position_name'=>'دورة','entity_snapshot'=>['course_title'=>$course->course_title,'branch'=>$course->branch_name],'status'=>'draft'
        ];
        $id=(new Evaluation())->create($data);
        if($id){ Audit::log('FLEX_COURSE_EVAL',$id,'evaluations'); $this->redirect("/evaluations/{$id}/fill"); }
        Flash::set('فشل','danger'); $this->redirect('/flexible/evaluations/create/course');
    }

    public function storeWorkshop() {
        Auth::enforce('evaluations.create');
        if($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/flexible/evaluations/create/workshop');
        $wid=(int)($_POST['workshop_id']??0); $tid=(int)($_POST['template_id']??0);
        if(!$wid||!$tid){ Flash::set('بيانات غير مكتملة','danger'); $this->redirect('/flexible/evaluations/create/workshop'); }
        $w=(new TrainingWorkshop())->findById($wid);
        if(!$w){ Flash::set('الورشة غير موجودة','danger'); $this->redirect('/flexible/evaluations/create/workshop'); }
        if($this->exists('workshop',$wid,null,null,$tid)){ Flash::set('يوجد تقييم','danger'); $this->redirect('/flexible/evaluations/create/workshop'); }
        $data=[
            'template_id'=>$tid,'entity_type'=>'workshop','entity_id'=>$wid,'evaluated_employee_id'=>null,'evaluator_employee_id'=>Auth::user()->employee_id??0,
            'evaluation_date'=>$_POST['evaluation_date']??date('Y-m-d'),'created_by_user_id'=>Auth::id(),
            'employee_name'=>$w->workshop_title,'employee_code'=>'workshop-'.$wid,'branch_name'=>$w->branch_name??'','department_name'=>'','position_name'=>'ورشة','entity_snapshot'=>['workshop_title'=>$w->workshop_title],'status'=>'draft'
        ];
        $id=(new Evaluation())->create($data);
        if($id){ Audit::log('FLEX_WORKSHOP_EVAL',$id,'evaluations'); $this->redirect("/evaluations/{$id}/fill"); }
        Flash::set('فشل','danger'); $this->redirect('/flexible/evaluations/create/workshop');
    }

    public function storeEmployeeInCourse() {
        Auth::enforce('evaluations.create');
        if($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/flexible/evaluations/create/employee-in-course');
        $empId=(int)($_POST['employee_id']??0); $courseId=(int)($_POST['course_id']??0); $tid=(int)($_POST['template_id']??0);
        if(!$empId||!$courseId||!$tid){ Flash::set('بيانات غير مكتملة','danger'); $this->redirect('/flexible/evaluations/create/employee-in-course'); }
        $emp=(new Employee())->findById($empId); $course=(new TrainingCourse())->findById($courseId);
        if(!$emp||!$course){ Flash::set('موظف أو دورة غير موجود','danger'); $this->redirect('/flexible/evaluations/create/employee-in-course'); }
        if($this->exists('employee',$empId,'course',$courseId,$tid)){ Flash::set('يوجد تقييم لهذا الموظف بنفس الدورة والقالب','danger'); $this->redirect('/flexible/evaluations/create/employee-in-course'); }
        $data=[
            'template_id'=>$tid,'entity_type'=>'employee','entity_id'=>$empId,'context_type'=>'course','context_id'=>$courseId,'schedule_id'=>null,
            'evaluated_employee_id'=>$empId,'evaluator_employee_id'=>Auth::user()->employee_id??0,
            'evaluation_date'=>$_POST['evaluation_date']??date('Y-m-d'),'created_by_user_id'=>Auth::id(),
            'employee_name'=>$emp->full_name,'employee_code'=>$emp->emp_code,'branch_name'=>$emp->branch_name??'','department_name'=>$emp->department_name??'','position_name'=>$emp->position_title??'','entity_snapshot'=>['course_title'=>$course->course_title],'status'=>'draft'
        ];
        $id=(new Evaluation())->create($data);
        if($id){ Audit::log('FLEX_EMP_IN_COURSE',$id,'evaluations'); $this->redirect("/evaluations/{$id}/fill"); }
        Flash::set('فشل','danger'); $this->redirect('/flexible/evaluations/create/employee-in-course');
    }

    public function storeEmployeeInWorkshop() {
        Auth::enforce('evaluations.create');
        if($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/flexible/evaluations/create/employee-in-workshop');
        $empId=(int)($_POST['employee_id']??0); $wid=(int)($_POST['workshop_id']??0); $tid=(int)($_POST['template_id']??0);
        if(!$empId||!$wid||!$tid){ Flash::set('بيانات غير مكتملة','danger'); $this->redirect('/flexible/evaluations/create/employee-in-workshop'); }
        $emp=(new Employee())->findById($empId); $w=(new TrainingWorkshop())->findById($wid);
        if(!$emp||!$w){ Flash::set('بيانات غير صحيحة','danger'); $this->redirect('/flexible/evaluations/create/employee-in-workshop'); }
        if($this->exists('employee',$empId,'workshop',$wid,$tid)){ Flash::set('يوجد تقييم','danger'); $this->redirect('/flexible/evaluations/create/employee-in-workshop'); }
        $data=[
            'template_id'=>$tid,'entity_type'=>'employee','entity_id'=>$empId,'context_type'=>'workshop','context_id'=>$wid,'schedule_id'=>null,
            'evaluated_employee_id'=>$empId,'evaluator_employee_id'=>Auth::user()->employee_id??0,
            'evaluation_date'=>$_POST['evaluation_date']??date('Y-m-d'),'created_by_user_id'=>Auth::id(),
            'employee_name'=>$emp->full_name,'employee_code'=>$emp->emp_code,'branch_name'=>$emp->branch_name??'','department_name'=>$emp->department_name??'','position_name'=>$emp->position_title??'','entity_snapshot'=>['workshop_title'=>$w->workshop_title],'status'=>'draft'
        ];
        $id=(new Evaluation())->create($data);
        if($id){ Audit::log('FLEX_EMP_IN_WORKSHOP',$id,'evaluations'); $this->redirect("/evaluations/{$id}/fill"); }
        Flash::set('فشل','danger'); $this->redirect('/flexible/evaluations/create/employee-in-workshop');
    }

    private function exists($et,$eid,$ct,$cid,$tid){
        $db=\App\Core\Database::getInstance();
        $db->query("SELECT id FROM evaluations WHERE entity_type=:et AND entity_id=:eid AND (context_type <=> :ct) AND (context_id <=> :cid) AND template_id=:tid AND status IN ('draft','pending_review','approved','completed') LIMIT 1");
        $db->bind(':et',$et); $db->bind(':eid',$eid); $db->bind(':ct',$ct); $db->bind(':cid',$cid); $db->bind(':tid',$tid);
        return (bool)$db->fetch();
    }
}

