<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

class PerformanceFilesController extends Controller {

    private function getScope() {
        if (Auth::userHasPermission('reports.view.all')) return null;
        $u=Auth::user(); $empId=$u->employee_id ??0;
        if(!$empId) return ['branch_id'=>-1,'department_id'=>-1];
        $emp=(new \App\Models\Employee())->findById($empId);
        return ['branch_id'=>(int)($emp->branch_id??-1),'department_id'=>(int)($emp->department_id??-1)];
    }

    private function scopeWhere($branchField, $deptField, &$bind, $scope){
        if($scope===null) return "";
        // will filter later in PHP if needed - for now return branch filter
        return "";
    }

    public function courses() {
        Auth::enforce('reports.view.all');
        $scope=$this->getScope();
        $db=\App\Core\Database::getInstance();
        // Get courses with avg from flexible evaluations (entity_type=course) + legacy? Legacy is in course_evaluations, but we treat as archive - we show flexible only, but also include legacy via UNION
        $sql="SELECT tc.id, cc.title as name, b.name as branch_name, d.name as dept_name,
                     COUNT(e.id) as cnt, AVG(e.final_score) as avg_score, MAX(e.final_score) as max_s, MIN(e.final_score) as min_s
              FROM training_courses tc
              JOIN course_catalog cc ON tc.catalog_id=cc.id
              LEFT JOIN branches b ON tc.branch_id=b.id
              LEFT JOIN departments d ON tc.department_id=d.id
              LEFT JOIN evaluations e ON e.entity_type='course' AND e.entity_id=tc.id AND e.status IN ('approved','completed')
              GROUP BY tc.id ORDER BY avg_score DESC";
        // Apply scope filter in PHP or SQL
        if($scope!==null){
            $sql="SELECT * FROM ($sql) as t WHERE 1=1";
            // For branch manager, filter by branch_name = scope branch name
            $branchName = (new \App\Models\Branch())->findById($scope['branch_id'])->name ?? null;
            if($branchName){
                // We will filter in PHP after fetch for simplicity
            }
        }
        $db->query($sql);
        $rows=$db->fetchAll();
        if($scope!==null){
            $branchName = $scope['branch_id']!=-1 ? ((new \App\Models\Branch())->findById($scope['branch_id'])->name ?? null) : null;
            if($branchName) $rows=array_filter($rows, fn($r)=> $r->branch_name==$branchName);
        }
        $this->view('performance/courses', ['pageTitle'=>'ملفات أداء الدورات','items'=>$rows]);
    }

    public function workshops() {
        Auth::enforce('reports.view.all');
        $scope=$this->getScope();
        $db=\App\Core\Database::getInstance();
        $db->query("SELECT tw.id, wc.title as name, b.name as branch_name,
                           COUNT(e.id) as cnt, AVG(e.final_score) as avg_score
                    FROM training_workshops tw
                    JOIN workshop_catalog wc ON tw.catalog_id=wc.id
                    LEFT JOIN branches b ON tw.branch_id=b.id
                    LEFT JOIN evaluations e ON e.entity_type='workshop' AND e.entity_id=tw.id AND e.status IN ('approved','completed')
                    GROUP BY tw.id ORDER BY avg_score DESC");
        $rows=$db->fetchAll();
        if($scope!==null){
            $branchName = (new \App\Models\Branch())->findById($scope['branch_id'])->name ?? null;
            if($branchName) $rows=array_filter($rows, fn($r)=> $r->branch_name==$branchName);
        }
        $this->view('performance/workshops', ['pageTitle'=>'ملفات أداء الورش','items'=>$rows]);
    }

    public function units() {
        Auth::enforce('reports.view.all');
        $scope=$this->getScope();
        $db=\App\Core\Database::getInstance();
        // Branches
        $db->query("SELECT b.id, b.name, 'branch' as type, COUNT(e.id) as cnt, AVG(e.final_score) as avg_score, MAX(e.final_score) as max_s, MIN(e.final_score) as min_s,
                    (SELECT AVG(final_score) FROM evaluations WHERE entity_type='employee' AND branch_name=b.name AND status IN ('approved','completed')) as emp_avg
                    FROM branches b LEFT JOIN evaluations e ON e.entity_type='branch' AND e.entity_id=b.id AND e.status IN ('approved','completed')
                    GROUP BY b.id");
        $branches=$db->fetchAll();
        // Departments
        $db->query("SELECT d.id, d.name, 'department' as type, b.name as branch_name, COUNT(e.id) as cnt, AVG(e.final_score) as avg_score,
                    (SELECT AVG(final_score) FROM evaluations WHERE entity_type='employee' AND department_name=d.name AND status IN ('approved','completed')) as emp_avg
                    FROM departments d LEFT JOIN branches b ON d.branch_id=b.id LEFT JOIN evaluations e ON e.entity_type='department' AND e.entity_id=d.id AND e.status IN ('approved','completed')
                    GROUP BY d.id");
        $depts=$db->fetchAll();
        $all=array_merge($branches,$depts);
        if($scope!==null){
            $bName=(new \App\Models\Branch())->findById($scope['branch_id'])->name ?? null;
            if($bName) $all=array_filter($all, fn($r)=> ($r->type==='branch' ? $r->name==$bName : $r->branch_name==$bName));
        }
        $this->view('performance/units', ['pageTitle'=>'ملفات أداء الوحدات','items'=>$all]);
    }

    public function employeeInCourse() {
        Auth::enforce('reports.view.all');
        $db=\App\Core\Database::getInstance();
        $db->query("SELECT e.entity_id as emp_id, emp.full_name as name, emp.emp_code, COUNT(e.id) as cnt, AVG(e.final_score) as avg_score, MAX(e.final_score) as max_s
                    FROM evaluations e JOIN employees emp ON e.entity_id=emp.id
                    WHERE e.entity_type='employee' AND e.context_type='course' AND e.status IN ('approved','completed')
                    GROUP BY e.entity_id ORDER BY avg_score DESC");
        $rows=$db->fetchAll();
        $this->view('performance/employee_in_course', ['pageTitle'=>'أداء الموظفين داخل الدورات','items'=>$rows]);
    }
}

