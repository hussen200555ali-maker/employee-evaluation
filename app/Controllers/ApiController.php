<?php
// في ملف /app/Controllers/ApiController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth; // CD-026: تأمين نقاط النهاية
use App\Models\Department; // استيراد مودل الأقسام
use App\Models\Employee;
use App\Models\Template; // استيراد مودل القوالب

class ApiController extends Controller {

    /**
     * CD-026: المصادقة إلزامية لكل نقاط النهاية.
     * منع الوصول غير المصرح به لبيانات الموظفين والأقسام والقوالب.
     */
    public function __construct() {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح — تسجيل الدخول مطلوب'], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    /**
     * يجلب الأقسام التابعة لفرع معين ويعيدها كـ JSON.
     *
     * @param int $branchId
     */
    public function getDepartmentsByBranch($branchId) {

        $departmentModel = new Department();
        $departments = $departmentModel->findByBranch($branchId);

        // إعداد الهيدر لإعلام المتصفح أن الاستجابة هي JSON
        header('Content-Type: application/json');
        // طباعة البيانات بصيغة JSON
        echo json_encode($departments);
        exit(); // إيقاف التنفيذ بعد إرسال الاستجابة
    }

     /**
     * =================================================================
     * دالة جديدة: للبحث عن الموظفين (للإكمال التلقائي)
     * =================================================================
     */
    public function searchEmployees() {
        $query = $_GET['q'] ?? '';

        $employeeModel = new Employee();
        // سنحتاج لإضافة دالة search في مودل الموظفين
        $employees = $employeeModel->search($query);

        header('Content-Type: application/json');
        echo json_encode($employees);
        exit();
    }

    /**
     * CD-040.2: قائمة البرامج التدريبية (دورات/ورش) للاستخدام في إنشاء التقييم.
     * مسار: GET /api/training-programs/list/:type
     * نقله من TrainingProgramController لأن ذاك يفرض training.view على كل طرقه،
     * بينما المُقيِّم يحتاجه إنشاء التقييم فقط (evaluations.create).
     */
    public function trainingProgramsList($type = 'course') {
        Auth::enforce('evaluations.create');
        $type = ($type === 'workshop') ? 'workshop' : 'course';
        $this->json((new \App\Models\TrainingProgram())->all($type));
    }

    /**
     * CD-041: قائمة المناصب المتاحة (JSON) للاستخدام في الإضافة السريعة.
     * GET /api/positions
     */
    public function positions() {
        Auth::enforce('evaluations.create');
        $db = \App\Core\Database::getInstance();
        $db->query('SELECT id, title FROM positions ORDER BY title');
        $this->json($db->fetchAll());
    }

    /**
     * CD-041.5: تحقق من وجود تقييم مسبق (موظف + قالب + تاريخ + سياق).
     * GET /api/evaluations/exists?employee_id=&template_id=&date=&context_id=&context_type=
     */
    public function evaluationExists() {
        Auth::enforce('evaluations.create');
        $empId = (int)($_GET['employee_id'] ?? 0);
        $tplId = (int)($_GET['template_id'] ?? 0);
        $date  = trim((string)($_GET['date'] ?? ''));
        $ctxId = (int)($_GET['context_id'] ?? 0);
        $ctxType = trim((string)($_GET['context_type'] ?? ''));
        if ($empId < 1 || $tplId < 1 || $date === '') {
            $this->json(['exists' => false, 'evaluation_id' => null]);
            return;
        }
        $id = (new \App\Models\Evaluation())->findExistingForEmployeeTemplateDate($empId, $tplId, $date, $ctxId, $ctxType);
        $this->json(['exists' => $id !== null, 'evaluation_id' => $id]);
    }

    /**
     * =================================================================
     * دالة جديدة: لجلب القوالب بناءً على منصب الموظف
     * =================================================================
     */
    public function getTemplatesByPosition($positionId) {
        $templateModel = new Template();
        $templates = $templateModel->findByPosition($positionId);
        header('Content-Type: application/json');
        echo json_encode($templates);
        exit();
    }

    public function getTemplatesByScope($scope) {
        $templateModel = new Template();
        $scope = trim($scope);
        $allowed = ['employee','employee_in_course','employee_in_workshop','course','workshop','unit'];
        if (!in_array($scope, $allowed)) {
            $this->json(['error'=>'Invalid scope'], 400); return;
        }
        $unitScope = $_GET['unit_scope'] ?? null;
        if ($scope==='unit' && $unitScope && !in_array($unitScope, ['branch','department'])) $unitScope=null;
        $templates = $templateModel->findByScope($scope, $unitScope);
        header('Content-Type: application/json');
        echo json_encode($templates);
        exit();
    }
     /**
     * يستقبل تاريخاً ميلادياً ويُرجع مقابله الهجري كـ JSON.
     */
    public function toHijri()
    {
        // التأكد من أن الطلب يحتوي على التاريخ
        if (!isset($_GET['date']) || empty($_GET['date'])) {
            // إرجاع خطأ إذا لم يتم إرسال التاريخ
            $this->json(['error' => 'Date parameter is missing.'], 400);
            return;
        }

        $gregorianDate = $_GET['date'];
        
        // استخدام دالة التحويل المساعدة
        $hijriData = convertToHijri($gregorianDate);

        if ($hijriData) {
            // إرجاع البيانات بنجاح
            $this->json(['hijri' => $hijriData->full_date]);
        } else {
            // إرجاع خطأ إذا كان التاريخ غير صالح
            $this->json(['error' => 'Invalid date format.'], 400);
        }
    }
}
