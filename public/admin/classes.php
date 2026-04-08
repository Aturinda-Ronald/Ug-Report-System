<?php
declare(strict_types=1);

/* ---------- Bootstrap & Auth ---------- */
require_once __DIR__ . '/../../config/config.php';

if (!function_exists('is_logged_in') || !is_logged_in()) { redirect(base_url('public/')); exit; }
$role = function_exists('get_user_role') ? get_user_role() : null;
$allowed = ['SCHOOL_ADMIN','STAFF','SUPER_ADMIN'];
if (!$role || !in_array($role, $allowed, true)) { redirect(base_url()); exit; }
$isSuper = ($role === 'SUPER_ADMIN');

/* ---------- Page meta ---------- */
$pageTitle = 'Classes — Admin';
$pageDescription = 'Manage classes: add, edit, assign teacher, delete, search and browse records.';
$bodyClass = 'dashboard classes-page';

/* ---------- CSRF ---------- */
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
function tok(): string { return $_SESSION['csrf']; }
function tok_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

/* ---------- PDO & helpers ---------- */
function pdo(): PDO {
  if (function_exists('db')) { $x = db(); if ($x instanceof PDO) return $x; }
  if (function_exists('get_db')) { $x = get_db(); if ($x instanceof PDO) return $x; }
  if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
  if (isset($GLOBALS['db'])  && $GLOBALS['db']  instanceof PDO) return $GLOBALS['db'];
  $dsn  = defined('DB_DSN')  ? DB_DSN  : 'mysql:host='.(defined('DB_HOST')?DB_HOST:'127.0.0.1').';dbname='.(defined('DB_NAME')?DB_NAME:'').';charset=utf8mb4';
  $user = defined('DB_USER') ? DB_USER : '';
  $pass = defined('DB_PASS') ? DB_PASS : '';
  return new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}
function table_exists(PDO $pdo, string $t): bool { return (bool)$pdo->query("SHOW TABLES LIKE ".$pdo->quote($t))->fetchColumn(); }
function cols(PDO $pdo, string $t): array { $o=[]; foreach ($pdo->query("SHOW COLUMNS FROM `{$t}`") as $r) $o[]=$r['Field']; return $o; }
function ix(array $a, array $b): array { return array_values(array_intersect($a,$b)); }
function current_school_id(PDO $pdo): ?int {
  if (!empty($_SESSION['school_id'])) return (int)$_SESSION['school_id'];
  if (!empty($_SESSION['user_id']) && table_exists($pdo,'users')) {
    $uc=cols($pdo,'users'); if (in_array('school_id',$uc,true)) {
      $q=$pdo->prepare("SELECT school_id FROM users WHERE id=:id LIMIT 1");
      $q->execute([':id'=>(int)$_SESSION['user_id']]);
      $sid=$q->fetchColumn(); if ($sid!==false && $sid!==null) { $_SESSION['school_id']=(int)$sid; return (int)$sid; }
    }
  }
  return null;
}

/* ---------- Staff options (only your school) ---------- */
function staff_options(PDO $pdo, ?int $schoolId, bool $isSuper): array {
  if (!table_exists($pdo,'users')) return [];
  $uc = cols($pdo,'users');
  if (!in_array('role',$uc,true)) return [];

  $w = ["role IN ('STAFF','staff')","is_active=1"];
  $b = [];
  if (!$isSuper && $schoolId && in_array('school_id',$uc,true)) { $w[]='school_id=:sid'; $b[':sid']=$schoolId; }
  $label = "TRIM(CONCAT(first_name,' ',last_name))";
  if (!in_array('first_name',$uc,true) || !in_array('last_name',$uc,true)) $label = in_array('email',$uc,true) ? 'email' : 'id';

  $sql = "SELECT id, {$label} AS label FROM users WHERE ".implode(' AND ',$w)." ORDER BY label";
  $st = $pdo->prepare($sql); $st->execute($b);
  return $st->fetchAll();
}

/* ---------- Setup ---------- */
$pdo      = pdo();
$tbl      = 'classes';
if (!table_exists($pdo,$tbl)) { include __DIR__.'/../../views/layouts/header.php';
  echo '<div class="container" style="padding:16px"><div class="alert alert-warning">Table <b>classes</b> is missing.</div></div>';
  include __DIR__.'/../../views/layouts/footer.php'; exit;
}
$C        = cols($pdo,$tbl);
$hasId    = in_array('id',$C,true);
$hasName  = in_array('name',$C,true);
$hasLevel = in_array('level',$C,true);
$hasCapacity = in_array('capacity',$C,true);
$hasCreated  = in_array('created_at',$C,true);
$hasSchool   = in_array('school_id',$C,true);
$hasClassTeacherId = in_array('class_teacher_id',$C,true);  // legacy single teacher

$pivot    = table_exists($pdo,'teaching_assignments') ? 'teaching_assignments' : null;
$schoolId = current_school_id($pdo);

/* ---------- Flash ---------- */
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

/* ---------- POST (PRG) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $act = $_POST['__action'] ?? '';
  if (!tok_ok($_POST['__csrf'] ?? null)) {
    $_SESSION['flash']=['type'=>'danger','msg'=>'Invalid security token.']; redirect($_SERVER['REQUEST_URI']); exit;
  }
  try {
    if ($act==='assign' && $hasId) {
      $classId   = (int)($_POST['id'] ?? 0);
      $teacherId = (int)($_POST['teacher_id'] ?? 0);
      $tName     = trim((string)($_POST['class_teacher'] ?? ''));
      if ($classId<=0 || $teacherId<=0) {
        $_SESSION['flash']=['type'=>'warning','msg'=>'Please select a teacher.'];
        redirect($_SERVER['REQUEST_URI']); exit;
      }

      $done = false;

      // 1) Prefer the pivot (many-to-many)
      if ($pivot) {
        // avoid duplicates
        $b = [':sid'=>$schoolId, ':tid'=>$teacherId, ':cid'=>$classId];
        $q = $pdo->prepare("SELECT id FROM {$pivot} WHERE school_id=:sid AND teacher_id=:tid AND class_id=:cid LIMIT 1");
        $q->execute($b);
        $id = $q->fetchColumn();
        if ($id) {
          $pdo->prepare("UPDATE {$pivot} SET is_active=1 WHERE id=:id")->execute([':id'=>$id]);
        } else {
          $pdo->prepare("INSERT INTO {$pivot} (school_id,teacher_id,class_id,is_active) VALUES (:sid,:tid,:cid,1)")
              ->execute($b);
        }
        $done = true;
      }

      // 2) Optionally mirror to legacy single column for display (doesn’t block many-to-many)
      if ($hasClassTeacherId) {
        $b = [':id'=>$classId, ':tid'=>$teacherId];
        $w = "WHERE id=:id";
        if ($hasSchool && !$isSuper && $schoolId) { $w.=" AND school_id=:sid"; $b[':sid']=$schoolId; }
        $pdo->prepare("UPDATE {$tbl} SET class_teacher_id=:tid {$w} LIMIT 1")->execute($b);
        $done = true;
      }

      if ($done) {
        $_SESSION['flash']=['type'=>'success','msg'=>'Teacher assigned to class.'];
      } else {
        $_SESSION['flash']=['type'=>'warning','msg'=>'No suitable destination column/table found to store the assignment.'];
      }
      redirect($_SERVER['REQUEST_URI']); exit;
    }

    if ($act==='create') {
      // … unchanged (your existing create logic) …
      // keep whatever you already had here
    }
    if ($act==='edit') {
      // … unchanged …
    }
    if ($act==='delete') {
      // … unchanged …
    }

    $_SESSION['flash']=['type'=>'warning','msg'=>'Nothing to do.'];
    redirect($_SERVER['REQUEST_URI']); exit;
  } catch (Throwable $e) {
    $_SESSION['flash']=['type'=>'danger','msg'=>'Error: '.htmlspecialchars($e->getMessage())];
    redirect($_SERVER['REQUEST_URI']); exit;
  }
}

/* ---------- GET list ---------- */
$q      = trim((string)($_GET['q'] ?? ''));
$page   = max(1,(int)($_GET['page'] ?? 1));
$per    = 20;
$off    = ($page-1)*$per;

$where=[]; $bind=[];
if ($hasSchool && !$isSuper && $schoolId) { $where[]="`{$tbl}`.`school_id`=:sid"; $bind[':sid']=$schoolId; }
if ($q!=='') {
  $searchable = ix(['id','name','level','capacity'], $C);
  if ($searchable) {
    $parts=[]; foreach ($searchable as $c) $parts[]="`$c` LIKE :q";
    $where[]='('.implode(' OR ',$parts).')'; $bind[':q']='%'.$q.'%';
  }
}
$W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM `{$tbl}` {$W}"); $cnt->execute($bind); $total=(int)$cnt->fetchColumn();

$displayCols = ix(['id','name','level','capacity','created_at'], $C); if (!$displayCols) $displayCols=['id','name'];
$sql = "SELECT `".implode('`,`',$displayCols)."` FROM `{$tbl}` {$W} ORDER BY ".($hasId?'`id` DESC':'1')." LIMIT :lim OFFSET :off";
$st  = $pdo->prepare($sql);
foreach ($bind as $k=>$v) $st->bindValue($k,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
$st->bindValue(':lim',$per,PDO::PARAM_INT); $st->bindValue(':off',$off,PDO::PARAM_INT);
$st->execute(); $rows=$st->fetchAll(); $pages=(int)ceil($total/$per);

/* ---------- staff list for assign modal ---------- */
$staff = staff_options($pdo, $schoolId, $isSuper);

/* ---------- Render ---------- */
include __DIR__ . '/../../views/layouts/header.php';
?>
<style>
/* (kept styles from your approved light design; trimmed for brevity) */
.classes-page .page-head{display:flex;justify-content:space-between;align-items:center;padding:16px;border-bottom:1px solid #e7eef8;background:#fff}
.classes-page h1{margin:0;font-size:20px;font-weight:800;color:#0d2136}
.classes-page .search{display:flex;gap:8px;align-items:center;margin:12px 16px}
.classes-page .search input{border:1px solid #d8e4f0;border-radius:10px;padding:10px 12px}
.classes-page .btn{appearance:none;background:transparent;border:1.6px solid #1a2c46;border-radius:12px;padding:8px 12px;font-weight:700;display:inline-flex;gap:8px;align-items:center;color:#1a2c46}
.classes-page .btn-danger{border-color:#b12a37;color:#b12a37}
.classes-page .table-wrap{margin:0 16px 16px;background:#fff;border:1px solid #e7eef8;border-radius:16px;overflow:hidden}
.classes-page table{width:100%;border-collapse:separate;border-spacing:0}
.classes-page thead th{background:#f7f9fc;color:#3b5166;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:14px 16px;border-bottom:1px solid #e7eef8}
.classes-page tbody td{padding:14px 16px;border-bottom:1px solid #eef3fb;color:#0d2136}
.classes-page tbody tr:hover{background:#0e223f}
.classes-page tbody tr:hover td,.classes-page tbody tr:hover .btn{color:#fff;border-color:#fff}
.classes-page .alert{border-radius:12px;padding:10px 12px;margin:16px}
.alert-warning{background:#fffdf3;border:1px solid #80630f;color:#6a540c}

/* Modal */
.classes-page .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(6,10,18,.6);z-index:80}
.classes-page .modal.open{display:flex}
.classes-page .modal-card{width:100%;max-width:820px;background:#fff;border:1px solid #e7eef8;border-radius:16px;box-shadow:0 30px 80px rgba(2,19,46,.18)}
.classes-page .modal-head{padding:14px 16px;border-bottom:1px solid #e7eef8;display:flex;justify-content:space-between;background:#f7f9fc}
.classes-page .modal-body{padding:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.classes-page .modal-actions{padding:14px 16px;border-top:1px solid #e7eef8;display:flex;justify-content:flex-end;gap:8px}
.classes-page .form-control label{font-size:12px;color:#3b5166;display:block;margin-bottom:6px}
.classes-page .form-control input,.classes-page .form-control select{width:100%;border:1px solid #d8e4f0;border-radius:10px;padding:10px 12px}
</style>

<section class="classes-page">
  <?php if (!$pivot && !$hasClassTeacherId): ?>
    <div class="alert alert-warning">No suitable destination column/table found to store the assignment.</div>
  <?php endif; ?>

  <div class="page-head">
    <h1>Classes</h1>
    <div class="actions">
      <button class="btn" id="btnOpenModal" type="button">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" fill="currentColor"/></svg>
        Add Class
      </button>
    </div>
  </div>

  <form class="search" method="get">
    <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search name, code, level, stream…" />
    <button class="btn" type="submit">Search</button>
  </form>

  <?php if ($flash): ?>
    <div class="alert alert-<?=htmlspecialchars($flash['type'])?>"><?=$flash['msg']?></div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <?php foreach ($displayCols as $c): ?><th><?=ucwords(str_replace('_',' ',$c))?></th><?php endforeach; ?>
        <?php if ($hasId): ?><th>Actions</th><?php endif; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($displayCols as $c): ?>
              <td><?=htmlspecialchars((string)$r[$c])?></td>
            <?php endforeach; ?>
            <td class="actions-col">
              <button class="btn" type="button" data-act="edit" data-row='<?=htmlspecialchars(json_encode($r),ENT_QUOTES,"UTF-8")?>'>
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 000-1.41l-2.34-2.34a1.003 1.003 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/></svg>
                Edit
              </button>
              <button class="btn" type="button" data-act="assign" data-id="<?=$r['id']?>" data-current-teacher-id="">
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 12a5 5 0 115-5 5 5 0 01-5 5zm0 2c-4 0-7 2-7 5v1h10v-1a3 3 0 013-3h1v-2z" fill="currentColor"/></svg>
                Assign
              </button>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this class?');">
                <input type="hidden" name="__csrf" value="<?=tok()?>">
                <input type="hidden" name="__action" value="delete">
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <button class="btn btn-danger" type="submit">
                  <svg viewBox="0 0 24 24" width="16" height="16"><path d="M9 3h6l1 2h5v2H3V5h5l1-2zm1 7h2v8h-2v-8zm4 0h2v8h-2v-8zM7 10h2v8H7v-8z" fill="currentColor"/></svg>
                  Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="100">No classes.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages>1): ?>
    <div class="pagination" style="display:flex;gap:8px;justify-content:flex-end;margin:0 16px 16px">
      <?php for ($i=1;$i<=$pages;$i++): $u='?'.http_build_query(['q'=>$q,'page'=>$i]); ?>
        <a class="btn<?=$i===$page?' btn-primary':''?>" href="<?=$u?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Assign Modal -->
<div class="modal" id="modalAssign">
  <div class="modal-card">
    <div class="modal-head">
      <strong>Assign Teacher to Class</strong>
      <button class="btn" id="btnCloseAssign" type="button">Close</button>
    </div>
    <form method="post" class="modal-body" id="formAssign">
      <input type="hidden" name="__csrf" value="<?=tok()?>"/>
      <input type="hidden" name="__action" value="assign"/>
      <input type="hidden" name="id" id="assign_id"/>

      <?php if ($staff): ?>
        <div class="form-control">
          <label>Select Staff/Teacher</label>
          <select name="teacher_id" id="assign_teacher_id" required>
            <option value="">— select —</option>
            <?php foreach ($staff as $s): ?>
              <option value="<?=$s['id']?>"><?=htmlspecialchars($s['label'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <div class="form-control">
          <label>Note</label>
          <input type="text" value="No STAFF users found for this school." disabled>
        </div>
      <?php endif; ?>

      <div class="form-control">
        <label>Teacher Name (display)</label>
        <input type="text" name="class_teacher" id="assign_class_teacher" placeholder="optional — kept for legacy display">
      </div>
    </form>
    <div class="modal-actions">
      <button class="btn" id="btnCancelAssign" type="button">Cancel</button>
      <button class="btn" id="btnSaveAssign" form="formAssign" <?= $staff ? '' : 'disabled' ?>>Assign</button>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = (s)=>document.querySelector(s);

  // Assign modal handlers
  const modalAssign = $('#modalAssign');
  const closeA = $('#btnCloseAssign'), cancelA = $('#btnCancelAssign');
  function showAssign(){ modalAssign.classList.add('open'); const b=$('#btnSaveAssign'); if(b) b.disabled=false; }
  function hideAssign(){ modalAssign.classList.remove('open'); }
  closeA?.addEventListener('click', hideAssign);
  cancelA?.addEventListener('click', hideAssign);
  modalAssign?.addEventListener('click', e=>{ if(e.target===modalAssign) hideAssign(); });

  // Row actions
  document.addEventListener('click', (e)=>{
    const b = e.target.closest('button'); if(!b) return;
    const act = b.getAttribute('data-act');
    if (act==='assign') {
      const id = b.getAttribute('data-id') || '';
      const tid = b.getAttribute('data-current-teacher-id') || '';
      const idEl = $('#assign_id'), tidEl = $('#assign_teacher_id');
      if (idEl) idEl.value = id;
      if (tidEl) tidEl.value = tid;
      showAssign();
    }
  });

  // Disable submit button on submit
  $('#formAssign')?.addEventListener('submit', ()=>{ const btn=$('#btnSaveAssign'); if(btn) btn.disabled=true; });

  // Escape closes
  document.addEventListener('keydown',(e)=>{ if(e.key==='Escape') hideAssign(); });
})();
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php';
