<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Branch;
use App\Models\Department;

class UnifiedReportController extends Controller {

    private function getScope() {
        if (Auth::userHasPermission('reports.view.all')) return null;
        $user = Auth::user();
        $empId = $user->employee_id ?? 0;
        if (!$empId) return ['branch_id'=>-1,'department_id'=>-1];
        $emp = (new \App\Models\Employee())->findById($empId);
        return ['branch_id'=> (int)($emp->branch_id ?? -1), 'department_id'=> (int)($emp->department_id ?? -1)];
    }

    private function checkScopeAccess($branchId, $deptId) {
        $scope = $this->getScope();
        if ($scope === null) return true;
        if ($branchId && (int)$branchId !== $scope['branch_id']) return false;
        if ($deptId && (int)$deptId !== $scope['department_id'] && (int)$branchId !== $scope['branch_id']) {
            // allow if dept belongs to branch scope? check dept's branch
            $dept = (new Department())->findById($deptId);
            if ($dept && (int)$dept->branch_id !== $scope['branch_id']) return false;
        }
        return true;
    }

    public function index() {
        Auth::enforce('reports.view.all');
        // Scope check for filtered requests will be done in data method, index shows all filters but limited
        $branches = (new Branch())->getAll();
        $departments = (new Department())->getAll();
        $this->view('reports/unified', [
            'pageTitle'=>'التقارير الموحدة - لوحة القيادة',
            'branches'=>$branches,
            'departments'=>$departments
        ]);
    }

    public function getData() {
        Auth::enforce('reports.view.all');
        $entityType = $_GET['entity_type'] ?? 'all';
        $branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
        $deptId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;

        if (!$this->checkScopeAccess($branchId, $deptId)) {
            $this->json(['error'=>'نطاق غير مصرح'], 403); return;
        }

        $db = \App\Core\Database::getInstance();

        // Build base WHERE for evaluations (approved/completed only - excludes expired/archived)
        $where = "WHERE e.status IN ('approved','completed') AND e.final_score IS NOT NULL";
        $bind = [];
        if ($entityType !== 'all') {
            $where .= " AND e.entity_type = :etype";
            $bind[':etype']=$entityType;
        }
        if ($branchId) { $where .= " AND (e.branch_name = (SELECT name FROM branches WHERE id=:bid) OR e.entity_type='branch' AND e.entity_id=:bid2)"; $bind[':bid']=$branchId; $bind[':bid2']=$branchId; }
        if ($deptId) { $where .= " AND (e.department_name = (SELECT name FROM departments WHERE id=:did) OR e.entity_type='department' AND e.entity_id=:did2)"; $bind[':did']=$deptId; $bind[':did2']=$deptId; }
        if ($start) { $where .= " AND e.evaluation_date >= :sdate"; $bind[':sdate']=$start; }
        if ($end) { $where .= " AND e.evaluation_date <= :edate"; $bind[':edate']=$end; }

        // Radar: AVG per section
        $db->query("SELECT cs.section_name, AVG(ea.normalized_score) as avg_score FROM evaluation_answers ea JOIN evaluations e ON ea.evaluation_id=e.id JOIN criteria_sections cs ON ea.section_id=cs.id $where GROUP BY cs.id ORDER BY AVG(ea.normalized_score) DESC LIMIT 12");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $radar = $db->fetchAll();

        // Line: timeline monthly
        $db->query("SELECT DATE_FORMAT(e.evaluation_date,'%Y-%m') as m, AVG(e.final_score) as avg_score, COUNT(*) as cnt FROM evaluations e $where GROUP BY m ORDER BY m ASC");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $line = $db->fetchAll();

        // Cross-analysis: unit vs employees inside
        $cross = [];
        if (!$entityType || $entityType==='all' || $entityType==='branch' || $entityType==='department') {
            $units = $db->query("SELECT id, name, branch_id FROM departments UNION SELECT id, name, NULL as branch_id FROM branches")->fetchAll() ?? [];
            // For each unit with evaluations, compute unitAvg vs employeeAvg
            $branches = (new Branch())->getAll();
            foreach($branches as $b){
                $db->query("SELECT AVG(final_score) as unit_avg FROM evaluations WHERE entity_type='branch' AND entity_id=:bid AND status IN ('approved','completed')");
                $db->bind(':bid',$b->id);
                $unitAvg = $db->fetch()->unit_avg ?? null;
                if($unitAvg===null) continue;
                $db->query("SELECT AVG(final_score) as emp_avg FROM evaluations WHERE entity_type='employee' AND branch_name=:bname AND status IN ('approved','completed')");
                $db->bind(':bname',$b->name);
                $empAvg=$db->fetch()->emp_avg ?? 0;
                $cross[]=['unit_name'=>$b->name,'unit_type'=>'branch','unit_avg'=>round($unitAvg,2),'emp_avg'=>round($empAvg,2),'diff'=>round($unitAvg-$empAvg,2)];
            }
            // departments
            $depts=(new Department())->getAll();
            foreach($depts as $d){
                $db->query("SELECT AVG(final_score) as unit_avg FROM evaluations WHERE entity_type='department' AND entity_id=:did AND status IN ('approved','completed')");
                $db->bind(':did',$d->id);
                $unitAvg=$db->fetch()->unit_avg ?? null;
                if($unitAvg===null) continue;
                $db->query("SELECT AVG(final_score) as emp_avg FROM evaluations WHERE entity_type='employee' AND department_name=:dname AND status IN ('approved','completed')");
                $db->bind(':dname',$d->name);
                $empAvg=$db->fetch()->emp_avg ?? 0;
                $cross[]=['unit_name'=>$d->name,'unit_type'=>'department','unit_avg'=>round($unitAvg,2),'emp_avg'=>round($empAvg,2),'diff'=>round($unitAvg-$empAvg,2)];
            }
        }

        // Summary counts by type
        $db->query("SELECT entity_type, COUNT(*) as cnt, AVG(final_score) as avg_score FROM evaluations WHERE status IN ('approved','completed') GROUP BY entity_type");
        $byType=$db->fetchAll();

        $this->json(['radar'=>$radar,'line'=>$line,'cross'=>$cross,'byType'=>$byType]);
    }

    public function export() {
        Auth::enforce('reports.view.all');
        $entityType = $_GET['entity_type'] ?? 'all';
        $where="WHERE status IN ('approved','completed')";
        $bind=[];
        if($entityType!=='all'){ $where.=" AND entity_type=:et"; $bind[':et']=$entityType; }
        $db=\App\Core\Database::getInstance();
        $db->query("SELECT entity_type, entity_id, employee_name as name, final_score, evaluation_date, branch_name, department_name FROM evaluations $where ORDER BY evaluation_date DESC");
        foreach($bind as $k=>$v) $db->bind($k,$v);
        $rows=$db->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="unified_report_'.date('Y-m-d').'.csv"');
        $out=fopen('php://output','w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['النوع','الاسم','الدرجة','التاريخ','الفرع','القسم']);
        foreach($rows as $r) fputcsv($out, [$r->entity_type,$r->name,$r->final_score,$r->evaluation_date,$r->branch_name,$r->department_name]);
        fclose($out); exit;
    }
}

