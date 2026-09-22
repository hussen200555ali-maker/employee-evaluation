<?php

namespace App\Models;

use App\Core\Model;
use PDOException;

/**
 * ==================================================
 *  ===>   كلاس ReportTemplateModel (نسخة محدّثة)  <===
 * ==================================================
 * 
 * هذا الكلاس يتوافق تمامًا مع بنية الجداول التي قدمتها.
 */
class ReportTemplateModel extends Model {

    /**
     * يحفظ إعدادات التقرير الحالية كقالب جديد في قاعدة البيانات.
     *
     * @param array $state - حالة التقرير الكاملة التي تحتوي على كل الإعدادات.
     * @return int - معرف القالب الجديد الذي تم إدراجه.
     * @throws \Exception - في حالة فشل أي خطوة من خطوات الحفظ.
     */
    public function saveTemplate(array $state): int {
        // استخراج البيانات الأساسية من الحالة
        $reportTitle = $state['report_title'];
        $coursesWeight = $state['courses_weight'];
        $workshopsWeight = $state['workshops_weight'];
        $employeeColumns = $state['employee_columns'];

        // بدء Transaction لضمان حفظ كل البيانات معًا أو لا شيء على الإطلاق
        $this->db->beginTransaction();

        try {
            // 1. حفظ البيانات الأساسية للقالب في جدول `report_templates`
            $sql1 = "INSERT INTO report_templates (template_name, courses_weight, workshops_weight) 
                     VALUES (:template_name, :courses_weight, :workshops_weight)";
            
            $this->db->query($sql1);
            $this->db->bind(':template_name', $reportTitle);
            $this->db->bind(':courses_weight', $coursesWeight);
            $this->db->bind(':workshops_weight', $workshopsWeight);
            $this->db->execute();
            
            $templateId = $this->db->lastInsertId();

            if (!$templateId) {
                throw new \Exception("فشل في الحصول على معرف القالب الجديد.");
            }

            // 2. التحقق من وجود أعمدة لحفظها
            if (!empty($employeeColumns)) {
                // 3. إعداد استعلام لإدراج أعمدة التقييم المرتبطة بالقالب
                $sql2 = "INSERT INTO report_template_columns (report_template_id, source_type, source_id, weight, order_index) 
                         VALUES (:report_template_id, :source_type, :source_id, :weight, :order_index)";
                
                // 4. المرور على كل عمود، تحليله، وحفظه
                foreach ($employeeColumns as $index => $column) {
                    // فصل المصدر إلى نوع ومعرف
                    list($sourceType, $sourceId) = explode('-', $column['source']);

                    $this->db->query($sql2); // إعادة تهيئة الاستعلام لكل عملية إدراج
                    $this->db->bind(':report_template_id', $templateId);
                    $this->db->bind(':source_type', $sourceType); // 'template' or 'criterion'
                    $this->db->bind(':source_id', (int)$sourceId);
                    $this->db->bind(':weight', $column['weight']);
                    $this->db->bind(':order_index', $index); // استخدام مؤشر الحلقة لترتيب الأعمدة
                    $this->db->execute();
                }
            }

            // 5. إذا نجحت كل العمليات، قم بتأكيد الحفظ (Commit)
            $this->db->commit();

            return (int)$templateId;

        } catch (PDOException $e) {
            // 6. في حالة حدوث أي خطأ، تراجع عن كل التغييرات (Rollback)
            $this->db->rollBack();
            // إلقاء استثناء جديد مع رسالة واضحة للمطور
            throw new \Exception("فشل حفظ القالب في قاعدة البيانات: " . $e->getMessage());
        }
    }
}
