<?php
// /app/Models/Template.php

namespace App\Models;

use App\Core\Model;
use PDO;

class Template extends Model {

    // ... (دالة getAll() ودالة getTemplateWithStructure() تبقى كما هي) ...
    public function getAll() {
        $sql = "SELECT 
                    t.*, 
                    u.username as created_by_name,
                    (SELECT COUNT(*) FROM criteria WHERE template_id = t.id) as criteria_count
                FROM form_templates t
                LEFT JOIN users u ON t.created_by = u.id
                ORDER BY t.created_at DESC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

  // في ملف app/Models/Template.php

public function getTemplateWithStructure($id) {
    // --- الخطوة 1: جلب البيانات من قاعدة البيانات ككائنات (Objects) ---

    // جلب القالب الرئيسي
    $this->db->query("SELECT * FROM form_templates WHERE id = :id");
    $this->db->bind(':id', $id);
    $template = $this->db->fetch(); // يعيد كائن stdClass

    if (!$template) {
        return false;
    }

    // جلب المعايير
    $this->db->query("SELECT * FROM criteria WHERE template_id = :id ORDER BY order_index ASC");
    $this->db->bind(':id', $id);
    $criteria = $this->db->fetchAll(); // يعيد مصفوفة من كائنات stdClass

    // --- الخطوة 2: المرور على كل معيار (وهو الآن كائن) لجلب أقسامه ---
    foreach ($criteria as $criterion) { // $criterion هو كائن
        $this->db->query("SELECT * FROM criteria_sections WHERE criterion_id = :criterion_id ORDER BY order_index ASC");
        
        // ===> الإصلاح 1: استخدام السهم (->) للوصول إلى خصائص الكائن <===
        $this->db->bind(':criterion_id', $criterion->id); 
        
        $sections = $this->db->fetchAll(); // مصفوفة من كائنات الأقسام
        
        // إضافة الأقسام إلى كائن المعيار
        $criterion->sections = $sections;
    }

    // إضافة هيكل المعايير الكامل إلى كائن القالب
    $template->criteria = $criteria;

    // --- الخطوة 3: تحويل الكائن المعقد بأكمله إلى مصفوفة (Array) ---
    // هذه هي الخدعة التي تضمن أن ما يخرج من هذه الدالة هو دائمًا مصفوفة،
    // وهو ما تتوقعه بقية أجزاء التطبيق (الكنترولر والواجهة).
    $finalArray = json_decode(json_encode($template), true);

    return $finalArray;
}


    /**
     * =================================================================
     * دالة إنشاء قالب جديد مع هيكله الكامل (النسخة النهائية)
     * =================================================================
     * @param array $templateData - البيانات الأساسية للقالب (بما في ذلك target_position_id).
     * @param array $criteriaStructure - الهيكل المنظم للمعايير والأقسام.
     * @return mixed - ID القالب الجديد عند النجاح, أو false عند الفشل.
     */// في ملف app/Models/Template.php

    /**
     * =================================================================
     * دالة إنشاء قالب جديد مع هيكله الكامل (النسخة النهائية والمؤكدة)
     * =================================================================
     */
// في ملف: app/Models/Template.php

 public function createTemplateWithStructure($templateData, $criteriaStructure) {
    $this->db->beginTransaction();
    try {
        // --- 1. إدخال بيانات القالب الأساسية (بالأسماء الصحيحة) ---
        $sql_template = "INSERT INTO form_templates 
                            (template_name, template_description, target_position_id, target_type, target_entity_id, unit_scope, is_active, created_by, updated_by) 
                         VALUES 
                            (:name, :desc, :pos_id, :ttype, :tent, :uscope, :active, :creator_id, :updater_id)";
        
        $this->db->query($sql_template);
        
        // ربط كل متغير باسمه الفريد في الاستعلام
        $this->db->bind(':name', $templateData['template_name']);
        $this->db->bind(':desc', $templateData['template_description']);
        $this->db->bind(':pos_id', $templateData['target_position_id']);
        $this->db->bind(':ttype', $templateData['target_type'] ?? 'employee');
        $this->db->bind(':tent', $templateData['target_entity_id'] ?? null);
        $this->db->bind(':uscope', $templateData['unit_scope'] ?? null);
        $this->db->bind(':active', $templateData['is_active']);
        $this->db->bind(':creator_id', $templateData['user_id']); // <-- استخدام اسم فريد
        $this->db->bind(':updater_id', $templateData['user_id']); // <-- استخدام اسم فريد
        
        $this->db->execute();
        $templateId = $this->db->lastInsertId();

        // --- 2. حفظ الهيكل (الكود التالي صحيح ولا يحتاج لتغيير) ---
        if (!empty($criteriaStructure)) {
            foreach ($criteriaStructure as $cIndex => $criterionData) {
                $sql_criterion = "INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index) 
                                  VALUES (:tid, :c_name, :c_desc, :c_weight, :c_order)";
                $this->db->query($sql_criterion);
                $this->db->bind(':tid', $templateId);
                $this->db->bind(':c_name', $criterionData['name']);
                $this->db->bind(':c_desc', $criterionData['description']);
                $this->db->bind(':c_weight', $criterionData['weight']);
                $this->db->bind(':c_order', $cIndex + 1);
                $this->db->execute();
                $criterionId = $this->db->lastInsertId();

                if (!empty($criterionData['sections'])) {
                    foreach ($criterionData['sections'] as $sIndex => $sectionData) {
                        $formulaConfig = null;
                        if (!empty($sectionData['formula']) || !empty($sectionData['auto_source'])) {
                            $formulaConfig = json_encode([
                                'type' => $sectionData['type'] ?? 'formula',
                                'formula' => $sectionData['formula'] ?? '',
                                'display' => $sectionData['formula_display'] ?? '',
                                'readonly' => (int)($sectionData['readonly'] ?? 0),
                                'auto_source' => $sectionData['auto_source'] ?? null
                            ], JSON_UNESCAPED_UNICODE);
                        }
                        $sql_section = "INSERT INTO criteria_sections (criterion_id, section_name, section_description, evaluation_type, evaluation_options, section_weight, order_index, min_value, max_value, allow_notes, notes_required, notes_placeholder, formula_config) 
                                        VALUES (:cid, :s_name, :s_desc, :s_type, :s_options, :s_weight, :s_order, :s_min, :s_max, :s_allow_n, :s_req_n, :s_placeholder, :s_formula)";
                        $this->db->query($sql_section);
                        $this->db->bind(':cid', $criterionId);
                        $this->db->bind(':s_name', $sectionData['name']);
                        $this->db->bind(':s_desc', $sectionData['description']);
                        $this->db->bind(':s_type', $sectionData['type']);
                        $this->db->bind(':s_options', json_encode($sectionData['options'] ?? [], JSON_UNESCAPED_UNICODE));
                        $this->db->bind(':s_weight', $sectionData['weight']);
                        $this->db->bind(':s_order', $sIndex + 1);
                        $this->db->bind(':s_min', $sectionData['min_value']);
                        $this->db->bind(':s_max', $sectionData['max_value']);
                        $this->db->bind(':s_allow_n', $sectionData['allow_notes']);
                        $this->db->bind(':s_req_n', $sectionData['notes_required']);
                        $this->db->bind(':s_placeholder', $sectionData['notes_placeholder']);
                        $this->db->bind(':s_formula', $formulaConfig);
                        $this->db->execute();
                    }
                }
            }
        }

        $this->db->commit();
        return $templateId;

    } catch (\PDOException $e) { // نلتقط خطأ PDO تحديداً
        $this->db->rollBack();
        // أعد كود التصحيح المؤقت لإظهار الخطأ بوضوح
        die("<h1>خطأ في قاعدة البيانات</h1><p>الرسالة: " . $e->getMessage() . "</p><pre>" . $e->getTraceAsString() . "</pre>");
    }
}


    /**
     * =================================================================
     * دالة تحديث قالب موجود مع كامل هيكله (النسخة النهائية والمؤكدة)
     * =================================================================
     */
  
    /**
     * =================================================================
     * دالة تحديث قالب موجود مع كامل هيكله (النسخة النهائية)
     * =================================================================
     * @param int $templateId - معرف القالب المراد تحديثه.
     * @param array $templateData - البيانات الأساسية الجديدة للقالب.
     * @param array $criteriaStructure - الهيكل الكامل الجديد للمعايير والأقسام.
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function updateTemplateWithStructure($templateId, $templateData, $criteriaStructure) {
        $this->db->beginTransaction();
        try {
            // --- 1. تحديث بيانات القالب الأساسية ---
            $sql = "UPDATE form_templates SET 
                        template_name = :name, 
                        template_description = :desc, 
                        target_position_id = :pos_id,
                        target_type = :ttype,
                        target_entity_id = :tent,
                        unit_scope = :uscope,
                        is_active = :active, 
                        updated_by = :updater, 
                        version = version + 1 
                    WHERE id = :id";
            
            $this->db->query($sql);
            $this->db->bind(':id', $templateId);
            $this->db->bind(':name', $templateData['template_name']);
            $this->db->bind(':desc', $templateData['template_description']);
            $this->db->bind(':pos_id', $templateData['target_position_id']);
            $this->db->bind(':ttype', $templateData['target_type'] ?? 'employee');
            $this->db->bind(':tent', $templateData['target_entity_id'] ?? null);
            $this->db->bind(':uscope', $templateData['unit_scope'] ?? null);
            $this->db->bind(':active', $templateData['is_active']);
            $this->db->bind(':updater', $templateData['user_id']);
            $this->db->execute();

            // --- 2. حذف كل المعايير القديمة المرتبطة بالقالب ---
            // هذا سيؤدي إلى حذف كل أقسامها تلقائياً بفضل إعداد "ON DELETE CASCADE" في قاعدة البيانات.
            $this->db->query("DELETE FROM criteria WHERE template_id = :template_id");
            $this->db->bind(':template_id', $templateId);
            $this->db->execute();

            // --- 3. إعادة بناء الهيكل من جديد باستخدام البيانات المحدثة ---
            // نستدعي الدالة المساعدة التي تحتوي على منطق الحفظ الصحيح.
            $this->saveStructure($templateId, $criteriaStructure);

            // تأكيد الحفظ النهائي.
            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            // تسجيل الخطأ في سجلات الخادم بدلاً من عرضه للمستخدم
            error_log("فشل تحديث القالب (ID: {$templateId}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * =================================================================
     * دالة مساعدة داخلية لحفظ هيكل المعايير والأقسام
     * =================================================================
     * تم استخلاص هذا المنطق في دالة منفصلة لأنه مشترك بين الإنشاء والتحديث.
     * @param int $templateId
     * @param array $criteriaStructure
     */
    private function saveStructure($templateId, $criteriaStructure) {
        if (empty($criteriaStructure)) {
            return; // لا تفعل شيئاً إذا كان الهيكل فارغاً
        }

        // المرور على كل معيار في الهيكل
        foreach ($criteriaStructure as $cIndex => $criterionData) {
            $sql_criterion = "INSERT INTO criteria (template_id, criterion_name, criterion_description, criterion_weight, order_index) 
                              VALUES (:tid, :c_name, :c_desc, :c_weight, :c_order)";
            $this->db->query($sql_criterion);
            $this->db->bind(':tid', $templateId);
            $this->db->bind(':c_name', $criterionData['name']);
            $this->db->bind(':c_desc', $criterionData['description']);
            $this->db->bind(':c_weight', $criterionData['weight']);
            $this->db->bind(':c_order', $cIndex + 1);
            $this->db->execute();
            $criterionId = $this->db->lastInsertId();

            // المرور على كل قسم داخل المعيار وحفظه
            if (!empty($criterionData['sections'])) {
                foreach ($criterionData['sections'] as $sIndex => $sectionData) {
                    // بناء formula_config JSON
                    $formulaConfig = null;
                    if (!empty($sectionData['formula']) || !empty($sectionData['auto_source'])) {
                        $formulaConfig = json_encode([
                            'type' => $sectionData['type'],
                            'formula' => $sectionData['formula'] ?? null,
                            'display' => $sectionData['formula_display'] ?? null,
                            'readonly' => (bool)($sectionData['readonly'] ?? false),
                            'auto_source' => $sectionData['auto_source'] ?? null
                        ], JSON_UNESCAPED_UNICODE);
                    }
                    
                    $sql_section = "INSERT INTO criteria_sections 
                                        (criterion_id, section_name, section_description, evaluation_type, evaluation_options, section_weight, order_index, min_value, max_value, allow_notes, notes_required, notes_placeholder, formula_config) 
                                    VALUES 
                                        (:cid, :s_name, :s_desc, :s_type, :s_options, :s_weight, :s_order, :s_min, :s_max, :s_allow_n, :s_req_n, :s_placeholder, :s_formula)";
                    $this->db->query($sql_section);
                    $this->db->bind(':cid', $criterionId);
                    $this->db->bind(':s_name', $sectionData['name']);
                    $this->db->bind(':s_desc', $sectionData['description']);
                    $this->db->bind(':s_type', $sectionData['type']);
                    $this->db->bind(':s_options', json_encode($sectionData['options'] ?? [], JSON_UNESCAPED_UNICODE));
                    $this->db->bind(':s_weight', $sectionData['weight']);
                    $this->db->bind(':s_order', $sIndex + 1);
                    $this->db->bind(':s_min', $sectionData['min_value']);
                    $this->db->bind(':s_max', $sectionData['max_value']);
                    $this->db->bind(':s_allow_n', $sectionData['allow_notes']);
                    $this->db->bind(':s_req_n', $sectionData['notes_required']);
                    $this->db->bind(':s_placeholder', $sectionData['notes_placeholder']);
                    $this->db->bind(':s_formula', $formulaConfig);
                    $this->db->execute();
                }
            }
        }
      }

    // باقي دوال المودل
    public function buildStructureFromRequest($postData) {
        $structuredCriteria = [];
        if (!isset($postData['criteria']) || !is_array($postData['criteria'])) {
            return [];
        }

        foreach ($postData['criteria'] as $c_data) {
            $criterion = [
                'name'        => urldecode($c_data['name']),
                'description' => urldecode($c_data['description']),
                'weight'      => (float)($c_data['weight']),
                'sections'    => []
            ];

            if (isset($c_data['sections']) && is_array($c_data['sections'])) {
                foreach ($c_data['sections'] as $s_data) {
                    $section = [
                        'name'             => urldecode($s_data['name']),
                        'description'      => urldecode($s_data['description']),
                        'type'             => $s_data['type'],
                        'weight'           => (float)($s_data['weight']),
                        'min_value'        => $s_data['min_value'] ?: null,
                        'max_value'        => $s_data['max_value'] ?: null,
                        'options'          => json_decode(urldecode($s_data['options'])),
                        'allow_notes'      => (int)($s_data['allow_notes']),
                        'notes_required'   => (int)($s_data['notes_required']),
                        'notes_placeholder'=> urldecode($s_data['notes_placeholder']),
                        'formula'          => urldecode($s_data['formula'] ?? ''),
                        'formula_display'  => urldecode($s_data['formula_display'] ?? ''),
                        'readonly'         => (int)($s_data['readonly'] ?? 0),
                        'auto_source'      => $s_data['auto_source'] ?? null
                    ];
                    $criterion['sections'][] = $section;
                }
            }
            $structuredCriteria[] = $criterion;
        }
        return $structuredCriteria;
    }

    public function getActiveTemplates() {
        $sql = "SELECT id, template_name FROM form_templates WHERE is_active = 1 ORDER BY template_name ASC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    public function getAllActiveWithType() {
        $sql = "SELECT id, template_name, target_type FROM form_templates WHERE is_active = 1 ORDER BY target_type ASC, template_name ASC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    public function getActiveByType(string $targetType) {
        $sql = "SELECT id, template_name, target_type FROM form_templates WHERE is_active = 1 AND target_type = :type ORDER BY template_name ASC";
        $this->db->query($sql);
        $this->db->bind(':type', $targetType);
        return $this->db->fetchAll();
    }

       /**
     * يبحث عن قالب واحد بواسطة ID.
     * @param int $id
     * @return mixed
     */
    public function findById($id) {
        $this->db->query("SELECT * FROM form_templates WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    /**
     * يحذف قالباً من قاعدة البيانات.
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $this->db->query("DELETE FROM form_templates WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function findByPosition($positionId) {
    $sql = "SELECT id, template_name 
            FROM form_templates 
            WHERE target_type='employee' AND (target_position_id = :position_id OR target_position_id IS NULL) AND is_active = 1
            ORDER BY template_name ASC";
    $this->db->query($sql);
    $this->db->bind(':position_id', $positionId);
    return $this->db->fetchAll();
}

    public function findByScope($targetType, $unitScope = null) {
        if ($targetType === 'unit') {
            if ($unitScope === null) {
                $sql = "SELECT id, template_name FROM form_templates WHERE target_type='unit' AND is_active=1 ORDER BY template_name ASC";
                $this->db->query($sql);
                return $this->db->fetchAll();
            }
            $sql = "SELECT id, template_name FROM form_templates WHERE target_type='unit' AND is_active=1 AND (unit_scope IS NULL OR unit_scope = :uscope) ORDER BY template_name ASC";
            $this->db->query($sql);
            $this->db->bind(':uscope', $unitScope);
            return $this->db->fetchAll();
        }
        $sql = "SELECT id, template_name FROM form_templates WHERE target_type=:ttype AND is_active=1 ORDER BY template_name ASC";
        $this->db->query($sql);
        $this->db->bind(':ttype', $targetType);
        return $this->db->fetchAll();
    }

    public function findByScopeWithPosition($targetType, $positionId = null, $unitScope = null) {
        if ($targetType === 'employee' && $positionId) {
            return $this->findByPosition($positionId);
        }
        return $this->findByScope($targetType, $unitScope);
    }
// ...

}
