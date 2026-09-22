<?php
// في ملف /app/Controllers/ApiController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Department; // استيراد مودل الأقسام
use App\Models\Employee;
use App\Models\Template; // استيراد مودل القوالب

class ApiController extends Controller {

    /**
     * يجلب الأقسام التابعة لفرع معين ويعيدها كـ JSON.
     *
     * @param int $branchId
     */
    public function getDepartmentsByBranch($branchId) {
        // التأكد من أن الطلب آمن (يمكن إضافة تحقق من تسجيل الدخول هنا)
        // \App\Core\Auth::handle();

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
