<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Auth;
use App\Models\ArchiveModel;

class ArchiveCoursesController extends Controller {

    private ArchiveModel $archiveModel;

    public function __construct() {
        $this->archiveModel = new ArchiveModel();
    }

    public function branches() {
        Auth::can('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $branches = $this->archiveModel->getCourseBranchCards($from, $to, $status, $search);
        $stats = $this->archiveModel->getCourseOverallStats($from, $to, $status);
        $pageTitle = 'أرشيف الدورات التدريبية';
        require APP_ROOT . '/app/Views/archive/courses.php';
    }

    public function departments(int $branchId) {
        Auth::can('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $departments = $this->archiveModel->getCourseDepartmentCards($branchId, $from, $to, $status, $search);
        $stats = $this->archiveModel->getCourseOverallStats($from, $to, $status);
        $pageTitle = 'أرشيف الدورات - الأقسام';
        $parentBranchId = $branchId;
        require APP_ROOT . '/app/Views/archive/courses_departments.php';
    }

    public function years(int $departmentId) {
        Auth::can('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        $years = $this->archiveModel->getCourseYearCards($departmentId, $from, $to, $status);
        $pageTitle = 'أرشيف الدورات - السنوات';
        $parentDeptId = $departmentId;
        require APP_ROOT . '/app/Views/archive/courses_years.php';
    }

    public function records(int $departmentId, int $year) {
        Auth::can('training.view');
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $records = $this->archiveModel->getCourseRecords($departmentId, $year, $from, $to, $search, $status);
        $pageTitle = 'أرشيف الدورات - السجلات';
        $parentDeptId = $departmentId;
        require APP_ROOT . '/app/Views/archive/courses_records.php';
    }

    public function show(int $courseId) {
        Auth::can('training.view');
        $evaluation = $this->archiveModel->getCourseEvaluation($courseId);
        if (!$evaluation) { echo 'لا يوجد تقييم لهذا الكيان'; return; }
        $answers = $this->archiveModel->getEvaluationAnswers($evaluation->id);
        $pageTitle = 'عرض تقييم الدورة';
        require APP_ROOT . '/app/Views/archive/show_evaluation.php';
    }
}
