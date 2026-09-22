<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>
<div class="container">
  <div class="page-header"><h2><i class="fas fa-tools text-primary"></i> تقييم ورشة ككيان (مرن)</h2></div>
  <div class="form-container">
    <form action="<?= APP_URL ?>/flexible/evaluations/store/workshop" method="POST">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="fw-bold">الورشة</label><select name="workshop_id" class="form-select" required><option value="">-- اختر ورشة --</option><?php foreach($workshops as $w): ?><option value="<?= $w->id ?>"><?= htmlspecialchars($w->workshop_title) ?> - <?= $w->branch_name ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="fw-bold">القالب (ورشة)</label><select name="template_id" class="form-select" required><option value="">-- اختر قالب ورشة --</option><?php foreach($templates as $t): ?><option value="<?= $t->id ?>"><?= htmlspecialchars($t->template_name) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="fw-bold">التاريخ</label><input type="date" name="evaluation_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-play"></i> بدء تقييم الورشة</button></div>
    </form>
  </div>
</div>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
