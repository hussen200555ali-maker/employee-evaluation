<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * مودل الدور (Role)
 * مسؤول عن التعامل مع جداول 'roles' و 'permissions' و 'role_permissions'.
 */
class Role extends Model {

    /**
     * يجلب كل الأدوار المتاحة في النظام.
     *
     * @return array
     */
    public function getAll() {
        $this->db->query("SELECT * FROM roles ORDER BY id ASC");
        return $this->db->fetchAll();
    }

    /**
     * ==================================================
     *           ===>  الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * يجلب كل الصلاحيات المتاحة في النظام، مع تصنيفها.
     *
     * @return array - مصفوفة مصنفة من الصلاحيات.
     */
    public function getAllPermissions() {
        $this->db->query("SELECT * FROM permissions ORDER BY category, id");
        $permissions = $this->db->fetchAll();

        // إعادة تنظيم الصلاحيات في مصفوفة مصنفة
        $categorized = [];
        foreach ($permissions as $permission) {
            $categorized[$permission->category][] = $permission;
        }
        return $categorized;
    }

    /**
     * ==================================================
     *           ===>  الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * يجلب الصلاحيات المرتبطة بدور معين.
     *
     * @param int $roleId
     * @return array - مصفوفة تحتوي على IDs الصلاحيات الممنوحة.
     */
    public function getRolePermissions($roleId) {
        $this->db->query("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
        $this->db->bind(':role_id', $roleId);
        // استخدام FETCH_COLUMN لجلب IDs الصلاحيات في مصفوفة بسيطة [1, 5, 11, ...].
        return $this->db->fetchAll(PDO::FETCH_COLUMN);
    }
    // في ملف App\Models\Role.php

// ... (داخل الكلاس)

/**
 * ==================================================
 *           ===>  الدالة الجديدة الأولى هنا  <===
 * ==================================================
 * 
 * يضيف صلاحية محددة إلى دور معين.
 *
 * @param int $roleId معرف الدور.
 * @param int $permissionId معرف الصلاحية.
 * @return bool
 */
public function addPermissionToRole($roleId, $permissionId) {
    // IGNORE تتجاهل الإدخال إذا كان السجل موجوداً بالفعل (لمنع الأخطاء)
    $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
    $this->db->query($sql);
    $this->db->bind(':role_id', $roleId);
    $this->db->bind(':permission_id', $permissionId);
    return $this->db->execute();
}

/**
 * ==================================================
 *           ===>  الدالة الجديدة الثانية هنا  <===
 * ==================================================
 * 
 * يزيل صلاحية محددة من دور معين.
 *
 * @param int $roleId معرف الدور.
 * @param int $permissionId معرف الصلاحية.
 * @return bool
 */
public function removePermissionFromRole($roleId, $permissionId) {
    $sql = "DELETE FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id";
    $this->db->query($sql);
    $this->db->bind(':role_id', $roleId);
    $this->db->bind(':permission_id', $permissionId);
    return $this->db->execute();
}

// في ملف App\Models\Role.php

// ... (بعد دالة getRolePermissions وقبل دالة addPermissionToRole)

/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تنشئ دوراً جديداً في قاعدة البيانات.
 *
 * @param string $name اسم الدور.
 * @param string $description وصف الدور.
 * @return bool
 */
public function createRole($name, $description) {
    // is_system_role يتم تعيينها إلى 0 (false) افتراضياً للأدوار الجديدة
    $sql = "INSERT INTO roles (name, description, is_system_role) VALUES (:name, :description, 0)";
    $this->db->query($sql);
    $this->db->bind(':name', $name);
    $this->db->bind(':description', $description);
    
    // استخدام try-catch لمعالجة خطأ الاسم المكرر (لأن حقل الاسم UNIQUE)
    try {
        return $this->db->execute();
    } catch (\PDOException $e) {
        // إذا كان الخطأ بسبب تكرار الإدخال، أرجع false
        if ($e->getCode() == 23000) {
            return false;
        }
        // لأي خطأ آخر، ألقه مرة أخرى
        throw $e;
    }
}
// في ملف App\Models\Role.php

// ... (بعد دالة createRole)

public function updateRole($id, $description) {
    // لا نسمح بتعديل أدوار النظام
    $sql = "UPDATE roles SET description = :description WHERE id = :id AND is_system_role = 0";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    $this->db->bind(':description', $description);
    return $this->db->execute();
}

public function deleteRole($id) {
    // لا نسمح بحذف أدوار النظام
    // أيضاً، قاعدة البيانات ستمنع الحذف إذا كان الدور مستخدماً في جدول user_roles بفضل Foreign Key
    $sql = "DELETE FROM roles WHERE id = :id AND is_system_role = 0";
    $this->db->query($sql);
    $this->db->bind(':id', $id);
    try {
        return $this->db->execute();
    } catch (\PDOException $e) {
        // خطأ 23000 يعني وجود قيد مفتاح خارجي يمنع الحذف
        if ($e->getCode() == 23000) {
            return false;
        }
        throw $e;
    }
}

/**
 * ==================================================
 *           ===>  أضف الدالة التالية  <===
 * ==================================================
 * 
 * تبحث عن دور واحد باستخدام معرفه (ID).
 *
 * @param int $id معرف الدور.
 * @return mixed كائن الدور أو false إذا لم يتم العثور عليه.
 */
public function findById($id) {
    $this->db->query("SELECT * FROM roles WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}

/**
 * بند 6 (CD-006): يبحث عن صلاحية واحدة بمعرّفها (للتحقق من الاسم ومنع تصعيد الصلاحيات).
 *
 * @param int $id معرف الصلاحية.
 * @return mixed كائن الصلاحية أو false إذا لم توجد.
 */
public function findPermissionById($id) {
    $this->db->query("SELECT * FROM permissions WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->fetch();
}
}
