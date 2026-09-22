<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<!-- تم نقل <div class="container"> إلى الهيدر -->
<div class="page-header">
    <h2><i class="fas fa-building"></i> إدارة الفروع</h2>
    <?php if (App\Core\Auth::can('branches.create')): ?>
        <!-- هذا الزر سيقوم بفتح نافذة الإضافة المنبثقة -->
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="fas fa-plus"></i> إضافة فرع جديد
        </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الفرع</th>
                <th>الموقع</th>
                <th>مدير الفرع</th>
                <th>عدد الموظفين</th>
                <th>تاريخ الإنشاء</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($branches)): ?>
                <?php foreach ($branches as $index => $branch): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($branch->name); ?></td>
                        <td><?php echo htmlspecialchars($branch->location); ?></td>
                        <td><?php echo htmlspecialchars($branch->manager_name ?? 'N/A'); ?></td>
                        <td><span class="badge-count"><?php echo $branch->employee_count; ?></span></td>
                        <td><?php echo date('Y-m-d', strtotime($branch->created_at)); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if (App\Core\Auth::can('branches.edit')): ?>
                                    <!-- زر التعديل: سيفتح نافذة التعديل ويمرر بيانات الفرع إليها -->
                                    <button class="btn-action btn-edit js-edit-branch"
                                            data-id="<?php echo $branch->id; ?>"
                                            data-name="<?php echo htmlspecialchars($branch->name); ?>"
                                            data-location="<?php echo htmlspecialchars($branch->location); ?>"
                                            data-manager-id="<?php echo $branch->manager_id; ?>"
                                            title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (App\Core\Auth::can('branches.delete')): ?>
                                    <!-- زر الحذف: سيستخدم JavaScript لتأكيد الحذف -->
                                    <button class="btn-action btn-delete js-delete-branch"
                                            data-id="<?php echo $branch->id; ?>"
                                            data-name="<?php echo htmlspecialchars($branch->name); ?>"
                                            title="حذف">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">لا توجد فروع لعرضها حالياً.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ================================================== -->
<!--           ===>  النوافذ المنبثقة (Modals)  <===      -->
<!-- ================================================== -->

<!-- نافذة إضافة فرع جديد -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/branches" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addBranchModalLabel">إضافة فرع جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="add-name" class="form-label">اسم الفرع</label>
                        <input type="text" class="form-control" id="add-name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add-location" class="form-label">الموقع</label>
                        <textarea class="form-control" id="add-location" name="location" rows="2" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add-manager_id" class="form-label">مدير الفرع (اختياري)</label>
                        <select class="form-select" id="add-manager_id" name="manager_id">
                            <option value="">-- اختر مديراً --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager->id; ?>"><?php echo htmlspecialchars($manager->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الفرع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل فرع -->
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- سيتم إرسال النموذج إلى الرابط /branches/update -->
            <form action="<?php echo APP_URL; ?>/branches/update" method="POST" id="editBranchForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="editBranchModalLabel">تعديل الفرع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- حقل مخفي لتمرير ID الفرع -->
                    <input type="hidden" name="id" id="edit-id">
                    <div class="form-group mb-3">
                        <label for="edit-name" class="form-label">اسم الفرع</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit-location" class="form-label">الموقع</label>
                        <textarea class="form-control" id="edit-location" name="location" rows="2" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit-manager_id" class="form-label">مدير الفرع (اختياري)</label>
                        <select class="form-select" id="edit-manager_id" name="manager_id">
                            <option value="">-- اختر مديراً --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager->id; ?>"><?php echo htmlspecialchars($manager->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php 
// تضمين الفوتر المشترك.
require_once APP_ROOT . '/app/Views/layouts/footer.php'; 
?>
