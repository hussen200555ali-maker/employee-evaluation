<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container">
  <div class="page-header"><h2><i class="fas fa-chalkboard-teacher text-primary"></i> تقييم دورة ككيان (مرن)</h2></div>
  <div class="form-container">
    <form action="<?= APP_URL ?>/flexible/evaluations/store/course" method="POST">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-bold">الدورة</label><select name="course_id" class="form-select" required><option value="">-- اختر دورة --</option><?php foreach($courses as $c): ?><option value="<?= $c->id ?>"><?= htmlspecialchars($c->course_title) ?> - <?= $c->branch_name ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="fw-bold">القالب (دورة)</label><select name="template_id" class="form-select" required><option value="">-- اختر قالب دورة --</option><?php foreach($templates as $t): ?><option value="<?= $t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="fw-bold">التاريخ</label><input type="date" name="evaluation_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-play"></i> بدء تقييم الدورة</button> <a href="<?= APP_URL ?>/training/courses" class="btn btn-secondary">إلغاء</a></div>
      <small class="text-muted d-block mt-2">الأرشيف القديم (50/30/20) متاح للقراءة فقط في <a href="<?= APP_URL ?>/training/evaluations/show/1">الأرشيف</a></small>
    </form>
  </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
