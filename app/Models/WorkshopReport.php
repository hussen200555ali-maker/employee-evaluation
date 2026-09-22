<?php
// ملف: app/Models/WorkshopReport.php

namespace App\Models;

use App\Core\Model;

class WorkshopReport extends Model {

    /**
     * يجلب بيانات التقرير لفرع معين.
     * @param int $branchId
     * @return array|null
     */
    public function getBranchReport($branchId) {
        // 1. جلب معلومات الفرع
        $this->db->query("SELECT id, name FROM branches WHERE id = :id");
        $this->db->bind(':id', $branchId);
        $branch = $this->db->fetch();
        if (!$branch) return null;

        // 2. جلب الأقسام التابعة لهذا الفرع والتي لديها ورش
        $departmentModel = new Department();
        $departments = $departmentModel->getWithWorkshopStats($branchId);
        if (empty($departments)) return null;

        // 3. جلب كل الورش لهذه الأقسام
        $workshopModel = new TrainingWorkshop();
        foreach ($departments as $dept) {
            $dept->workshops = $workshopModel->getByDepartmentWithEvaluations($dept->id);
        }

        // 4. بناء هيكل البيانات النهائي
        return [
            'title' => 'تقرير الورش للفرع: ' . $branch->name,
            'branches' => [
                [
                    'name' => $branch->name,
                    'departments' => $departments
                ]
            ]
        ];
    }

    /**
     * يجلب بيانات التقرير لجميع الفروع (التقرير الشامل).
     * @return array
     */
    public function getCompanyReport() {
        $workshopModel = new TrainingWorkshop();
        $branches = $workshopModel->countByBranchWithIds();
        $reportData = [];

        foreach ($branches as $branch) {
            $branchReport = $this->getBranchReport($branch->id);
            if ($branchReport) {
                $reportData[] = $branchReport['branches'][0];
            }
        }

        return [
            'title' => 'التقرير الشامل للورش التدريبية',
            'branches' => $reportData
        ];
    }
}
