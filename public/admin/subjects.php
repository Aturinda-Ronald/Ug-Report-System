<?php
declare(strict_types=1);

// Bootstrap
require_once __DIR__ . '/../../config/config.php';

// ---- Auth gate ----
if (!function_exists('is_logged_in') || !is_logged_in()) {
    redirect(base_url('public/'));
    exit;
}
$role = function_exists('get_user_role') ? get_user_role() : null;
$allowed = ['SCHOOL_ADMIN', 'STAFF', 'SUPER_ADMIN'];
if (!$role || !in_array($role, $allowed, true)) {
    redirect(base_url());
    exit;
}

// Current school context
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$schoolId = $_SESSION['school_id'] ?? (function_exists('get_school_id') ? get_school_id() : null);

// ---- Page meta ----
$pageTitle       = 'Subjects — Admin';
$pageDescription = 'Manage subjects: add, edit, assign teacher, delete, search and browse records.';
$bodyClass       = 'dashboard subjects-page';

// ---- CSRF ----
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
function subj_csrf(): string { return $_SESSION['csrf']; }
function subj_csrf_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

// ---- DB helpers ----
function __get_pdo_subj(): PDO {
    if (function_exists('db'))     { $m = db();     if ($m instanceof PDO) return $m; }
    if (function_exists('get_db')) { $m = get_db(); if ($m instanceof PDO) return $m; }
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
    if (isset($GLOBALS['db'])  && $GLOBALS['db']  instanceof PDO) return $GLOBALS['db'];

    $dsn  = defined('DB_DSN')  ? DB_DSN  : 'mysql:host='.(defined('DB_HOST')?DB_HOST:'127.0.0.1').';dbname='.(defined('DB_NAME')?DB_NAME:'').';charset=utf8mb4';
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
function __table_exists_subj(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    return (bool)$stmt->fetchColumn();
}
function __columns_subj(PDO $pdo, string $table): array {
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $row) { $cols[] = $row['Field']; }
    return $cols;
}
function __ix(array $a, array $b): array { return array_values(array_intersect($a, $b)); }

// Staff/teacher lookup (optional)
function __get_staff_options_subj(PDO $pdo): array {
    $candidates = [
        ['table' => 'users',    'role_col' => 'role'],
        ['table' => 'staff',    'role_col' => null],
        ['table' => 'teachers', 'role_col' => null],
    ];
    foreach ($candidates as $cand) {
        if (!__table_exists_subj($pdo, $cand['table'])) continue;
        $cols = __columns_subj($pdo, $cand['table']);
        if (!in_array('id', $cols, true)) continue;

        if (in_array('full_name', $cols, true))      $label = 'full_name';
        elseif (in_array('name', $cols, true))       $label = 'name';
        elseif (in_array('first_name', $cols, true) && in_array('last_name', $cols, true)) $label = "CONCAT(first_name,' ',last_name)";
        elseif (in_array('username', $cols, true))   $label = 'username';
        elseif (in_array('email', $cols, true))      $label = 'email';
        else                                         $label = 'id';

        $where = '';
        if ($cand['role_col'] && in_array($cand['role_col'], $cols, true)) {
            $where = "WHERE `{$cand['role_col']}` IN ('STAFF','TEACHER','teacher','staff')";
        }

        $sql = "SELECT id, {$label} AS label FROM `{$cand['table']}` {$where} ORDER BY label LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll();
        if (!empty($rows)) return ['table'=>$cand['table'],'options'=>$rows];
    }
    return ['table'=>null,'options'=>[]];
}

$pdo           = __get_pdo_subj();
$subjectsTable = 'subjects';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (!__table_exists_subj($pdo, $subjectsTable)) {
    include __DIR__ . '/../../views/layouts/header.php';
    echo '<div class="container" style="padding:16px">';
    echo '<div class="alert alert-warning">The table <strong>'.$subjectsTable.'</strong> was not found. Please import your schema or update this file.</div>';
    echo '</div>';
    include __DIR__ . '/../../views/layouts/footer.php';
    exit;
}

$cols           = __columns_subj($pdo, $subjectsTable);
$hasId          = in_array('id', $cols, true);
$hasSchoolCol   = in_array('school_id', $cols, true);
$hasTeacherId   = in_array('teacher_id', $cols, true);
$hasTeacherNm   = in_array('subject_teacher', $cols, true);
$hasIsPractical = in_array('is_practical', $cols, true);
$hasMaxMark     = in_array('max_mark', $cols, true);
$hasPassMark    = in_array('pass_mark', $cols, true);
$hasIsActive    = in_array('is_active', $cols, true);
$hasUpdatedAt   = in_array('updated_at', $cols, true);
$hasCreatedAt   = in_array('created_at', $cols, true);

// Candidate fields (will be intersected with real columns)
$candidate = [
    'name','code','level','category','short_code','department','compulsory',
    'paper_code','paper_name',
    'subject_teacher','teacher_id',
    'is_practical','max_mark','pass_mark','is_active',
    'status','school_id','created_at','updated_at',
];

// ---- POST with PRG ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['__action'] ?? '';
    $token  = $_POST['__csrf']   ?? '';

    if (!subj_csrf_ok($token)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Invalid security token, please try again.'];
        redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    try {
        // CREATE
        if ($action === 'create') {
            $insCols = __ix($candidate, $cols);

            if ($hasSchoolCol && !in_array('school_id', $insCols, true)) $insCols[] = 'school_id';

            $payload = $_POST;
            if (!empty($_POST['subject_name']) && in_array('name', $cols, true)) {
                $payload['name'] = $_POST['subject_name'];
            }

            $params = []; $ph = [];
            foreach ($insCols as $c) {
                if ($c === 'school_id' && $hasSchoolCol)        { $params[":$c"] = $schoolId; }
                elseif ($c === 'created_at' && $hasCreatedAt)   { $params[':created_at'] = date('Y-m-d H:i:s'); }
                elseif ($c === 'updated_at' && $hasUpdatedAt)   { $params[':updated_at'] = date('Y-m-d H:i:s'); }
                else                                            { $params[":$c"] = $payload[$c] ?? null; }
                $ph[] = ":$c";
            }

            if ($hasIsPractical && !isset($params[':is_practical'])) $params[':is_practical'] = 0;
            if ($hasMaxMark     && ($params[':max_mark']  ?? '')==='') $params[':max_mark']  = 100;
            if ($hasPassMark    && ($params[':pass_mark'] ?? '')==='') $params[':pass_mark'] = 50;
            if ($hasIsActive    && !isset($params[':is_active']))      $params[':is_active'] = 1;

            $sql = "INSERT INTO `{$subjectsTable}` (`".implode('`,`',$insCols)."`) VALUES (".implode(',', $ph).")";
            $pdo->prepare($sql)->execute($params);

            $_SESSION['flash'] = ['type'=>'success','msg'=>'Subject added successfully.'];
            redirect($_SERVER['REQUEST_URI']); exit;
        }

        // EDIT
        if ($action === 'edit' && $hasId) {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $editable = __ix($candidate, $cols);
                $editable = array_values(array_diff($editable, ['teacher_id','subject_teacher','created_at','school_id']));

                $payload = $_POST;
                if (!empty($_POST['subject_name']) && in_array('name', $cols, true)) {
                    $payload['name'] = $_POST['subject_name'];
                }

                $sets=[]; $params=[':id'=>$id];
                foreach ($editable as $c) {
                    if (array_key_exists($c, $payload)) {
                        $sets[] = "`$c` = :$c";
                        $params[":$c"] = ($payload[$c] === '') ? null : $payload[$c];
                    }
                }
                if ($hasUpdatedAt) { $sets[]='`updated_at`=:updated_at'; $params[':updated_at']=date('Y-m-d H:i:s'); }

                if ($sets) {
                    $where = "WHERE `id`=:id";
                    if ($hasSchoolCol && $schoolId !== null) { $where .= " AND `school_id`=:_sid"; $params[':_sid']=$schoolId; }
                    $pdo->prepare("UPDATE `{$subjectsTable}` SET ".implode(',', $sets)." {$where} LIMIT 1")->execute($params);
                }

                $_SESSION['flash']=['type'=>'success','msg'=>'Subject updated.'];
                redirect($_SERVER['REQUEST_URI']); exit;
            }
        }

        // DELETE
        if ($action === 'delete' && $hasId) {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $params=[':id'=>$id]; $where="WHERE `id`=:id";
                if ($hasSchoolCol && $schoolId !== null) { $where.=" AND `school_id`=:_sid"; $params[':_sid']=$schoolId; }
                $pdo->prepare("DELETE FROM `{$subjectsTable}` {$where} LIMIT 1")->execute($params);
                $_SESSION['flash']=['type'=>'success','msg'=>'Subject deleted.'];
            }
            redirect($_SERVER['REQUEST_URI']); exit;
        }

        // ASSIGN TEACHER
        if ($action === 'assign' && $hasId && ($hasTeacherId || $hasTeacherNm)) {
            $id        = (int)($_POST['id'] ?? 0);
            $teacherId = isset($_POST['teacher_id']) && $_POST['teacher_id']!=='' ? (int)$_POST['teacher_id'] : null;
            $teacherNm = trim((string)($_POST['subject_teacher'] ?? ''));

            if ($id > 0) {
                $sets=[]; $params=[':id'=>$id];
                if ($hasTeacherId && $teacherId !== null) { $sets[]='`teacher_id`=:teacher_id'; $params[':teacher_id']=$teacherId; }
                if ($hasTeacherNm && $teacherNm !== '')   { $sets[]='`subject_teacher`=:subject_teacher'; $params[':subject_teacher']=$teacherNm; }
                if ($hasUpdatedAt) { $sets[]='`updated_at`=:updated_at'; $params[':updated_at']=date('Y-m-d H:i:s'); }

                if ($sets) {
                    $where="WHERE `id`=:id";
                    if ($hasSchoolCol && $schoolId !== null) { $where.=" AND `school_id`=:_sid"; $params[':_sid']=$schoolId; }
                    $pdo->prepare("UPDATE `{$subjectsTable}` SET ".implode(',', $sets)." {$where} LIMIT 1")->execute($params);
                    $_SESSION['flash']=['type'=>'success','msg'=>'Teacher assigned to subject.'];
                } else {
                    $_SESSION['flash']=['type'=>'warning','msg'=>'No suitable teacher fields found (add `teacher_id` and/or `subject_teacher`).'];
                }
            }
            redirect($_SERVER['REQUEST_URI']); exit;
        }

        $_SESSION['flash']=['type'=>'warning','msg'=>'Nothing to do.'];
        redirect($_SERVER['REQUEST_URI']); exit;

    } catch (Throwable $e) {
        $_SESSION['flash']=['type'=>'danger','msg'=>'Error: '.htmlspecialchars($e->getMessage())];
        redirect($_SERVER['REQUEST_URI']); exit;
    }
}

// ---- GET (list) ----
$q      = trim((string)($_GET['q'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 20;
$offset = ($page - 1) * $per;

$where = []; $bind = [];
if ($hasSchoolCol && $schoolId !== null) { $where[]="`school_id`=:sid"; $bind[':sid']=$schoolId; }

$searchable = __ix(['name','code','level','category','subject_teacher'], $cols);
if ($q !== '' && $searchable) {
    $parts=[]; foreach($searchable as $c){ $parts[]="`$c` LIKE :q"; }
    $where[]='('.implode(' OR ', $parts).')'; $bind[':q']='%'.$q.'%';
}
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM `{$subjectsTable}` {$whereSql}");
$cnt->execute($bind);
$total = (int)$cnt->fetchColumn();

$preferred = __ix(
    ['id','name','code','level','category','is_practical','max_mark','pass_mark','is_active','created_at'],
    $cols
);
if (!$preferred) $preferred = array_slice($cols, 0, min(8, count($cols)));

$sel = $pdo->prepare("SELECT `".implode('`,`',$preferred)."` FROM `{$subjectsTable}` {$whereSql} ORDER BY ".($hasId?'`id` DESC':'1')." LIMIT :lim OFFSET :off");
foreach ($bind as $k=>$v) { $sel->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
$sel->bindValue(':lim', $per, PDO::PARAM_INT);
$sel->bindValue(':off', $offset, PDO::PARAM_INT);
$sel->execute();
$rows  = $sel->fetchAll();
$pages = (int)ceil($total / $per);

$staff = ($hasTeacherId || $hasTeacherNm) ? __get_staff_options_subj($pdo) : ['options'=>[]];
$staffOptions = $staff['options'];

// ---- Render ----
include __DIR__ . '/../../views/layouts/header.php';
?>
<style>
/* ===== Design tokens (consistent across admin) ===== */
:root{
  --ink:#0d2136;          /* base text */
  --muted:#3b5166;        /* secondary text */
  --border:#e7eef8;       /* card border */
  --row-sep:#eef3fb;      /* row separators */
  --row-hover:#0e223f;    /* dark hover row */
  --btn:#1a2c46;          /* button outline/text */
  --btn-hover:#0b1729;    /* btn hover on light bg */
  --danger:#b12a37;       /* delete outline/text */
  --danger-hover:#8e2430; /* delete hover */
}

/* ===== Page chrome ===== */
.subjects-page .page-head{
  display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;
  padding:16px;border-bottom:1px solid var(--border);background:#fff;
}
.subjects-page h1{margin:0;font-size:20px;font-weight:800;letter-spacing:.2px;color:var(--ink)}
.subjects-page .actions{display:flex;gap:8px;align-items:center}

/* ===== Search (transparent like Classes) ===== */
.subjects-page .search{
  display:flex;gap:8px;align-items:center;
  margin:12px 16px; padding:0; background:transparent; border:0;
}
.subjects-page input[type="text"], .subjects-page select, .subjects-page input[type="number"]{
  background:#fff;border:1px solid #d8e4f0;color:var(--ink);
  border-radius:10px;padding:10px 12px
}

/* ===== Carded table ===== */
.subjects-page .table-wrap{
  margin:0 16px 16px 16px;background:#fff;border:1px solid var(--border);border-radius:16px;
  box-shadow:0 12px 28px rgba(2,19,46,.08),0 2px 6px rgba(2,19,46,.06);overflow:hidden
}
.subjects-page table{width:100%;min-width:980px;border-collapse:separate;border-spacing:0}
.subjects-page thead th{
  background:#f7f9fc;color:var(--muted);font-size:12px;letter-spacing:.06em;text-transform:uppercase;
  padding:14px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1
}
.subjects-page tbody td{
  padding:14px 16px;border-bottom:1px solid var(--row-sep);color:var(--ink);vertical-align:middle
}
.subjects-page tbody tr:nth-child(even){background:#fbfdff}

/* Row hover: dark band + ALL text white */
.subjects-page tbody tr:hover{background:var(--row-hover) !important}
.subjects-page tbody tr:hover td,
.subjects-page tbody tr:hover th,
.subjects-page tbody tr:hover a{color:#fff !important}
.subjects-page tbody tr:hover td{border-color:transparent}

.subjects-page th:first-child,.subjects-page td:first-child{padding-left:18px}
.subjects-page th:last-child,.subjects-page td:last-child{padding-right:18px}

/* ===== Outlined buttons (icons inherit currentColor) ===== */
.subjects-page .btn{
  appearance:none;background:transparent !important;color:var(--btn);border:1.6px solid var(--btn);
  border-radius:12px;padding:8px 12px;font-weight:700;cursor:pointer;
  transition:color .15s ease,border-color .15s ease;text-decoration:none;
  display:inline-flex;align-items:center;gap:8px
}
.subjects-page .btn svg{width:16px;height:16px}
.subjects-page .btn:hover{color:var(--btn-hover);border-color:var(--btn-hover)}
.subjects-page .btn-primary{background:transparent !important;color:var(--btn);border-color:var(--btn)}
.subjects-page .btn-danger{background:transparent !important;color:var(--danger);border-color:var(--danger)}
.subjects-page .btn-danger:hover{color:var(--danger-hover);border-color:var(--danger-hover)}

/* On dark hovered row, keep buttons legible */
.subjects-page tbody tr:hover .btn{color:#fff !important;border-color:#fff !important}
.subjects-page tbody tr:hover .btn-danger{color:#ffd6db !important;border-color:#ffd6db !important}

.subjects-page .actions-col{white-space:nowrap}

/* Empty state */
.subjects-page .empty{
  padding:24px;text-align:center;color:var(--muted);
  border:1px dashed var(--border);border-radius:12px;background:#fff;margin:12px
}

/* Pagination */
.subjects-page .pagination{display:flex;gap:6px;align-items:center;justify-content:flex-end;padding:0 16px 16px 16px}
.subjects-page .page-link{padding:6px 10px;border-radius:8px;border:1px solid var(--btn);text-decoration:none;color:var(--btn);background:transparent}
.subjects-page .page-link:hover{color:var(--btn-hover);border-color:var(--btn-hover)}
.subjects-page .page-link.active{color:#fff;border-color:#1c2f4c;background:#1c2f4c}

/* ===== Modals (light, consistent, clipped corners) ===== */
.subjects-page .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:80;background:rgba(6,10,18,.6)}
.subjects-page .modal.open{display:flex}
.subjects-page .modal-card{
  width:100%;max-width:860px;background:#fff;border:1px solid var(--border);border-radius:16px;
  box-shadow:0 30px 80px rgba(2,19,46,.18);overflow:hidden /* clip header/footer to radius */
}
.subjects-page .modal-head{
  padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;
  background:#f7f9fc;color:var(--ink)
}
.subjects-page .modal-body{padding:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.subjects-page .modal-actions{padding:14px 16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px}

/* form inputs inside modals */
.subjects-page .form-control label{font-size:12px;color:var(--muted);display:block;margin-bottom:6px}
.subjects-page .form-control input,.subjects-page .form-control select{
  width:100%;background:#fff;border:1px solid #d8e4f0;color:var(--ink);border-radius:10px;padding:10px 12px
}

/* Alerts */
.alert{border-radius:12px;padding:10px 12px;margin:16px}
.alert-success{background:#f3fffa;border:1px solid #1e7f5d;color:#145c46}
.alert-danger{background:#fff6f7;border:1px solid #7f2a33;color:#7f2a33}
.alert-warning{background:#fffdf3;border:1px solid #80630f;color:#6a540c}
</style>

<section class="subjects-page">
  <div class="page-head">
    <h1>Subjects</h1>
    <div class="actions">
      <button class="btn btn-primary" id="btnOpenAdd" type="button">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
        Add Subject
      </button>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
      <?php echo $flash['msg']; ?>
    </div>
  <?php endif; ?>

  <form class="search" method="get" action="">
    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name, code, level, category, teacher…">
    <button class="btn" type="submit">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zM9.5 14A4.5 4.5 0 1 1 14 9.5 4.505 4.505 0 0 1 9.5 14z"/></svg>
      Search
    </button>
  </form>

  <div class="table-wrap">
    <?php if ($total === 0): ?>
      <div class="empty">No subjects found<?php echo $q ? ' for "<strong>'.htmlspecialchars($q).'</strong>"' : ''; ?>.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <?php foreach ($preferred as $c): ?>
              <th><?php echo ucwords(str_replace('_',' ', $c)); ?></th>
            <?php endforeach; ?>
            <?php if ($hasId): ?><th class="actions-col">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach ($preferred as $c): ?>
                <td>
                  <?php
                    $val = $r[$c];
                    if (in_array($c, ['is_practical','is_active'], true)) {
                        echo (int)$val ? '1' : '0';
                    } else {
                        echo htmlspecialchars(is_scalar($val) ? (string)$val : json_encode($val));
                    }
                  ?>
                </td>
              <?php endforeach; ?>

              <?php if ($hasId): ?>
                <td class="actions-col">
                  <!-- Edit -->
                  <button class="btn" type="button" data-act="edit"
                          data-row='<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8"); ?>' title="Edit">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    Edit
                  </button>

                  <?php if ($hasTeacherId || $hasTeacherNm): ?>
                    <!-- Assign -->
                    <button class="btn" type="button" data-act="assign"
                            data-id="<?php echo (int)$r['id']; ?>"
                            data-current-name="<?php echo htmlspecialchars((string)($r['subject_teacher'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-current-teacher-id="<?php echo htmlspecialchars((string)($r['teacher_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            title="Assign Teacher">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.2 0-8 2.1-8 5v1h16v-1c0-2.9-3.8-5-8-5z"/></svg>
                      Assign
                    </button>
                  <?php endif; ?>

                  <!-- Delete -->
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this subject? This cannot be undone.');">
                    <input type="hidden" name="__csrf" value="<?php echo subj_csrf(); ?>">
                    <input type="hidden" name="__action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-danger" type="submit" title="Delete">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3h6l1 2h5v2H3V5h5l1-2zm1 7h2v8h-2v-8zm4 0h2v8h-2v-8zM7 10h2v8H7v-8z"/></svg>
                      Delete
                    </button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($i=1; $i<=$pages; $i++):
        $url = '?'.http_build_query(['q'=>$q,'page'=>$i]);
        $cls = 'page-link'.($i === $page ? ' active' : '');
      ?>
        <a class="<?php echo $cls; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Add Subject Modal -->
<div class="modal" id="modalAdd">
  <div class="modal-card">
    <div class="modal-head">
      <strong>Add Subject</strong>
      <button class="btn" id="btnCloseAdd" type="button">Close</button>
    </div>
    <form method="post" class="modal-body" id="formAdd" action="">
      <input type="hidden" name="__action" value="create">
      <input type="hidden" name="__csrf" value="<?php echo subj_csrf(); ?>">

      <div class="form-control"><label>Subject Name</label><input type="text" name="name" placeholder="e.g., Mathematics" required></div>
      <div class="form-control"><label>Code</label><input type="text" name="code" placeholder="e.g., MTC"></div>
      <div class="form-control"><label>Level</label><input type="text" name="level" placeholder="O_LEVEL / A_LEVEL"></div>
      <div class="form-control"><label>Category</label><input type="text" name="category" placeholder="CORE / ELECTIVE"></div>

      <?php if ($hasIsPractical): ?>
        <div class="form-control"><label>Is Practical</label>
          <select name="is_practical"><option value="0">No</option><option value="1">Yes</option></select>
        </div>
      <?php endif; ?>

      <?php if ($hasMaxMark): ?>
        <div class="form-control"><label>Max Mark</label><input type="number" step="0.01" name="max_mark" value="100"></div>
      <?php endif; ?>

      <?php if ($hasPassMark): ?>
        <div class="form-control"><label>Pass Mark</label><input type="number" step="0.01" name="pass_mark" value="50"></div>
      <?php endif; ?>

      <?php if ($hasIsActive): ?>
        <div class="form-control"><label>Is Active</label>
          <select name="is_active"><option value="1">Yes</option><option value="0">No</option></select>
        </div>
      <?php endif; ?>
    </form>
    <div class="modal-actions">
      <button class="btn" id="btnCancelAdd" type="button">Cancel</button>
      <button class="btn btn-primary" id="btnSaveAdd" form="formAdd" type="submit">Save</button>
    </div>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal" id="modalEdit">
  <div class="modal-card">
    <div class="modal-head">
      <strong>Edit Subject</strong>
      <button class="btn" id="btnCloseEdit" type="button">Close</button>
    </div>
    <form method="post" class="modal-body" id="formEdit" action="">
      <input type="hidden" name="__action" value="edit">
      <input type="hidden" name="__csrf" value="<?php echo subj_csrf(); ?>">
      <input type="hidden" name="id" id="edit_id">

      <div class="form-control"><label>Subject Name</label><input type="text" name="name" id="edit_name" required></div>
      <div class="form-control"><label>Code</label><input type="text" name="code" id="edit_code"></div>
      <div class="form-control"><label>Level</label><input type="text" name="level" id="edit_level"></div>
      <div class="form-control"><label>Category</label><input type="text" name="category" id="edit_category"></div>

      <?php if ($hasIsPractical): ?>
        <div class="form-control"><label>Is Practical</label>
          <select name="is_practical" id="edit_is_practical"><option value="0">No</option><option value="1">Yes</option></select>
        </div>
      <?php endif; ?>

      <?php if ($hasMaxMark): ?>
        <div class="form-control"><label>Max Mark</label><input type="number" step="0.01" name="max_mark" id="edit_max_mark"></div>
      <?php endif; ?>

      <?php if ($hasPassMark): ?>
        <div class="form-control"><label>Pass Mark</label><input type="number" step="0.01" name="pass_mark" id="edit_pass_mark"></div>
      <?php endif; ?>

      <?php if ($hasIsActive): ?>
        <div class="form-control"><label>Is Active</label>
          <select name="is_active" id="edit_is_active"><option value="1">Yes</option><option value="0">No</option></select>
        </div>
      <?php endif; ?>
    </form>
    <div class="modal-actions">
      <button class="btn" id="btnCancelEdit" type="button">Cancel</button>
      <button class="btn btn-primary" id="btnSaveEdit" form="formEdit" type="submit">Update</button>
    </div>
  </div>
</div>

<?php if ($hasTeacherId || $hasTeacherNm): ?>
<!-- Assign Teacher Modal -->
<div class="modal" id="modalAssign">
  <div class="modal-card">
    <div class="modal-head">
      <strong>Assign Teacher to Subject</strong>
      <button class="btn" id="btnCloseAssign" type="button">Close</button>
    </div>
    <form method="post" class="modal-body" id="formAssign" action="">
      <input type="hidden" name="__action" value="assign">
      <input type="hidden" name="__csrf" value="<?php echo subj_csrf(); ?>">
      <input type="hidden" name="id" id="assign_id">

      <?php if (!empty($staffOptions) && $hasTeacherId): ?>
        <div class="form-control">
          <label>Select Staff/Teacher</label>
          <select name="teacher_id" id="assign_teacher_id">
            <option value="">— select —</option>
            <?php foreach ($staffOptions as $opt): ?>
              <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars((string)$opt['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php if ($hasTeacherNm): ?>
        <div class="form-control">
          <label>Subject Teacher (name)</label>
          <input type="text" name="subject_teacher" id="assign_subject_teacher" placeholder="e.g., Mr. Okello">
        </div>
      <?php endif; ?>
    </form>
    <div class="modal-actions">
      <button class="btn" id="btnCancelAssign" type="button">Cancel</button>
      <button class="btn btn-primary" id="btnSaveAssign" form="formAssign" type="submit">Assign</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  const $ = (sel)=>document.querySelector(sel);

  // Add
  const modalAdd=$('#modalAdd'), addOpen=$('#btnOpenAdd'), addClose=$('#btnCloseAdd'), addCancel=$('#btnCancelAdd');
  function showAdd(){ modalAdd.classList.add('open'); const b=$('#btnSaveAdd'); if(b) b.disabled=false; }
  function hideAdd(){ modalAdd.classList.remove('open'); }
  addOpen?.addEventListener('click',showAdd);
  addClose?.addEventListener('click',hideAdd);
  addCancel?.addEventListener('click',hideAdd);
  modalAdd?.addEventListener('click',e=>{ if(e.target===modalAdd) hideAdd(); });

  // Edit
  const modalEdit=$('#modalEdit'), editClose=$('#btnCloseEdit'), editCancel=$('#btnCancelEdit');
  function showEdit(){ modalEdit.classList.add('open'); const b=$('#btnSaveEdit'); if(b) b.disabled=false; }
  function hideEdit(){ modalEdit.classList.remove('open'); }
  editClose?.addEventListener('click',hideEdit);
  editCancel?.addEventListener('click',hideEdit);
  modalEdit?.addEventListener('click',e=>{ if(e.target===modalEdit) hideEdit(); });

  // Assign
  const modalAssign=$('#modalAssign'), assignClose=$('#btnCloseAssign'), assignCancel=$('#btnCancelAssign');
  function showAssign(){ modalAssign.classList.add('open'); const b=$('#btnSaveAssign'); if(b) b.disabled=false; }
  function hideAssign(){ modalAssign.classList.remove('open'); }
  assignClose?.addEventListener('click',hideAssign);
  assignCancel?.addEventListener('click',hideAssign);
  modalAssign?.addEventListener('click',e=>{ if(e.target===modalAssign) hideAssign(); });

  // Row actions
  document.addEventListener('click', e=>{
    const btn = e.target.closest('button'); if(!btn) return;
    const act = btn.getAttribute('data-act');

    if (act==='edit') {
      const raw = btn.getAttribute('data-row'); if(!raw) return;
      try{
        const r = JSON.parse(raw);
        const set=(id,v)=>{ const el=document.getElementById(id); if(el) el.value=(v??''); };
        set('edit_id', r.id ?? '');
        set('edit_name', r.name ?? r.subject_name ?? '');
        set('edit_code', r.code ?? '');
        set('edit_level', r.level ?? '');
        set('edit_category', r.category ?? '');
        const ip=document.getElementById('edit_is_practical'); if(ip && r.hasOwnProperty('is_practical')) ip.value=String(r.is_practical ?? '0');
        const mm=document.getElementById('edit_max_mark');    if(mm && r.hasOwnProperty('max_mark'))     mm.value=r.max_mark ?? '';
        const pm=document.getElementById('edit_pass_mark');   if(pm && r.hasOwnProperty('pass_mark'))    pm.value=r.pass_mark ?? '';
        const ia=document.getElementById('edit_is_active');   if(ia && r.hasOwnProperty('is_active'))    ia.value=String(r.is_active ?? '1');
        showEdit();
      }catch(_){}
    }

    if (act==='assign') {
      const id = btn.getAttribute('data-id') || '';
      const name = btn.getAttribute('data-current-name') || '';
      const tid = btn.getAttribute('data-current-teacher-id') || '';
      const idEl=document.getElementById('assign_id');
      const nameEl=document.getElementById('assign_subject_teacher');
      const tidEl=document.getElementById('assign_teacher_id');
      if(idEl) idEl.value=id;
      if(nameEl) nameEl.value=name;
      if(tidEl) tidEl.value=tid;
      showAssign();
    }
  });

  // disable submit buttons on submit
  document.getElementById('formAdd')?.addEventListener('submit',()=>{ const b=document.getElementById('btnSaveAdd'); if(b) b.disabled=true; });
  document.getElementById('formEdit')?.addEventListener('submit',()=>{ const b=document.getElementById('btnSaveEdit'); if(b) b.disabled=true; });
  document.getElementById('formAssign')?.addEventListener('submit',()=>{ const b=document.getElementById('btnSaveAssign'); if(b) b.disabled=true; });

  // Esc closes
  document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ hideAdd(); hideEdit(); hideAssign(); }});
})();
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php';
