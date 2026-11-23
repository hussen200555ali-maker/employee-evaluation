<?php
/**
 * بند 15 — شاشة الإشعارات (معاد تصميمها 2026-11-18 لتطابق الهوية البصرية للمشروع).
 * تعرض إشعارات المستخدم كافة (الأحدث أولًا — غير المقروءة والأحدث مميزة بصريا).
 */
require_once APP_ROOT . '/app/Views/layouts/header.php';

$notifications = $data['notifications'] ?? [];
$unreadCount = (int)($data['unreadCount'] ?? 0);
$totalCount = count($notifications);
$readCount = max(0, $totalCount - $unreadCount);

// إحصاءات حسب النوع
$countWarning = $countDanger = $countInfo = $countSuccess = 0;
foreach ($notifications as $_n) {
    switch ((string)($_n['type'] ?? 'info')) {
        case 'warning': $countWarning++; break;
        case 'danger': $countDanger++; break;
        case 'success': $countSuccess++; break;
        default: $countInfo++;
    }
}

// خريطة type → أيقونة + عنوان + فئة بصرية — البادجات الصفحات تبقى مطابقة للوثيقة CD-021
$typeIcon = [
    'info'    => ['fa-circle-info', 'معلومات', 'info'],
    'success' => ['fa-circle-check', 'نجاح', 'success'],
    'warning' => ['fa-triangle-exclamation', 'تنبيه', 'warning'],
    'danger'  => ['fa-circle-xmark', 'خطر', 'danger'],
];
?>

<style>
/* ====== notifications — brand redesign (teal/gold identity) ====== */
.ntf-page { padding: 1.1rem 1.1rem 2.4rem; }
.ntf-page * { box-sizing: border-box; }

/* Hero banner */
.ntf-hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    border-radius: 20px;
    padding: clamp(1.4rem, 2.4vw + 1rem, 2.2rem);
    margin-bottom: 1.1rem;
    color: #effaf5;
    background: linear-gradient(120deg, #093f37 0%, #0d6658 64%, #147b6a 100%);
    box-shadow: 0 18px 40px rgba(9,63,55,.22);
    display: flex; align-items: center; justify-content: space-between; gap: 1.4rem; flex-wrap: wrap;
}
.ntf-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1; opacity: .42;
    background-image: radial-gradient(circle at 90% 22%, rgba(255,255,255,.22) 0 2px, transparent 3px);
    background-size: 18px 18px;
}
.ntf-hero::before {
    content: ''; position: absolute; z-index: -1; top: -32%; left: -7%; width: 17rem; height: 17rem;
    border-radius: 50%; background: radial-gradient(circle, rgba(200,148,63,.22), transparent 65%); filter: blur(18px);
}
.ntf-hero__left { flex: 1 1 22rem; min-width: 17rem; }
.ntf-hero .eyebrow {
    display: inline-flex; align-items: center; gap: .5rem;
    color: #c6eadf; font-size: .74rem; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; margin-bottom: .65rem;
}
.ntf-hero .eyebrow i { color: #f3ca78; font-size: .95rem; }
.ntf-hero h1 {
    color: #fff; font-weight: 900; letter-spacing: -.02em;
    font-size: clamp(1.55rem, 2.2vw + .8rem, 2.3rem);
    line-height: 1.25; margin: 0 0 .6rem;
}
.ntf-hero h1 .accent { color: #f3ca78; }
.ntf-hero p { color: rgba(255,255,255,.78); max-width: 36rem; font-size: .92rem; line-height: 1.7; margin: 0 0 1rem; }

/* unread CTA */
.ntf-hero__cta {
    display: inline-flex; align-items: center; gap: .55rem;
    background: linear-gradient(90deg, #f3ca78, #c8943f);
    color: #3a2a06; font-weight: 800; border-radius: 12px; padding: .72rem 1.1rem;
    text-decoration: none; border: 0; cursor: pointer; box-shadow: 0 12px 28px rgba(200,148,63,.32);
    transition: transform .18s ease; font-family: inherit; font-size: .9rem;
}
.ntf-hero__cta:hover { transform: translateY(-2px); }
.ntf-hero__cta i { font-size: 1.05rem; }

.ntf-hero__stats { display: flex; gap: .65rem; flex-wrap: wrap; flex: 0 0 auto; }
.ntf-stat {
    min-width: 6.8rem; padding: .85rem .9rem; border-radius: 16px;
    border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.10);
    backdrop-filter: blur(10px);
}
.ntf-stat i { color: #f3ca78; font-size: .8rem; display: block; margin-bottom: .35rem; }
.ntf-stat strong { display: block; color: #fff; font-weight: 900; font-size: 1.55rem; line-height: 1; letter-spacing: -.02em; }
.ntf-stat span { font-size: .7rem; color: rgba(255,255,255,.7); display: block; margin-top: .35rem; }
.ntf-stat--mint i { color: #8ce3c1; }
.ntf-stat--warn i { color: #f3ca78; }

/* Filter strip */
.ntf-filter {
    display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; justify-content: space-between;
    padding: .55rem .65rem; margin-bottom: .85rem;
    background: rgba(255,255,255,.85); border: 1px solid #e2eee8; border-radius: 16px;
    box-shadow: 0 4px 14px rgba(22,48,43,.06);
}
.ntf-filter__search { position: relative; flex: 1 1 16rem; min-width: 14rem; }
.ntf-filter__search i { position: absolute; top: 50%; right: .85rem; transform: translateY(-50%); color: #6f817b; font-size: .9rem; pointer-events: none; }
.ntf-filter__search input {
    width: 100%; padding: .65rem 2.35rem .65rem .9rem; border-radius: 12px;
    border: 1px solid #d6e3dd; background: #f8fbf9; color: #16302b;
    font-family: inherit; font-size: .88rem; transition: border-color .2s, box-shadow .2s;
}
.ntf-filter__search input:focus {
    outline: none; border-color: #0d5e52; background: #fff;
    box-shadow: 0 0 0 .22rem rgba(13,94,82,.14);
}

.ntf-counter {
    display: inline-flex; align-items: center; gap: .55rem;
    background: #f4f9f6; border: 1px solid #e2eee8; border-radius: 999px;
    padding: .35rem .75rem .35rem .95rem; font-size: .74rem; font-weight: 800; color: #0d5e52;
}
.ntf-counter strong { color: #093f37; font-weight: 900; }

.ntf-pills { display: flex; gap: .45rem; flex-wrap: wrap; }
.pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .8rem .45rem .95rem; border-radius: 999px;
    border: 1px solid #d6e3dd; background: #fff; color: #16302b;
    font-family: inherit; font-size: .78rem; font-weight: 800; cursor: pointer;
    transition: background .18s, color .18s, border-color .18s;
}
.pill i { font-size: .85rem; }
.pill span { display: inline-grid; place-items: center; min-width: 1.4rem; height: 1.4rem; padding: 0 .35rem; font-size: .68rem; font-weight: 900; border-radius: 999px; background: #e8f0ec; color: #3a534c; }
.pill:hover { border-color: #c8943f; background: #fffaf0; }
.pill--info i { color: #4286b0; }
.pill--warning i { color: #c8943f; }
.pill--success i { color: #1e8f5a; }
.pill--danger i { color: #c3534d; }
.pill.active { background: linear-gradient(120deg, #093f37, #0d6658); border-color: transparent; color: #fff; }
.pill.active i { color: #f3ca78; }
.pill.active span { background: rgba(255,255,255,.2); color: #fff; }

/* List */
.ntf-card {
    background: #fff; border: 1px solid #e2eee8; border-radius: 18px;
    box-shadow: 0 6px 22px rgba(22,48,43,.06); overflow: hidden;
}
.ntf-card__header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: .8rem 1.1rem; border-bottom: 1px solid #eef4f0;
    background: linear-gradient(180deg, #fff, #fbfdfc);
}
.ntf-card__header .table-title {
    color: #16302b; font-weight: 800; font-size: .98rem; letter-spacing: -.01em;
    display: flex; align-items: center; gap: .5rem;
}
.ntf-card__header .table-title i { color: #c8943f; }
.ntf-card__header .table-subtitle {
    font-size: .74rem; color: #6f817b; display: block; margin-top: .2rem; font-weight: 600;
}
.ntf-card__header__link {
    display: inline-flex; align-items: center; gap: .35rem; color: #0d5e52;
    font-size: .82rem; font-weight: 700; text-decoration: none;
}
.ntf-card__header__link i { font-size: .7rem; }

.ntf-list { display: flex; flex-direction: column; }
.ntf-item {
    display: grid; grid-template-columns: 56px 1fr auto; gap: .85rem; padding: .9rem 1.1rem;
    border-bottom: 1px solid #eef4f0; align-items: start; transition: background .18s;
}
.ntf-item:last-child { border-bottom: 0; }
.ntf-item:nth-child(even) { background: #fbfdfc; }
.ntf-item:hover { background: #f6fbf8; }

.ntf-item--unread {
    background: linear-gradient(90deg, #fffaf0 0%, rgba(255,250,240,0) 60%);
}
.ntf-item--unread::before {
    content: ''; position: absolute; inset: 0; border-right: 4px solid #c8943f; border-radius: 0 12px 12px 0; pointer-events: none;
}

.ntf-item__icon {
    width: 3.2rem; height: 3.2rem; border-radius: 14px; display: grid; place-items: center; font-size: 1.2rem;
    flex: 0 0 auto;
}
.ntf-item__icon--info { background: #ecfdf5; color: #1e8f5a; }
.ntf-item__icon--success { background: #ecfdf5; color: #1e8f5a; }
.ntf-item__icon--warning { background: #fffaf0; color: #8d5e0c; }
.ntf-item__icon--danger { background: #fef2f2; color: #b03228; }

.ntf-item__title {
    display: flex; align-items: center; gap: .5rem; justify-content: space-between;
    margin-bottom: .25rem;
}
.ntf-item__title h4 {
    margin: 0; font-size: .95rem; font-weight: 800; color: #16302b; letter-spacing: -.01em; display: flex; align-items: center; gap: .4rem;
}
.ntf-item--unread .ntf-item__title h4 { color: #093f37; }
.ntf-item__title small {
    color: #6f817b; font-size: .72rem; white-space: nowrap;
}
.ntf-item--unread .ntf-item__title small { color: #c8943f; font-weight: 800; }

.ntf-item__msg {
    font-size: .86rem; color: #34443d; line-height: 1.65; margin-bottom: .4rem; white-space: pre-wrap;
}
.ntf-item__footer {
    display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; margin-top: .3rem;
}
.ntf-type-badge {
    display: inline-flex; align-items: center; gap: .35rem; padding: .28rem .6rem; border-radius: 999px;
    font-size: .7rem; font-weight: 800; border: 1px solid;
}
.ntf-type-badge--info { background: #eff6ff; color: #1858a2; border-color: #bfdbfe; }
.ntf-type-badge--success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.ntf-type-badge--warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.ntf-type-badge--danger { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

.ntf-open-link {
    display: inline-flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 800;
    color: #0d5e52; text-decoration: none; padding: .25rem .55rem; border-radius: 8px; transition: background .18s;
}
.ntf-open-link:hover { background: #e3f1ec; }

.ntf-item__action button,
.ntf-item__action form button {
    display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .8rem;
    border: 1px solid #d6e3dd; border-radius: 10px; background: #fff; color: #34443d;
    font-family: inherit; font-size: .76rem; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: background .18s, color .18s, border-color .18s;
}
.ntf-item__action button:hover,
.ntf-item__action form button:hover {
    background: #0d5e52; color: #fff; border-color: #0d5e52;
}
.ntf-unread-badge {
    display: inline-flex; align-items: center; padding: .16rem .55rem; border-radius: 999px;
    background: #fdf3df; border: 1px solid #ecd9a8; color: #8d5e0c;
    font-size: .64rem; font-weight: 800; letter-spacing: .02em; white-space: nowrap;
}
.ntf-item { position: relative; }

/* clear buttons in hero */
.ntf-hero__ghosts { display: inline-flex; gap: .5rem; flex-wrap: wrap; margin-top: .6rem; }
.ntf-hero__ghost {
    display: inline-flex; align-items: center; gap: .45rem;
    border: 1px solid rgba(255,255,255,.45); background: rgba(255,255,255,.09);
    color: #eaf6ef; border-radius: 10px; padding: .42rem .78rem;
    font-family: inherit; font-size: .8rem; font-weight: 700; cursor: pointer;
    transition: background .18s, border-color .18s, transform .18s;
}
.ntf-hero__ghost:hover { background: rgba(255,255,255,.18); border-color: #f3ca78; transform: translateY(-1px); }
.ntf-hero__ghost--danger { color: #ffd9d4; border-color: rgba(255,200,190,.55); }
.ntf-hero__ghost--danger:hover { background: rgba(178,50,40,.4); border-color: #ffb3ab; }

/* per-item action group + danger variant */
.ntf-item__actions { display: flex; align-items: center; gap: .45rem; }
.ntf-item__action--danger button { border-color: #ecd9d7 !important; color: #a33a33 !important; }
.ntf-item__action--danger button:hover { background: #b91c1c !important; border-color: #b91c1c !important; color: #fff !important; }

/* Empty state */
.ntf-empty {
    padding: 2.5rem 1rem; text-align: center; color: #6f8a80;
}
.ntf-empty i {
    font-size: 2.4rem; color: #c8943f; opacity: .7; display: block; margin-bottom: .55rem;
}
.ntf-empty h3 { font-size: 1rem; font-weight: 800; color: #16302b; margin: 0 0 .35rem; }
.ntf-empty p { font-size: .82rem; margin: 0; }

.sr-only {
    position: absolute !important; height: 1px; width: 1px; overflow: hidden;
    clip: rect(1px, 1px, 1px, 1px); white-space: nowrap; border: 0; padding: 0; margin: -1px;
}

@media (max-width: 720px) {
    .ntf-hero { padding: 1.25rem; gap: 1rem; }
    .ntf-page { padding: .7rem .7rem 1.6rem; }
    .ntf-item { grid-template-columns: 48px 1fr; }
    .ntf-item__actions { grid-column: 1 / -1; justify-self: start; }
}
</style>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($unreadCount > 0)): ?>
    <?php // لا شيء هنا — فقط الحفاظ على بنية الصفحة عند الـ POST الإضافية (لا يحدث فعلاً) ?>
<?php endif; ?>

<div class="ntf-page">

    <!-- ====== Hero Banner ====== -->
    <section class="ntf-hero" aria-labelledby="notifications-title">
        <div class="ntf-hero__left">
            <span class="eyebrow"><i class="fas fa-bell"></i> مركز الإشعارات</span>
            <h1 id="notifications-title">الإشعارات <span class="accent">الشخصية</span></h1>
            <p>كل إشعارات حسابك في مكان واحد — الأحدث أولًا، مع تمييز واضح لغير المقروء منها وتعليمات مباشرة للتعامل بها.</p>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="<?php echo APP_URL; ?>/notifications/read-all" class="d-inline" style="display: inline-block">
                    <?= csrf_field() ?>
                    <button type="submit" class="ntf-hero__cta">
                        <i class="fas fa-check-double"></i> تعليم الكل كمقروء (<?= (int)$unreadCount ?>)
                    </button>
                </form>
            <?php else: ?>
                <span class="ntf-hero__cta" style="background: linear-gradient(90deg, #f4f9f6, #ffffff); color: #34443d; box-shadow: none; cursor: default">
                    <i class="fas fa-circle-check"></i> لا توجد إشعارات غير مقروءة
                </span>
            <?php endif; ?>
            <?php if ($totalCount > 0): ?>
                <div class="ntf-hero__ghosts">
                    <?php if ($readCount > 0): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/notifications/clear-read" class="m-0" onsubmit="return confirm('سيتم حذف كل الإشعارات المقروءة نهائياً. متابعة؟');">
                            <?= csrf_field() ?>
                            <button type="submit" class="ntf-hero__ghost"><i class="fas fa-broom"></i> مسح المقروءة (<?= (int)$readCount ?>)</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/notifications/clear-all" class="m-0" onsubmit="return confirm('سيتم حذف جميع إشعاراتك (مقروءة وغير مقروءة) نهائياً. متابعة؟');">
                        <?= csrf_field() ?>
                        <button type="submit" class="ntf-hero__ghost ntf-hero__ghost--danger"><i class="fas fa-trash"></i> مسح الكل (<?= (int)$totalCount ?>)</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <div class="ntf-hero__stats" aria-label="إحصائيات سريعة">
            <div class="ntf-stat">
                <i class="fas fa-envelope"></i>
                <strong><?= (int)$totalCount ?></strong>
                <span>الإجمالي</span>
            </div>
            <div class="ntf-stat ntf-stat--mint">
                <i class="fas fa-envelope-open-text"></i>
                <strong><?= (int)($totalCount - $unreadCount) ?></strong>
                <span>مقروء</span>
            </div>
            <div class="ntf-stat ntf-stat--warn">
                <i class="fas fa-bell"></i>
                <strong><?= (int)$unreadCount ?></strong>
                <span>غير مقروء</span>
            </div>
        </div>
    </section>

    <!-- ====== Filter strip ====== -->
    <div class="ntf-filter" role="search">
        <label class="ntf-filter__search" for="notificationSearch">
            <i class="fas fa-magnifying-glass"></i>
            <input id="notificationSearch" type="search" placeholder="ابحث في الإشعاراʅ" autocomplete="off">
        </label>
        <div class="ntf-pills" id="notificationTypePills" role="tablist" aria-label="تصفية حسب النوع">
            <button type="button" class="pill active" data-type="all" role="tab">الكل <span><?= (int)$totalCount ?></span></button>
            <button type="button" class="pill pill--warning" data-type="warning" role="tab"><i class="fas fa-triangle-exclamation"></i> تنبيه <span><?= (int)$countWarning ?></span></button>
            <button type="button" class="pill pill--danger" data-type="danger" role="tab"><i class="fas fa-circle-xmark"></i> خطر <span><?= (int)$countDanger ?></span></button>
            <button type="button" class="pill pill--success" data-type="success" role="tab"><i class="fas fa-circle-check"></i> نجاح <span><?= (int)$countSuccess ?></span></button>
            <button type="button" class="pill pill--info" data-type="info" role="tab"><i class="fas fa-circle-info"></i> معلومة <span><?= (int)$countInfo ?></span></button>
        </div>
        <span class="ntf-counter" aria-live="polite"><i class="fas fa-layer-group"></i> <strong id="notificationVisibleCount"><?= (int)$totalCount ?></strong> إشعار</span>
    </div>

    <!-- ====== Card + List ====== -->
    <section class="ntf-card" aria-labelledby="notifications-list-title">
        <div class="ntf-card__header">
            <div>
                <h2 class="table-title" id="notifications-list-title"><i class="fas fa-list-ul"></i> جميع الإشعارات</h2>
                <small class="table-subtitle">حديثة أولًا — الإشعارات غير المقروءة تأتي بتمييز بصري واضح.</small>
            </div>
            <a class="ntf-card__header__link" href="<?php echo APP_URL; ?>/notifications" title="إعادة تحميل القائمة">
                <i class="fas fa-rotate-right"></i> تحديث القائمة
            </a>
        </div>

        <?php if ($notifications === []): ?>
            <div class="ntf-empty" role="status">
                <i class="fas fa-bell-slash"></i>
                <h3>لا توجد إشعارات حالياً</h3>
                <p>عند وصول إشعارات جديدة ستظهر هنا تلقائيًا.</p>
            </div>
        <?php else: ?>
            <div class="ntf-list" id="notificationList">
                <?php foreach ($notifications as $notif):
                    $ntype = (string)($notif['type'] ?? 'info');
                    [$nIcon, $nLabel, $nClass] = $typeIcon[$ntype] ?? $typeIcon['info'];
                    $unread = (int)($notif['is_read'] ?? 1) === 0;
                    $nid = (int)$notif['id'];
                    $relatedUrl = !empty($notif['related_url']) ? APP_URL . $notif['related_url'] : null;
                ?>
                    <article class="ntf-item<?= $unread ? ' ntf-item--unread' : '' ?>"
                             data-type="<?= htmlspecialchars($ntype, ENT_QUOTES, 'UTF-8') ?>"
                             data-title="<?= htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8') ?>"
                             data-message="<?= htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="ntf-item__icon ntf-item__icon--<?= htmlspecialchars($nClass, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                            <i class="fas <?= htmlspecialchars($nIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <div class="ntf-item__body">
                            <div class="ntf-item__title">
                                <h4><?= htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8') ?><?php if ($unread): ?><span class="ntf-unread-badge">غير مقروء</span><?php endif; ?></h4>
                                <small><?= htmlspecialchars($notif['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <p class="ntf-item__msg"><?= nl2br(htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <div class="ntf-item__footer">
                                <span class="ntf-type-badge ntf-type-badge--<?= htmlspecialchars($nClass, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas <?= htmlspecialchars($nIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <?= htmlspecialchars($nLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($relatedUrl): ?>
                                    <a class="ntf-open-link" href="<?= htmlspecialchars($relatedUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-arrow-up-right-from-square"></i> فتح الارتباط
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ntf-item__actions">
                            <?php if ($unread): ?>
                                <form method="POST" action="<?= APP_URL ?>/notifications/read/<?= (int)$nid ?>" class="ntf-item__action m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" title="تعليم كمقروء"><i class="fas fa-check"></i> تعليم</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="<?= APP_URL ?>/notifications/delete/<?= (int)$nid ?>" class="ntf-item__action ntf-item__action--danger m-0" onsubmit="return confirm('حذف هذا الإشعار نهائياً؟');">
                                <?= csrf_field() ?>
                                <button type="submit" title="حذف الإشعار" aria-label="حذف الإشعار"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="ntf-empty" id="notificationNoMatches" style="display: none;" role="status">
                <i class="fas fa-filter-circle-xmark"></i>
                <h3>لا توجد إشعارات مطابقة</h3>
                <p>جرّب تغيير كلمة البحث أو النوع.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
/* notifications — client-side filter + type pills (no backend changes) */
(function () {
    var searchInput = document.getElementById('notificationSearch');
    var items = Array.prototype.slice.call(document.querySelectorAll('#notificationList > .ntf-item'));
    var pills = document.querySelectorAll('#notificationTypePills .pill');
    var visibleCount = document.getElementById('notificationVisibleCount');
    var noMatches = document.getElementById('notificationNoMatches');
    var activeType = 'all';

    function applyFilters() {
        var q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var shown = 0;
        items.forEach(function (el) {
            var matchesType = activeType === 'all' || (el.getAttribute('data-type') || '') === activeType;
            var haystack = [
                el.getAttribute('data-title') || '',
                el.getAttribute('data-message') || '',
                el.getAttribute('data-type') || ''
            ].join(' ').toLowerCase();
            var matchesQuery = !q || haystack.indexOf(q) !== -1;
            var visible = matchesType && matchesQuery;
            el.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        if (visibleCount) visibleCount.textContent = String(shown);
        if (noMatches) noMatches.style.display = shown === 0 ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            activeType = pill.getAttribute('data-type') || 'all';
            pills.forEach(function (p) { p.classList.toggle('active', p === pill); });
            applyFilters();
        });
    });
    applyFilters();
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
