<?php require_once APP_ROOT . '/app/Views/layouts/header.php'; 
$q = function($k) use($filters){ return htmlspecialchars($filters[$k] ?? '', ENT_QUOTES, 'UTF-8'); };
$qs = function($over=[]) use($filters){
    $p=array_merge(['search'=>$filters['search'],'branch_id'=>$filters['branch_id'],'department_id'=>$filters['department_id'],'position_id'=>$filters['position_id'],'is_active'=>$filters['is_active'],'sort'=>$filters['sort'],'dir'=>$filters['dir']],$over);
    foreach($p as $k=>$v) if($v===''||$v===null) unset($p[$k]);
    return http_build_query($p);
};
$branchJson=json_encode(array_map(fn($b)=>['id'=>$b->id,'name'=>$b->name], $branches ?? []), JSON_UNESCAPED_UNICODE);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap');
:root{--brand:#0d5e52;--deep:#0c2823;--gold:#c8943f;--canvas:#f0f4f8;--card:#fff;--border:#e2e8f0;--muted:#64748b;--text:#1e293b;--shadow-sm:0 2px 8px rgba(15,42,68,.06);--shadow-md:0 8px 24px rgba(15,42,68,.10);--shadow-lg:0 16px 40px rgba(15,42,68,.14);}
*{font-family:'Tajawal',sans-serif;}

.emp-wrap{background:var(--canvas);min-height:100vh;padding:clamp(12px,2vw,24px) 0 clamp(24px,3vw,40px);position:relative;overflow:hidden;}
.emp-wrap::before{content:'';position:fixed;inset:0;pointer-events:none;background:radial-gradient(ellipse at 15% 10%,rgba(13,94,82,.05) 0%,transparent 50%),radial-gradient(ellipse at 85% 90%,rgba(200,148,63,.04) 0%,transparent 50%);z-index:0;}
.emp-container{width:min(96%,1600px);margin:0 auto;padding:0 clamp(8px,1.5vw,20px);position:relative;z-index:1;}

.page-header{background:linear-gradient(135deg,#0c2823 0%,#0d5e52 55%,#115e4b 100%);border-radius:20px;padding:clamp(24px,3vw,36px);color:#fff;position:relative;overflow:hidden;margin-bottom:28px;box-shadow:0 16px 48px rgba(12,40,35,.25),0 2px 8px rgba(0,0,0,.08);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(12px);}
.page-header::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 80% 20%,rgba(200,148,63,.12) 0%,transparent 40%),radial-gradient(circle at 20% 80%,rgba(255,255,255,.04) 0%,transparent 40%);pointer-events:none;}
.page-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(200,148,63,.4),transparent);}
.page-header-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;z-index:1;}
.page-title{display:flex;align-items:center;gap:14px;margin:0;font-size:clamp(28px,3.5vw,42px);font-weight:900;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.15);}
.page-title-icon{width:60px;height:60px;border-radius:16px;background:rgba(255,255,255,.12);border:2px solid rgba(200,148,63,.3);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:24px;flex-shrink:0;box-shadow:0 4px 16px rgba(200,148,63,.2);}
.page-header-stats{display:flex;gap:clamp(24px,3vw,48px);flex-wrap:wrap;margin-top:clamp(16px,2vw,24px);position:relative;z-index:1;}
.page-stat{font-size:clamp(13px,1.2vw,15px);color:rgba(255,255,255,.7);display:flex;align-items:center;gap:8px;}
.page-stat i{color:var(--gold);font-size:14px;}
.page-stat strong{color:#fff;font-weight:800;font-size:clamp(16px,1.5vw,20px);}

.btn-add-emp{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:12px;font-size:15px;font-weight:700;text-decoration:none;background:rgba(255,255,255,.12);color:#fff;border:2px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);transition:all .3s cubic-bezier(.4,0,.2,1);position:relative;z-index:1;}
.btn-add-emp:hover{background:var(--gold);color:#fff;border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(200,148,63,.35);}

.emp-filters{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:clamp(20px,2.5vw,28px);box-shadow:0 4px 20px rgba(15,42,68,.08);margin-bottom:20px;position:relative;z-index:5;}
.filter-row{display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;}
.filter-group{flex:1;min-width:160px;}
.filter-group label{display:block;font-size:13px;font-weight:700;color:var(--muted);margin-bottom:6px;}
.filter-group input,.filter-group select{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);background:#fff;transition:border-color .2s,box-shadow .2s;outline:none;font-family:'Tajawal',sans-serif;}
.filter-group input:focus,.filter-group select:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(13,94,82,.1);}
.btn-filter{flex-shrink:0;padding:10px 28px;background:var(--brand);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .25s;display:inline-flex;align-items:center;gap:6px;font-family:'Tajawal',sans-serif;}
.btn-filter:hover{background:var(--gold);transform:translateY(-1px);box-shadow:0 4px 12px rgba(200,148,63,.3);}
.btn-reset{flex-shrink:0;padding:10px 18px;background:#fff;color:var(--muted);border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all .25s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:'Tajawal',sans-serif;}
.btn-reset:hover{border-color:var(--brand);color:var(--brand);background:rgba(13,94,82,.04);transform:translateY(-1px);}

.chip{display:inline-flex;align-items:center;gap:.35rem;background:var(--brand);color:#fff;border-radius:999px;padding:.25rem .65rem;font-size:.78rem;}
.chip button{border:0;background:rgba(255,255,255,.25);color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:.7rem;}

.table-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);animation:fadeIn .5s ease both;transition:box-shadow .3s ease;}
.table-card:hover{box-shadow:var(--shadow-md);}

.emp-table{width:100%;border-collapse:separate;border-spacing:0;}
.emp-table thead th{background:#f8fafc;color:var(--text);font-size:15px;font-weight:800;padding:16px 18px;border-bottom:2px solid var(--border);text-align:center;white-space:nowrap;}
.emp-table thead th:nth-child(2){text-align:right;}
.emp-table tbody tr{transition:all .25s ease;}
.emp-table tbody tr:nth-child(even){background:#f8fafb;}
.emp-table tbody tr:nth-child(odd){background:#fff;}
.emp-table tbody tr:hover{background:rgba(13,94,82,.04);border-right:3px solid var(--brand);}
.emp-table tbody td{padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:var(--text);vertical-align:middle;height:48px;}

.emp-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;background:linear-gradient(135deg,var(--deep),var(--brand));border:2px solid var(--gold);flex-shrink:0;}

.emp-code-badge{display:inline-block;background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:4px 10px;font-size:12px;font-weight:700;color:var(--muted);}

.status-pill{padding:4px 12px;border-radius:999px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px;}
.status-pill::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.status-active{background:rgba(13,94,82,.15);color:var(--brand);}
.status-active::before{background:var(--brand);}
.status-inactive{background:rgba(148,163,184,.12);color:#78849a;}
.status-inactive::before{background:#94a3b8;}

.action-btn{width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border);background:#fff;color:var(--muted);cursor:pointer;transition:all .25s ease;text-decoration:none;font-size:15px;}
.action-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1);}
.action-btn.view:hover{background:rgba(200,148,63,.12);border-color:var(--gold);color:var(--gold);}
.action-btn.edit:hover{background:rgba(13,94,82,.12);border-color:var(--brand);color:var(--brand);}
.action-btn.delete:hover{background:rgba(239,68,68,.12);border-color:#ef4444;color:#ef4444;}

.empty-state{text-align:center;padding:clamp(40px,6vw,80px) 20px;}
.empty-state i{font-size:52px;color:#cbd5e1;}
.empty-state h6{margin:16px 0 8px;font-size:18px;font-weight:700;color:var(--muted);}

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
    .emp-table{min-width:800px;}
    .filter-row{flex-direction:column;}
    .filter-group{min-width:100%;}
    .page-header-row{flex-direction:column;align-items:flex-start;}
}
</style>

<div class="emp-wrap">
<div class="emp-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-row">
            <h2 class="page-title">
                <span class="page-title-icon"><i class="fas fa-users"></i></span>
                قائمة الموظفين
            </h2>
            <?php if(App\Core\Auth::can('employees.create')): ?>
            <a href="<?= APP_URL ?>/employees/create" class="btn-add-emp"><i class="fas fa-plus"></i> إضافة موظف جديد</a>
            <?php endif; ?>
        </div>
        <div class="page-header-stats">
            <div class="page-stat"><i class="fas fa-users"></i> <strong><?= (int)$total ?></strong> موظف<?= $q('search')||$q('branch_id')||$q('department_id')||$q('position_id')||$q('is_active')!=='' ? ' — مفلتر' : '' ?></div>
            <div class="page-stat"><i class="fas fa-building"></i> <strong><?= count($branches) ?></strong> فرع</div>
            <div class="page-stat"><i class="fas fa-sitemap"></i> <strong><?= count($departments) ?></strong> قسم</div>
            <div class="page-stat"><i class="fas fa-briefcase"></i> <strong><?= count($positions) ?></strong> منصب</div>
        </div>
    </div>

    <!-- Filter Card -->
    <form id="empFilterForm" method="get" action="<?= APP_URL ?>/employees" class="emp-filters">
        <div class="filter-row">
            <div class="filter-group" style="flex:3;">
                <label><i class="fas fa-search" style="color:var(--brand)"></i> بحث</label>
                <input id="searchInput" type="text" name="search" value="<?= $q('search') ?>" placeholder="الاسم، الرقم الوظيفي، اللقب">
            </div>
            <div class="filter-group" style="flex:2;">
                <label><i class="fas fa-building" style="color:var(--brand)"></i> الفرع</label>
                <select id="branchSelect" name="branch_id">
                    <option value="">الكل</option>
                    <?php foreach($branches as $b): ?><option value="<?= (int)$b->id ?>" <?= (string)$filters['branch_id']===(string)$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex:2;">
                <label><i class="fas fa-sitemap" style="color:var(--brand)"></i> القسم</label>
                <select id="deptSelect" name="department_id">
                    <option value="">الكل</option>
                    <?php foreach($departments as $d): ?><option value="<?= (int)$d->id ?>" <?= (string)$filters['department_id']===(string)$d->id?'selected':'' ?>><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex:2;">
                <label><i class="fas fa-briefcase" style="color:var(--brand)"></i> المنصب</label>
                <select name="position_id">
                    <option value="">الكل</option>
                    <?php foreach($positions as $p): ?><option value="<?= (int)$p->id ?>" <?= (string)$filters['position_id']===(string)$p->id?'selected':'' ?>><?= htmlspecialchars($p->title) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex:1;">
                <label><i class="fas fa-toggle-on" style="color:var(--brand)"></i> الحالة</label>
                <select name="is_active">
                    <option value="">الكل</option>
                    <option value="1" <?= $filters['is_active']==='1'?'selected':'' ?>>نشط</option>
                    <option value="0" <?= $filters['is_active']==='0'?'selected':'' ?>>غير نشط</option>
                </select>
            </div>
            <div class="filter-group" style="flex:0 0 auto;display:flex;gap:8px;">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> فلترة</button>
                <a href="<?= APP_URL ?>/employees" class="btn-reset" title="إعادة ضبط الفلاتر"><i class="fas fa-undo"></i> إعادة ضبط</a>
            </div>
        </div>
        <input type="hidden" name="sort" value="<?= $q('sort') ?>"><input type="hidden" name="dir" value="<?= $q('dir') ?>">
    </form>

    <?php $chips=[]; if($q('search')) $chips[]=['k'=>'search','l'=>'بحث: '.$q('search')]; if($q('branch_id')){ $n=''; foreach($branches as $b) if((string)$b->id===$q('branch_id')) $n=$b->name; $chips[]=['k'=>'branch_id','l'=>'فرع: '.htmlspecialchars($n)]; } if($q('department_id')){ $n=''; foreach($departments as $d) if((string)$d->id===$q('department_id')) $n=$d->name; $chips[]=['k'=>'department_id','l'=>'قسم: '.htmlspecialchars($n)]; } if($q('position_id')){ $n=''; foreach($positions as $p) if((string)$p->id===$q('position_id')) $n=$p->title; $chips[]=['k'=>'position_id','l'=>'منصب: '.htmlspecialchars($n)]; } if($q('is_active')!=='') $chips[]=['k'=>'is_active','l'=> $q('is_active')==='1'?'نشط':'غير نشط']; ?>
    <?php if($chips): ?><div class="d-flex flex-wrap gap-2 mb-3 align-items-center"><span class="small text-muted">فلاتر نشطة:</span><?php foreach($chips as $c): ?><span class="chip"><?= $c['l'] ?> <button type="button" onclick="removeFilter('<?= $c['k'] ?>')" title="إزالة">×</button></span><?php endforeach; ?></div><?php endif; ?>

    <!-- Table Card -->
    <div class="table-card">
    <div class="table-responsive">
    <table class="emp-table">
        <thead><tr><th>الرقم الوظيفي</th><th>الموظف</th><th>المنصب</th><th>القسم</th><th>الفرع</th><th>الحالة</th><th class="text-center">الإجراءات</th></tr></thead>
        <tbody>
        <?php if(!empty($employees)): foreach($employees as $e):
            $initials=implode('', array_map(fn($w)=> mb_substr($w,0,1,'UTF-8'), array_slice(explode(' ', trim($e->full_name ?? '')),0,2))); $initials = $initials ?: '؟';
        ?>
            <tr>
                <td><span class="emp-code-badge"><?= htmlspecialchars($e->emp_code ?: '—') ?></span></td>
                <td><div class="d-flex align-items-center gap-2"><div class="emp-avatar"><?= htmlspecialchars(mb_strtoupper($initials,'UTF-8')) ?></div><div><a href="<?= APP_URL ?>/employees/<?= (int)$e->id ?>" class="fw-bold text-decoration-none" style="color:var(--text)"><?= htmlspecialchars($e->full_name) ?></a><?php if(!empty($e->nickname)): ?><div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($e->nickname) ?></div><?php endif; ?></div></div></td>
                <td><?= htmlspecialchars($e->position_title ?? '—') ?></td>
                <td><?= htmlspecialchars($e->department_name ?? '—') ?></td>
                <td><?= htmlspecialchars($e->branch_name ?? '—') ?></td>
                <td><?php if($e->is_active): ?><span class="status-pill status-active">نشط</span><?php else: ?><span class="status-pill status-inactive">غير نشط</span><?php endif; ?></td>
                <td class="text-center"><div class="d-inline-flex gap-1"><a href="<?= APP_URL ?>/employees/<?= (int)$e->id ?>" class="action-btn view" title="عرض"><i class="fas fa-eye"></i></a><?php if(App\Core\Auth::can('employees.edit')): ?><a href="<?= APP_URL ?>/employees/<?= (int)$e->id ?>/edit" class="action-btn edit" title="تعديل"><i class="fas fa-edit"></i></a><?php endif; ?><?php if(App\Core\Auth::can('employees.delete')): ?><button class="action-btn delete js-delete-employee" data-id="<?= (int)$e->id ?>" data-name="<?= htmlspecialchars($e->full_name,ENT_QUOTES) ?>" title="حذف"><i class="fas fa-trash-alt"></i></button><?php endif; ?></div></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-users-slash d-block mb-2"></i><h6>لا يوجد موظفون يطابقون الفلترة</h6><p class="small mb-3" style="color:var(--muted)">جرّب تغيير البحث أو <a href="<?= APP_URL ?>/employees" style="color:var(--brand)">إعادة تعيين الفلاتر</a></p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>

    <?php if($totalPages>1): $cur=(int)$page; ?>
    <div class="table-footer" style="border-top:none;border-radius:0 0 16px 16px;">
        <span class="page-info">صفحة <?= (int)$page ?> من <?= (int)$totalPages ?> — <?= (int)$total ?> موظف</span>
        <div class="pagination-wrap">
            <?php
            $baseUrl = APP_URL . '/employees?' . http_build_query(array_filter(['search'=>$filters['search'],'branch_id'=>$filters['branch_id'],'department_id'=>$filters['department_id'],'position_id'=>$filters['position_id'],'is_active'=>$filters['is_active'],'sort'=>$filters['sort'],'dir'=>$filters['dir']]));
            $sep = '&';
            ?>
            <a href="<?= $baseUrl.$sep.'page='.max(1,$cur-1) ?>" class="page-btn arrow <?= $cur<=1?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
            <?php
            $start=max(1,$cur-2); $end=min($totalPages,$cur+2);
            if($start > 1): ?>
                <a href="<?= $baseUrl.$sep.'page=1' ?>" class="page-btn">1</a>
                <?php if($start > 2): ?><span class="page-btn disabled" style="border:none;background:none;min-width:auto;">…</span><?php endif; ?>
            <?php endif;
            for($i=$start;$i<=$end;$i++): ?>
                <a href="<?= $baseUrl.$sep.'page='.$i ?>" class="page-btn <?= $i===$cur?'active':'' ?>"><?= $i ?></a>
            <?php endfor;
            if($end < $totalPages): ?>
                <?php if($end < $totalPages - 1): ?><span class="page-btn disabled" style="border:none;background:none;min-width:auto;">…</span><?php endif; ?>
                <a href="<?= $baseUrl.$sep.'page='.$totalPages ?>" class="page-btn"><?= $totalPages ?></a>
            <?php endif; ?>
            <a href="<?= $baseUrl.$sep.'page='.min($totalPages,$cur+1) ?>" class="page-btn arrow <?= $cur>=$totalPages?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<script>
(function(){
    const form=document.getElementById('empFilterForm'), search=document.getElementById('searchInput'), branch=document.getElementById('branchSelect'), dept=document.getElementById('deptSelect');
    let t=null; if(search) search.addEventListener('input',()=>{clearTimeout(t); t=setTimeout(()=>form.requestSubmit(),350);});
    window.removeFilter=function(k){ const u=new URL(window.location); u.searchParams.delete(k); if(k==='branch_id') u.searchParams.delete('department_id'); u.searchParams.delete('page'); window.location=u.href; };
    if(branch && dept){
        branch.addEventListener('change', async function(){
            const id=this.value; dept.innerHTML='<option value="">الكل</option>';
            if(!id) return;
            try{
                const r=await fetch('<?= APP_URL ?>/api/departments/by-branch/'+encodeURIComponent(id));
                const list=await r.json();
                list.forEach(d=>{ const o=document.createElement('option'); o.value=d.id; o.textContent=d.name; dept.appendChild(o); });
            }catch(e){}
        });
    }
})();
</script>
<?php require_once APP_ROOT . '/app/Views/layouts/footer.php'; ?>
