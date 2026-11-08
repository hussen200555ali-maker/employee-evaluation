<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container">
  <div class="page-header"><h2><i class="fas fa-user-graduate text-primary"></i> تقييم موظف داخل دورة (مرن)</h2></div>
  <div class="form-container">
    <form action="<?= APP_URL ?>/flexible/evaluations/store/employee-in-course" method="POST">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-4"><label class="fw-bold">الدورة</label><select name="course_id" class="form-select" required><option value="">-- اختر دورة --</option><?php foreach($courses as $c): ?><option value="<?= $c->id ?>"><?= htmlspecialchars($c->course_title) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="fw-bold">الموظف</label><select name="employee_id" class="form-select" required><option value="">-- اختر موظف --</option><?php $emps=(new App\Models\Employee())->getAll(); foreach($emps as $e): ?><option value="<?= $e->id ?>"><?= htmlspecialchars($e->full_name) ?> (<?= $e->emp_code ?>)</option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="fw-bold">القالب (موظف في دورة)</label><select name="template_id" class="form-select" required><option value="">-- اختر قالب --</option><?php foreach($templates as $t): ?><option value="<?= $t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="row g-3 mt-1"><div class="col-md-4"><label class="fw-bold">التاريخ</label><input type="date" name="evaluation_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div></div>
      <div class="mt-4"><button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-play"></i> بدء التقييم</button></div>
    </form>
  </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
