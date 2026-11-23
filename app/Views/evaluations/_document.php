<?php
// Shared evaluation document (used by evaluations/show and reports/report attachments).
// Expected vars: $evaluation (object), $template (array), $answers (array), $program (?object), $evaluator (?object), $hijriDate (?object), $e (callable).
$targetKey = $template['target_type'] ?? 'employee';
$typeLabels = [
    'employee'             => ['فردي', 'eval-type-annual', 'fas fa-user'],
    'employee_in_course'   => ['دورة', 'eval-type-course', 'fas fa-graduation-cap'],
    'employee_in_workshop' => ['ورشة', 'eval-type-workshop', 'fas fa-chalkboard-teacher'],
    'skill'                => ['مهاري', 'eval-type-skill', 'fas fa-bullseye'],
];
$typeInfo = $typeLabels[$targetKey] ?? $typeLabels['employee'];

if (!isset($employee) || !is_object($employee)) {
    $employee = isset($evaluation) && is_object($evaluation)
        ? (object)[
            'full_name' => $evaluation->evaluated_name ?? $evaluation->employee_name ?? '',
            'emp_code' => $evaluation->evaluated_code ?? $evaluation->employee_code ?? '',
            'branch_name' => null,
            'department_name' => null,
        ]
        : null;
}
?>
<?php
// ترقيم الأقسام ديناميكياً (عربي-هندي) — يُستخدم لاختيارية قسم الملاحظات
$toArabic = static function (int $n): string {
    $digits = [0 => '٠', 1 => '١', 2 => '٢', 3 => '٣', 4 => '٤', 5 => '٥', 6 => '٦', 7 => '٧', 8 => '٨', 9 => '٩'];
    return implode('', array_map(static fn(string $d): string => $digits[(int) $d] ?? $d, str_split((string) $n)));
};
$sectionNum = 0;
?>
<div class="a4-page">
  <header class="doc-header">
    <div class="header-emblem"><i class="<?php echo $typeInfo[2]; ?>"></i></div>
    <div class="header-titles">
      <p class="company">اسم الشركة — وحدة الموارد البشرية</p>
      <p class="basmala">بسم الله الرحمن الرحيم</p>
      <h1 class="doc-title"><?php echo htmlspecialchars($template['template_name'] ?? ''); ?></h1>
    </div>
    <div class="header-meta">
      <p><strong>التاريخ:</strong> <?php echo date('Y/m/d', strtotime($evaluation->evaluation_date)); ?></p>
      <?php if (!empty($hijriDate)): ?>
        <p><strong>الموافق:</strong> <?php echo "{$hijriDate->day} {$hijriDate->month_name} {$hijriDate->year}"; ?></p>
        <p><strong>اليوم:</strong> <?php echo htmlspecialchars($hijriDate->weekday); ?></p>
      <?php else: ?>
        <p><strong>الموظف:</strong> <?php echo htmlspecialchars($employee->full_name ?? $evaluation->evaluated_name ?? '—'); ?></p>
        <p><strong>الرقم:</strong> <?php echo htmlspecialchars($employee->emp_code ?? $evaluation->evaluated_code ?? $evaluation->employee_code ?? '—'); ?></p>
      <?php endif; ?>
      <p style="margin-top:4px; padding-top:4px; border-top:1px dashed #e2e8f0; font-size:10px; color:#64748b;"><i class="fas fa-hashtag" style="color:var(--gold);"></i> تقرير #<?php echo str_pad($evaluation->id,5,'0',STR_PAD_LEFT); ?></p>
    </div>
  </header>

  <div class="doc-body">
    <?php $program = $program ?? null; ?>
    <?php if ($employee): ?>
    <div class="section">
      <div class="section-head"><div class="section-num"><?= $toArabic(++$sectionNum) ?></div><h2>بيانات الموظف</h2><div class="line"></div></div>
      <div class="program-card">
        <div class="program-grid" style="grid-template-columns: repeat(2, 1fr);">
          <div class="program-cell"><label><i class="fas fa-user"></i> الاسم</label><span><?php echo htmlspecialchars($employee->full_name ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-hashtag"></i> الرقم الوظيفي</label><span><?php echo htmlspecialchars($employee->emp_code ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-building"></i> الفرع</label><span><?php echo htmlspecialchars($employee->branch_name ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-sitemap"></i> القسم</label><span><?php echo htmlspecialchars($employee->department_name ?? '—'); ?></span></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($program)): ?>
    <div class="section">
      <div class="section-head"><div class="section-num"><?= $toArabic(++$sectionNum) ?></div><h2>بيانات البرنامج التدريبي</h2><div class="line"></div></div>
      <div class="program-card">
        <div class="program-card-head">
          <h3><i class="<?php echo ($program->type ?? '') === 'workshop' ? 'fas fa-chalkboard-teacher' : 'fas fa-graduation-cap'; ?>"></i> <?php echo ($program->type ?? '') === 'workshop' ? 'الورشة التدريبية' : 'الدورة التدريبية'; ?></h3>
        </div>
        <div class="program-grid">
          <div class="program-cell"><label><i class="fas fa-book-open"></i> عنوان البرنامج</label><span><?php echo htmlspecialchars($program->title ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-calendar-alt"></i> تاريخ البرنامج</label><span><?php echo !empty($program->start_date) ? date('Y/m/d', strtotime($program->start_date)) . ' → ' . (!empty($program->end_date) ? date('Y/m/d', strtotime($program->end_date)) : '—') : '—'; ?></span></div>
          <div class="program-cell"><label><i class="fas fa-building"></i> الفرع المنفّذ</label><span><?php echo htmlspecialchars($program->branch_name ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-users"></i> القسم المنفّذ</label><span><?php echo htmlspecialchars($program->department_name ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-user-tie"></i> المسؤول</label><span><?php echo htmlspecialchars($program->responsible_person ?? '—'); ?></span></div>
          <div class="program-cell"><label><i class="fas fa-tag"></i> نوع البرنامج</label><span><?php echo ($program->type ?? '') === 'workshop' ? 'ورشة' : 'دورة'; ?></span></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="section">
      <div class="section-head"><div class="section-num"><?= $toArabic(++$sectionNum) ?></div><h2>معايير التقييم</h2><div class="line"></div></div>
      <?php foreach(($template['criteria'] ?? []) as $criterion): ?>
      <div class="criterion-card">
        <div class="criterion-head"><h3><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($criterion['criterion_name']); ?></h3><span class="count"><?php echo count($criterion['sections']); ?> بنود</span></div>
        <?php $descriptiveCourse = ($template['target_type'] ?? '') === 'employee_in_course' && ($template['evaluation_mode'] ?? '') === 'descriptive'; $levelHeader = $descriptiveCourse ? 'الوصف' : 'المستوى'; ?>
        <table class="eval-table">
          <thead><tr><th style="width:32%">عنصر التقييم</th><th class="<?php echo $descriptiveCourse ? 'desc-th' : ''; ?>"><?php echo $levelHeader; ?></th><?php if (!$descriptiveCourse): ?><th style="width:30%">ملاحظات</th><?php endif; ?></tr></thead>
          <tbody>
          <?php foreach(($criterion['sections'] ?? []) as $section): ?>
            <?php $answer = $answers[$section['id']] ?? null; $display = null; $notes = ''; $cls = 'badge-na'; $sType=(string)($section['evaluation_type'] ?? ''); $isBadgeable = in_array($sType, ['grade','overall_grade','percentage','numeric','boolean','scale','multiple_choice'], true);
              if ($answer) {
                if (($section['evaluation_type'] ?? '') === 'grade') { $opts = json_decode($section['evaluation_options'] ?? '[]', true); foreach (($opts ?? []) as $opt) { if ((string)($opt['value'] ?? '') === (string)$answer->raw_value || (string)($opt['label'] ?? '') === (string)$answer->raw_value) { $display = $opt['label']; break; } } if ($display === null && is_string($opts[0] ?? null)) $display = $answer->raw_value; }
                else $display = $answer->raw_value;
                $notes = $answer->notes ?? '';
                $low = strtolower((string)$display); if ($display !== null && (strpos($low,'ممتاز') !== false || $display === 'طبق' || $display === 'قرأ' || $display === 'رفع' || $display === 'إلتزم')) $cls = 'badge-excellent'; elseif ($display !== null && (strpos($low,'جيد') !== false)) $cls = 'badge-good'; elseif ($display === '—' || $display === null) $cls = 'badge-na'; else $cls = 'badge-average';
              }
            ?>
            <tr><td><?php echo htmlspecialchars($section['section_name']); ?></td><td class="<?php echo $isBadgeable ? '' : 'text-plain'; ?>"><?php if ($display === null || $display === ''): ?><span class="badge badge-na">—</span><?php elseif ($isBadgeable): ?><span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($display); ?></span><?php else: ?><div class="cell-plain"><?php echo nl2br(htmlspecialchars($display)); ?></div><?php endif; ?></td><?php if (!$descriptiveCourse): ?><td><?php echo $notes ? nl2br(htmlspecialchars($notes)) : '<span style="color:#cbd5e1;">—</span>'; ?></td><?php endif; ?></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($evaluation->notes)): ?>
    <div class="section">
      <div class="section-head"><div class="section-num"><?= $toArabic(++$sectionNum) ?></div><h2>ملاحظات ورأي المقيّم</h2><div class="line"></div></div>
      <div class="program-card" style="padding:14px 18px;">
        <div style="white-space:pre-wrap;font-size:11px;line-height:1.8;color:#1a2332;"><?php echo nl2br(htmlspecialchars((string)$evaluation->notes)); ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="section">
      <div class="section-head"><div class="section-num"><?= $toArabic(++$sectionNum) ?></div><h2>بيانات القائم بالتقييم</h2><div class="line"></div></div>
      <div class="evaluator-card">
        <div class="eval-person"><div class="lbl"><i class="fas fa-user-tie"></i> الاسم</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_name ?? ($evaluator->full_name ?? 'غير محدد')); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-award"></i> الصفة</div><div class="val"><?php echo htmlspecialchars($evaluation->evaluator_title ?? ($evaluator->position_title ?? '—')); ?></div></div>
        <div class="eval-person"><div class="lbl"><i class="fas fa-calendar"></i> تاريخ التقييم</div><div class="val" dir="ltr"><?php echo date('Y/m/d', strtotime($evaluation->evaluation_date)); ?></div></div>
      </div>
      <div class="signature-area">
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع المقيّم</div><div class="sig-sub"><?php echo htmlspecialchars($evaluation->evaluator_name ?? ''); ?></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">توقيع مسؤول النظام</div><div class="sig-sub"></div></div>
        <div class="sig-box"><div class="sig-line"></div><div class="sig-label">الختم الرسمي</div><div class="sig-sub" style="color:#94a3b8;">—</div></div>
      </div>
    </div>
  </div>

  <footer class="doc-footer">
    <div class="left"><span class="dot"></span> وثيقة سرية — للاستخدام الداخلي فقط</div>
    <div>مستخدم #<?php echo (int)\App\Core\Auth::user()->id; ?> · <?php echo date('Y/m/d H:i'); ?></div>
  </footer>
</div>
