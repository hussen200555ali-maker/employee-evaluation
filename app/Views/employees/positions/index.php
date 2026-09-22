<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>إدارة المناصب الوظيفية</h2>
        <!-- زر لفتح نافذة إضافة منصب جديد -->
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addPositionModal">
            <i class="fas fa-plus"></i> إضافة منصب جديد
        </button>
    </div>

    <!-- لعرض رسائل النجاح أو الخطأ -->
    <?php App\Core\Flash::display(); ?>

    <div class="content-card">
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>المنصب الوظيفي</th>
                        <th>الفئة</th>
                        <th>عدد الموظفين</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($positions)): ?>
                        <?php foreach ($positions as $position): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($position->title); ?></td>
                                <td>
                                    <!-- عرض ترجمة جميلة للفئة -->
                                    <?php 
                                        $categories = ['manager' => 'مدير', 'deputy' => 'نائب', 'staff' => 'موظف', 'other' => 'أخرى'];
                                        echo $categories[$position->category] ?? 'غير معروف';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $position->employee_count; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- زر التعديل: يفتح نافذة التعديل ويملؤها بالبيانات -->
                                        <button class="btn-action btn-edit js-edit-position"
                                                data-id="<?php echo $position->id; ?>"
                                                data-title="<?php echo htmlspecialchars($position->title); ?>"
                                                data-category="<?php echo $position->category; ?>"
                                                title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- زر الحذف: يستخدم جافاسكريبت لتأكيد الحذف -->
                                        <button class="btn-action btn-delete js-delete-position"
                                                data-id="<?php echo $position->id; ?>"
                                                data-title="<?php echo htmlspecialchars($position->title); ?>"
                                                title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">لا توجد مناصب لعرضها حالياً.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================================================== -->
<!--           ===> النوافذ المنبثقة (Modals) <===       -->
<!-- ================================================== -->

<!-- نافذة إضافة منصب جديد -->
<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/employees/positions/store" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addPositionModalLabel">إضافة منصب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">اسم المنصب</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">الفئة</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="staff">موظف</option>
                            <option value="deputy">نائب مدير</option>
                            <option value="manager">مدير</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المنصب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل المنصب -->
<div class="modal fade" id="editPositionModal" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPositionForm"  action="<?php echo APP_URL; ?>/employees/positions/update" method="POST">
                <?= csrf_field() ?>
                <!-- حقل مخفي لمعرف المنصب -->
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPositionModalLabel">تعديل المنصب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-title" class="form-label">اسم المنصب</label>
                        <input type="text" class="form-control" id="edit-title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-category" class="form-label">الفئة</label>
                        <select class="form-select" id="edit-category" name="category" required>
                            <option value="staff">موظف</option>
                            <option value="deputy">نائب مدير</option>
                            <option value="manager">مدير</option>
                            <option value="other">أخرى</option>
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

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
