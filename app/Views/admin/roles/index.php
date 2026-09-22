<?php
/**
 * @file
 * @brief واجهة عرض وإدارة الأدوار والصلاحيات.
 *
 * @description
 * تعرض هذه الصفحة كل الأدوار الموجودة في النظام على شكل بطاقات.
 * كل بطاقة تحتوي على صلاحيات الدور، مع إمكانية تعديلها بشكل فوري (AJAX).
 * كما توفر الصفحة وظائف لإنشاء، تعديل، وحذف الأدوار المخصصة.
 */

// تضمين الهيدر المشترك للتطبيق.
require_once APP_ROOT . '/app/Views/layouts/header.php';
?>

<!-- ================================================== -->
<!--                 1. رأس الصفحة (Header)              -->
<!-- ================================================== -->
<div class="page-header">
    <h2><i class="fas fa-user-tag"></i> إدارة الأدوار والصلاحيات</h2>
    
    <!-- زر الانتقال إلى صفحة إنشاء دور جديد -->
    <a href="<?php echo APP_URL; ?>/admin/roles/create" class="btn-add">
        <i class="fas fa-plus"></i> إضافة دور جديد
    </a>
</div>

<p class="lead">
    تحكم في الصلاحيات الممنوحة لكل دور في النظام. أي تغيير يتم حفظه تلقائياً.
</p>


<!-- ================================================== -->
<!--            2. شبكة عرض بطاقات الأدوار (Grid)         -->
<!-- ================================================== -->
<div class="roles-grid">
    <?php if (!empty($data['roles'])): ?>
        <?php foreach ($data['roles'] as $role): ?>
            
            <!-- بطاقة لكل دور -->
            <div class="role-card <?php if ($role->is_system_role) echo 'is-system-role'; ?>">
                
                <!-- 2.1. رأس البطاقة -->
                <div class="role-card-header">
                    <!-- عنوان الدور -->
                    <div class="role-title">
                        <i class="fas fa-shield-alt"></i>
                        <h3><?php echo htmlspecialchars($role->name); ?></h3>
                    </div>
                    
                    <!-- شارة "دور نظام" أو أزرار الإجراءات -->
                    <div>
                        <?php if ($role->is_system_role): ?>
                            <!-- إذا كان دور نظام، نعرض شارة للقراءة فقط -->
                            <span class="system-role-badge" title="هذا دور أساسي لا يمكن تعديله أو حذفه.">
                                <i class="fas fa-lock"></i> دور نظام
                            </span>
                        <?php else: ?>
                            <!-- إذا كان دوراً مخصصاً، نعرض أزرار التعديل والحذف -->
                            <div class="action-buttons">
                                <button class="btn-action btn-edit js-edit-role" 
                                        data-id="<?php echo $role->id; ?>"
                                        data-name="<?php echo htmlspecialchars($role->name); ?>"
                                        data-description="<?php echo htmlspecialchars($role->description); ?>"
                                        title="تعديل الدور">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete js-delete-role" 
                                        data-id="<?php echo $role->id; ?>"
                                        data-name="<?php echo htmlspecialchars($role->name); ?>"
                                        title="حذف الدور">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2.2. جسم البطاقة -->
                <div class="role-card-body">
                    <!-- وصف الدور -->
                    <p class="role-description"><?php echo htmlspecialchars($role->description); ?></p>
                    
                    <!-- نموذج الصلاحيات (يستخدم للتحديث عبر AJAX) -->
                    <form class="permission-form" data-role-id="<?php echo $role->id; ?>">
                        <div class="permissions-container">
                            <?php foreach ($data['allPermissions'] as $category => $permissions): ?>
                                <div class="permission-category">
                                    <h5><?php echo htmlspecialchars($category); ?></h5>
                                    <div class="permission-list">
                                        <?php foreach ($permissions as $permission): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input permission-checkbox" 
                                                       type="checkbox" 
                                                       id="perm-<?php echo $role->id; ?>-<?php echo $permission->id; ?>"
                                                       value="<?php echo $permission->id; ?>"
                                                       <?php 
                                                       // التحقق إذا كان الدور يملك هذه الصلاحية
                                                       if (in_array($permission->id, $role->permissions)) {
                                                           echo 'checked';
                                                       }
                                                       // تعطيل المربع إذا كان الدور هو "Admin"
                                                       if ($role->name === 'Admin') {
                                                           echo ' disabled';
                                                       }
                                                       ?>>
                                                <label class="form-check-label" for="perm-<?php echo $role->id; ?>-<?php echo $permission->id; ?>">
                                                    <?php echo htmlspecialchars($permission->description); ?>
                                                    <small>(<?php echo htmlspecialchars($permission->name); ?>)</small>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
            </div> <!-- نهاية بطاقة الدور -->

        <?php endforeach; ?>
    <?php else: ?>
        <!-- رسالة في حالة عدم وجود أي أدوار -->
        <div class="no-data-message">
            <i class="fas fa-exclamation-circle"></i>
            <h4>لا توجد أدوار لعرضها</h4>
            <p>لم يتم تعريف أي أدوار في النظام بعد. يمكنك البدء بإضافة دور جديد.</p>
        </div>
    <?php endif; ?>
</div>


<!-- ================================================== -->
<!--        3. النافذة المنبثقة (Modal) لتعديل الدور      -->
<!-- ================================================== -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- النموذج سيتم إرساله إلى مسار التحديث -->
            <form id="editRoleForm" method="POST" action="<?php echo APP_URL; ?>/admin/roles/update">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">تعديل الدور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- حقل مخفي لتمرير ID الدور -->
                    <input type="hidden" name="id" id="edit-id">
                    
                    <!-- حقل اسم الدور (للقراءة فقط) -->
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">اسم الدور (الاسم البرمجي)</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required pattern="[A-Za-z_]+" readonly>
                        <small class="text-muted">لا يمكن تغيير الاسم البرمجي للدور بعد إنشائه.</small>
                    </div>
                    
                    <!-- حقل وصف الدور (قابل للتعديل) -->
                    <div class="mb-3">
                        <label for="edit-description" class="form-label">وصف الدور (الاسم الظاهر)</label>
                        <input type="text" class="form-control" id="edit-description" name="description" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php 
// تضمين الفوتر المشترك للتطبيق.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
