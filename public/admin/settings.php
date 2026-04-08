<?php
declare(strict_types=1);

/**
 * public/admin/settings.php
 * School/Admin settings panel:
 * - School profile (name, code, contacts, logo)
 * - Academic settings (current year/term, default marks possible, rounding, pass mark, CSV import)
 * - Grading (default grade scale) + manage assessment types (exams)
 * - Security (require 2FA, password policy – optional)
 *
 * The code auto-detects your DB tables/columns and only writes to columns that exist.
 * It scopes settings by school_id when possible.
 */

require_once __DIR__ . '/../../config/config.php';

/* ------------ Auth gate ------------ */
if (function_exists('require_role')) {
    require_role('SCHOOL_ADMIN', 'STAFF', 'SUPER_ADMIN');
} else {
    if (!function_exists('is_logged_in') || !is_logged_in()) {
        redirect(base_url('public/'));
        exit;
    }
    $role = function_exists('get_user_role') ? get_user_role() : null;
    if (!$role || !in_array($role, ['SCHOOL_ADMIN','STAFF','SUPER_ADMIN'], true)) {
        redirect(base_url());
        exit;
    }
}

/* ------------ Page meta ------------ */
$pageTitle       = 'Settings — Admin';
$pageDescription = 'Configure school profile, academic and grading settings.';
$bodyClass       = 'dashboard settings-page';

/* ------------ Header ------------ */
include __DIR__ . '/../../views/layouts/header.php';

/* ------------ DB helpers ------------ */
function dbx(): PDO {
    if (function_exists('db'))     { $x = db();     if ($x instanceof PDO) return $x; }
    if (function_exists('get_db')) { $x = get_db(); if ($x instanceof PDO) return $x; }
    $dsn  = defined('DB_DSN')  ? DB_DSN  : ('mysql:host='.(DB_HOST ?? '127.0.0.1').';dbname='.(DB_NAME ?? '').';charset=utf8mb4');
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
function tbl_exists(PDO $pdo, string $name): bool {
    $q=$pdo->prepare("SHOW TABLES LIKE :t"); $q->execute([':t'=>$name]); return (bool)$q->fetchColumn();
}
function cols(PDO $pdo, string $table): array {
    $c=[]; foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $r) $c[]=$r['Field']; return $c;
}
function pick(array $cols, array $cands): ?string {
    foreach ($cands as $c) if (in_array($c, $cols, true)) return $c; return null;
}
function current_school_id(PDO $pdo): ?int {
    if (!empty($_SESSION['school_id'])) return (int)$_SESSION['school_id'];
    if (!empty($_SESSION['user_id']) && tbl_exists($pdo,'users')) {
        $q=$pdo->prepare("SELECT school_id FROM users WHERE id=:id LIMIT 1");
        $q->execute([':id'=>(int)$_SESSION['user_id']]);
        $sid = $q->fetchColumn();
        if ($sid!==false) return (int)$sid;
    }
    return null;
}

/* ------------ CSRF ------------ */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf(): string { return $_SESSION['csrf']; }
function csrf_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

/* ------------ Prepare context ------------ */
$pdo      = dbx();
$schoolId = current_school_id($pdo);

$flash = ['type'=>null, 'msg'=>null];

/* ------------ Settings key/value table (optional) ------------ */
$kvTable = null; $kvCols = [];
foreach (['school_settings','settings','app_settings'] as $cand) {
    if (tbl_exists($pdo,$cand)) { $kvTable=$cand; $kvCols=cols($pdo,$cand); break; }
}
$kvKey   = $kvTable ? (pick($kvCols,['key','setting_key','name','option']) ?? 'key') : null;
$kvVal   = $kvTable ? (pick($kvCols,['value','setting_value','val','data','content']) ?? 'value') : null;
$kvSid   = $kvTable && in_array('school_id',$kvCols,true) ? 'school_id' : null;
$kvIdCol = $kvTable && in_array('id',$kvCols,true) ? 'id' : null;

function setting_get(?PDO $pdo, ?string $kvTable, ?string $kvKey, ?string $kvVal, ?string $kvSid, ?int $schoolId, string $key, $default=null) {
    if (!$pdo || !$kvTable || !$kvKey || !$kvVal) return $default;
    $sql  = "SELECT `{$kvVal}` FROM `{$kvTable}` WHERE `{$kvKey}`=:k";
    $bind = [':k'=>$key];
    if ($kvSid && $schoolId) { $sql .= " AND `{$kvSid}` = :sid"; $bind[':sid']=$schoolId; }
    $sql .= " ORDER BY 1 DESC LIMIT 1";
    $st=$pdo->prepare($sql); $st->execute($bind);
    $v=$st->fetchColumn();
    return $v!==false ? $v : $default;
}
function setting_set(PDO $pdo, string $kvTable, string $kvKey, string $kvVal, ?string $kvSid, ?int $schoolId, string $key, $value): void {
    // Try update, else insert
    $where = "`{$kvKey}`=:k";
    $bind  = [':k'=>$key];
    if ($kvSid && $schoolId) { $where .= " AND `{$kvSid}`=:sid"; $bind[':sid']=$schoolId; }

    $u=$pdo->prepare("UPDATE `{$kvTable}` SET `{$kvVal}`=:v WHERE {$where} LIMIT 1");
    $ok=$u->execute($bind+[':v'=>$value]);
    if ($u->rowCount()===0) {
        $cols = "`{$kvKey}`,`{$kvVal}`".($kvSid && $schoolId ? ",`{$kvSid}`" : "");
        $vals = ":k,:v".($kvSid && $schoolId ? ",:sid" : "");
        $i=$pdo->prepare("INSERT INTO `{$kvTable}` ({$cols}) VALUES ({$vals})");
        $i->execute($bind+[':v'=>$value]);
    }
}

/* ------------ Schools table (profile) ------------ */
$schoolsTable = tbl_exists($pdo,'schools') ? 'schools' : (tbl_exists($pdo,'school') ? 'school' : null);
$schoolCols   = $schoolsTable ? cols($pdo,$schoolsTable) : [];
// Common column names
$colName   = pick($schoolCols, ['school_name','name','title']);
$colCode   = pick($schoolCols, ['code','school_code']);
$colPhone  = pick($schoolCols, ['phone','phone_number','tel']);
$colEmail  = pick($schoolCols, ['email','contact_email']);
$colAddr   = pick($schoolCols, ['address','postal_address','location']);
$colWeb    = pick($schoolCols, ['website','site']);
$colLogo   = pick($schoolCols, ['logo','logo_url','logo_path','logo_file']);

/* ------------ Grade scales ------------ */
$scales = [];
$scaleTable = tbl_exists($pdo,'grade_scales') ? 'grade_scales' : null;
$scaleCols  = $scaleTable ? cols($pdo,$scaleTable) : [];
$scaleHasSid= $scaleTable && in_array('school_id',$scaleCols,true);
$scaleDefCol= $scaleTable ? (in_array('is_default',$scaleCols,true) ? 'is_default' : null) : null;

if ($scaleTable) {
    $sql  = "SELECT id, ".(in_array('name',$scaleCols,true)?'name':'code')." AS label FROM `{$scaleTable}`";
    $bind = [];
    if ($scaleHasSid && $schoolId) { $sql.=" WHERE school_id=:sid"; $bind[':sid']=$schoolId; }
    $sql .= " ORDER BY id";
    $st=$pdo->prepare($sql); $st->execute($bind); $scales=$st->fetchAll();
}
$defaultScaleFromTable = null;
if ($scaleTable && $scaleDefCol) {
    $sql="SELECT id FROM `{$scaleTable}` ".($scaleHasSid && $schoolId ? "WHERE school_id=:sid AND " : "WHERE ")."`{$scaleDefCol}` = 1 ORDER BY id LIMIT 1";
    $st=$pdo->prepare($sql); $st->execute($scaleHasSid && $schoolId ? [':sid'=>$schoolId] : []);
    $defaultScaleFromTable = $st->fetchColumn();
}
// Also allow default via settings kv
$defaultScaleId = (int)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'default_grade_scale_id', (int)($defaultScaleFromTable ?? 0));

/* ------------ Assessment types ------------ */
$assessTable = tbl_exists($pdo,'assessment_types') ? 'assessment_types' : null;
$assessCols  = $assessTable ? cols($pdo,$assessTable) : [];
$assessHasSid= $assessTable && in_array('school_id',$assessCols,true);
$assessName  = $assessTable ? (pick($assessCols,['name','title','label']) ?? 'name') : null;
$assessCode  = $assessTable ? (pick($assessCols,['code','short_code']) ?? null) : null;
$assessWeight= $assessTable && in_array('weight',$assessCols,true) ? 'weight' : null;

$assessments = [];
if ($assessTable) {
    $sql="SELECT id, `{$assessName}` AS name".($assessCode?", `{$assessCode}` AS code":"").($assessWeight?", `{$assessWeight}` AS weight":"")." FROM `{$assessTable}`";
    $bind=[]; if ($assessHasSid && $schoolId) { $sql.=" WHERE school_id=:sid"; $bind[':sid']=$schoolId; }
    $sql.=" ORDER BY id";
    $st=$pdo->prepare($sql); $st->execute($bind); $assessments=$st->fetchAll();
}

/* ------------ Load current school profile row ------------ */
$profile = ['school_name'=>$_SESSION['school_name'] ?? '', 'code'=>'', 'phone'=>'', 'email'=>'', 'address'=>'', 'website'=>'', 'logo'=>''];
if ($schoolsTable && $schoolId) {
    $st=$pdo->prepare("SELECT * FROM `{$schoolsTable}` WHERE id=:id OR ".(in_array('school_id',$schoolCols,true) ? "school_id=:id" : "id=:id")." LIMIT 1");
    $st->execute([':id'=>$schoolId]);
    $row=$st->fetch(); if ($row) {
        $profile['school_name'] = $colName && isset($row[$colName]) ? (string)$row[$colName] : $profile['school_name'];
        $profile['code']    = $colCode && isset($row[$colCode]) ? (string)$row[$colCode] : '';
        $profile['phone']   = $colPhone && isset($row[$colPhone]) ? (string)$row[$colPhone] : '';
        $profile['email']   = $colEmail && isset($row[$colEmail]) ? (string)$row[$colEmail] : '';
        $profile['address'] = $colAddr && isset($row[$colAddr]) ? (string)$row[$colAddr] : '';
        $profile['website'] = $colWeb  && isset($row[$colWeb])  ? (string)$row[$colWeb]  : '';
        $profile['logo']    = $colLogo && isset($row[$colLogo]) ? (string)$row[$colLogo] : '';
    }
}

/* ------------ Academic defaults from settings ------------ */
$curYear     = (int)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'current_year', (int)date('Y'));
$curTerm     = (int)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'current_term', 1);
$marksDef    = (float)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'default_marks_possible', 100);
$roundMode   = (string)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'rounding_mode', 'nearest'); // nearest|floor|ceil
$passMark    = (float)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'pass_mark', 50);
$csvEnabled  = (string)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'enable_csv_import', '1');
$require2FA  = (string)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'require_2fa', '0');
$pwdMinLen   = (int)setting_get($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'password_min_length', 8);

/* ------------ Handle POST actions ------------ */
if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_ok($_POST['__csrf'] ?? null)) {
    $action = $_POST['__action'] ?? '';

    // --- Save School Profile ---
    if ($action === 'save_profile') {
        try {
            $updates = [];
            $bind    = [];

            // Handle logo upload (public/uploads/logos)
            $logoRel = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext  = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $safe = preg_replace('/[^a-z0-9_\-\.]/i','_', basename($_FILES['logo']['name'], ".{$ext}"));
                if ($ext === 'svg' || $ext === 'png' || $ext === 'jpg' || $ext === 'jpeg' || $ext === 'webp') {
                    $dir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    $dirLogos = $dir . '/logos';
                    if (!is_dir($dirLogos)) @mkdir($dirLogos, 0775, true);
                    $filename = $safe . '-' . time() . '.' . $ext;
                    $dest = $dirLogos . '/' . $filename;
                    if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        $logoRel = 'uploads/logos/' . $filename; // relative to /public
                    }
                }
            }

            if ($schoolsTable && $schoolId) {
                if ($colName) { $updates[]="`{$colName}`=:nm"; $bind[':nm']=$_POST['school_name'] ?? ''; }
                if ($colCode) { $updates[]="`{$colCode}`=:cd"; $bind[':cd']=$_POST['code'] ?? ''; }
                if ($colPhone){ $updates[]="`{$colPhone}`=:ph"; $bind[':ph']=$_POST['phone'] ?? ''; }
                if ($colEmail){ $updates[]="`{$colEmail}`=:em"; $bind[':em']=$_POST['email'] ?? ''; }
                if ($colAddr) { $updates[]="`{$colAddr}`=:ad"; $bind[':ad']=$_POST['address'] ?? ''; }
                if ($colWeb)  { $updates[]="`{$colWeb}`=:wb";  $bind[':wb']=$_POST['website'] ?? ''; }
                if ($colLogo && $logoRel){ $updates[]="`{$colLogo}`=:lg"; $bind[':lg']=$logoRel; }

                if ($updates) {
                    // Identify row: prefer id = :id, else school_id = :id
                    $idCol = in_array('id',$schoolCols,true) ? 'id' : (in_array('school_id',$schoolCols,true) ? 'school_id' : null);
                    if ($idCol) {
                        $sql="UPDATE `{$schoolsTable}` SET ".implode(',', $updates)." WHERE `{$idCol}`=:id LIMIT 1";
                        $bind[':id'] = $schoolId;
                        $pdo->prepare($sql)->execute($bind);
                    }
                }
                // Also mirror to KV settings for consistency if table exists
                if ($kvTable) {
                    setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'school_name',$_POST['school_name'] ?? '');
                    if ($logoRel) setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'school_logo',$logoRel);
                }
                $flash = ['type'=>'success','msg'=>'School profile saved.'];
            } elseif ($kvTable) {
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'school_name',$_POST['school_name'] ?? '');
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'school_code',$_POST['code'] ?? '');
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'phone',$_POST['phone'] ?? '');
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'email',$_POST['email'] ?? '');
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'address',$_POST['address'] ?? '');
                setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'website',$_POST['website'] ?? '');
                if ($logoRel) setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'school_logo',$logoRel);
                $flash = ['type'=>'success','msg'=>'School profile saved (settings store).'];
            } else {
                $flash = ['type'=>'warning','msg'=>'No suitable table found to save school profile.'];
            }
        } catch (Throwable $e) {
            $flash = ['type'=>'danger','msg'=>'Failed to save profile: '.htmlspecialchars($e->getMessage())];
        }
    }

    // --- Save Academic Settings ---
    if ($action === 'save_academic' && $kvTable) {
        try {
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'current_year', (int)($_POST['current_year'] ?? date('Y')));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'current_term', (int)($_POST['current_term'] ?? 1));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'default_marks_possible', (float)($_POST['default_marks_possible'] ?? 100));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'rounding_mode', (string)($_POST['rounding_mode'] ?? 'nearest'));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'pass_mark', (float)($_POST['pass_mark'] ?? 50));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'enable_csv_import', isset($_POST['enable_csv_import']) ? '1' : '0');
            $flash = ['type'=>'success','msg'=>'Academic settings saved.'];
            // refresh local vars
            $curYear=(int)$_POST['current_year']; $curTerm=(int)$_POST['current_term']; $marksDef=(float)$_POST['default_marks_possible'];
            $roundMode=(string)$_POST['rounding_mode']; $passMark=(float)$_POST['pass_mark']; $csvEnabled= isset($_POST['enable_csv_import'])?'1':'0';
        } catch (Throwable $e) {
            $flash = ['type'=>'danger','msg'=>'Failed to save academic settings: '.htmlspecialchars($e->getMessage())];
        }
    }

    // --- Save Default Grade Scale ---
    if ($action === 'save_grading') {
        try {
            $sel = (int)($_POST['default_grade_scale_id'] ?? 0);
            if ($scaleTable && $scaleDefCol) {
                // If scales are shared by school, scope reset to this school only
                if ($scaleHasSid && $schoolId) {
                    $pdo->prepare("UPDATE `{$scaleTable}` SET `{$scaleDefCol}`=0 WHERE school_id=:sid")->execute([':sid'=>$schoolId]);
                    if ($sel>0) $pdo->prepare("UPDATE `{$scaleTable}` SET `{$scaleDefCol}`=1 WHERE id=:id AND school_id=:sid LIMIT 1")->execute([':id'=>$sel,':sid'=>$schoolId]);
                } else {
                    $pdo->query("UPDATE `{$scaleTable}` SET `{$scaleDefCol}`=0");
                    if ($sel>0) $pdo->prepare("UPDATE `{$scaleTable}` SET `{$scaleDefCol}`=1 WHERE id=:id LIMIT 1")->execute([':id'=>$sel]);
                }
            }
            if ($kvTable) setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'default_grade_scale_id',$sel);
            $defaultScaleId=$sel;
            $flash = ['type'=>'success','msg'=>'Default grading scale saved.'];
        } catch (Throwable $e) {
            $flash = ['type'=>'danger','msg'=>'Failed to save grade scale: '.htmlspecialchars($e->getMessage())];
        }
    }

    // --- Manage Assessment Types (add/edit/delete) ---
    if ($assessTable) {
        try {
            if ($action === 'add_assess') {
                $name = trim((string)($_POST['assess_name'] ?? ''));
                $code = trim((string)($_POST['assess_code'] ?? ''));
                $w    = (float)($_POST['weight'] ?? 0);
                if ($name !== '') {
                    $colsIns = ["`{$assessName}`"];
                    $valsIns = [":nm"];
                    $bindIns = [':nm'=>$name];
                    if ($assessCode)  { $colsIns[]="`{$assessCode}`";  $valsIns[]=':cd';  $bindIns[':cd']=$code; }
                    if ($assessWeight){ $colsIns[]="`{$assessWeight}`";$valsIns[]=':wt';  $bindIns[':wt']=$w; }
                    if ($assessHasSid && $schoolId){ $colsIns[]='`school_id`'; $valsIns[]=':sid'; $bindIns[':sid']=$schoolId; }
                    $pdo->prepare("INSERT INTO `{$assessTable}` (".implode(',',$colsIns).") VALUES (".implode(',',$valsIns).")")->execute($bindIns);
                    $flash = ['type'=>'success','msg'=>'Assessment type added.'];
                }
            }
            if ($action === 'edit_assess') {
                $id   = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['assess_name'] ?? ''));
                $code = trim((string)($_POST['assess_code'] ?? ''));
                $w    = (float)($_POST['weight'] ?? 0);
                if ($id>0) {
                    $set=[]; $bind=[':id'=>$id];
                    if ($assessName)   { $set[]="`{$assessName}`=:nm"; $bind[':nm']=$name; }
                    if ($assessCode)   { $set[]="`{$assessCode}`=:cd"; $bind[':cd']=$code; }
                    if ($assessWeight) { $set[]="`{$assessWeight}`=:wt"; $bind[':wt']=$w; }
                    if ($set) {
                        $sql="UPDATE `{$assessTable}` SET ".implode(',',$set)." WHERE id=:id".($assessHasSid && $schoolId ? " AND school_id=:sid" : "")." LIMIT 1";
                        if ($assessHasSid && $schoolId) $bind[':sid']=$schoolId;
                        $pdo->prepare($sql)->execute($bind);
                        $flash=['type'=>'success','msg'=>'Assessment type updated.'];
                    }
                }
            }
            if ($action === 'delete_assess') {
                $id=(int)($_POST['id'] ?? 0);
                if ($id>0) {
                    $sql="DELETE FROM `{$assessTable}` WHERE id=:id".($assessHasSid && $schoolId ? " AND school_id=:sid" : "")." LIMIT 1";
                    $bind=[':id'=>$id]; if ($assessHasSid && $schoolId) $bind[':sid']=$schoolId;
                    $pdo->prepare($sql)->execute($bind);
                    $flash=['type'=>'success','msg'=>'Assessment type deleted.'];
                }
            }
            // reload list
            $assessments = [];
            $sql="SELECT id, `{$assessName}` AS name".($assessCode?", `{$assessCode}` AS code":"").($assessWeight?", `{$assessWeight}` AS weight":"")." FROM `{$assessTable}`";
            $bind=[]; if ($assessHasSid && $schoolId) { $sql.=" WHERE school_id=:sid"; $bind[':sid']=$schoolId; }
            $sql.=" ORDER BY id"; $st=$pdo->prepare($sql); $st->execute($bind); $assessments=$st->fetchAll();
        } catch (Throwable $e) {
            $flash = ['type'=>'danger','msg'=>'Assessment change failed: '.htmlspecialchars($e->getMessage())];
        }
    }

    // --- Security (2FA, pwd length) ---
    if ($action === 'save_security' && $kvTable) {
        try {
            $req2 = isset($_POST['require_2fa']) ? '1' : '0';
            $pmin = max(6, (int)($_POST['password_min_length'] ?? 8));
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'require_2fa',$req2);
            setting_set($pdo,$kvTable,$kvKey,$kvVal,$kvSid,$schoolId,'password_min_length',$pmin);
            $require2FA = $req2; $pwdMinLen = $pmin;
            $flash=['type'=>'success','msg'=>'Security settings saved.'];
        } catch (Throwable $e) {
            $flash=['type'=>'danger','msg'=>'Failed to save security settings: '.htmlspecialchars($e->getMessage())];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD']==='POST') {
    $flash = ['type'=>'danger','msg'=>'Invalid form token. Please refresh and try again.'];
}

/* ------------ Styles ------------ */
?>
<style>
.settings-page .panel{ background:#fff; border:1px solid #203047; border-radius:14px; padding:14px; margin:16px; color:#13233f; }
.settings-page .panel h2{ margin:0 0 10px; font-size:16px; letter-spacing:.02em; color:#13233f; }
.settings-page .grid{ display:grid; gap:12px; }
@media (min-width:768px){ .settings-page .grid-2{ grid-template-columns:repeat(2,minmax(0,1fr)); } .settings-page .grid-3{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
.settings-page .input, .settings-page .select, .settings-page textarea{
  width:100%; background:#fff; border:1px solid #d4dbe7; color:#13233f; border-radius:10px; padding:10px 12px;
}
.settings-page .btn{ display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; border:1px solid #223146; background:#0f1626; color:#eaf2ff; text-decoration:none; font-weight:700; cursor:pointer; }
.settings-page .btn-primary{ background:linear-gradient(90deg,#00C4CC,#00CC66); color:#062117; border:none; }
.settings-page .btn-danger{ background:#6e2631; border-color:#7f2a33; color:#fff; }
.settings-page .note{ color:#51607a; font-size:12px; }
.settings-page .table-wrap{ overflow:auto; }
.settings-page table{ width:100%; border-collapse:collapse; min-width:640px; }
.settings-page th, .settings-page td{ border-bottom:1px solid #e6ebf4; padding:8px 10px; text-align:left; }
.settings-page th{ font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#51607a; }
.settings-page .flash{ margin:16px; padding:10px 12px; border-radius:12px; }
.settings-page .ok{ background:#e7fbf4; border:1px solid #1e7f5d; color:#0b5a3f; }
.settings-page .bad{ background:#fdecef; border:1px solid #7f2a33; color:#7f2a33; }
.settings-page .warn{ background:#fff7e6; border:1px solid #80630f; color:#6a5412; }
</style>

<section class="settings-page">

  <?php if ($flash['type']): ?>
    <div class="flash <?php echo $flash['type']==='success'?'ok':($flash['type']==='danger'?'bad':'warn'); ?>">
      <?php echo $flash['msg']; ?>
    </div>
  <?php endif; ?>

  <!-- School profile -->
  <div class="panel">
    <h2>School Profile &amp; Branding</h2>
    <?php if (!$schoolsTable && !$kvTable): ?>
      <p class="note">No suitable table found (schools/school or settings). Form will not save.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="grid grid-2">
      <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
      <input type="hidden" name="__action" value="save_profile">
      <div>
        <label class="note">School name</label>
        <input class="input" type="text" name="school_name" value="<?php echo htmlspecialchars($profile['school_name']); ?>" required>
      </div>
      <div>
        <label class="note">School code</label>
        <input class="input" type="text" name="code" value="<?php echo htmlspecialchars($profile['code']); ?>">
      </div>
      <div>
        <label class="note">Phone</label>
        <input class="input" type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>">
      </div>
      <div>
        <label class="note">Email</label>
        <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>">
      </div>
      <div>
        <label class="note">Address</label>
        <input class="input" type="text" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>">
      </div>
      <div>
        <label class="note">Website</label>
        <input class="input" type="url" name="website" placeholder="https://example.com" value="<?php echo htmlspecialchars($profile['website']); ?>">
      </div>
      <div>
        <label class="note">Logo (SVG/PNG/JPG/WEBP)</label>
        <input class="input" type="file" name="logo" accept=".svg,.png,.jpg,.jpeg,.webp">
      </div>
      <div>
        <label class="note">Current logo</label>
        <div class="note">
          <?php if (!empty($profile['logo'])): ?>
            <img src="<?php echo base_url(trim($profile['logo'],'/')); ?>" alt="Logo" style="max-height:56px; background:#f6f8fc; padding:6px; border:1px solid #e6ebf4; border-radius:8px;">
          <?php else: ?>
            <span>— none —</span>
          <?php endif; ?>
        </div>
      </div>
      <div style="grid-column:1/-1">
        <button class="btn btn-primary" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13l4 4L19 7l-1.5-1.5L9 14 6.5 11.5z"/></svg>
          Save Profile
        </button>
      </div>
    </form>
  </div>

  <!-- Academic settings -->
  <div class="panel">
    <h2>Academic Settings</h2>
    <?php if (!$kvTable): ?><p class="note">A settings table was not found; academic settings may not persist.</p><?php endif; ?>
    <form method="post" class="grid grid-3">
      <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
      <input type="hidden" name="__action" value="save_academic">
      <div>
        <label class="note">Current year</label>
        <input class="input" type="number" name="current_year" value="<?php echo (int)$curYear; ?>" min="2000" max="2100">
      </div>
      <div>
        <label class="note">Current term</label>
        <select class="select" name="current_term">
          <?php foreach ([1=>'Term 1',2=>'Term 2',3=>'Term 3'] as $tid=>$lbl): ?>
            <option value="<?php echo $tid; ?>" <?php echo (int)$curTerm===$tid?'selected':''; ?>><?php echo $lbl; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="note">Default “marks possible”</label>
        <input class="input" type="number" step="0.01" name="default_marks_possible" value="<?php echo htmlspecialchars((string)$marksDef); ?>">
      </div>
      <div>
        <label class="note">Rounding mode</label>
        <select class="select" name="rounding_mode">
          <option value="nearest" <?php echo $roundMode==='nearest'?'selected':''; ?>>Nearest</option>
          <option value="floor"   <?php echo $roundMode==='floor'  ?'selected':''; ?>>Floor</option>
          <option value="ceil"    <?php echo $roundMode==='ceil'   ?'selected':''; ?>>Ceil</option>
        </select>
      </div>
      <div>
        <label class="note">Pass mark (%)</label>
        <input class="input" type="number" step="0.01" name="pass_mark" value="<?php echo htmlspecialchars((string)$passMark); ?>">
      </div>
      <div style="display:flex; align-items:center; gap:10px;">
        <input id="csv_ok" type="checkbox" name="enable_csv_import" value="1" <?php echo $csvEnabled==='1'?'checked':''; ?>>
        <label class="note" for="csv_ok">Enable CSV import in results</label>
      </div>
      <div style="grid-column:1/-1">
        <button class="btn btn-primary" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13l4 4L19 7l-1.5-1.5L9 14 6.5 11.5z"/></svg>
          Save Academic Settings
        </button>
      </div>
    </form>
  </div>

  <!-- Grading & Exams -->
  <div class="panel">
    <h2>Grading &amp; Exams</h2>

    <form method="post" class="grid">
      <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
      <input type="hidden" name="__action" value="save_grading">
      <div class="grid grid-3">
        <div style="grid-column:1/3">
          <label class="note">Default grade scale</label>
          <select class="select" name="default_grade_scale_id">
            <option value="0">— None —</option>
            <?php foreach ($scales as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>" <?php echo (int)$defaultScaleId===(int)$s['id']?'selected':''; ?>>
                #<?php echo (int)$s['id']; ?> — <?php echo htmlspecialchars((string)$s['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="note" style="margin-top:6px;">This scale will be used to compute grades/points when entering results.</div>
        </div>
        <div style="align-self:end">
          <button class="btn btn-primary" type="submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13l4 4L19 7l-1.5-1.5L9 14 6.5 11.5z"/></svg>
            Save
          </button>
        </div>
      </div>
    </form>

    <?php if ($assessTable): ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <?php if ($assessCode): ?><th>Code</th><?php endif; ?>
              <?php if ($assessWeight): ?><th>Weight</th><?php endif; ?>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$assessments): ?>
              <tr><td colspan="5" class="note">No assessment types defined.</td></tr>
            <?php else: foreach ($assessments as $a): ?>
              <tr>
                <td><?php echo (int)$a['id']; ?></td>
                <td>
                  <form method="post" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
                    <input type="hidden" name="__action" value="edit_assess">
                    <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                    <input class="input" type="text" name="assess_name" value="<?php echo htmlspecialchars((string)$a['name']); ?>" style="max-width:220px;">
                    <?php if ($assessCode): ?>
                      <input class="input" type="text" name="assess_code" value="<?php echo htmlspecialchars((string)($a['code'] ?? '')); ?>" style="max-width:140px;">
                    <?php endif; ?>
                    <?php if ($assessWeight): ?>
                      <input class="input" type="number" step="0.01" name="weight" value="<?php echo htmlspecialchars((string)($a['weight'] ?? '0')); ?>" style="max-width:120px;">
                    <?php endif; ?>
                    <button class="btn" type="submit" title="Update">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13l4 4L19 7l-1.5-1.5L9 14l-2.5-2.5z"/></svg>
                      Save
                    </button>
                  </form>
                </td>
                <?php if ($assessCode): ?><td><?php echo htmlspecialchars((string)($a['code'] ?? '')); ?></td><?php endif; ?>
                <?php if ($assessWeight): ?><td><?php echo htmlspecialchars((string)($a['weight'] ?? '')); ?></td><?php endif; ?>
                <td>
                  <form method="post" onsubmit="return confirm('Delete this assessment type?');" style="display:inline;">
                    <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
                    <input type="hidden" name="__action" value="delete_assess">
                    <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                    <button class="btn btn-danger" type="submit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <form method="post" class="grid grid-3" style="margin-top:12px;">
        <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
        <input type="hidden" name="__action" value="add_assess">
        <div>
          <label class="note">New assessment name</label>
          <input class="input" type="text" name="assess_name" placeholder="e.g., Mid-Term" required>
        </div>
        <?php if ($assessCode): ?>
          <div>
            <label class="note">Code</label>
            <input class="input" type="text" name="assess_code" placeholder="e.g., MT">
          </div>
        <?php endif; ?>
        <?php if ($assessWeight): ?>
          <div>
            <label class="note">Weight</label>
            <input class="input" type="number" step="0.01" name="weight" value="0">
          </div>
        <?php endif; ?>
        <div style="grid-column:1/-1">
          <button class="btn btn-primary" type="submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
            Add Assessment Type
          </button>
        </div>
      </form>
    <?php else: ?>
      <p class="note">No <code>assessment_types</code> table found. This section is hidden.</p>
    <?php endif; ?>
  </div>

  <!-- Security -->
  <div class="panel">
    <h2>Security</h2>
    <?php if (!$kvTable): ?><p class="note">A settings table was not found; security settings may not persist.</p><?php endif; ?>
    <form method="post" class="grid grid-3">
      <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
      <input type="hidden" name="__action" value="save_security">
      <div style="display:flex; align-items:center; gap:10px;">
        <input id="req2fa" type="checkbox" name="require_2fa" value="1" <?php echo $require2FA==='1'?'checked':''; ?>>
        <label for="req2fa" class="note">Require 2FA for staff/admin sign-in</label>
      </div>
      <div>
        <label class="note">Password minimum length</label>
        <input class="input" type="number" name="password_min_length" min="6" value="<?php echo (int)$pwdMinLen; ?>">
      </div>
      <div style="grid-column:1/-1">
        <button class="btn btn-primary" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13l4 4L19 7l-1.5-1.5L9 14l-2.5-2.5z"/></svg>
          Save Security Settings
        </button>
      </div>
    </form>
  </div>

</section>

<?php
include __DIR__ . '/../../views/layouts/footer.php';
