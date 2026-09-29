<?php 
// تضمين الهيدر المشترك.
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>

<div class="page-header">
    <h2><i class="fas fa-sitemap"></i> إدارة الأقسام</h2>
    <?php if (App\Core\Auth::userHasPermission('departments.create')): ?>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
            <i class="fas fa-plus"></i> إضافة قسم جديد
        </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم القسم</th>
                <th>الفرع التابع له</th>
                <th>عدد الموظفين</th>
                <!--th>إحصائيات الورش</th-->
                <th>تاريخ الإنشاء</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
          <?php if (!empty($departments)): ?>
              <?php foreach ($departments as $index => $dept): ?>
                  <tr>
                      <td><?php echo $index + 1; ?></td>
                      <td><strong><?php echo htmlspecialchars($dept->name); ?></strong></td>
                      <td><?php echo htmlspecialchars($dept->branch_name ?? 'قسم مركزي'); ?></td>
                      <td><?php echo $dept->employee_count; ?></td>
                      <!--td>
                          <span class="badge bg-primary">ورش: <?php echo $dept->total_workshops; ?></span>
                          <span class="badge bg-success">
                              متوسط التقييم: 
                              <?php echo isset($dept->avg_final_score) ? round($dept->avg_final_score, 2) . '%' : 'لم يقيم بعد'; ?>
                          </span>
                      </td-->
                      <td><?php echo date('Y-m-d', strtotime($dept->created_at)); ?></td>
                      <td>
                          <?php if (App\Core\Auth::userHasPermission('departments.edit')): ?>
                              <button class="btn btn-sm btn-edit js-edit-department"
                                      data-id="<?php echo $dept->id; ?>"
                                      data-name="<?php echo htmlspecialchars($dept->name); ?>"
                                      data-branch-id="<?php echo $dept->branch_id; ?>">
                                  <i class="fas fa-edit"></i> تعديل
                              </button>
                          <?php endif; ?>
                          <?php if (App\Core\Auth::userHasPermission('departments.delete')): ?>
                              <button class="btn btn-sm btn-delete js-delete-department"
                                      data-id="<?php echo $dept->id; ?>"
                                      data-name="<?php echo htmlspecialchars($dept->name); ?>">
                                  <i class="fas fa-trash"></i> حذف
                              </button>
                          <?php endif; ?>
                      </td>
                  </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr>
                  <td colspan="7" class="text-center">لا توجد أقسام لعرضها.</td>
              </tr>
          <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- نافذة إضافة قسم جديد -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/departments" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">إضافة قسم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="add-name" class="form-label">اسم القسم</label>
                        <input type="text" class="form-control" id="add-name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add-branch_id" class="form-label">الفرع التابع له (اختياري)</label>
                        <select class="form-select" id="add-branch_id" name="branch_id">
                            <option value="">-- قسم مركزي (بدون فرع) --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch->id; ?>"><?php echo htmlspecialchars($branch->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ القسم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تعديل قسم -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo APP_URL; ?>/departments/update" method="POST" id="editDepartmentForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">تعديل القسم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="form-group mb-3">
                        <label for="edit-name" class="form-label">اسم القسم</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit-branch_id" class="form-label">الفرع التابع له (اختياري)</label>
                        <select class="form-select" id="edit-branch_id" name="branch_id">
                            <option value="">-- قسم مركزي (بدون فرع) --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch->id; ?>"><?php echo htmlspecialchars($branch->name); ?></option>
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
