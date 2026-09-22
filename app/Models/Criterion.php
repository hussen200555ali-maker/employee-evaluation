<?php
namespace App\Models;

use App\Core\Model;

/**
 * مودل المعيار (Criterion)
 */
class Criterion extends Model {

    /**
     * يجلب كل المعايير الخاصة بقالب معين.
     */
    public function findByTemplateId($template_id) {
        $this->db->query("SELECT * FROM criteria WHERE template_id = :template_id ORDER BY order_index ASC");
        $this->db->bind(':template_id', $template_id);
        return $this->db->fetchAll();
    }

    /**
     * ينشئ معياراً جديداً.
     */
    public function create($data) {
        $this->db->query('INSERT INTO criteria (template_id, name, weight_percent) VALUES (:template_id, :name, :weight_percent)');
        $this->db->bind(':template_id', $data['template_id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':weight_percent', $data['weight_percent']);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
     /**
     * يجلب معياراً واحداً بواسطة ID.
     * (سنحتاجه لجلب البيانات قبل التعديل أو الحذف).
     */
    public function findById($id) {
        $this->db->query("SELECT * FROM criteria WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    /**
     * يحدّث بيانات معيار موجود.
     *
     * @param array $data - مصفوفة تحتوي على بيانات المعيار (id, name, weight_percent).
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function update($data) {
        $this->db->query('UPDATE criteria SET name = :name, weight_percent = :weight_percent WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':weight_percent', $data['weight_percent']);
        return $this->db->execute();
    }

    /**
     * يحذف معياراً من قاعدة البيانات.
     *
     * @param int $id - معرف المعيار المراد حذفه.
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function delete($id) {
        // (لاحقاً، يجب التحقق من عدم وجود أقسام فرعية مرتبطة بالمعيار قبل حذفه)
        $this->db->query('DELETE FROM criteria WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
      /**
     * يجلب كل الأقسام الفرعية الخاصة بمعيار معين.
     *
     * @param int $criterion_id - معرف المعيار.
     * @return array - مصفوفة من كائنات الأقسام.
     */
    public function getSections($criterion_id) {
        $this->db->query("SELECT * FROM criteria_sections WHERE criterion_id = :criterion_id ORDER BY order_index ASC");
        $this->db->bind(':criterion_id', $criterion_id);
        return $this->db->fetchAll();
    }

    /**
     * ينشئ قسماً فرعياً جديداً.
     *
     * @param array $data - بيانات القسم.
     * @return mixed - ID القسم الجديد أو false.
     */
    public function addSection($data) {
        $sql = "INSERT INTO criteria_sections (criterion_id, name, input_type, options_json, max_value, weight_percent_within_criterion, has_notes, notes_required) 
                VALUES (:criterion_id, :name, :input_type, :options_json, :max_value, :weight_percent_within_criterion, :has_notes, :notes_required)";
        
        $this->db->query($sql);
        $this->db->bind(':criterion_id', $data['criterion_id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':input_type', $data['input_type']);
        $this->db->bind(':options_json', $data['options_json']);
        $this->db->bind(':max_value', $data['max_value']);
        $this->db->bind(':weight_percent_within_criterion', $data['weight_percent']);
        $this->db->bind(':has_notes', $data['has_notes']);
        $this->db->bind(':notes_required', $data['notes_required']);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * يحذف قسماً فرعياً.
     *
     * @param int $section_id - معرف القسم.
     * @return bool
     */
    public function deleteSection($section_id) {
        $this->db->query("DELETE FROM criteria_sections WHERE id = :id");
        $this->db->bind(':id', $section_id);
        return $this->db->execute();
    }
    
    /**
     * يجلب قسماً واحداً بواسطة ID (للتدقيق).
     */
    public function findSectionById($section_id) {
        $this->db->query("SELECT * FROM criteria_sections WHERE id = :id");
        $this->db->bind(':id', $section_id);
        return $this->db->fetch();
    }
 /**
     * يحدّث بيانات قسم فرعي موجود.
     *
     * @param array $data - بيانات القسم.
     * @return bool
     */
    public function updateSection($data) {
        $sql = "UPDATE criteria_sections SET 
                    name = :name, 
                    input_type = :input_type, 
                    options_json = :options_json, 
                    max_value = :max_value, 
                    weight_percent_within_criterion = :weight_percent, 
                    has_notes = :has_notes, 
                    notes_required = :notes_required 
                WHERE id = :id";
        
        $this->db->query($sql);
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':input_type', $data['input_type']);
        $this->db->bind(':options_json', $data['options_json']);
        $this->db->bind(':max_value', $data['max_value']);
        $this->db->bind(':weight_percent', $data['weight_percent']);
        $this->db->bind(':has_notes', $data['has_notes']);
        $this->db->bind(':notes_required', $data['notes_required']);

        return $this->db->execute();
    }

  /**
     * ==================================================
     *           ===>  أضف الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * تجلب جميع المعايير مع اسم القالب الذي تنتمي إليه.
     * ضرورية لصفحة بناء التقارير.
     *
     * @return array
     */
    public function getAllWithTemplateName() {
        $sql = "SELECT 
                    c.id, 
                    c.criterion_name, 
                    ft.template_name 
                FROM 
                    criteria c
                JOIN 
                    form_templates ft ON c.template_id = ft.id
                ORDER BY 
                    ft.template_name, c.criterion_name";
        
        $this->db->query($sql);
        return $this->db->fetchAll();
    }


  }
