<?php
// نحدد الـ Namespace ليعكس مكان الملف في هيكل المشروع.
namespace App\Models;

// استيراد الفئة الأساسية للمودل.
use App\Core\Model;

/**
 * مودل الفرع (Branch)
 * مسؤول عن كل العمليات المتعلقة بجدول 'branches' في قاعدة البيانات.
 */
class Branch extends Model {

    /**
     * يجلب جميع الفروع من قاعدة البيانات.
     *
     * @return array - مصفوفة من كائنات الفروع.
     */
    public function getAll() {
        // جملة SQL لجلب كل الفروع مع اسم المدير وعدد الموظفين في كل فرع.
        // نستخدم LEFT JOIN لضمان ظهور الفروع حتى لو لم يكن لها مدير أو موظفين.
        // نستخدم COUNT(e.id) لحساب عدد الموظفين و AS لإعطاء الحقل اسماً مستعاراً.
        $sql = "SELECT 
                    b.*, 
                    m.full_name as manager_name,
                    COUNT(e.id) as employee_count
                FROM 
                    branches b
                LEFT JOIN 
                    employees m ON b.manager_id = m.id
                LEFT JOIN 
                    employees e ON b.id = e.branch_id
                GROUP BY 
                    b.id
                ORDER BY 
                    b.name ASC";
        
        // تحضير وتنفيذ الاستعلام باستخدام الدوال المساعدة من فئة Database.
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * ينشئ فرعاً جديداً في قاعدة البيانات.
     *
     * @param array $data - مصفوفة تحتوي على بيانات الفرع (name, location, manager_id).
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function create($data) {
        // جملة SQL لإدخال البيانات.
        $this->db->query('INSERT INTO branches (name, location, manager_id) VALUES (:name, :location, :manager_id)');

        // ربط القيم.
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':manager_id', $data['manager_id']);

        // تنفيذ الاستعلام.
        return $this->db->execute();
    }

    /**
     * يحدّث بيانات فرع موجود.
     *
     * @param array $data - مصفوفة تحتوي على بيانات الفرع (id, name, location, manager_id).
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function update($data) {
        // جملة SQL للتحديث.
        $this->db->query('UPDATE branches SET name = :name, location = :location, manager_id = :manager_id WHERE id = :id');

        // ربط القيم.
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':manager_id', $data['manager_id']);

        // تنفيذ الاستعلام.
        return $this->db->execute();
    }

    /**
     * يحذف فرعاً من قاعدة البيانات.
     *
     * @param int $id - معرف الفرع المراد حذفه.
     * @return bool - true عند النجاح, false عند الفشل.
     */
    public function delete($id) {
        // جملة SQL للحذف.
        $this->db->query('DELETE FROM branches WHERE id = :id');
        // ربط القيمة.
        $this->db->bind(':id', $id);
        // تنفيذ الاستعلام.
        return $this->db->execute();
    }
    
    /**
     * يتحقق مما إذا كان الفرع يحتوي على أقسام.
     *
     * @param int $id - معرف الفرع.
     * @return bool - true إذا كان يحتوي على أقسام، false إذا لم يكن.
     */
    public function hasDepartments($id) {
        $this->db->query('SELECT COUNT(*) as count FROM departments WHERE branch_id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->fetch();
        return $row->count > 0;
    }
      /**
     * ==================================================
     *           ===>  أضف الدالة الجديدة هنا  <===
     * ==================================================
     * تجلب كل الفروع مع مصفوفة من الأقسام التابعة لكل فرع.
     * هذه الدالة ضرورية لصفحة مركز التقارير الجديدة.
     */
    public function getAllWithDepartments() {
        // 1. جلب كل الفروع
        $this->db->query("SELECT id, name FROM branches ORDER BY name ASC");
        $branches = $this->db->fetchAll();

        // 2. جلب كل الأقسام
        $this->db->query("SELECT id, name, branch_id FROM departments ORDER BY name ASC");
        $departments = $this->db->fetchAll();

        // 3. دمج الأقسام داخل الفروع المناسبة
        // نستخدم حلقة foreach على الفروع (بالمرجعية &) لإضافة خاصية جديدة مباشرة
        foreach ($branches as &$branch) {
            $branch->departments = []; // إنشاء مصفوفة فارغة للأقسام
            foreach ($departments as $department) {
                if ($department->branch_id == $branch->id) {
                    // إذا كان القسم يتبع للفرع الحالي، أضفه إلى المصفوفة
                    $branch->departments[] = $department;
                }
            }
        }

        // 4. إرجاع مصفوفة الفروع الكاملة
        return $branches;
    }
 public function findById($id) {
        $this->db->query("SELECT * FROM branches WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->fetch();
}

/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تبحث عن فرع معين وتجلب معه اسم المدير.
 *
 * @param int $id معرف الفرع.
 * @return mixed كائن الفرع مع اسم المدير أو false.
 */
public function findByIdWithManager($id) {
    $sql = "SELECT b.*, e.full_name as manager_name
            FROM branches b
            LEFT JOIN employees e ON b.manager_id = e.id
            WHERE b.id = :id";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}

}