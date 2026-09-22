<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-user-shield"></i> إدارة المستخدمين</h2>
<a href="<?php echo APP_URL; ?>/admin/users/create" class="btn-add">
        <i class="fas fa-user-plus"></i> إضافة مستخدم جديد
    </a>
    <!-- يمكن إضافة زر "إضافة مستخدم" هنا لاحقاً -->
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>البريد الإلكتروني</th>
                <th>الموظف المرتبط</th>
                <th>الدور الحالي</th>
                <th>الحالة</th>
                <th>آخر دخول</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user->username); ?></strong></td>
                        <td><?php echo htmlspecialchars($user->email); ?></td>
                        <td><?php echo htmlspecialchars($user->employee_name ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge-role role-<?php echo strtolower($user->role_name ?? 'default'); ?>">
                                <?php echo htmlspecialchars($user->role_name ?? 'بدون دور'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-<?php echo $user->status; ?>">
                                <?php echo $user->status; ?>
                            </span>
                        </td>
                        <td><?php echo $user->last_login_at ? date('Y-m-d H:i', strtotime($user->last_login_at)) : 'لم يسجل دخول'; ?></td>
                        <td>
                            <div class="action-buttons">
                               <!-- ================================================== -->
                              <!--           ===>  أضف الزر التعديل   <===           -->
                              <!-- ================================================== -->
                              <a href="<?php echo APP_URL; ?>/admin/users/edit/<?php echo $user->id; ?>" class="btn-action btn-edit" title="تعديل بيانات المستخدم">
                                  <i class="fas fa-pencil-alt"></i>
                              </a>

                              <!-- زر تغيير الدور (يصبح الزر الثاني) -->
                            
                            <button class="btn-action btn-edit js-edit-user-role"
                                        data-user-id="<?php echo $user->id; ?>"
                                        data-username="<?php echo htmlspecialchars($user->username); ?>"
                                        data-current-role-id="<?php echo $user->role_id; ?>"
                                        title="تغيير الدور">
                                    <i class="fas fa-user-tag"></i>
                                </button>
                                  <!-- ================================================== -->
                                <!--           ===>  أضف الزر تغيير حالة المستخدم   <===           -->
                                <!-- ================================================== -->
                                <?php if ($user->status === 'active'): ?>
                                    <!-- إذا كان المستخدم نشطاً، أظهر زر التعليق (أحمر) -->
                                    <button class="btn-action btn-delete js-toggle-status"
                                            data-user-id="<?php echo $user->id; ?>"
                                            data-username="<?php echo htmlspecialchars($user->username); ?>"
                                            title="تعليق الحساب">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- إذا كان المستخدم معلقاً، أظهر زر التفعيل (أخضر) -->
                                    <button class="btn-action btn-view js-toggle-status"
                                            data-user-id="<?php echo $user->id; ?>"
                                            data-username="<?php echo htmlspecialchars($user->username); ?>"
                                            title="تفعيل الحساب">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                <?php endif; ?>
                                 <!-- ================================================== -->
                                  <!--           ===>  أضف الزر عادة تعيين كلمة المرور  <===           -->
                                  <!-- ================================================== -->
                                  <button class="btn-action btn-warning js-reset-password"
                                          data-user-id="<?php echo $user->id; ?>"
                                          data-username="<?php echo htmlspecialchars($user->username); ?>"
                                          title="إعادة تعيين كلمة المرور">
                                      <i class="fas fa-key"></i>
                                  </button>

                                    <!-- ================================================== -->
                                    <!--           ===>  أضف الزر الحذف   <===           -->
                                    <!-- ================================================== -->
                                    <button class="btn-action btn-danger js-delete-user"
                                            data-user-id="<?php echo $user->id; ?>"
                                            data-username="<?php echo htmlspecialchars($user->username); ?>"
                                            title="حذف الحساب">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">لا يوجد مستخدمين لعرضهم.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- نافذة تعديل دور المستخدم -->
<div class="modal fade" id="editUserRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/admin/users/update-role" method="POST" id="editUserRoleForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">تغيير دور المستخدم: <span id="username-placeholder"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div class="form-group">
                        <label for="edit-role-id" class="form-label">الدور الجديد</label>
                        <select class="form-select" id="edit-role-id" name="role_id" required>
                            <option value="">-- اختر دوراً --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role->id; ?>"><?php echo htmlspecialchars($role->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// تضمين الفوتر المشترك.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
