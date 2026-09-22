<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap');
:root{--brand:#0d5e52;--deep:#0c2823;--gold:#c8943f;--canvas:#f0f4f8;--card:#fff;--border:#e2e8f0;--muted:#64748b;--text:#1e293b;--shadow-sm:0 2px 8px rgba(15,42,68,.06);--shadow-md:0 8px 24px rgba(15,42,68,.10);--shadow-lg:0 16px 40px rgba(15,42,68,.14);}
*{font-family:'Tajawal',sans-serif;}

.eval-wrap{background:var(--canvas);min-height:100vh;padding:clamp(12px,2vw,24px) 0 clamp(24px,3vw,40px);position:relative;overflow:hidden;}
.eval-wrap::before{content:'';position:fixed;inset:0;pointer-events:none;background:radial-gradient(ellipse at 15% 10%,rgba(13,94,82,.05) 0%,transparent 50%),radial-gradient(ellipse at 85% 90%,rgba(200,148,63,.04) 0%,transparent 50%);z-index:0;}
.eval-container{width:min(96%,1600px);margin:0 auto;padding:0 clamp(8px,1.5vw,20px);position:relative;z-index:1;}

.page-header{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 55%,#115e4b 100%);border-radius:20px;padding:clamp(24px,3vw,36px);color:#fff;position:relative;overflow:hidden;margin-bottom:28px;box-shadow:0 16px 48px rgba(12,40,35,.25),0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(12px);}
.page-header::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(200,148,63,.12) 0%,transparent 40%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.04) 0%,transparent 40%);pointer-events:none;}
.page-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(200,148,63,.4),transparent);}
.page-header-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;z-index:1;}
.page-title{display:flex;align-items:center;gap:14px;margin:0;font-size:clamp(28px,3.5vw,42px);font-weight:900;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.15);}
.page-title-icon{width:60px;height:60px;border-radius:16px;background:rgba(255,255,255,.12);border:2px solid rgba(200,148,63,.3);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:24px;flex-shrink:0;box-shadow:0 4px 16px rgba(200,148,63,.2);}
.page-header-stats{display:flex;gap:clamp(16px,2vw,32px);flex-wrap:wrap;margin-top:clamp(12px,1.5vw,20px);position:relative;z-index:1;}
.page-stat{font-size:clamp(12px,1.1vw,14px);color:rgba(255,255,255,.7);display:flex;align-items:center;gap:6px;}
.page-stat i{color:var(--gold);}
.page-stat strong{color:#fff;font-weight:800;}

.btn-excel{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:12px;font-size:15px;font-weight:700;text-decoration:none;background:rgba(255,255,255,.12);color:#fff;border:2px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);transition:all .3s cubic-bezier(.4,0,.2,1);position:relative;z-index:1;}
.btn-excel:hover{background:var(--gold);color:#fff;border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(200,148,63,.35);}

.filters-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:clamp(20px,2.5vw,28px);box-shadow:var(--shadow-sm);margin-bottom:28px;}
.filter-row{display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;}
.filter-group{flex:1;min-width:180px;}
.filter-group label{display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:6px;}
.filter-group input,.filter-group select{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);background:#fff;transition:border-color .2s,box-shadow .2s;outline:none;font-family:'Tajawal',sans-serif;}
.filter-group input:focus,.filter-group select:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(13,94,82,.1);}
.btn-filter{flex-shrink:0;padding:10px 28px;background:var(--brand);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .25s;display:inline-flex;align-items:center;gap:6px;font-family:'Tajawal',sans-serif;}
.btn-filter:hover{background:var(--gold);transform:translateY(-1px);box-shadow:0 4px 12px rgba(200,148,63,.3);}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);animation:fadeIn .5s ease both;}

.modern-table{width:100%;border-collapse:separate;border-spacing:0;}
.modern-table thead th{background:#e9edf2;color:var(--text);font-size:15px;font-weight:700;padding:16px 18px;border-bottom:2px solid var(--border);text-align:right;position:sticky;top:0;z-index:2;white-space:nowrap;}
.modern-table thead th.sortable{cursor:pointer;user-select:none;transition:color .2s;}
.modern-table thead th.sortable:hover{color:var(--brand);}
.modern-table thead th.sortable .sort-icon{margin-right:4px;font-size:12px;opacity:.4;transition:opacity .2s;}
.modern-table thead th.sortable.asc .sort-icon,
.modern-table thead th.sortable.desc .sort-icon{opacity:1;color:var(--brand);}

.modern-table tbody tr{transition:all .25s ease;}
.modern-table tbody tr:nth-child(even){background:#f8fafb;}
.modern-table tbody tr:nth-child(odd){background:#fff;}
.modern-table tbody tr:hover{background:rgba(13,94,82,.04);box-shadow:inset 3px 0 0 var(--brand);}

.modern-table tbody td{padding:14px 16px;border-bottom:1px solid #f1f5f9;font-size:16px;color:var(--text);vertical-align:middle;height:58px;}

.emp-cell-name{font-weight:800;font-size:17px;color:var(--text);display:block;}
.emp-cell-pos{font-size:13px;color:var(--muted);display:block;margin-top:2px;}

.branch-cell,.dept-cell{font-size:14px;color:var(--muted);white-space:nowrap;}

.count-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(59,130,246,.08);color:#3b82f6;border-radius:20px;padding:5px 14px;font-size:14px;font-weight:800;}

.score-ring-xs{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;position:relative;background:conic-gradient(var(--ring-color,var(--brand)) calc(var(--score)*3.6deg),#eef2f7 0deg);box-shadow:0 2px 8px rgba(13,94,82,.1);transition:transform .3s;}
.score-ring-xs:hover{transform:scale(1.1);}
.score-ring-xs::before{content:'';position:absolute;width:33px;height:33px;background:#fff;border-radius:50%;box-shadow:inset 0 1px 3px rgba(0,0,0,.04);}
.score-ring-xs span{position:relative;font-weight:900;font-size:12px;letter-spacing:-.3px;}

.na-badge-xs{display:inline-flex;align-items:center;gap:4px;color:#94a3b8;font-size:12px;font-weight:600;}
.na-badge-xs i{font-size:13px;color:#cbd5e1;}

.btn-view-sm{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;background:var(--brand);color:#fff;border:2px solid var(--brand);transition:all .25s cubic-bezier(.4,0,.2,1);white-space:nowrap;}
.btn-view-sm:hover{background:var(--gold);border-color:var(--gold);color:#fff;transform:translateY(-1px);box-shadow:0 4px 10px rgba(200,148,63,.3);}

.empty-state{text-align:center;padding:clamp(40px,6vw,80px) 20px;}
.empty-state i{font-size:52px;color:#cbd5e1;}
.empty-state h4{margin:16px 0 0;font-size:18px;font-weight:700;color:var(--muted);}

.table-footer{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-top:1px solid var(--border);background:#f8fafc;font-size:13px;color:var(--muted);flex-wrap:wrap;gap:12px;}

.pagination-wrap{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:42px;height:42px;border-radius:10px;padding:0 14px;font-size:15px;font-weight:700;color:var(--muted);background:#fff;border:1px solid var(--border);cursor:pointer;transition:all .25s cubic-bezier(.4,0,.2,1);text-decoration:none;font-family:'Tajawal',sans-serif;}
.page-btn:hover{background:rgba(13,94,82,.08);color:var(--brand);border-color:var(--brand);transform:translateY(-2px);box-shadow:0 4px 10px rgba(13,94,82,.12);}
.page-btn.active{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 6px 16px rgba(13,94,82,.3);cursor:default;}
.page-btn.active:hover{transform:none;box-shadow:0 6px 16px rgba(13,94,82,.3);}
.page-btn.arrow{background:#fff;border:1.5px solid var(--brand);color:var(--brand);font-size:16px;}
.page-btn.arrow:hover{background:var(--gold);border-color:var(--gold);color:#fff;}
.page-btn.disabled{opacity:.4;pointer-events:none;cursor:default;}
.page-info{font-size:13px;color:var(--muted);font-weight:600;}

@keyframes fadeIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}}

@media(max-width:900px){
    .table-card{overflow-x:auto;}
    .modern-table{min-width:700px;}
    .filter-row{flex-direction:column;}
    .filter-group{min-width:100%;}
    .page-header-row{flex-direction:column;align-items:flex-start;}
}
</style>

<div class="eval-wrap">
<div class="eval-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-row">
            <h2 class="page-title">
                <span class="page-title-icon"><i class="fas fa-folder-open"></i></span>
                ملفات أداء الموظفين
            </h2>
            <?php if (\App\Core\Auth::userHasPermission('evaluations.fill')): ?>
                <a class="btn-excel" href="<?php echo APP_URL; ?>/evaluations/excel/import">
                    <i class="fas fa-file-excel"></i> استيراد وتصدير Excel
                </a>
            <?php endif; ?>
        </div>
        <div class="page-header-stats">
            <div class="page-stat"><i class="fas fa-users"></i> <strong><?php echo $pagination['totalCount']; ?></strong> موظف</div>
            <div class="page-stat"><i class="fas fa-building"></i> <strong><?php echo count($branches); ?></strong> فرع</div>
            <div class="page-stat"><i class="fas fa-sitemap"></i> <strong><?php echo count($departments); ?></strong> قسم</div>
            <div class="page-stat"><i class="fas fa-clipboard-list"></i> <strong><?php echo $pagination['totalPages']; ?></strong> صفحة</div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filters-card">
        <form action="<?php echo APP_URL; ?>/evaluations" method="GET" class="filter-row">
            <div class="filter-group">
                <label for="search"><i class="fas fa-search"></i> بحث بالاسم أو الرقم الوظيفي</label>
                <input type="text" id="search" name="search" placeholder="أدخل اسم أو رقم وظيفي..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label for="branch_id"><i class="fas fa-building"></i> الفرع</label>
                <select id="branch_id" name="branch_id">
                    <option value="">الكل</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch->id; ?>" <?php echo (($filters['branch_id'] ?? '') == $branch->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="department_id"><i class="fas fa-sitemap"></i> القسم</label>
                <select id="department_id" name="department_id">
                    <option value="">الكل</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department->id; ?>" <?php echo (($filters['department_id'] ?? '') == $department->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> فلترة</button>
        </form>
    </div>

    <!-- Modern Data Table -->
    <div class="table-card">
        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>لا توجد بيانات لعرضها</h4>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="modern-table" id="empTable">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الفرع</th>
                        <th>القسم</th>
                        <th class="sortable" data-col="count" data-type="num">
                            عدد التقييمات <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-col="score" data-type="num">
                            متوسط الأداء <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee):
                        $hasScore = isset($employee->average_score) && $employee->evaluations_count > 0;
                        $score = $hasScore ? round((float)$employee->average_score, 1) : null;
                        $ringColor = $score !== null ? ($score >= 80 ? 'var(--brand)' : ($score >= 50 ? 'var(--gold)' : '#ef4444')) : '#cbd5e1';
                    ?>
                    <tr>
                        <td>
                            <span class="emp-cell-name"><?php echo htmlspecialchars($employee->full_name); ?></span>
                            <span class="emp-cell-pos"><?php echo htmlspecialchars($employee->position_title ?? 'غير محدد'); ?> · #<?php echo htmlspecialchars($employee->emp_code); ?></span>
                        </td>
                        <td class="branch-cell"><?php echo htmlspecialchars($employee->branch_name ?? '—'); ?></td>
                        <td class="dept-cell"><?php echo htmlspecialchars($employee->department_name ?? '—'); ?></td>
                        <td data-value="<?php echo $employee->evaluations_count; ?>">
                            <span class="count-pill"><i class="fas fa-clipboard-list"></i> <?php echo $employee->evaluations_count; ?></span>
                        </td>
                        <td data-value="<?php echo $score ?? -1; ?>">
                            <?php if ($hasScore): ?>
                                <div class="score-ring-xs" style="--score:<?php echo $score; ?>;--ring-color:<?php echo $ringColor; ?>">
                                    <span style="color:<?php echo $ringColor; ?>"><?php echo $score; ?>%</span>
                                </div>
                            <?php else: ?>
                                <span class="na-badge-xs"><i class="fas fa-hourglass-half"></i> لا توجد بيانات</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo APP_URL . '/employees/' . $employee->id . '/evaluations'; ?>" class="btn-view-sm">
                                <i class="fas fa-chart-line"></i> عرض السجل
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="page-info">إجمالي: <?php echo $pagination['totalCount']; ?> موظف — صفحة <?php echo $pagination['currentPage']; ?> من <?php echo $pagination['totalPages']; ?></span>
            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="pagination-wrap">
                <?php
                $cp = $pagination['currentPage'];
                $tp = $pagination['totalPages'];
                $baseUrl = APP_URL . '/evaluations?' . http_build_query(array_filter(['search' => $filters['search'] ?? '', 'branch_id' => $filters['branch_id'] ?? '', 'department_id' => $filters['department_id'] ?? '']));
                $sep = strpos($baseUrl, '?') !== false ? '&' : '?';
                ?>
                <a href="<?php echo $baseUrl . $sep . 'page=' . max(1, $cp - 1); ?>" class="page-btn arrow <?php echo $cp <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
                <?php
                $start = max(1, $cp - 2);
                $end = min($tp, $cp + 2);
                if ($start > 1): ?>
                    <a href="<?php echo $baseUrl . $sep . 'page=1'; ?>" class="page-btn">1</a>
                    <?php if ($start > 2): ?><span class="page-btn disabled" style="border:none;background:none;min-width:auto;">…</span><?php endif; ?>
                <?php endif;
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?php echo $baseUrl . $sep . 'page=' . $i; ?>" class="page-btn <?php echo $i == $cp ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor;
                if ($end < $tp): ?>
                    <?php if ($end < $tp - 1): ?><span class="page-btn disabled" style="border:none;background:none;min-width:auto;">…</span><?php endif; ?>
                    <a href="<?php echo $baseUrl . $sep . 'page=' . $tp; ?>" class="page-btn"><?php echo $tp; ?></a>
                <?php endif; ?>
                <a href="<?php echo $baseUrl . $sep . 'page=' . min($tp, $cp + 1); ?>" class="page-btn arrow <?php echo $cp >= $tp ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    var table=document.getElementById('empTable');
    if(!table)return;
    var headers=table.querySelectorAll('th.sortable');
    headers.forEach(function(th){
        th.addEventListener('click',function(){
            var col=th.getAttribute('data-col');
            var type=th.getAttribute('data-type');
            var tbody=table.querySelector('tbody');
            var rows=Array.from(tbody.querySelectorAll('tr'));
            var isAsc=th.classList.contains('asc');
            headers.forEach(function(h){h.classList.remove('asc','desc');h.querySelector('.sort-icon').className='fas fa-sort sort-icon';});
            rows.sort(function(a,b){
                var aVal=type==='num'?parseFloat(a.children[getColIndex(col)].getAttribute('data-value'))||0:a.children[getColIndex(col)].textContent.trim();
                var bVal=type==='num'?parseFloat(b.children[getColIndex(col)].getAttribute('data-value'))||0:b.children[getColIndex(col)].textContent.trim();
                if(type==='num'){return isAsc?bVal-aVal:aVal-bVal;}
                return isAsc?bVal.localeCompare(aVal,'ar'):aVal.localeCompare(bVal,'ar');
            });
            th.classList.add(isAsc?'desc':'asc');
            th.querySelector('.sort-icon').className='fas fa-sort-'+(isAsc?'down':'up')+' sort-icon';
            rows.forEach(function(r){tbody.appendChild(r);});
        });
    });
    function getColIndex(col){
        var map={count:3,score:4};
        return map[col]!==undefined?map[col]:0;
    }
});
</script>

<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
