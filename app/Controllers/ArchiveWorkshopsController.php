<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Auth;
use App\Models\ArchiveModel;

class ArchiveWorkshopsController extends Controller {

    private ArchiveModel $archiveModel;

    public function __construct() {
        $this->archiveModel = new ArchiveModel();
    }

    public function branches() {
        Auth::enforce('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $branches = $this->archiveModel->getWorkshopBranchCards($from, $to, $status, $search);
        $stats = $this->archiveModel->getWorkshopOverallStats($from, $to, $status);
        $pageTitle = 'أرشيف الورش التدريبية';
        require APP_ROOT . '/app/Views/archive/workshops.php';
    }

    public function departments(int $branchId) {
        Auth::enforce('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $departments = $this->archiveModel->getWorkshopDepartmentCards($branchId, $from, $to, $status, $search);
        $stats = $this->archiveModel->getWorkshopOverallStats($from, $to, $status);
        $pageTitle = 'أرشيف الورش - الأقسام';
        $parentBranchId = $branchId;
        require APP_ROOT . '/app/Views/archive/workshops_departments.php';
    }

    public function years(int $departmentId) {
        Auth::enforce('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $years = $this->archiveModel->getWorkshopYearCards($departmentId, $from, $to, $status);
        $pageTitle = 'أرشيف الورش - السنوات';
        $parentDeptId = $departmentId;
        require APP_ROOT . '/app/Views/archive/workshops_years.php';
    }

    public function records(int $departmentId, int $year) {
        Auth::enforce('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $records = $this->archiveModel->getWorkshopRecords($departmentId, $year, $from, $to, $search, $status);
        $pageTitle = 'أرشيف الورش - السجلات';
        $parentDeptId = $departmentId;
        require APP_ROOT . '/app/Views/archive/workshops_records.php';
    }

    public function show(int $workshopId) {
        Auth::enforce('training.view');
        $evaluation = $this->archiveModel->getWorkshopEvaluation($workshopId);
        if (!$evaluation) { echo 'لا يوجد تقييم لهذا الكيان'; return; }
        $answers = $this->archiveModel->getEvaluationAnswers($evaluation->id);
        $pageTitle = 'عرض تقييم الورشة';
        require APP_ROOT . '/app/Views/archive/show_evaluation.php';
    }
}

