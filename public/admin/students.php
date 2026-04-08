<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

/* ---------------- Auth ---------------- */
if (!function_exists('is_logged_in') || !is_logged_in()) {
  redirect(base_url('public/')); exit;
}
$role = function_exists('get_user_role') ? get_user_role() : null;
if (!$role || !in_array($role, ['SCHOOL_ADMIN','STAFF','SUPER_ADMIN'], true)) {
  redirect(base_url()); exit;
}

/* ---------------- CSRF ---------------- */
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function s_csrf(): string { return $_SESSION['csrf']; }
function s_csrf_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

/* ---------------- Flash (PRG-safe) ---------------- */
function flash_set(string $type, string $msg): void { $_SESSION['_flash'] = ['type'=>$type,'msg'=>$msg]; }
function flash_get(): array {
  $f = $_SESSION['_flash'] ?? ['type'=>null,'msg'=>null];
  unset($_SESSION['_flash']); return $f;
}
function prg_redirect(): void {
  $url = strtok($_SERVER['REQUEST_URI'], '#');
  header('Location: '.$url, true, 303); exit;
}

/* ---------------- DB helpers ---------------- */
function dbh(): PDO {
  if (function_exists('db')) { $x = db(); if ($x instanceof PDO) return $x; }
  if (function_exists('get_db')) { $x = get_db(); if ($x instanceof PDO) return $x; }
  $dsn  = defined('DB_DSN') ? DB_DSN : ('mysql:host='.(DB_HOST ?? '127.0.0.1').';dbname='.(DB_NAME ?? '').';charset=utf8mb4');
  $usr  = defined('DB_USER') ? DB_USER : '';
  $pwd  = defined('DB_PASS') ? DB_PASS : '';
  return new PDO($dsn, $usr, $pwd, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}
function table_exists(PDO $pdo, string $name): bool {
  $q = $pdo->prepare("SHOW TABLES LIKE :t"); $q->execute([':t'=>$name]);
  return (bool)$q->fetchColumn();
}
function cols(PDO $pdo, string $table): array {
  $out=[]; foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $r) $out[]=$r['Field']; return $out;
}

/* ---------------- Current school ---------------- */
function current_school_id(PDO $pdo): ?int {
  if (!empty($_SESSION['school_id'])) return (int)$_SESSION['school_id'];
  if (!empty($_SESSION['user_id']) && table_exists($pdo,'users')) {
    $s=$pdo->prepare("SELECT school_id FROM users WHERE id=:id LIMIT 1");
    $s->execute([':id'=>(int)$_SESSION['user_id']]);
    $sid = $s->fetchColumn();
    if ($sid!==false && $sid!==null) return (int)$sid;
  }
  if (function_exists('get_user_role') && get_user_role()==='SUPER_ADMIN' && table_exists($pdo,'schools')) {
    $sid = $pdo->query("SELECT id FROM schools ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($sid!==false) return (int)$sid;
  }
  return null;
}

$pdo = dbh();
$schoolId = current_school_id($pdo);

/* ---------------- Tables + columns ---------------- */
if (!table_exists($pdo,'students')) { echo '<div style="padding:16px" class="alert alert-danger">The <b>students</b> table was not found.</div>'; exit; }
$sc = cols($pdo,'students');
$userCols = table_exists($pdo,'users') ? cols($pdo,'users') : [];
$hasUsers = !empty($userCols);

$hasStuId     = in_array('id',$sc,true);
$hasStuUser   = in_array('user_id',$sc,true);
$hasDoB       = in_array('date_of_birth',$sc,true);
$hasOther     = in_array('other_names',$sc,true);
$hasClassId   = in_array('class_id',$sc,true);
$hasStreamId  = in_array('stream_id',$sc,true);
$hasCreated   = in_array('created_at',$sc,true);
$hasUpdated   = in_array('updated_at',$sc,true);

/* ---------------- Classes / Streams ---------------- */
$classes = [];
if (table_exists($pdo,'classes')) {
  $st = $pdo->prepare("SELECT id, name FROM classes WHERE school_id=:sid ORDER BY name");
  $st->execute([':sid'=>$schoolId ?? 0]);
  $classes = $st->fetchAll();
}
$streams = [];
if (table_exists($pdo,'streams')) {
  $st = $pdo->prepare("SELECT id, name FROM streams WHERE school_id=:sid ORDER BY name");
  $st->execute([':sid'=>$schoolId ?? 0]);
  $streams = $st->fetchAll();
}

/* ---------------- Helpers ---------------- */
function synth_email_from_index(string $indexNo, int $schoolId): string {
  $slug = strtolower(preg_replace('/[^a-z0-9]+/i','',$indexNo));
  if ($slug==='') $slug = 'student'.time();
  return $slug . '+sid' . $schoolId . '@students.local';
}
function ensure_unique_email(PDO $pdo, string $email): string {
  if (!table_exists($pdo,'users')) return $email;
  $uCols = cols($pdo,'users');
  if (!in_array('email', $uCols, true)) return $email;
  $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email=:e");
  $base = $email; $n=0;
  while (true) {
    $q->execute([':e'=>$email]);
    if ((int)$q->fetchColumn() === 0) return $email;
    $n++; [$local,$dom] = array_pad(explode('@',$base,2),2,'students.local');
    $email = $local . ".$n@" . $dom;
  }
}
function add_col(array &$cols, array &$vals, array &$bind, string $col, $value): void {
  $ph = ':p_' . $col;
  $cols[] = "`$col`"; $vals[] = $ph; $bind[$ph] = $value;
}
function student_exists(PDO $pdo, int $schoolId, string $indexNo): ?int {
  $q = $pdo->prepare("SELECT id FROM students WHERE school_id=:sid AND index_no=:ix LIMIT 1");
  $q->execute([':sid'=>$schoolId, ':ix'=>$indexNo]);
  $id = $q->fetchColumn();
  return $id!==false ? (int)$id : null;
}

/* ---------------- Handle POST ---------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && s_csrf_ok($_POST['__csrf'] ?? null)) {
  $act = $_POST['__action'] ?? '';

  /* CREATE student (+ user) */
  if ($act==='create') {
    if (!$schoolId) {
      flash_set('danger','School context missing. Please log in under a school.');
      prg_redirect();
    }

    $index_no   = trim((string)($_POST['index_no'] ?? ''));
    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $last_name  = trim((string)($_POST['last_name'] ?? ''));
    $gender     = (string)($_POST['gender'] ?? ''); // '', 'M', 'F'
    $password   = (string)($_POST['password'] ?? '');
    $password2  = (string)($_POST['password2'] ?? '');
    $user_email = trim((string)($_POST['user_email'] ?? ''));

    if ($index_no==='')                      { flash_set('danger','Index number is required.'); prg_redirect(); }
    if ($first_name==='')                    { flash_set('danger','First name is required.'); prg_redirect(); }
    if ($last_name==='')                     { flash_set('danger','Last name is required.'); prg_redirect(); }
    if (!in_array($gender,['M','F',''],true)){ flash_set('danger','Gender must be M or F.'); prg_redirect(); }
    if ($password==='' || $password2==='')   { flash_set('danger','Password and confirmation are required.'); prg_redirect(); }
    if ($password!==$password2)              { flash_set('danger','Passwords do not match.'); prg_redirect(); }

    // Code-level duplicate protection even if DB unique index is missing
    if (student_exists($pdo, (int)$schoolId, $index_no)) {
      flash_set('warning','A student with this Index Number already exists in this school.');
      prg_redirect();
    }

    try {
      $pdo->beginTransaction();

      // (1) Create user if table exists
      $user_id = null;
      if ($hasUsers) {
        $uCols=[]; $uVals=[]; $uBind=[];
        if (in_array('school_id',$userCols,true))     add_col($uCols,$uVals,$uBind,'school_id',$schoolId);
        if (in_array('email',$userCols,true)) {
          $email = $user_email !== '' ? $user_email : synth_email_from_index($index_no, (int)$schoolId);
          $email = ensure_unique_email($pdo, $email);
          add_col($uCols,$uVals,$uBind,'email',$email);
        }
        if (in_array('username',$userCols,true))      add_col($uCols,$uVals,$uBind,'username',$index_no);
        if (in_array('first_name',$userCols,true))    add_col($uCols,$uVals,$uBind,'first_name',$first_name);
        if (in_array('last_name',$userCols,true))     add_col($uCols,$uVals,$uBind,'last_name',$last_name);
        if (in_array('password_hash',$userCols,true)) add_col($uCols,$uVals,$uBind,'password_hash',password_hash($password,PASSWORD_BCRYPT));
        elseif (in_array('password',$userCols,true))  add_col($uCols,$uVals,$uBind,'password',password_hash($password,PASSWORD_BCRYPT));
        if (in_array('role',$userCols,true))          add_col($uCols,$uVals,$uBind,'role','STUDENT');
        if (in_array('is_active',$userCols,true))     add_col($uCols,$uVals,$uBind,'is_active',1);
        if (in_array('created_at',$userCols,true))    { $uCols[]='`created_at`'; $uVals[]='NOW()'; }
        if (in_array('updated_at',$userCols,true))    { $uCols[]='`updated_at`'; $uVals[]='NOW()'; }

        if ($uCols) {
          $sqlU = "INSERT INTO users (".implode(',', $uCols).") VALUES (".implode(',', $uVals).")";
          $pdo->prepare($sqlU)->execute($uBind);
          $user_id = (int)$pdo->lastInsertId();
        }
      }

      // (2) Create student — deterministic placeholders (no HY093)
      $sCols=[]; $sVals=[]; $sBind=[];
      if (in_array('school_id',$sc,true))   add_col($sCols,$sVals,$sBind,'school_id',$schoolId);
      if ($hasStuUser)                      add_col($sCols,$sVals,$sBind,'user_id',$user_id?:null);
      if (in_array('index_no',$sc,true))    add_col($sCols,$sVals,$sBind,'index_no',$index_no);
      if (in_array('first_name',$sc,true))  add_col($sCols,$sVals,$sBind,'first_name',$first_name);
      if ($hasOther)                        add_col($sCols,$sVals,$sBind,'other_names',($_POST['other_names'] ?? null) ?: null);
      if (in_array('last_name',$sc,true))   add_col($sCols,$sVals,$sBind,'last_name',$last_name);
      if (in_array('gender',$sc,true))      add_col($sCols,$sVals,$sBind,'gender',($gender!==''?$gender:null));
      if ($hasDoB)                          add_col($sCols,$sVals,$sBind,'date_of_birth',($_POST['date_of_birth'] ?? null) ?: null);
      if ($hasClassId)                      add_col($sCols,$sVals,$sBind,'class_id',($_POST['class_id'] ?? null) ?: null);
      if ($hasStreamId)                     add_col($sCols,$sVals,$sBind,'stream_id',($_POST['stream_id'] ?? null) ?: null);
      foreach (['guardian_name','guardian_phone','guardian_email','address','status'] as $opt) {
        if (in_array($opt,$sc,true)) add_col($sCols,$sVals,$sBind,$opt,($_POST[$opt] ?? null) ?: null);
      }
      if ($hasCreated) { $sCols[]='`created_at`'; $sVals[]='NOW()'; }
      if ($hasUpdated) { $sCols[]='`updated_at`'; $sVals[]='NOW()'; }

      if (!$sCols) throw new RuntimeException('No insertable columns found on students.');
      $sqlS = "INSERT INTO students (".implode(',', $sCols).") VALUES (".implode(',', $sVals).")";
      $pdo->prepare($sqlS)->execute($sBind);

      $pdo->commit();
      flash_set('success','Student'.($user_id?' & user account':'').' created.');
      prg_redirect();
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      // Handle unique constraint (23000) gracefully
      if ($e->getCode()==='23000') {
        flash_set('warning','Duplicate Index Number for this school. Nothing was saved.');
      } else {
        flash_set('danger','Create failed: '.htmlspecialchars($e->getMessage()));
      }
      prg_redirect();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash_set('danger','Create failed: '.htmlspecialchars($e->getMessage()));
      prg_redirect();
    }
  }

  /* EDIT student (+ optional user password) */
  if ($act==='edit' && $hasStuId) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id>0) {
      try {
        $pdo->beginTransaction();

        $sets=[]; $bind=[':id'=>$id];
        $editable = array_intersect(
          ['index_no','first_name','other_names','last_name','gender','date_of_birth','class_id','stream_id','guardian_name','guardian_phone','guardian_email','address','status'],
          $sc
        );
        foreach ($editable as $col) {
          $ph=':p_'.$col; $sets[]="`$col`=$ph"; $bind[$ph]=($_POST[$col] ?? null) ?: null;
        }
        if ($hasUpdated) { $sets[]='`updated_at`=NOW()'; }
        if ($sets) $pdo->prepare("UPDATE students SET ".implode(',', $sets)." WHERE id=:id LIMIT 1")->execute($bind);

        // Optional password change for linked user
        $newp  = (string)($_POST['new_password'] ?? '');
        $newp2 = (string)($_POST['new_password2'] ?? '');
        if ($newp !== '') {
          if ($newp !== $newp2) throw new RuntimeException('New passwords do not match.');
          if ($hasUsers && $hasStuUser) {
            $u=$pdo->prepare("SELECT user_id FROM students WHERE id=:id"); $u->execute([':id'=>$id]);
            $uid = (int)$u->fetchColumn();
            if ($uid>0) {
              $passCol = in_array('password_hash',$userCols,true) ? 'password_hash' : (in_array('password',$userCols,true)?'password':null);
              if ($passCol) {
                $pdo->prepare("UPDATE users SET `$passCol`=:pw".(in_array('updated_at',$userCols,true)?", updated_at=NOW()":"")." WHERE id=:id LIMIT 1")
                    ->execute([':pw'=>password_hash($newp,PASSWORD_BCRYPT), ':id'=>$uid]);
              }
            }
          }
        }

        $pdo->commit();
        flash_set('success','Student updated'.($newp!==''?' (password changed)':'').'.');
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('danger','Update failed: '.htmlspecialchars($e->getMessage()));
      }
      prg_redirect();
    }
  }

  /* ASSIGN class/stream */
  if ($act==='assign' && $hasStuId) {
    $id = (int)($_POST['id'] ?? 0);
    $sets=[]; $bind=[':id'=>$id];
    if ($hasClassId  && array_key_exists('class_id',$_POST))  { $sets[]='`class_id`=:cid';   $bind[':cid']=($_POST['class_id']??null)?:null; }
    if ($hasStreamId && array_key_exists('stream_id',$_POST)) { $sets[]='`stream_id`=:sid';  $bind[':sid']=($_POST['stream_id']??null)?:null; }
    if ($hasUpdated) $sets[]='`updated_at`=NOW()';
    if ($sets) {
      $pdo->prepare("UPDATE students SET ".implode(',', $sets)." WHERE id=:id LIMIT 1")->execute($bind);
      flash_set('success','Class/stream updated.');
      prg_redirect();
    }
  }

  /* RESET password (user) */
  if ($act==='reset_password' && $hasStuId) {
    $id  = (int)($_POST['id'] ?? 0);
    $rp1 = (string)($_POST['rp1'] ?? '');
    $rp2 = (string)($_POST['rp2'] ?? '');
    if ($rp1==='' || $rp2==='') { flash_set('danger','Password required.'); prg_redirect(); }
    if ($rp1!==$rp2)            { flash_set('danger','Passwords do not match.'); prg_redirect(); }

    if ($hasUsers && $hasStuUser) {
      $u=$pdo->prepare("SELECT user_id FROM students WHERE id=:id"); $u->execute([':id'=>$id]);
      $uid = (int)$u->fetchColumn();
      if ($uid>0) {
        $passCol = in_array('password_hash',$userCols,true) ? 'password_hash' : (in_array('password',$userCols,true)?'password':null);
        if ($passCol) {
          $pdo->prepare("UPDATE users SET `$passCol`=:pw".(in_array('updated_at',$userCols,true)?", updated_at=NOW()":"")." WHERE id=:id LIMIT 1")
              ->execute([':pw'=>password_hash($rp1,PASSWORD_BCRYPT), ':id'=>$uid]);
          flash_set('success','Password reset.');
          prg_redirect();
        }
      }
    }
  }

  /* DELETE student (+ user) */
  if ($act==='delete' && $hasStuId) {
    $id = (int)($_POST['id'] ?? 0);
    try {
      $pdo->beginTransaction();
      $uid = null;
      if ($hasStuUser) {
        $u=$pdo->prepare("SELECT user_id FROM students WHERE id=:id"); $u->execute([':id'=>$id]);
        $uid = $u->fetchColumn(); $uid = $uid!==false ? (int)$uid : null;
      }
      $pdo->prepare("DELETE FROM students WHERE id=:id LIMIT 1")->execute([':id'=>$id]);
      if ($uid && $hasUsers) $pdo->prepare("DELETE FROM users WHERE id=:id LIMIT 1")->execute([':id'=>$uid]);
      $pdo->commit();
      flash_set('success','Student'.($uid?' & user':'').' deleted.');
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash_set('danger','Delete failed: '.htmlspecialchars($e->getMessage()));
    }
    prg_redirect();
  }

} elseif ($_SERVER['REQUEST_METHOD']==='POST') {
  flash_set('danger','Invalid form token. Refresh and try again.');
  prg_redirect();
}

/* ---------------- Fetch list ---------------- */
$flash = flash_get();

$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20; $off = ($page-1)*$per;

$where = []; $bind = [];
if ($schoolId !== null && in_array('school_id',$sc,true)) { $where[]='school_id=:sid'; $bind[':sid']=$schoolId; }
if ($q!=='') {
  $searchable = array_values(array_intersect(['index_no','first_name','last_name'], $sc));
  if ($searchable) {
    $or=[]; foreach($searchable as $c){ $or[]="`$c` LIKE :q"; }
    $where[] = '('.implode(' OR ',$or).')'; $bind[':q']='%'.$q.'%';
  }
}
$W = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM students {$W}"); $cnt->execute($bind);
$total = (int)$cnt->fetchColumn();

$listCols = array_values(array_intersect(['id','index_no','first_name','last_name','gender','class_id','stream_id','created_at'], $sc));
if (!$listCols) $listCols = array_slice($sc,0,min(8,count($sc)));

$sel = $pdo->prepare("SELECT `".implode('`,`',$listCols)."` FROM students {$W} ORDER BY ".(in_array('id',$sc,true)?'id DESC':'1')." LIMIT :lim OFFSET :off");
foreach ($bind as $k=>$v) $sel->bindValue($k,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
$sel->bindValue(':lim',$per,PDO::PARAM_INT);
$sel->bindValue(':off',$off,PDO::PARAM_INT);
$sel->execute();
$rows = $sel->fetchAll();
$pages = (int)ceil($total/$per);

/* ---------------- Maps for names ---------------- */
$clsMap = []; foreach ($classes as $c) $clsMap[(int)$c['id']] = $c['name'];
$strMap = []; foreach ($streams as $s) $strMap[(int)$s['id']] = $s['name'];

/* ---------------- Header include ---------------- */
$pageTitle = 'Students — Admin';
$pageDescription = 'Register students (creates user accounts), edit, assign class/stream, delete.';
$bodyClass = 'dashboard students-page';
include __DIR__ . '/../../views/layouts/header.php';
?>
<style>
/* ---------- Page chrome ---------- */
.students-page .page-head{
  display:flex;gap:12px;align-items:center;justify-content:space-between;
  padding:16px;border-bottom:1px solid #e7eef8;background:#ffffff
}
.students-page h1{margin:0;font-size:20px;font-weight:800;letter-spacing:.2px;color:#0d2136}
.students-page .btn{
  display:inline-flex;gap:8px;align-items:center;border-radius:10px;padding:10px 12px;
  border:1px solid #d8e4f0;background:#f7fbff;color:#0d2136;font-weight:700;text-decoration:none;cursor:pointer
}
.students-page .btn:hover{box-shadow:0 6px 14px rgba(13,33,54,.08)}
.students-page .btn-primary{background:linear-gradient(90deg,#00c4cc,#00cc88);color:#062117;border:none}
.students-page .btn-danger{background:#741f2a;border-color:#7f2a33;color:#fff}
.students-page input,.students-page select{
  background:#fff;border:1px solid #d8e4f0;color:#0d2136;border-radius:10px;padding:10px 12px
}

/* ---------- Carded table (clean, shadow, nice hover) ---------- */
.students-page .table-wrap{
  padding:0;background:#fff;border:1px solid #e7eef8;border-radius:16px;
  box-shadow:0 12px 28px rgba(2,19,46,.08), 0 2px 6px rgba(2,19,46,.06); overflow:hidden; margin:16px
}
.students-page table{width:100%;border-collapse:separate;border-spacing:0}
.students-page thead th{
  background:#f7f9fc;color:#3b5166;font-size:12px;letter-spacing:.06em;text-transform:uppercase;
  padding:14px 16px;border-bottom:1px solid #e7eef8; position:sticky; top:0; z-index:1
}
.students-page tbody td{
  padding:14px 16px;border-bottom:1px solid #eef3fb;color:#0d2136; vertical-align:middle
}
.students-page tbody tr:nth-child(even){background:#fbfdff}
.students-page tbody tr:hover{
  background:#0e223f !important;color:#ffffff !important; transition:background .15s ease,color .15s ease
}
.students-page tbody tr:hover td{border-color:transparent}
.students-page th:first-child,.students-page td:first-child{padding-left:18px}
.students-page th:last-child,.students-page td:last-child{padding-right:18px}

/* ---------- Modals ---------- */
.students-page .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(6,10,18,.6);z-index:80}
.students-page .modal.open{display:flex}
.students-page .modal-card{width:100%;max-width:900px;background:#ffffff;border:1px solid #e7eef8;border-radius:16px; box-shadow:0 20px 40px rgba(2,19,46,.18)}
.students-page .modal-head{
  padding:14px 16px;border-bottom:1px solid #e7eef8;display:flex;align-items:center;justify-content:space-between;background:#f7f9fc;color:#0d2136
}
.students-page .modal-body{padding:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.students-page .modal-actions{padding:14px 16px;border-top:1px solid #e7eef8;display:flex;gap:8px;justify-content:flex-end}
.form-control label{font-size:12px;color:#3b5166;display:block;margin-bottom:6px}

/* ---------- Alerts ---------- */
.alert{border-radius:12px;padding:10px 12px;margin:16px}
.alert-success{background:#f3fffa;border:1px solid #1e7f5d;color:#145c46}
.alert-danger{background:#fff6f7;border:1px solid #7f2a33;color:#7f2a33}
.alert-warning{background:#fffdf3;border:1px solid #80630f;color:#6a540c}


/* ===== Consistent card/table + outlined buttons (no fill) ===== */
:root{
  --ink:#0d2136;          /* base text */
  --muted:#3b5166;        /* secondary text */
  --border:#e7eef8;       /* card border */
  --row-sep:#eef3fb;      /* row separators */
  --row-hover:#0e223f;    /* dark navy hover row */
  --btn:#1a2c46;          /* button outline/text default (navy) */
  --btn-hover:#0b1729;    /* darker navy on hover (light bg) */
  --danger:#b12a37;       /* delete outline/text */
  --danger-hover:#8e2430; /* delete hover (light bg) */
}

/* card shell */
.students-page .table-wrap{
  margin:16px; background:#fff; border:1px solid var(--border); border-radius:16px;
  box-shadow:0 12px 28px rgba(2,19,46,.08), 0 2px 6px rgba(2,19,46,.06); overflow:hidden;
}

/* table skeleton */
.students-page table{width:100%; border-collapse:separate; border-spacing:0}
.students-page thead th{
  background:#f7f9fc; color:var(--muted); font-size:12px; letter-spacing:.06em; text-transform:uppercase;
  padding:14px 16px; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:1;
}
.students-page tbody td{
  padding:14px 16px; border-bottom:1px solid var(--row-sep); color:var(--ink); vertical-align:middle;
}
.students-page tbody tr:nth-child(even){ background:#fbfdff; }

/* row hover -> navy band + white text */
.students-page tbody tr:hover{
  background:var(--row-hover) !important; color:#fff !important;
}
.students-page tbody tr:hover td,
.students-page tbody tr:hover th,
.students-page tbody tr:hover a{ color:#fff !important; }
.students-page tbody tr:hover td{ border-color:transparent; }

/* ------- OUTLINED BUTTONS (no fill) ------- */
.students-page .btn{
  appearance:none; background:transparent !important;
  color:var(--btn); border:1.6px solid var(--btn);
  border-radius:12px; padding:8px 12px; font-weight:700; cursor:pointer;
  transition:color .15s ease, border-color .15s ease; text-decoration:none;
}
/* normal hover on light backgrounds (forms/modals/header) */
.students-page .btn:hover{
  color:var(--btn-hover); border-color:var(--btn-hover);
}
/* primary stays outline only */
.students-page .btn-primary{
  background:transparent !important; color:var(--btn); border-color:var(--btn);
}
/* delete: outlined red, never filled */
.students-page .btn-danger{
  background:transparent !important; color:var(--danger); border-color:var(--danger);
}
.students-page .btn-danger:hover{
  color:var(--danger-hover); border-color:var(--danger-hover);
}

/* when a TABLE ROW is hovered (dark background), flip buttons to white */
.students-page tbody tr:hover .btn{
  color:#ffffff !important; border-color:#ffffff !important;
}
.students-page tbody tr:hover .btn-danger{
  color:#ffd6db !important; border-color:#ffd6db !important;
}

/* page chrome + search box */
.students-page .page-head{
  display:flex; gap:12px; align-items:center; justify-content:space-between;
  padding:16px; border-bottom:1px solid var(--border); background:#fff;
}
.students-page h1{ margin:0; font-size:20px; font-weight:800; color:var(--ink); }
.students-page input,.students-page select{
  background:#fff; border:1px solid #d8e4f0; color:var(--ink);
  border-radius:10px; padding:10px 12px;
}

/* fine border alignment */
.students-page th:first-child,.students-page td:first-child{ padding-left:18px; }
.students-page th:last-child,.students-page td:last-child{ padding-right:18px; }

/* --- Modal corner fixes (students + classes) --- */
.students-page .modal-card,
.classes-page  .modal-card{
  border-radius:16px;
  overflow:hidden;               /* trims header/footer backgrounds to radius */
}

/* match header band to the same radius */
.students-page .modal-head,
.classes-page  .modal-head{
  border-top-left-radius:16px;
  border-top-right-radius:16px;
}

/* and the footer/action bar too, for symmetry */
.students-page .modal-actions,
.classes-page  .modal-actions{
  border-bottom-left-radius:16px;
  border-bottom-right-radius:16px;
}


</style>

<section class="students-page">
  <div class="page-head">
    <h1>Students</h1>
    <div class="actions"><button class="btn btn-primary" id="openAdd" type="button">Add Student</button></div>
  </div>

  <?php if ($flash['type']): ?>
    <div class="alert alert-<?php echo $flash['type']==='success'?'success':($flash['type']==='warning'?'warning':'danger'); ?>">
      <?php echo $flash['msg']; ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:8px;padding:16px 16px 0 16px">
    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name or index no.">
    <button class="btn" type="submit">Search</button>
  </form>

  <div class="table-wrap">
    <?php if ($total===0): ?>
      <div class="alert alert-warning" style="margin:12px">No students found.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <?php foreach ($listCols as $h): ?>
              <th><?php echo ucwords(str_replace('_',' ',$h)); ?></th>
            <?php endforeach; ?>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach ($listCols as $h): ?>
                <td>
                  <?php
                  if ($h==='class_id')   echo isset($r[$h])&&$r[$h]? htmlspecialchars($clsMap[(int)$r[$h]] ?? (string)$r[$h]) : '—';
                  elseif ($h==='stream_id') echo isset($r[$h])&&$r[$h]? htmlspecialchars($strMap[(int)$r[$h]] ?? (string)$r[$h]) : '—';
                  else echo htmlspecialchars((string)$r[$h]);
                  ?>
                </td>
              <?php endforeach; ?>
              <td style="white-space:nowrap">
                <button class="btn" type="button" data-act="edit" data-row='<?php echo htmlspecialchars(json_encode($r),ENT_QUOTES,"UTF-8"); ?>'>Edit</button>
                <button class="btn" type="button" data-act="assign"
                        data-id="<?php echo (int)$r['id']; ?>"
                        data-class-id="<?php echo htmlspecialchars((string)($r['class_id'] ?? ''),ENT_QUOTES,'UTF-8'); ?>"
                        data-stream-id="<?php echo htmlspecialchars((string)($r['stream_id'] ?? ''),ENT_QUOTES,'UTF-8'); ?>">Assign</button>
                <button class="btn" type="button" data-act="reset" data-id="<?php echo (int)$r['id']; ?>">Reset</button>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this student?');">
                  <input type="hidden" name="__csrf" value="<?php echo s_csrf(); ?>">
                  <input type="hidden" name="__action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php if ($pages>1): ?>
    <div style="display:flex;gap:8px;justify-content:flex-end;padding:0 16px 16px 16px">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <a class="btn<?php echo $i===$page?' btn-primary':''; ?>" href="<?php echo '?'.http_build_query(['q'=>$q,'page'=>$i]); ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Add Student -->
<div class="modal" id="modalAdd">
  <div class="modal-card">
    <div class="modal-head">
      <strong>Register Student</strong>
      <div style="font-size:12px;color:#3b5166">School: <?php echo htmlspecialchars((string)($schoolId ?? '—')); ?></div>
      <button class="btn" id="closeAdd" type="button">Close</button>
    </div>
    <form method="post" class="modal-body" id="formAdd">
      <input type="hidden" name="__csrf" value="<?php echo s_csrf(); ?>">
      <input type="hidden" name="__action" value="create">

      <div class="form-control"><label>Index Number *</label>
        <input type="text" name="index_no" required placeholder="type any index no (no format rule)">
      </div>

      <div class="form-control"><label>First Name *</label><input type="text" name="first_name" required></div>
      <div class="form-control"><label>Other Names</label><input type="text" name="other_names"></div>
      <div class="form-control"><label>Last Name *</label><input type="text" name="last_name" required></div>

      <div class="form-control"><label>Gender</label>
        <select name="gender">
          <option value="">—</option>
          <option value="M">M</option>
          <option value="F">F</option>
        </select>
      </div>

      <div class="form-control"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>

      <div class="form-control"><label>Class</label>
        <select name="class_id">
          <option value="">—</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-control"><label>Stream</label>
        <select name="stream_id">
          <option value="">—</option>
          <?php foreach ($streams as $s): ?>
            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-control"><label>Guardian Name</label><input type="text" name="guardian_name"></div>
      <div class="form-control"><label>Guardian Phone</label><input type="text" name="guardian_phone"></div>
      <div class="form-control"><label>Guardian Email</label><input type="email" name="guardian_email"></div>
      <div class="form-control"><label>Address</label><input type="text" name="address"></div>
      <div class="form-control"><label>Status</label>
        <select name="status">
          <option value="">—</option>
          <option value="ACTIVE">ACTIVE</option>
          <option value="GRADUATED">GRADUATED</option>
          <option value="TRANSFERRED">TRANSFERRED</option>
          <option value="DROPPED">DROPPED</option>
        </select>
      </div>

      <!-- User account -->
      <div class="form-control"><label>Login Email (optional)</label><input type="email" name="user_email" placeholder="If blank, we generate one"></div>
      <div class="form-control"><label>Password *</label><input type="password" name="password" required></div>
      <div class="form-control"><label>Confirm Password *</label><input type="password" name="password2" required></div>
    </form>
    <div class="modal-actions"><button class="btn" id="cancelAdd" type="button">Cancel</button><button class="btn btn-primary" form="formAdd">Save</button></div>
  </div>
</div>

<!-- Edit Student -->
<div class="modal" id="modalEdit">
  <div class="modal-card">
    <div class="modal-head"><strong>Edit Student</strong><button class="btn" id="closeEdit" type="button">Close</button></div>
    <form method="post" class="modal-body" id="formEdit">
      <input type="hidden" name="__csrf" value="<?php echo s_csrf(); ?>">
      <input type="hidden" name="__action" value="edit">
      <input type="hidden" name="id" id="edit_id">

      <div class="form-control"><label>Index Number</label><input type="text" name="index_no" id="edit_index_no"></div>
      <div class="form-control"><label>First Name</label><input type="text" name="first_name" id="edit_first_name"></div>
      <div class="form-control"><label>Other Names</label><input type="text" name="other_names" id="edit_other_names"></div>
      <div class="form-control"><label>Last Name</label><input type="text" name="last_name" id="edit_last_name"></div>

      <div class="form-control"><label>Gender</label>
        <select name="gender" id="edit_gender">
          <option value="">—</option>
          <option value="M">M</option>
          <option value="F">F</option>
        </select>
      </div>

      <div class="form-control"><label>Date of Birth</label><input type="date" name="date_of_birth" id="edit_date_of_birth"></div>

      <div class="form-control"><label>Class</label>
        <select name="class_id" id="edit_class_id">
          <option value="">—</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-control"><label>Stream</label>
        <select name="stream_id" id="edit_stream_id">
          <option value="">—</option>
          <?php foreach ($streams as $s): ?>
            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-control"><label>Guardian Name</label><input type="text" name="guardian_name" id="edit_guardian_name"></div>
      <div class="form-control"><label>Guardian Phone</label><input type="text" name="guardian_phone" id="edit_guardian_phone"></div>
      <div class="form-control"><label>Guardian Email</label><input type="email" name="guardian_email" id="edit_guardian_email"></div>
      <div class="form-control"><label>Address</label><input type="text" name="address" id="edit_address"></div>
      <div class="form-control"><label>Status</label>
        <select name="status" id="edit_status">
          <option value="">—</option>
          <option value="ACTIVE">ACTIVE</option>
          <option value="GRADUATED">GRADUATED</option>
          <option value="TRANSFERRED">TRANSFERRED</option>
          <option value="DROPPED">DROPPED</option>
        </select>
      </div>

      <div class="form-control"><label>New Password</label><input type="password" name="new_password" id="edit_new_password" placeholder="Leave blank to keep"></div>
      <div class="form-control"><label>Confirm New Password</label><input type="password" name="new_password2" id="edit_new_password2" placeholder="Leave blank to keep"></div>
    </form>
    <div class="modal-actions"><button class="btn" id="cancelEdit" type="button">Cancel</button><button class="btn btn-primary" form="formEdit">Update</button></div>
  </div>
</div>

<!-- Assign Class/Stream -->
<div class="modal" id="modalAssign">
  <div class="modal-card">
    <div class="modal-head"><strong>Assign Class / Stream</strong><button class="btn" id="closeAssign" type="button">Close</button></div>
    <form method="post" class="modal-body" id="formAssign">
      <input type="hidden" name="__csrf" value="<?php echo s_csrf(); ?>">
      <input type="hidden" name="__action" value="assign">
      <input type="hidden" name="id" id="assign_id">

      <div class="form-control"><label>Class</label>
        <select name="class_id" id="assign_class_id">
          <option value="">—</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-control"><label>Stream</label>
        <select name="stream_id" id="assign_stream_id">
          <option value="">—</option>
          <?php foreach ($streams as $s): ?>
            <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
    <div class="modal-actions"><button class="btn" id="cancelAssign" type="button">Cancel</button><button class="btn btn-primary" form="formAssign">Save</button></div>
  </div>
</div>

<!-- Reset Password -->
<div class="modal" id="modalReset">
  <div class="modal-card">
    <div class="modal-head"><strong>Reset Student Password</strong><button class="btn" id="closeReset" type="button">Close</button></div>
    <form method="post" class="modal-body" id="formReset">
      <input type="hidden" name="__csrf" value="<?php echo s_csrf(); ?>">
      <input type="hidden" name="__action" value="reset_password">
      <input type="hidden" name="id" id="reset_id">
      <div class="form-control"><label>New Password</label><input type="password" name="rp1" required></div>
      <div class="form-control"><label>Confirm Password</label><input type="password" name="rp2" required></div>
    </form>
    <div class="modal-actions"><button class="btn" id="cancelReset" type="button">Cancel</button><button class="btn btn-primary" form="formReset">Reset</button></div>
  </div>
</div>

<script>
(function(){
  const $=s=>document.querySelector(s), on=(el,ev,fn)=>el&&el.addEventListener(ev,fn), set=(id,v)=>{const e=$('#'+id); if(e) e.value=(v??'');};

  // Add
  const modA=$('#modalAdd'); on($('#openAdd'),'click',()=>modA.classList.add('open'));
  ['closeAdd','cancelAdd'].forEach(id=>on($('#'+id),'click',()=>modA.classList.remove('open')));
  on(modA,'click',e=>{ if(e.target===modA) modA.classList.remove('open'); });

  // Edit
  const modE=$('#modalEdit'); ['closeEdit','cancelEdit'].forEach(id=>on($('#'+id),'click',()=>modE.classList.remove('open')));
  on(modE,'click',e=>{ if(e.target===modE) modE.classList.remove('open'); });

  // Assign
  const modS=$('#modalAssign'); ['closeAssign','cancelAssign'].forEach(id=>on($('#'+id),'click',()=>modS.classList.remove('open')));
  on(modS,'click',e=>{ if(e.target===modS) modS.classList.remove('open'); });

  // Reset
  const modR=$('#modalReset'); ['closeReset','cancelReset'].forEach(id=>on($('#'+id),'click',()=>modR.classList.remove('open')));
  on(modR,'click',e=>{ if(e.target===modR) modR.classList.remove('open'); });

  // Row actions
  document.addEventListener('click', e=>{
    const b = e.target.closest('button'); if(!b) return;
    const act = b.getAttribute('data-act');

    if (act==='edit') {
      const r = JSON.parse(b.getAttribute('data-row')||'{}');
      set('edit_id', r.id);
      set('edit_index_no', r.index_no||'');
      set('edit_first_name', r.first_name||'');
      set('edit_other_names', r.other_names||'');
      set('edit_last_name', r.last_name||'');
      set('edit_gender', r.gender||'');
      set('edit_date_of_birth', (r.date_of_birth||'').toString().slice(0,10));
      set('edit_class_id', r.class_id||'');
      set('edit_stream_id', r.stream_id||'');
      set('edit_guardian_name', r.guardian_name||'');
      set('edit_guardian_phone', r.guardian_phone||'');
      set('edit_guardian_email', r.guardian_email||'');
      set('edit_address', r.address||'');
      set('edit_status', r.status||'');
      set('edit_new_password',''); set('edit_new_password2','');
      modE.classList.add('open');
    }

    if (act==='assign') {
      set('assign_id', b.getAttribute('data-id'));
      set('assign_class_id', b.getAttribute('data-class-id') || '');
      set('assign_stream_id', b.getAttribute('data-stream-id') || '');
      modS.classList.add('open');
    }

    if (act==='reset') {
      set('reset_id', b.getAttribute('data-id'));
      modR.classList.add('open');
    }
  });

  document.addEventListener('keydown', e=>{
    if (e.key==='Escape') [modA,modE,modS,modR].forEach(m=>m&&m.classList.remove('open'));
  });
})();
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php';
