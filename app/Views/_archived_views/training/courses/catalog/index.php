<?php 
// في ملف /app/Views/training/courses/index.php
require_once APP_ROOT . '/app/Views/layouts/header.php'; 
?>
<div class="container mt-4">

    <!-- عنوان الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book"></i> كتالوج الدورات</h2>
        <a href="<?php echo APP_URL; ?>/training/courses/catalog/create" class="btn btn-success">
            <i class="fas fa-plus"></i> إضافة دورة جديدة
        </a>
    </div>

    <!-- جدول الدورات -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>اسم الدورة</th>
                        <th>الوصف</th>
                        <th>التصنيف</th>
                        <th>تاريخ الإنشاء</th>
                        <th>آخر تحديث</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['courses'])): ?>
                        <?php foreach ($data['courses'] as $course): ?>
                            <tr>
                                <td><?php echo $course->id; ?></td>
                                <td><strong><?php echo htmlspecialchars($course->title); ?></strong></td>
                                <td><?php echo nl2br(htmlspecialchars($course->description)); ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?php echo htmlspecialchars($course->category ?: 'غير مصنف'); ?>
                                    </span>
                                </td>
                                <td><?php echo date("Y-m-d", strtotime($course->created_at)); ?></td>
                                <td><?php echo date("Y-m-d", strtotime($course->updated_at)); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/training/courses/catalog/edit/<?php echo $course->id; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                  <form action="<?php echo APP_URL; ?>/training/courses/catalog/delete" method="POST" 
                                        style="display:inline;" 
                                        onsubmit="return confirm('هل أنت متأكد من الحذف؟\n⚠️ ملاحظة: لا يمكن حذف دورة لها تقييمات.');">
                <?= csrf_field() ?>
                                      <input type="hidden" name="id" value="<?php echo $course->id; ?>">
                                      <button type="submit" class="btn btn-sm btn-danger">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  </form>

                              </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-muted">🚫 لا توجد دورات مسجلة حالياً</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>

