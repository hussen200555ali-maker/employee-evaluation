<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Branch;
use App\Models\Department;
use App\Models\RatingSetting;

class StatisticalReportController extends Controller {

    private function getScope() {
        if (Auth::userHasPermission('reports.view.all')) return null;
        $user = Auth::user();
        $empId = $user->employee_id ?? 0;
        if (!$empId) return ['branch_id'=>-1,'department_id'=>-1];
        $emp = (new \App\Models\Employee())->findById($empId);
        return ['branch_id'=> (int)($emp->branch_id ?? -1), 'department_id'=> (int)($emp->department_id ?? -1)];
    }

    private function buildWhere($entityFilter, $branchId, $deptId, $start, $end, &$bind) {
        $where = "WHERE e.status IN ('approved','completed') AND e.final_score IS NOT NULL";
        if ($entityFilter !== 'all') {
            if ($entityFilter==='employee') { $where .= " AND e.entity_type='employee' AND e.context_type IS NULL"; }
            elseif ($entityFilter==='employee_in_course') { $where .= " AND e.entity_type='employee' AND e.context_type='course'"; }
            elseif ($entityFilter==='course') { $where .= " AND e.entity_type='course'"; }
            elseif ($entityFilter==='employee_in_workshop') { $where .= " AND e.entity_type='employee' AND e.context_type='workshop'"; }
            elseif ($entityFilter==='workshop') { $where .= " AND e.entity_type='workshop'"; }
            elseif ($entityFilter==='unit') { $where .= " AND e.entity_type IN ('branch','department')"; }
        }
        if ($branchId) { $where .= " AND (e.branch_name = (SELECT name FROM branches WHERE id=:bid) OR (e.entity_type='branch' AND e.entity_id=:bid2))"; $bind[':bid']=$branchId; $bind[':bid2']=$branchId; }
        if ($deptId) { $where .= " AND (e.department_name = (SELECT name FROM departments WHERE id=:did) OR (e.entity_type='department' AND e.entity_id=:did2))"; $bind[':did']=$deptId; $bind[':did2']=$deptId; }
        if ($start) { $where .= " AND e.evaluation_date >= :sdate"; $bind[':sdate']=$start; }
        if ($end) { $where .= " AND e.evaluation_date <= :edate"; $bind[':edate']=$end; }
        return $where;
    }

    public function index() {
        Auth::can('reports.view.all');
        $branches=(new Branch())->getAll();
        $departments=(new Department())->getAll();
        $scope=$this->getScope();
        if($scope!==null){
            // filter branches/depts to scope only - manager sees only his
            $branches=array_filter($branches, fn($b)=> (int)$b->id=== $scope['branch_id']);
            $departments=array_filter($departments, fn($d)=> (int)$d->branch_id=== $scope['branch_id'] || (int)$d->id=== $scope['department_id']);
        }
        $this->view('statistics/report', [
            'pageTitle'=>'التقارير الإحصائية الرسمية',
            'branches'=>$branches,
            'departments'=>$departments
        ]);
    }

    public function data() {
        Auth::can('reports.view.all');
        $entityFilter=$_GET['entity_filter'] ?? 'all';
        $branchId=!empty($_GET['branch_id'])?(int)$_GET['branch_id']:null;
        $deptId=!empty($_GET['department_id'])?(int)$_GET['department_id']:null;
        $start=$_GET['start_date'] ?? null;
        $end=$_GET['end_date'] ?? null;
        $scope=$this->getScope();
        if($scope!==null){
            if($branchId && (int)$branchId !== $scope['branch_id']){ $this->json(['error'=>'نطاق غير مصرح'],403); return; }
            if($deptId){
                $dept=(new Department())->findById($deptId);
                if($dept && (int)$dept->branch_id !== $scope['branch_id'] && (int)$deptId !== $scope['department_id']){ $this->json(['error'=>'نطاق غير مصرح'],403); return; }
            }
        }
        $bind=[];
        $where=$this->buildWhere($entityFilter,$branchId,$deptId,$start,$end,$bind);
        $db=\App\Core\Database::getInstance();
        $ratingSettings=(new RatingSetting())->getAll();
        // Main stats
        $db->query("SELECT COUNT(*) as cnt, AVG(final_score) as avg_score, MAX(final_score) as max_score, MIN(final_score) as min_score FROM evaluations e $where");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $stats=$db->fetch();
        // Distribution - dynamic
        $cases=[];
        foreach($ratingSettings as $i=>$rs){
            $cases[]="SUM(CASE WHEN final_score >= ".(float)$rs->min_score." AND final_score <= ".(float)$rs->max_score." THEN 1 ELSE 0 END) as cat_$i";
        }
        $caseSql=$cases?implode(", ",$cases):"0 as dummy";
        $db->query("SELECT $caseSql FROM evaluations e $where");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $distRaw=$db->fetch();
        $dist=[];
        foreach($ratingSettings as $i=>$rs){
            $dist[]=['label'=>$rs->label,'color'=>$rs->color,'min'=>$rs->min_score,'max'=>$rs->max_score,'cnt'=>(int)($distRaw->{"cat_$i"}??0)];
        }
        // By entity
        $db->query("SELECT entity_type, COUNT(*) as cnt, AVG(final_score) as avg_score FROM evaluations e $where GROUP BY entity_type");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $byType=$db->fetchAll();
        // Company avg for comparison
        $db->query("SELECT AVG(final_score) as company_avg FROM evaluations WHERE status IN ('approved','completed') AND final_score IS NOT NULL");
        $company=$db->fetch();
        // Detailed grouped by branch/department
        $db->query("SELECT COALESCE(branch_name,'غير محدد') as name, COUNT(*) as cnt, AVG(final_score) as avg_score, MAX(final_score) as max_s, MIN(final_score) as min_s FROM evaluations e $where GROUP BY branch_name ORDER BY avg_score DESC");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $byBranch=$db->fetchAll();

        $this->json([
            'stats'=>['cnt'=>(int)($stats->cnt??0),'avg'=>round($stats->avg_score??0,2),'max'=>round($stats->max_score??0,2),'min'=>round($stats->min_score??0,2),'company_avg'=>round($company->company_avg??0,2)],
            'dist'=>$dist,
            'ratings'=>$ratingSettings,
            'byType'=>$byType,'byBranch'=>$byBranch
        ]);
    }

    public function exportExcel() {
        Auth::can('reports.view.all');
        $entityFilter=$_GET['entity_filter'] ?? 'all';
        $branchId=!empty($_GET['branch_id'])?(int)$_GET['branch_id']:null;
        $deptId=!empty($_GET['department_id'])?(int)$_GET['department_id']:null;
        $start=$_GET['start_date'] ?? null; $end=$_GET['end_date'] ?? null;
        $bind=[]; $where=$this->buildWhere($entityFilter,$branchId,$deptId,$start,$end,$bind);
        $db=\App\Core\Database::getInstance();
        $db->query("SELECT entity_type, employee_name as name, final_score, evaluation_date, branch_name, department_name, status FROM evaluations e $where ORDER BY evaluation_date DESC");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $rows=$db->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="statistics_'.date('Y-m-d').'.csv"');
        $out=fopen('php://output','w'); fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['النوع','الاسم','الدرجة','التاريخ','الفرع','القسم','الحالة']);
        foreach($rows as $r) fputcsv($out, [$r->entity_type,$r->name,$r->final_score,$r->evaluation_date,$r->branch_name,$r->department_name,$r->status]);
        fclose($out); exit;
    }

    public function exportPdf() {
        Auth::can('reports.view.all');
        // For now redirect to print view which has print logic
        $this->json(['message'=>'Use print view for PDF'], 200);
    }
}
