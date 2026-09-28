<?php
namespace App\Models;
use PDO;
use App\Core\Model;

class User extends Model {

    /** آخر قائمة أخطاء سياسة كلمة المرور (بند 11) — تُقرأ من الواجهة لعرضها. */
    public array $lastPasswordErrors = [];

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function findByUsernameOrEmail($identifier) {
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.username = :username OR u.email = :email";
        
        // استخدام النمط الصحيح المتوافق مع فئة Database المساعد
        $this->db->query($sql);
        $this->db->bind(':username', $identifier);
        $this->db->bind(':email', $identifier);
        return $this->db->fetch();
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function findById($id) {
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        $user = $this->db->fetch();

        if ($user) {
            if ($user->employee_id) {
                $emp_sql = "SELECT full_name FROM employees WHERE id = :emp_id";
                $this->db->query($emp_sql);
                $this->db->bind(':emp_id', $user->employee_id);
                $employee = $this->db->fetch();
                $user->full_name = $employee ? $employee->full_name : 'موظف محذوف';
            } else {
                $user->full_name = 'مدير النظام';
            }
        }
        return $user;
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function getPermissions($userId) {
        $sql = "SELECT p.name 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :user_id";

        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        // ملاحظة: fetchAll() في فئة المساعد لا تقبل معاملات،
        // لذا سنحتاج إلى تعديل بسيط في فئة Database أيضاً.
        return $this->db->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function getAll() {
        $sql = "SELECT u.id, u.username, u.email, u.status, r.name as role_name
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                ORDER BY u.username ASC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function updateRole($userId, $roleId) {
        $userId = (int)$userId;
        $roleId = (int)$roleId;

        // بند 9 (CD-006): تغليف الحذف/الإدراج في Transaction لضمان الذرية.
        // $this->db هو Database::getInstance() (انظر Model::__construct)،
        // وgetPdo() يعيد نفس الاتصال — فالمعاملة تغطي الاستعلامين معًا.
        // بدونها: لو فشل الإدراج بعد نجاح الحذف (مثل role_id غير موجود)،
        // يبقى المستخدم بلا دور نهائيًا.
        $pdo = \App\Core\Database::getInstance()->getPdo();
        $pdo->beginTransaction();

        try {
            $this->db->query("DELETE FROM user_roles WHERE user_id = :user_id");
            $this->db->bind(':user_id', $userId);
            $this->db->execute();

            $this->db->query("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':role_id', $roleId);
            $this->db->execute();

            $pdo->commit();
            return true;

        } catch (\PDOException $e) {
            // التراجع يُرجع الصف المحذوف، فيحتفظ المستخدم بدوره القديم.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // التمييز: دور غير موجود ⇒ false (نمط باقي الدوال، بلا استثناء).
            // أي خطأ آخر (اتصال/قيد) ⇒ يُعاد إلقاؤه كما كان سلوك الدوال الأخرى.
            $this->db->query("SELECT id FROM roles WHERE id = :id");
            $this->db->bind(':id', $roleId);
            if (!$this->db->fetch()) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * ==================================================
     *           ===>  الدالة المصححة هنا  <===
     * ==================================================
     */
    public function hasRole($userId, $roleName) {
        $sql = "SELECT COUNT(*) 
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = :user_id AND r.name = :role_name";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':role_name', $roleName);
        
        return $this->db->fetchColumn() > 0;
    }

    // ======================================================================
    // بند 3 (CD-006) — حماية آخر مدير نظام نشط
    // يُعرَّف «مدير النظام» ديناميكيًا عبر Auth::adminRoleIds()
    // (roles.name='Admin' AND is_system_role=1) — بلا معرّف صريح.
    // ======================================================================

    /** بناء قائمة العناصر النائبة لمعرّفات أدوار Admin. */
    private function adminRolePlaceholders(array $adminRoleIds): string {
        $ph = [];
        foreach (array_keys($adminRoleIds) as $i) {
            $ph[] = ':rid' . $i;
        }
        return implode(',', $ph);
    }

    /**
     * عدد مدراء النظام النشطين (بلا تكرار).
     * @return int
     */
    public function activeAdminCount() {
        $adminRoleIds = \App\Core\Auth::adminRoleIds();
        if ($adminRoleIds === []) {
            return 0;
        }
        $in = $this->adminRolePlaceholders($adminRoleIds);
        $this->db->query("SELECT COUNT(DISTINCT u.id)
                          FROM users u
                          JOIN user_roles ur ON ur.user_id = u.id
                          WHERE u.status = 'active' AND ur.role_id IN ($in)");
        foreach ($adminRoleIds as $i => $rid) {
            $this->db->bind(':rid' . $i, $rid);
        }
        return (int)$this->db->fetchColumn();
    }

    /**
     * هل المستخدم المعطى مدير نظام نشط؟
     * @param int $userId
     * @return bool
     */
    public function isActiveAdmin($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }
        $adminRoleIds = \App\Core\Auth::adminRoleIds();
        if ($adminRoleIds === []) {
            return false;
        }
        $in = $this->adminRolePlaceholders($adminRoleIds);
        $this->db->query("SELECT COUNT(*)
                          FROM users u
                          JOIN user_roles ur ON ur.user_id = u.id
                          WHERE u.id = :uid AND u.status = 'active' AND ur.role_id IN ($in)");
        $this->db->bind(':uid', $userId);
        foreach ($adminRoleIds as $i => $rid) {
            $this->db->bind(':rid' . $i, $rid);
        }
        return $this->db->fetchColumn() > 0;
    }

    /**
     * هل المستخدم المعطى هو آخر مدير نظام نشط؟
     * @param int $userId
     * @return bool
     */
    public function isLastActiveAdmin($userId) {
        return $this->isActiveAdmin($userId) && $this->activeAdminCount() <= 1;
    }

/*  public function getUserRoles($userId) {
    $this->db->query("SELECT r.name FROM roles r
                     JOIN user_roles ur ON r.id = ur.role_id
                     WHERE ur.user_id = :user_id");
    $this->db->bind(':user_id', $userId);
    return $this->db->fetchAll(\PDO::FETCH_COLUMN); // <-- غير هذا السطر
}*/
/* يجلب أسماء الأدوار المرتبطة بمستخدم معين.
     * @param int $userId
     * @return array - مصفوفة من أسماء الأدوار (مثل ['Admin', 'Manager']).
     */
    public function getUserRoles($userId) {
        $this->db->query("SELECT r.name FROM roles r
                          JOIN user_roles ur ON r.id = ur.role_id
                          WHERE ur.user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        // استخدام array_column لجلب عمود 'name' فقط في مصفوفة بسيطة
        return array_column($this->db->fetchAll(), 'name');
    }
/*
public function getUserPermissions($userId) {
    $this->db->query("SELECT p.name FROM permissions p
                     JOIN role_permissions rp ON p.id = rp.permission_id
                     JOIN user_roles ur ON rp.role_id = ur.role_id
                     WHERE ur.user_id = :user_id");
    $this->db->bind(':user_id', $userId);
    return $this->db->fetchAll(\PDO::FETCH_COLUMN); // <-- وغير هذا السطر
}*/

 /**
     * ===> تأكد من وجود هذه الدالة <===
     * يجلب أسماء كل الصلاحيات الممنوحة لمستخدم معين.
     * @param int $userId
     * @return array - مصفوفة من أسماء الصلاحيات.
     */
    public function getUserPermissions($userId) {
        $this->db->query("SELECT DISTINCT p.name FROM permissions p
                          JOIN role_permissions rp ON p.id = rp.permission_id
                          JOIN user_roles ur ON rp.role_id = ur.role_id
                          WHERE ur.user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return array_column($this->db->fetchAll(), 'name');
    }
    /**
     * ==================================================
     *           ===>  الدالة الجديدة هنا  <===
     * ==================================================
     * 
     * يجلب جميع المستخدمين مع تفاصيل إضافية (اسم الموظف المرتبط، اسم الدور).
     *
     * @return array
     */
    public function getAllWithDetails() {
        $sql = "SELECT 
                    u.id, u.username, u.email, u.status, u.last_login_at,
                    e.full_name as employee_name,
                    r.name as role_name,
                    r.id as role_id
                FROM users u
                LEFT JOIN employees e ON u.employee_id = e.id
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                ORDER BY u.username ASC";
        
        $this->db->query($sql);
        return $this->db->fetchAll();
    }
    /**
     * 
     * يحدّث تاريخ آخر تسجيل دخول للمستخدم.
     *
     * @param int $userId - معرف المستخدم.
     * @return bool
     */
    public function updateLastLogin($userId) {
        // نستخدم NOW() لجلب الوقت الحالي من سيرفر قاعدة البيانات مباشرة.
        $this->db->query("UPDATE users SET last_login_at = NOW() WHERE id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }


/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تنشئ مستخدماً جديداً مع دوره في قاعدة البيانات.
 *
 * @param array $data بيانات المستخدم (username, email, password, role_id, employee_id).
 * @return mixed ID المستخدم الجديد عند النجاح, أو false عند الفشل.
 */
public function createUser($data) {
    // استخدام Transaction لضمان تنفيذ العمليتين معاً أو فشلهما معاً
    $this->db->beginTransaction();

    try {
        // 1. إدخال المستخدم في جدول 'users'
        $sqlUser = "INSERT INTO users (username, email, password_hash, employee_id, status) 
                    VALUES (:username, :email, :password_hash, :employee_id, 'active')";
        
        $this->db->query($sqlUser);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->execute();

        // الحصول على ID المستخدم الجديد
        $userId = $this->db->lastInsertId();

        // 2. إدخال دوره في جدول 'user_roles'
        $sqlRole = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $this->db->query($sqlRole);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':role_id', $data['role_id']);
        $this->db->execute();

        // إذا تمت كل العمليات بنجاح، قم بتأكيد الحفظ
        $this->db->commit();
        return $userId;

    } catch (\PDOException $e) {
        // إذا حدث أي خطأ، قم بالتراجع عن كل التغييرات
        $this->db->rollBack();
        // إذا كان الخطأ بسبب تكرار الإدخال (اسم مستخدم أو بريد)
        if ($e->getCode() == 23000) {
            return false;
        }
        // لأي خطأ آخر، ألقه مرة أخرى
        throw $e;
    }
}
/**
 * ==================================================
 *           ===>  الدالة الجديدة هنا  <===
 * ==================================================
 * 
 * تغير حالة المستخدم وتعيد الحالة الجديدة.
 *
 * @param int $userId معرف المستخدم.
 * @return string|false الحالة الجديدة ('active' أو 'suspended') أو false عند الفشل.
 */
public function toggleStatus($userId) {
    // أولاً، احصل على الحالة الحالية للمستخدم
    $this->db->query("SELECT status FROM users WHERE id = :id");
    $this->db->bind(':id', $userId);
    $user = $this->db->fetch();

    if (!$user) {
        return false;
    }

    // تحديد الحالة الجديدة
    $newStatus = ($user->status === 'active') ? 'suspended' : 'active';

    // تحديث قاعدة البيانات
    $this->db->query("UPDATE users SET status = :status WHERE id = :id");
    $this->db->bind(':status', $newStatus);
    $this->db->bind(':id', $userId);

    if ($this->db->execute()) {
        return $newStatus; // أرجع الحالة الجديدة عند النجاح
    } else {
        return false;
    }
}

// في ملف App\Models\User.php

/**
 * 
 * يحدّث بيانات مستخدم موجود، مع إمكانية تحديث كلمة المرور بشكل اختياري.
 *
 * @param array $data بيانات المستخدم.
 * @return bool
 */
/**
 * تحدّث بيانات مستخدم (مع فرض سياسة كلمة المرور عند تغييرها — بند 11).
 *
 * @param array $data بيانات التحديث (id/username/email/employee_id + password اختيارية)
 * @return bool
 */
public function updateUser($data) {
    // بند 11: فرض السياسة قبل التنفيذ عند مرور كلمة مرور
    if (!empty($data['password'])) {
        $this->lastPasswordErrors = [];
        $policy = \App\Core\Auth::validatePasswordPolicy($data['password'], (int)$data['id']);
        if (!$policy['ok']) {
            $this->lastPasswordErrors = $policy['errors'];
            return false;
        }
    }

    // التقاط الهاش الحالي قبل التحديث لتسجيله في التاريخ (بند 11)
    $oldHash = null;
    if (!empty($data['password'])) {
        $this->db->query("SELECT password_hash FROM users WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $current = $this->db->fetch();
        $oldHash = $current->password_hash ?? null;
    }

    // 1. بناء الجزء الأساسي من جملة SQL
    $sql = "UPDATE users SET username = :username, email = :email, employee_id = :employee_id";
    
    // 2. إضافة جزء تحديث كلمة المرور (فقط إذا لم تكن فارغة)
    if (!empty($data['password'])) {
        $sql .= ", password_hash = :password_hash";
    }
    
    // 3. إكمال جملة SQL
    $sql .= " WHERE id = :id";

    // 4. تحضير الاستعلام وربط القيم
    $this->db->query($sql);
    $this->db->bind(':id', $data['id']);
    $this->db->bind(':username', $data['username']);
    $this->db->bind(':email', $data['email']);
    $this->db->bind(':employee_id', $data['employee_id']);

    // ربط قيمة كلمة المرور (فقط إذا كانت موجودة)
    if (!empty($data['password'])) {
        $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
    }

    // 5. تنفيذ الاستعلام
    try {
        $ok = $this->db->execute();
        if ($ok && !empty($data['password']) && $oldHash !== null) {
            \App\Core\Auth::recordPasswordHistory((int)$data['id'], $oldHash);
        }
        return $ok;
    } catch (\PDOException $e) {
        // في حالة وجود اسم مستخدم أو بريد مكرر
        return false;
    }
}
// دالة حذف مستخدم

public function deleteUser($id) {
    $this->db->query("DELETE FROM users WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->execute();
}
/**
 * حارس موحّد لتغيير كلمة المرور: فرض السياسة + التقاط الهاش القديم (بند 11).
 *
 * @param int $userId معرف المستخدم.
 * @param string $newPassword كلمة المرور الجديدة (غير مشفرة).
 * @return string|null هاش كلمة المرور القديمة إذا سُمح بالتغيير، وإلا null.
 */
private function guardPasswordChange($userId, $newPassword) {
    $this->lastPasswordErrors = [];
    $policy = \App\Core\Auth::validatePasswordPolicy($newPassword, (int)$userId);
    if (!$policy['ok']) {
        $this->lastPasswordErrors = $policy['errors'];
        return null;
    }
    $this->db->query("SELECT password_hash FROM users WHERE id = :id");
    $this->db->bind(':id', $userId);
    $current = $this->db->fetch();
    if (!$current) {
        return null;
    }
    return $current->password_hash ?? null;
}

/**
 * تحدّث كلمة مرور مستخدم معين مع فرض سياسة بند 11 وتسجيل الهاش القديم في التاريخ.
 *
 * @param int $userId معرف المستخدم.
 * @param string $newPassword كلمة المرور الجديدة (غير مشفرة).
 * @param bool $mustChange تعيين must_change_password = 1 (بند 9: إلزام التغيير عند الدخول التالي).
 * @return bool
 */
public function updatePassword($userId, $newPassword, $mustChange = false) {
    $oldHash = $this->guardPasswordChange($userId, $newPassword);
    if ($oldHash === null) {
        return false;
    }
    $sql = "UPDATE users SET password_hash = :password_hash, must_change_password = "
        . ($mustChange ? 1 : 0)
        . " WHERE id = :id";
    $this->db->query($sql);
    $this->db->bind(':password_hash', password_hash($newPassword, PASSWORD_DEFAULT));
    $this->db->bind(':id', $userId);
    $ok = $this->db->execute();
    if ($ok) {
        \App\Core\Auth::recordPasswordHistory((int)$userId, $oldHash);
    }
    return $ok;
}
}
