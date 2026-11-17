<?php
require_once APP_ROOT . '/app/Views/layouts/header.php';

$filters      = $filters ?? [];
$stats        = $stats ?? [];
$page         = max(1, (int)($page ?? 1));
$perPage      = max(1, (int)($perPage ?? 15));
$totalCount   = (int)($totalCount ?? 0);
$totalPages   = (int)($totalPages ?? 0);
$issuers      = $issuers ?? [];
$branches     = $branches ?? [];
$years        = $years ?? [];
$snapshots    = $snapshots ?? [];
$viewMode     = $viewMode ?? 'list';
$employeeRows = $employeeRows ?? [];
$employeeDetail = $employeeDetail ?? null;
$employeeId   = (int)($employeeId ?? 0);
$latestReport = $latestReport ?? null;

$current = [
    'q'          => (string)($filters['q'] ?? ''),
    'branch_id'  => (int)($filters['branch_id'] ?? 0),
    'year'       => (int)($filters['year'] ?? 0),
    'type'       => (string)($filters['type'] ?? ''),
    'date_from'  => (string)($filters['date_from'] ?? ''),
    'date_to'    => (string)($filters['date_to'] ?? ''),
    'created_by' => (int)($filters['created_by'] ?? 0),
    'sort'       => (string)($filters['sort'] ?? 'newest'),
    'per_page'   => (int)($filters['per_page'] ?? 15),
    'view'       => (string)($filters['view'] ?? 'list'),
];

$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

$archiveUrl = static function (array $changes = [], ?int $pageNo = null) use ($current, $viewMode, $employeeId): string {
    $data = array_merge($current, $changes);
    $query = [];

    if ((string)($data['q'] ?? '') !== '') {
        $query['q'] = (string)$data['q'];
    }
    if ((int)($data['branch_id'] ?? 0) > 0) {
        $query['branch_id'] = (int)$data['branch_id'];
    }
    if ((int)($data['year'] ?? 0) > 0) {
        $query['year'] = (int)$data['year'];
    }
    if ((string)($data['type'] ?? '') !== '') {
        $query['type'] = (string)$data['type'];
    }
    if ((string)($data['date_from'] ?? '') !== '') {
        $query['date_from'] = (string)$data['date_from'];
    }
    if ((string)($data['date_to'] ?? '') !== '') {
        $query['date_to'] = (string)$data['date_to'];
    }
    if ((int)($data['created_by'] ?? 0) > 0) {
        $query['created_by'] = (int)$data['created_by'];
    }
    if ((string)($data['sort'] ?? 'newest') !== 'newest') {
        $query['sort'] = (string)$data['sort'];
    }
    if ((int)($data['per_page'] ?? 15) !== 15) {
        $query['per_page'] = (int)$data['per_page'];
    }
    if ((string)($data['view'] ?? 'list') !== 'list') {
        $query['view'] = (string)$data['view'];
    }
    if ($pageNo !== null && $pageNo > 1) {
        $query['page'] = $pageNo;
    }

    $basePath = ($viewMode === 'detail' && $employeeId > 0)
        ? APP_URL . '/reports/archive/employee/' . $employeeId
        : APP_URL . '/reports/archive';
    return $basePath . ($query !== [] ? '?' . http_build_query($query) : '');
};

$typeLabels = [
    'employee' => 'فردي عام',
    'periodic' => 'دوري',
    'course'   => 'دورة',
    'workshop' => 'ورشة',
    'skill'    => 'مهاري',
];

$branchNames = [];
foreach ($branches as $branch) {
    $branchNames[(int)$branch->id] = (string)$branch->name;
}

$issuerNames = [];
foreach ($issuers as $issuer) {
    $issuerNames[(int)$issuer->id] = trim((string)($issuer->username ?? '')) !== ''
        ? (string)$issuer->username
        : 'مستخدم #' . (int)$issuer->id;
}

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date('d/m/Y', $timestamp);
};

$formatTime = static function (?string $value): string {
    $timestamp = $value !== null && $value !== '' ? strtotime($value) : false;
    return $timestamp === false ? '' : date('H:i', $timestamp);
};

$relativeTime = static function (?string $value): string {
    $timestamp = $value !== null && $value !== '' ? strtotime($value) : false;
    if ($timestamp === false) {
        return '';
    }
    $diff = time() - $timestamp;
    if ($diff < 0) {
        return '';
    }

    $arabicUnit = static function (int $count, string $one, string $two, string $few, string $many): string {
        return match (true) {
            $count === 1 => $one,
            $count === 2 => $two,
            $count <= 10 => $count . ' ' . $few,
            default => $count . ' ' . $many,
        };
    };

    if ($diff < 3600) {
        $minutes = max(1, (int)floor($diff / 60));
        return 'منذ ' . $arabicUnit($minutes, 'دقيقة', 'دقيقتين', 'دقائق', 'دقيقة');
    }
    if ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return 'منذ ' . $arabicUnit($hours, 'ساعة', 'ساعتين', 'ساعات', 'ساعة');
    }
    if ($diff < 2592000) {
        $days = (int)floor($diff / 86400);
        return 'منذ ' . $arabicUnit($days, 'يوم', 'يومين', 'أيام', 'يوماً');
    }
    return '';
};

$formatScore = static function (?float $score): string {
    if ($score === null) {
        return '—';
    }
    $text = rtrim(rtrim(number_format($score, 1, '.', ''), '0'), '.');
    return ($text === '' ? '0' : $text) . '%';
};

$scoreClass = static function (?float $score): string {
    if ($score === null) {
        return 'is-none';
    }
    if ($score >= 80) {
        return 'is-high';
    }
    if ($score >= 60) {
        return 'is-mid';
    }
    return 'is-low';
};

$hasFilters = $current['q'] !== ''
    || $current['branch_id'] > 0
    || $current['year'] > 0
    || $current['type'] !== ''
    || $current['date_from'] !== ''
    || $current['date_to'] !== ''
    || $current['created_by'] > 0;

$activeChips = [];
if ($current['q'] !== '') {
    $activeChips[] = ['label' => 'بحث: ' . $current['q'], 'changes' => ['q' => '']];
}
if ($current['branch_id'] > 0) {
    $activeChips[] = [
        'label'   => 'الفرع: ' . ($branchNames[$current['branch_id']] ?? ('#' . $current['branch_id'])),
        'changes' => ['branch_id' => 0],
    ];
}
if ($current['year'] > 0) {
    $activeChips[] = ['label' => 'السنة: ' . $current['year'], 'changes' => ['year' => 0]];
}
if ($current['type'] !== '') {
    $activeChips[] = [
        'label'   => 'النوع: ' . ($typeLabels[$current['type']] ?? $current['type']),
        'changes' => ['type' => ''],
    ];
}
if ($current['date_from'] !== '') {
    $activeChips[] = ['label' => 'من: ' . $formatDate($current['date_from']), 'changes' => ['date_from' => '']];
}
if ($current['date_to'] !== '') {
    $activeChips[] = ['label' => 'إلى: ' . $formatDate($current['date_to']), 'changes' => ['date_to' => '']];
}
if ($current['created_by'] > 0) {
    $activeChips[] = [
        'label'   => 'أصدره: ' . ($issuerNames[$current['created_by']] ?? ('#' . $current['created_by'])),
        'changes' => ['created_by' => 0],
    ];
}

$firstRow = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$lastRow  = min($page * $perPage, $totalCount);

$paginationItems = [];
if ($totalPages > 1) {
    $shown = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
            $shown[] = $i;
        }
    }
    $previous = null;
    foreach ($shown as $number) {
        if ($previous !== null && $number - $previous > 1) {
            $paginationItems[] = ['type' => 'gap'];
        }
        $paginationItems[] = ['type' => 'page', 'number' => $number];
        $previous = $number;
    }
}

$hiddenFilterInputs = static function () use ($current, $e): string {
    $html = '';
    foreach (['q', 'type', 'date_from', 'date_to', 'view'] as $key) {
        if ((string)$current[$key] !== '') {
            $html .= '<input type="hidden" name="' . $e($key) . '" value="' . $e($current[$key]) . '">';
        }
    }
    foreach (['branch_id', 'year', 'created_by'] as $key) {
        if ((int)$current[$key] > 0) {
            $html .= '<input type="hidden" name="' . $e($key) . '" value="' . (int)$current[$key] . '">';
        }
    }
    return $html;
};
?>
<style>
.archive-page {
    --ar-green: #0c2823;
    --ar-green-soft: #14443c;
    --ar-gold: #c8943f;
    --ar-gold-soft: #f5f0e8;
    --ar-border: #e8e2d6;
    --ar-muted: #6c757d;
    --ar-danger: #8b2222;
    padding-bottom: 32px;
}
.archive-page h1 { font-size: 22px; font-weight: 800; color: var(--ar-green); margin: 0; }
.archive-page h2 { font-size: 16px; font-weight: 800; color: var(--ar-green); margin: 0; }

.archive-hero {
    display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;
    background: linear-gradient(135deg, #0c2823 0%, #123a33 100%);
    border-radius: 16px; padding: 22px 24px; color: #fff; margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(12, 40, 35, 0.16);
}
.archive-hero__main { display: flex; align-items: center; gap: 16px; min-width: 240px; }
.archive-hero__icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 22px;
    background: rgba(200, 148, 63, 0.14); color: var(--ar-gold);
    border: 1px solid rgba(200, 148, 63, 0.28);
}
.archive-hero h1 { color: #fff; }
.archive-hero__subtitle { font-size: 12px; color: rgba(255, 255, 255, 0.66); margin: 4px 0 0; line-height: 1.7; }
.archive-hero__meta { display: flex; align-items: center; gap: 6px; font-size: 11px; color: rgba(255, 255, 255, 0.55); margin-top: 6px; }
.archive-hero__actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.btn-hero-primary, .btn-hero-ghost {
    display: inline-flex; align-items: center; gap: 7px; border-radius: 9px; padding: 9px 16px;
    font-size: 12px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: 0.15s ease;
}
.btn-hero-primary { background: var(--ar-gold); color: #14261f; }
.btn-hero-primary:hover { background: #d9a851; color: #14261f; }
.btn-hero-ghost { background: rgba(255, 255, 255, 0.08); color: #fff; border: 1px solid rgba(255, 255, 255, 0.18); }
.btn-hero-ghost:hover { background: rgba(255, 255, 255, 0.16); color: #fff; }

.scope-note {
    display: flex; align-items: center; gap: 8px; background: var(--ar-gold-soft); border: 1px solid #e6dcc8;
    border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: var(--ar-green);
}
.scope-note i { color: var(--ar-gold); }

.stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
.stat-card {
    background: #fff; border: 1px solid var(--ar-border); border-radius: 14px; padding: 16px 18px;
    display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}
.stat-card__icon {
    width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0; font-size: 17px;
    display: flex; align-items: center; justify-content: center;
    background: var(--ar-gold-soft); color: var(--ar-green);
}
.stat-card__value { font-size: 22px; font-weight: 900; color: var(--ar-green); line-height: 1.15; }
.stat-card__label { font-size: 11px; color: var(--ar-muted); margin-top: 3px; }
.stat-card--accent { background: var(--ar-green); border-color: var(--ar-green); }
.stat-card--accent .stat-card__icon { background: rgba(200, 148, 63, 0.16); color: var(--ar-gold); }
.stat-card--accent .stat-card__value { color: var(--ar-gold); }
.stat-card--accent .stat-card__label { color: rgba(255, 255, 255, 0.58); }

.panel {
    background: #fff; border: 1px solid var(--ar-border); border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.04); margin-bottom: 16px;
}
.panel__head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    padding: 14px 18px; border-bottom: 1px solid #f0ece3; background: #fcfaf6;
}
.panel__head-main { display: flex; align-items: center; gap: 10px; }
.panel__head i { color: var(--ar-gold); }
.panel__body { padding: 16px 18px; }

.archive-filters { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 12px; align-items: end; }
.field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.field > label { font-size: 11px; font-weight: 700; color: var(--ar-muted); }
.field select, .field input {
    width: 100%; padding: 9px 12px; border: 1px solid #ddd6c8; border-radius: 8px;
    font-family: 'Cairo'; font-size: 12px; background: #fff; color: #222; height: 38px;
}
.field select:focus, .field input:focus {
    outline: none; border-color: var(--ar-gold); box-shadow: 0 0 0 3px rgba(200, 148, 63, 0.14);
}
.field--branch { grid-column: span 2; }
.field--year { grid-column: span 1; }
.field--type { grid-column: span 2; }
.field--dates { grid-column: span 3; }
.field--dates .date-row { display: flex; gap: 6px; align-items: center; }
.field--dates .date-row input { width: auto; flex: 1 1 0; min-width: 0; }
.field--dates .date-row span { flex-shrink: 0; font-size: 10px; color: var(--ar-muted); }
.field--issuer { grid-column: span 2; }
.field--search { grid-column: span 2; }
.filter-actions { display: flex; gap: 8px; align-items: center; grid-column: span 12; justify-content: flex-end; }
.btn-apply, .btn-clear {
    display: inline-flex; align-items: center; gap: 7px; border-radius: 8px; padding: 9px 20px;
    font-family: 'Cairo'; font-size: 12px; font-weight: 700; cursor: pointer; border: none; height: 38px;
}
.btn-apply { background: var(--ar-green); color: var(--ar-gold); }
.btn-apply:hover { background: #103831; }
.btn-apply:disabled { opacity: 0.7; cursor: wait; }
.btn-clear { background: #f3f1ec; color: var(--ar-muted); text-decoration: none; border: 1px solid #e4ded2; }
.btn-clear:hover { background: #eae6dd; color: var(--ar-green); }
.btn-clear--ghost { background: transparent; border-color: transparent; }
.btn-clear--ghost:hover { background: #f3f1ec; }

.active-filters { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #eee6d8; }
.active-filters__label { font-size: 11px; font-weight: 700; color: var(--ar-muted); }
.filter-chip {
    display: inline-flex; align-items: center; gap: 6px; background: var(--ar-gold-soft);
    border: 1px solid #e6dcc8; color: var(--ar-green); border-radius: 20px; padding: 4px 10px;
    font-size: 11px; font-weight: 600; text-decoration: none;
}
.filter-chip:hover { border-color: var(--ar-gold); color: var(--ar-green); }
.filter-chip i { color: var(--ar-gold); font-size: 9px; }
.filter-chip--clear { background: transparent; border-style: dashed; color: var(--ar-danger); border-color: rgba(139, 34, 34, 0.25); }
.filter-chip--clear i { color: var(--ar-danger); }

.results-tools { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.results-tools label { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--ar-muted); margin: 0; }
.results-tools select {
    padding: 6px 10px; border: 1px solid #ddd6c8; border-radius: 7px; font-family: 'Cairo';
    font-size: 11px; background: #fff; height: 32px;
}
.results-count { font-size: 11px; color: var(--ar-muted); }
.results-count strong { color: var(--ar-green); }

.table-scroll { overflow-x: auto; }
.archive-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.archive-table thead th {
    background: #0c2823; color: #fff; padding: 11px 14px; text-align: right;
    font-size: 11px; font-weight: 700; white-space: nowrap; position: sticky; top: 0; z-index: 2;
}
.archive-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f0ece3; font-size: 12px; text-align: right; vertical-align: middle; }
.archive-table tbody tr:nth-child(even) { background: #fdfbf7; }
.archive-table tbody tr:hover { background: rgba(200, 148, 63, 0.06); }
.archive-table tbody tr:last-child td { border-bottom: none; }

.report-cell { display: flex; flex-direction: column; gap: 3px; }
.report-number { display: inline-flex; align-items: center; gap: 6px; font-weight: 800; color: var(--ar-green); unicode-bidi: isolate; }
.report-date { font-size: 10px; color: var(--ar-muted); unicode-bidi: isolate; }
.report-relative { font-size: 10px; color: #a6a6a6; }
.btn-copy {
    border: none; background: transparent; color: #b9b2a5; cursor: pointer; padding: 2px; font-size: 10px;
    border-radius: 5px; line-height: 1;
}
.btn-copy:hover { color: var(--ar-gold); background: var(--ar-gold-soft); }

.employee-cell { display: flex; align-items: center; gap: 10px; }
.employee-avatar {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center;
    justify-content: center; background: var(--ar-green); color: var(--ar-gold); font-size: 14px; font-weight: 800;
}
.employee-name { font-weight: 700; color: #1f2d29; }
.employee-meta { font-size: 10px; color: var(--ar-muted); margin-top: 2px; line-height: 1.5; }
.employee-code { font-size: 10px; color: #9aa39f; unicode-bidi: isolate; }

.branch-chip {
    display: inline-flex; align-items: center; gap: 5px; background: #f4f6f4; color: #40524c;
    border: 1px solid #e6ebe7; border-radius: 20px; padding: 4px 10px; font-size: 10px; font-weight: 600;
}
.branch-chip i { color: #8aa39a; font-size: 9px; }

.eval-cell { display: flex; flex-direction: column; gap: 5px; }
.eval-badges { display: flex; flex-wrap: wrap; gap: 4px; }
.type-badge {
    display: inline-flex; align-items: center; gap: 4px; border-radius: 20px;
    padding: 3px 8px; font-size: 9px; font-weight: 700; border: 1px solid transparent; white-space: nowrap;
}
.type-badge b { font-weight: 800; }
.type-badge--employee { background: rgba(13, 94, 82, 0.09); color: #0d5e52; border-color: rgba(13, 94, 82, 0.14); }
.type-badge--periodic { background: rgba(94, 66, 140, 0.09); color: #5e428c; border-color: rgba(94, 66, 140, 0.14); }
.type-badge--course { background: rgba(200, 148, 63, 0.12); color: #9a6d1f; border-color: rgba(200, 148, 63, 0.2); }
.type-badge--workshop { background: rgba(176, 92, 39, 0.1); color: #a3541f; border-color: rgba(176, 92, 39, 0.16); }
.type-badge--skill { background: rgba(32, 111, 74, 0.1); color: #206f4a; border-color: rgba(32, 111, 74, 0.16); }

.score-line { display: flex; align-items: center; gap: 7px; }
.score-value { font-size: 10px; font-weight: 800; color: var(--ar-green); unicode-bidi: isolate; }
.score-track { flex: 1; min-width: 46px; max-width: 86px; height: 5px; border-radius: 4px; background: #ece9e1; overflow: hidden; }
.score-fill { display: block; height: 100%; border-radius: 4px; }
.score-fill.is-high { background: #0d5e52; }
.score-fill.is-mid { background: var(--ar-gold); }
.score-fill.is-low { background: var(--ar-danger); }
.score-empty { font-size: 10px; color: #b3ada1; }

.issuer-cell { display: flex; align-items: center; gap: 6px; color: #4a5a55; font-size: 11px; }
.issuer-cell i { color: #b9b2a5; font-size: 10px; }

.actions-cell { display: flex; align-items: center; gap: 5px; }
.icon-btn {
    width: 30px; height: 30px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #e6e0d4; background: #fff; color: #6c757d; font-size: 12px; text-decoration: none;
    transition: 0.15s ease;
}
.icon-btn:hover { color: var(--ar-green); border-color: var(--ar-gold); background: var(--ar-gold-soft); }
.icon-btn.is-primary { background: var(--ar-green); border-color: var(--ar-green); color: var(--ar-gold); }
.icon-btn.is-primary:hover { background: #103831; color: var(--ar-gold); }
.icon-btn.is-pdf:hover { color: #b3261e; border-color: rgba(179, 38, 30, 0.4); background: rgba(179, 38, 30, 0.06); }
.icon-btn.is-xlsx:hover { color: #1d6f42; border-color: rgba(29, 111, 66, 0.4); background: rgba(29, 111, 66, 0.06); }
.icon-btn.is-docx:hover { color: #2b579a; border-color: rgba(43, 87, 154, 0.4); background: rgba(43, 87, 154, 0.06); }

.panel__foot {
    display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
    padding: 12px 18px; border-top: 1px solid #f0ece3; background: #fcfaf6;
}
.pagination { display: flex; justify-content: center; gap: 4px; flex-wrap: wrap; }
.pagination a, .pagination span {
    min-width: 32px; height: 32px; padding: 0 8px; border-radius: 7px; border: 1px solid #e0d9cc; background: #fff;
    color: #6c757d; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center;
    justify-content: center; text-decoration: none; unicode-bidi: isolate;
}
.pagination a:hover { border-color: var(--ar-gold); color: var(--ar-green); }
.pagination a.active { background: var(--ar-green); color: var(--ar-gold); border-color: var(--ar-green); }
.pagination span.is-disabled { opacity: 0.45; cursor: not-allowed; }
.pagination span.is-gap { border: none; background: transparent; color: #b3ada1; }

.empty-state { text-align: center; padding: 52px 20px; }
.empty-state__icon {
    width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%; background: var(--ar-gold-soft);
    color: var(--ar-gold); display: flex; align-items: center; justify-content: center; font-size: 26px;
}
.empty-state__title { font-size: 15px; font-weight: 800; color: var(--ar-green); }
.empty-state__hint { font-size: 11px; color: #9aa39f; margin-top: 6px; line-height: 1.8; }
.empty-state__action { margin-top: 16px; }
.empty-state__chips { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; margin-top: 12px; }
.empty-chips-label { font-size: 10px; color: #b3ada1; }

.copy-toast {
    position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: var(--ar-green); color: #fff; padding: 9px 18px; border-radius: 9px; font-size: 12px;
    font-weight: 700; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); opacity: 0; pointer-events: none;
    transition: 0.2s ease; z-index: 9999;
}
.copy-toast.is-visible { opacity: 1; transform: translateX(-50%) translateY(0); }

.view-toggle { display: inline-flex; border: 1px solid var(--ar-border); border-radius: 8px; overflow: hidden; }
.view-toggle a { padding: 7px 14px; font-size: 11px; font-weight: 700; color: var(--ar-muted); text-decoration: none; background: #fff; }
.view-toggle a:hover { background: var(--ar-gold-soft); color: var(--ar-green); }
.view-toggle a.active { background: var(--ar-green); color: var(--ar-gold); }

@media (max-width: 1200px) {
    .field--branch, .field--type, .field--issuer, .field--search { grid-column: span 3; }
    .field--year { grid-column: span 2; }
    .field--dates { grid-column: span 4; }
    .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 860px) {
    .field--branch, .field--type, .field--issuer, .field--search, .field--year, .field--dates { grid-column: span 6; }
    .filter-actions { grid-column: span 12; justify-content: stretch; }
    .filter-actions .btn-apply, .filter-actions .btn-clear { flex: 1; justify-content: center; }
    .archive-table { min-width: 0; }
    .archive-table thead { display: none; }
    .archive-table, .archive-table tbody, .archive-table tr, .archive-table td { display: block; width: 100%; }
    .archive-table tbody tr {
        border: 1px solid var(--ar-border); border-radius: 12px; margin: 12px; padding: 6px 12px;
        background: #fff !important;
    }
    .archive-table tbody td {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
        border-bottom: 1px dashed #f0ece3; padding: 9px 0; text-align: left;
    }
    .archive-table tbody td:last-child { border-bottom: none; }
    .archive-table tbody td::before {
        content: attr(data-label); color: var(--ar-muted); font-size: 10px; font-weight: 700;
        flex-shrink: 0; padding-top: 2px; text-align: right;
    }
    .archive-table tbody td > * { max-width: 72%; }
    .archive-table tbody td[data-label=""]::before { display: none; }
    .archive-table tbody td[data-label=""] > * { max-width: 100%; width: 100%; }
    .archive-table tbody td > .actions-cell, .archive-table tbody td > .employee-cell,
    .archive-table tbody td > .eval-cell, .archive-table tbody td > .report-cell { justify-content: flex-end; }
    .employee-cell { flex-direction: row-reverse; text-align: right; }
    .employee-cell > div { text-align: right; }
    .panel__foot { flex-direction: column; }
}
@media (max-width: 560px) {
    .archive-hero { padding: 18px; }
    .archive-hero__actions { width: 100%; }
    .archive-hero__actions a { flex: 1; justify-content: center; }
    .stats-grid { grid-template-columns: 1fr; }
    .field--branch, .field--type, .field--issuer, .field--search, .field--year, .field--dates { grid-column: span 12; }
}

@media print {
    .archive-hero__actions, .archive-filters, .active-filters, .results-tools, .actions-cell,
    .btn-copy, .scope-note, .copy-toast, .pagination, .panel__foot .btn-clear { display: none !important; }
    .archive-hero { background: #fff !important; color: #000 !important; box-shadow: none; border: 1px solid #ccc; }
    .archive-hero h1, .archive-hero__subtitle, .archive-hero__meta { color: #000 !important; }
    .archive-table { min-width: 0; }
    .archive-table thead th { background: #eee !important; color: #000 !important; }
    .panel { box-shadow: none; border-color: #ccc; }
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
}
</style>

<div class="archive-page">
    <section class="archive-hero">
        <div class="archive-hero__main">
            <div class="archive-hero__icon"><i class="fas fa-box-archive"></i></div>
            <div>
                <h1><?= $e($pageTitle ?? 'سجل التقارير المؤرشفة') ?></h1>
                <p class="archive-hero__subtitle">سجل ثابت لكل تقرير تم إصداره — لا يمكن تعديله أو حذفه بعد الإصدار.</p>
                <?php if (!empty($stats['last_issue'])): ?>
                    <div class="archive-hero__meta">
                        <i class="fas fa-clock"></i>
                        آخر إصدار: <span dir="ltr"><?= $e($formatDate($stats['last_issue']) . ' ' . $formatTime($stats['last_issue'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="archive-hero__actions">
            <a class="btn-hero-primary" href="<?= APP_URL ?>/reports/create"><i class="fas fa-file-circle-plus"></i> إصدار تقرير جديد</a>
            <a class="btn-hero-ghost" href="<?= $e($archiveUrl()) ?>"><i class="fas fa-rotate"></i> تحديث</a>
        </div>
    </section>

    <?php if (!empty($scopeLabel) && (($scope['mode'] ?? 'all') !== 'all')): ?>
        <div class="scope-note">
            <i class="fas fa-shield-alt"></i>
            <span>تظهر لك تقارير <?= $e($scopeLabel) ?> فقط.</span>
        </div>
    <?php endif; ?>

    <?php if ($viewMode === 'detail' && $employeeDetail): ?>
        <div class="panel" style="margin-bottom:16px;">
            <div class="panel__body" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="employee-avatar" style="width:46px;height:46px;font-size:18px;"><?= $e(mb_substr((string)$employeeDetail->full_name,0,1,'UTF-8')) ?></div>
                    <div>
                        <div style="font-size:16px;font-weight:800;color:var(--ar-green);"><?= $e($employeeDetail->full_name) ?></div>
                        <div style="font-size:11px;color:var(--ar-muted);"><?= $e($employeeDetail->emp_code ?? '') ?> · <?= $e($employeeDetail->position_title ?? '') ?> · <?= $e($employeeDetail->department_name ?? '') ?> · <?= $e($employeeDetail->branch_name ?? '') ?></div>
                    </div>
                </div>
                <a class="btn-clear" href="<?= $e(APP_URL . '/reports/archive?view=employee') ?>"><i class="fas fa-arrow-right"></i> رجوع إلى قائمة الموظفين</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card stat-card--accent">
            <div class="stat-card__icon"><i class="fas fa-file-lines"></i></div>
            <div>
                <div class="stat-card__value"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="stat-card__label"><?= $viewMode === 'detail' ? 'إجمالي تقارير الموظف' : 'إجمالي النتائج' ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="stat-card__value"><?= (int)($stats['this_month'] ?? 0) ?></div>
                <div class="stat-card__label">تقارير هذا الشهر</div>
            </div>
        </div>
        <?php if ($viewMode === 'detail'): ?>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-card__value" dir="ltr"><?= $e($latestReport->report_number ?? '—') ?></div>
                <div class="stat-card__label">آخر إصدار</div>
                <?php if (!empty($latestReport->created_at)): ?>
                    <div style="font-size:10px;color:var(--ar-muted);margin-top:2px;"><?= $e($formatDate($latestReport->created_at) . ' · ' . $formatTime($latestReport->created_at)) ?></div>
                    <?php $rel = $relativeTime($latestReport->created_at); ?>
                    <?php if ($rel !== ''): ?><div style="font-size:10px;color:#a6a6a6;"><?= $e($rel) ?></div><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-card__value"><?= (int)($stats['employees'] ?? 0) ?></div>
                <div class="stat-card__label">موظفون مشمولون</div>
            </div>
        </div>
        <?php endif; ?>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fas fa-gauge-high"></i></div>
            <div>
                <div class="stat-card__value">
                    <?php $avgScore = $stats['avg_score'] ?? null; ?>
                    <?= $e($formatScore($avgScore !== null ? (float)$avgScore : null)) ?>
                </div>
                <div class="stat-card__label"><?= $viewMode === 'detail' ? 'متوسط درجات الموظف' : 'متوسط درجات التقارير' ?></div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel__head">
            <div class="panel__head-main">
                <i class="fas fa-filter"></i>
                <h2>تصفية السجل</h2>
            </div>
        </div>
        <div class="panel__body">
            <form method="GET" action="<?= $viewMode === 'detail' && $employeeId > 0 ? APP_URL . '/reports/archive/employee/' . $employeeId : APP_URL . '/reports/archive' ?>" class="archive-filters" id="archiveFilters">
                <input type="hidden" name="sort" value="<?= $e($current['sort']) ?>">
                <input type="hidden" name="per_page" value="<?= (int)$current['per_page'] ?>">
                <?php if (($current['view'] ?? 'list') !== 'list'): ?>
                    <input type="hidden" name="view" value="<?= $e($current['view']) ?>">
                <?php endif; ?>

                <?php if ($viewMode !== 'detail'): ?>
                <div class="field field--branch">
                    <label for="filterBranch">الفرع (وقت الإصدار)</label>
                    <select name="branch_id" id="filterBranch">
                        <option value="0">كل الفروع</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= (int)$branch->id ?>" <?= $current['branch_id'] === (int)$branch->id ? 'selected' : '' ?>>
                                <?= $e($branch->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="field field--year">
                    <label for="filterYear">السنة</label>
                    <select name="year" id="filterYear">
                        <option value="0">الكل</option>
                        <?php foreach ($years as $yearOption): ?>
                            <option value="<?= (int)$yearOption ?>" <?= $current['year'] === (int)$yearOption ? 'selected' : '' ?>>
                                <?= (int)$yearOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field field--type">
                    <label for="filterType">نوع التقييم</label>
                    <select name="type" id="filterType" title="حسب تصنيف النسخة المحفوظة وقت الإصدار">
                        <option value="">كل الأنواع</option>
                        <?php foreach ($typeLabels as $typeKey => $typeLabel): ?>
                            <option value="<?= $e($typeKey) ?>" <?= $current['type'] === $typeKey ? 'selected' : '' ?>>
                                <?= $e($typeLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field field--dates">
                    <label>تاريخ الإصدار</label>
                    <div class="date-row">
                        <input type="date" name="date_from" value="<?= $e($current['date_from']) ?>" aria-label="من تاريخ">
                        <span>إلى</span>
                        <input type="date" name="date_to" value="<?= $e($current['date_to']) ?>" aria-label="إلى تاريخ">
                    </div>
                </div>

                <div class="field field--issuer">
                    <label for="filterIssuer">أصدره</label>
                    <select name="created_by" id="filterIssuer">
                        <option value="0">كل المستخدمين</option>
                        <?php foreach ($issuers as $issuer): ?>
                            <option value="<?= (int)$issuer->id ?>" <?= $current['created_by'] === (int)$issuer->id ? 'selected' : '' ?>>
                                <?= $e($issuerNames[(int)$issuer->id] ?? ('#' . (int)$issuer->id)) ?> (<?= (int)$issuer->total ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field field--search">
                    <label for="filterSearch">بحث</label>
                    <input type="text" name="q" id="filterSearch" value="<?= $e($current['q']) ?>"
                           placeholder="<?= $viewMode === 'detail' ? 'بحث في تقارير هذا الموظف...' : 'رقم المرجع أو اسم الموظف أو رقمه الوظيفي' ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-apply" id="filterSubmit"><i class="fas fa-magnifying-glass"></i> تطبيق الفلاتر</button>
                    <?php if ($hasFilters): ?>
                        <a class="btn-clear" href="<?= $e($archiveUrl([
                            'q' => '', 'branch_id' => 0, 'year' => 0, 'type' => '',
                            'date_from' => '', 'date_to' => '', 'created_by' => 0,
                        ])) ?>"><i class="fas fa-xmark"></i> مسح الكل</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($activeChips !== []): ?>
                <div class="active-filters">
                    <span class="active-filters__label"><i class="fas fa-sliders"></i> فلاتر نشطة:</span>
                    <?php foreach ($activeChips as $chip): ?>
                        <a class="filter-chip" href="<?= $e($archiveUrl($chip['changes'])) ?>" title="إزالة هذا الفلتر">
                            <?= $e($chip['label']) ?>
                            <i class="fas fa-xmark"></i>
                        </a>
                    <?php endforeach; ?>
                    <a class="filter-chip filter-chip--clear" href="<?= $e($archiveUrl([
                        'q' => '', 'branch_id' => 0, 'year' => 0, 'type' => '',
                        'date_from' => '', 'date_to' => '', 'created_by' => 0,
                    ])) ?>" title="مسح كل الفلاتر">
                        <i class="fas fa-rotate-left"></i> مسح الكل
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel__head">
            <div class="panel__head-main">
                <i class="fas fa-list-check"></i>
                <h2><?= $viewMode === 'detail' && $employeeDetail ? 'تقارير ' . $e($employeeDetail->full_name) : 'النتائج' ?> <span class="results-count">(<?= (int)$totalCount ?>)</span></h2>
            </div>
            <?php if ($viewMode !== 'detail'): ?>
                <div class="view-toggle" role="group" aria-label="طريقة العرض">
                    <a class="<?= ($filters['view'] ?? 'list') === 'list' ? 'active' : '' ?>" href="<?= $e($archiveUrl(['view' => 'list'])) ?>">قائمة</a>
                    <a class="<?= ($filters['view'] ?? 'list') === 'employee' ? 'active' : '' ?>" href="<?= $e($archiveUrl(['view' => 'employee'])) ?>">حسب الموظف</a>
                </div>
            <?php endif; ?>
            <form method="GET" action="<?= $viewMode === 'detail' && $employeeId > 0 ? APP_URL . '/reports/archive/employee/' . $employeeId : APP_URL . '/reports/archive' ?>" class="results-tools" id="resultsTools">
                <?= $hiddenFilterInputs() ?>
                <span class="results-count">
                    عرض <strong><?= (int)$firstRow ?></strong>–<strong><?= (int)$lastRow ?></strong>
                    من <strong><?= (int)$totalCount ?></strong>
                </span>
                <label for="sortSelect">الترتيب
                    <select name="sort" id="sortSelect">
                        <option value="newest" <?= $current['sort'] === 'newest' ? 'selected' : '' ?>>الأحدث أولاً</option>
                        <option value="oldest" <?= $current['sort'] === 'oldest' ? 'selected' : '' ?>>الأقدم أولاً</option>
                        <option value="report_no" <?= $current['sort'] === 'report_no' ? 'selected' : '' ?>>رقم التقرير</option>
                        <option value="name_asc" <?= $current['sort'] === 'name_asc' ? 'selected' : '' ?>>اسم الموظف (أ-ي)</option>
                        <option value="name_desc" <?= $current['sort'] === 'name_desc' ? 'selected' : '' ?>>اسم الموظف (ي-أ)</option>
                        <option value="branch" <?= $current['sort'] === 'branch' ? 'selected' : '' ?>>الفرع</option>
                    </select>
                </label>
                <label for="perPageSelect">لكل صفحة
                    <select name="per_page" id="perPageSelect">
                        <?php foreach ([10, 15, 25, 50] as $size): ?>
                            <option value="<?= (int)$size ?>" <?= $current['per_page'] === (int)$size ? 'selected' : '' ?>><?= (int)$size ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <noscript><button type="submit" class="btn-clear">تطبيق</button></noscript>
            </form>
        </div>

        <?php if ($viewMode === 'employee'): ?>
            <?php if ($employeeRows !== []): ?>
                <div class="table-scroll">
                    <table class="archive-table">
                        <caption class="visually-hidden">سجل التقارير حسب الموظف</caption>
                        <thead>
                            <tr>
                                <th scope="col">الموظف</th>
                                <th scope="col">الفرع</th>
                                <th scope="col">عدد التقارير</th>
                                <th scope="col">آخر إصدار</th>
                                <th scope="col">متوسط الدرجة</th>
                                <th scope="col">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employeeRows as $empRow): ?>
                                <?php
                                $empName = trim((string)($empRow->full_name ?? '')) !== '' ? (string)$empRow->full_name : 'موظف غير معروف';
                                $empInitial = mb_substr($empName, 0, 1, 'UTF-8');
                                $detailUrl = APP_URL . '/reports/archive/employee/' . (int)$empRow->id;
                                ?>
                                <tr>
                                    <td data-label="الموظف">
                                        <div class="employee-cell">
                                            <div class="employee-avatar" aria-hidden="true"><?= $e($empInitial) ?></div>
                                            <div>
                                                <div class="employee-name"><?= $e($empName) ?></div>
                                                <div class="employee-code" dir="ltr"><?= $e($empRow->emp_code ?? '—') ?></div>
                                                <?php if (trim((string)($empRow->position_title ?? '')) !== '' || trim((string)($empRow->department_name ?? '')) !== ''): ?>
                                                    <div class="employee-meta"><?= $e(trim(($empRow->position_title ?? '') . ' · ' . ($empRow->department_name ?? ''), ' ·')) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="الفرع">
                                        <span class="branch-chip"><i class="fas fa-building"></i> <?= $e(trim((string)($empRow->branch_name ?? '')) !== '' ? $empRow->branch_name : 'غير محدد') ?></span>
                                    </td>
                                    <td data-label="عدد التقارير">
                                        <span class="type-badge type-badge--course"><b><?= (int)$empRow->report_count ?></b> تقرير</span>
                                    </td>
                                    <td data-label="آخر إصدار">
                                        <div class="report-cell">
                                            <span class="report-number" dir="ltr"><?= $e($empRow->latest_report_number ?? '—') ?></span>
                                            <span class="report-date"><?= $e($formatDate($empRow->latest_created_at ?? null)) ?> · <?= $e($formatTime($empRow->latest_created_at ?? null)) ?></span>
                                            <?php $rel = $relativeTime($empRow->latest_created_at ?? null); ?>
                                            <?php if ($rel !== ''): ?><span class="report-relative"><?= $e($rel) ?></span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="متوسط الدرجة">
                                        <?php $avg = isset($empRow->avg_score) && $empRow->avg_score !== null ? (float)$empRow->avg_score : null; ?>
                                        <?php if ($avg !== null): ?>
                                            <div class="score-line">
                                                <span class="score-value" dir="ltr"><?= $e($formatScore($avg)) ?></span>
                                                <span class="score-track"><span class="score-fill <?= $e($scoreClass($avg)) ?>" style="width: <?= $e(number_format(max(0, min(100, $avg)), 0)) ?>%"></span></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="score-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="إجراءات">
                                        <div class="actions-cell">
                                            <a class="icon-btn is-primary" href="<?= $e($detailUrl) ?>" title="عرض سجل التقارير" aria-label="عرض سجل التقارير <?= $e($empName) ?>">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($hasFilters): ?>
                <div class="empty-state">
                    <div class="empty-state__icon"><i class="fas fa-magnifying-glass"></i></div>
                    <div class="empty-state__title">لا توجد نتائج مطابقة للفلاتر الحالية</div>
                    <div class="empty-state__hint">جرّب توسيع نطاق التاريخ أو إزالة بعض الفلاتر.</div>
                    <div class="empty-state__action">
                        <a class="btn-clear" href="<?= $e($archiveUrl(['q' => '', 'branch_id' => 0, 'year' => 0, 'type' => '', 'date_from' => '', 'date_to' => '', 'created_by' => 0])) ?>"><i class="fas fa-rotate-left"></i> مسح كل الفلاتر</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state__icon"><i class="fas fa-folder-open"></i></div>
                    <div class="empty-state__title">لم تُصدر أي تقارير بعد</div>
                    <div class="empty-state__hint">عند إصدار أول تقرير سيظهر هنا سجل الموظفين.</div>
                    <div class="empty-state__action">
                        <a class="btn-hero-primary" href="<?= APP_URL ?>/reports/create"><i class="fas fa-file-circle-plus"></i> إصدار تقرير جديد</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif ($snapshots !== []): ?>
                <table class="archive-table">
                    <caption class="visually-hidden">قائمة التقارير المؤرشفة</caption>
                    <thead>
                        <tr>
                            <th scope="col">التقرير</th>
                            <th scope="col">الموظف</th>
                            <th scope="col">الفرع</th>
                            <th scope="col">التقييمات</th>
                            <th scope="col">أصدره</th>
                            <th scope="col">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($snapshots as $snapshot): ?>
                            <?php
                            $averages = json_decode((string)($snapshot->averages_json ?? ''), true);
                            if (!is_array($averages)) {
                                $averages = [];
                            }
                            $overallAverage = isset($averages['overall']['average']) && $averages['overall']['average'] !== null
                                ? (float)$averages['overall']['average']
                                : null;
                            $overallGrade = trim((string)($averages['overall']['grade_label'] ?? ''));
                            $recordTargets = json_decode((string)($snapshot->record_targets_json ?? '[]'), true);
                            $recordEvalTypes = json_decode((string)($snapshot->record_eval_types_json ?? '[]'), true);
                            if (!is_array($recordTargets)) {
                                $recordTargets = [];
                            }
                            if (!is_array($recordEvalTypes)) {
                                $recordEvalTypes = [];
                            }
                            $deriveSnapshotType = static function ($target, $legacy): string {
                                if ($target === 'employee') {
                                    return 'employee';
                                }
                                if ($target === 'skill') {
                                    return 'skill';
                                }
                                if (in_array($target, ['employee_in_course', 'course'], true)) {
                                    return 'course';
                                }
                                if (in_array($target, ['employee_in_workshop', 'workshop'], true)) {
                                    return 'workshop';
                                }
                                $legacy = is_string($legacy) ? $legacy : '';
                                if ($legacy === '' || $legacy === 'annual') {
                                    return 'employee';
                                }
                                return $legacy;
                            };
                            $typeCounts = [];
                            $recordCount = max(count($recordTargets), count($recordEvalTypes));
                            for ($i = 0; $i < $recordCount; $i++) {
                                $type = $deriveSnapshotType($recordTargets[$i] ?? null, $recordEvalTypes[$i] ?? null);
                                if (isset($typeLabels[$type])) {
                                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                                }
                            }
                            $rowTypeCounts = [];
                            foreach ($typeCounts as $typeKey => $count) {
                                $rowTypeCounts[$typeKey] = ['label' => $typeLabels[$typeKey], 'count' => $count];
                            }
                            $displayName = trim((string)($snapshot->full_name ?? '')) !== ''
                                ? (string)$snapshot->full_name
                                : 'موظف غير معروف';
                            $initial = mb_substr($displayName, 0, 1, 'UTF-8');
                            $metaParts = array_values(array_filter([
                                trim((string)($snapshot->position_title ?? '')),
                                trim((string)($snapshot->department_name ?? '')),
                            ], static fn(string $part): bool => $part !== ''));
                            $viewUrl = APP_URL . '/employees/profile/' . (int)$snapshot->employee_id . '/reports/' . (int)$snapshot->id;
                            ?>
                            <tr>
                                <td data-label="التقرير">
                                    <div class="report-cell">
                                        <span class="report-number" dir="ltr">
                                            <?= $e($snapshot->report_number) ?>
                                            <button type="button" class="btn-copy" data-copy="<?= $e($snapshot->report_number) ?>"
                                                    title="نسخ رقم التقرير" aria-label="نسخ رقم التقرير <?= $e($snapshot->report_number) ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </span>
                                        <span class="report-date"><?= $e($formatDate($snapshot->created_at)) ?> · <?= $e($formatTime($snapshot->created_at)) ?></span>
                                        <?php $relative = $relativeTime($snapshot->created_at); ?>
                                        <?php if ($relative !== ''): ?>
                                            <span class="report-relative"><?= $e($relative) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="الموظف">
                                    <div class="employee-cell">
                                        <div class="employee-avatar" aria-hidden="true"><?= $e($initial) ?></div>
                                        <div>
                                            <div class="employee-name"><?= $e($displayName) ?></div>
                                            <div class="employee-code" dir="ltr"><?= $e($snapshot->emp_code ?? '—') ?></div>
                                            <?php if ($metaParts !== []): ?>
                                                <div class="employee-meta"><?= $e(implode(' · ', $metaParts)) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="الفرع">
                                    <span class="branch-chip">
                                        <i class="fas fa-building"></i>
                                        <?= $e(trim((string)($snapshot->branch_name ?? '')) !== '' ? $snapshot->branch_name : 'غير محدد') ?>
                                    </span>
                                </td>
                                <td data-label="التقييمات">
                                    <div class="eval-cell">
                                        <?php if ($rowTypeCounts !== []): ?>
                                            <div class="eval-badges">
                                                <?php foreach ($rowTypeCounts as $typeKey => $typeInfo): ?>
                                                    <span class="type-badge type-badge--<?= $e($typeKey) ?>">
                                                        <?= $e($typeInfo['label']) ?> <b><?= (int)$typeInfo['count'] ?></b>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ((int)($snapshot->records_count ?? 0) === 0): ?>
                                            <span class="score-empty">لا توجد تقييمات</span>
                                        <?php else: ?>
                                            <span class="score-empty"><?= (int)$snapshot->records_count ?> تقييم</span>
                                        <?php endif; ?>

                                        <?php if ($overallAverage !== null): ?>
                                            <div class="score-line" title="<?= $e($overallGrade !== '' ? $overallGrade : 'متوسط الدرجة') ?>">
                                                <span class="score-value" dir="ltr"><?= $e($formatScore($overallAverage)) ?></span>
                                                <span class="score-track">
                                                    <span class="score-fill <?= $e($scoreClass($overallAverage)) ?>"
                                                          style="width: <?= $e(number_format(max(0, min(100, $overallAverage)), 0)) ?>%"></span>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="أصدره">
                                    <span class="issuer-cell">
                                        <i class="fas fa-user-pen"></i>
                                        <?= $e(trim((string)($snapshot->created_by ?? '')) !== '' ? $snapshot->created_by : '—') ?>
                                    </span>
                                </td>
                                <td data-label="إجراءات">
                                    <div class="actions-cell">
                                        <a class="icon-btn is-primary" href="<?= $e($viewUrl) ?>" title="عرض التقرير" aria-label="عرض التقرير <?= $e($snapshot->report_number) ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a class="icon-btn is-pdf" href="<?= APP_URL ?>/reports/export/<?= (int)$snapshot->id ?>/pdf" title="تصدير PDF" aria-label="تصدير PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a class="icon-btn is-xlsx" href="<?= APP_URL ?>/reports/export/<?= (int)$snapshot->id ?>/xlsx" title="تصدير Excel" aria-label="تصدير Excel">
                                            <i class="fas fa-file-excel"></i>
                                        </a>
                                        <a class="icon-btn is-docx" href="<?= APP_URL ?>/reports/export/<?= (int)$snapshot->id ?>/docx" title="تصدير Word" aria-label="تصدير Word">
                                            <i class="fas fa-file-word"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($hasFilters): ?>
            <div class="empty-state">
                <div class="empty-state__icon"><i class="fas fa-magnifying-glass"></i></div>
                <div class="empty-state__title">لا توجد نتائج مطابقة للفلاتر الحالية</div>
                <div class="empty-state__hint">جرّب توسيع نطاق التاريخ أو إزالة بعض الفلاتر.</div>
                <div class="empty-state__chips">
                    <?php foreach ($activeChips as $chip): ?>
                        <a class="filter-chip" href="<?= $e($archiveUrl($chip['changes'])) ?>"><?= $e($chip['label']) ?> <i class="fas fa-xmark"></i></a>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state__action">
                    <a class="btn-clear" href="<?= $e($archiveUrl([
                        'q' => '', 'branch_id' => 0, 'year' => 0, 'type' => '',
                        'date_from' => '', 'date_to' => '', 'created_by' => 0,
                    ])) ?>"><i class="fas fa-rotate-left"></i> مسح كل الفلاتر</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state__icon"><i class="fas fa-folder-open"></i></div>
                <div class="empty-state__title">لم تُصدر أي تقارير بعد</div>
                <div class="empty-state__hint">عند إصدار أول تقرير سيظهر هنا في السجل المؤرشف بشكل دائم.</div>
                <div class="empty-state__action">
                    <a class="btn-hero-primary" href="<?= APP_URL ?>/reports/create"><i class="fas fa-file-circle-plus"></i> إصدار تقرير جديد</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($snapshots !== [] || $employeeRows !== []): ?>
            <div class="panel__foot">
                <div class="results-count">
                    صفحة <strong><?= (int)$page ?></strong> من <strong><?= (int)max(1, $totalPages) ?></strong>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="التنقل بين صفحات السجل">
                        <?php if ($page > 1): ?>
                            <a href="<?= $e($archiveUrl([], $page - 1)) ?>" aria-label="الصفحة السابقة">السابق</a>
                        <?php else: ?>
                            <span class="is-disabled" aria-disabled="true">السابق</span>
                        <?php endif; ?>

                        <?php foreach ($paginationItems as $item): ?>
                            <?php if ($item['type'] === 'gap'): ?>
                                <span class="is-gap">…</span>
                            <?php else: ?>
                                <?php $number = (int)$item['number']; ?>
                                <a class="<?= $number === $page ? 'active' : '' ?>"
                                   href="<?= $e($archiveUrl([], $number)) ?>"
                                   <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= $e($archiveUrl([], $page + 1)) ?>" aria-label="الصفحة التالية">التالي</a>
                        <?php else: ?>
                            <span class="is-disabled" aria-disabled="true">التالي</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<div class="copy-toast" id="copyToast" role="status" aria-live="polite">تم نسخ رقم التقرير</div>

<script>
(function () {
    var filterForm = document.getElementById('archiveFilters');
    var submitButton = document.getElementById('filterSubmit');
    if (filterForm && submitButton) {
        filterForm.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> جاري التصفية...';
        });
    }

    var tools = document.getElementById('resultsTools');
    if (tools) {
        tools.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                tools.submit();
            });
        });
    }

    var toast = document.getElementById('copyToast');
    var toastTimer = null;

    function showToast(message) {
        if (!toast) {
            return;
        }
        toast.textContent = message;
        toast.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 1800);
    }

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = button.getAttribute('data-copy') || '';

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value)
                    .then(function () { showToast('تم نسخ رقم التقرير'); })
                    .catch(function () { fallbackCopy(value); });
                return;
            }

            fallbackCopy(value);
        });
    });

    function fallbackCopy(value) {
        var input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        try {
            document.execCommand('copy');
            showToast('تم نسخ رقم التقرير');
        } catch (error) {
            showToast('تعذر النسخ');
        }
        document.body.removeChild(input);
    }
})();
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
