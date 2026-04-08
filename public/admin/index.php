<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require admin authentication
require_role('SCHOOL_ADMIN', 'STAFF');

// Page variables
$pageTitle = 'Admin Dashboard - Uganda Results System';
$pageDescription = 'School administration dashboard';
$bodyClass = 'admin-page';

// Get current user and school info
$currentUser   = $_SESSION['user_name']  ?? 'Administrator';
$currentSchool = $_SESSION['school_name'] ?? 'School';
$userRole      = get_user_role();

/* ---------------- Minimal DB helpers (safe, no external changes) ---------------- */
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
function table_exists(PDO $pdo, string $t): bool {
  $q=$pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t LIMIT 1");
  $q->execute([':t'=>$t]); return (bool)$q->fetchColumn();
}
function col_exists(PDO $pdo, string $t, string $c): bool {
  $q=$pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:t AND column_name=:c LIMIT 1");
  $q->execute([':t'=>$t, ':c'=>$c]); return (bool)$q->fetchColumn();
}
function columns(PDO $pdo, string $t): array {
  $cols = [];
  $q = $pdo->prepare("SHOW COLUMNS FROM `{$t}`");
  try { $q->execute(); foreach ($q as $r) $cols[] = $r['Field']; } catch(Throwable $e) {}
  return $cols;
}
function current_school_id(PDO $pdo): ?int {
  if (!empty($_SESSION['school_id'])) return (int)$_SESSION['school_id'];
  if (!empty($_SESSION['user_id']) && table_exists($pdo,'users') && col_exists($pdo,'users','school_id')) {
    $q=$pdo->prepare("SELECT school_id FROM users WHERE id=:id LIMIT 1");
    $q->execute([':id'=>(int)$_SESSION['user_id']]);
    $sid=$q->fetchColumn();
    if ($sid!==false && $sid!==null) { $_SESSION['school_id']=(int)$sid; return (int)$sid; }
  }
  return null;
}

/* ---------------- Pull counts scoped to school ---------------- */
$pdo = dbh();
$schoolId = current_school_id($pdo);

$stat_students = 0;
$stat_classes  = 0;
$stat_subjects = 0;
$stat_reports  = 0;

if ($schoolId !== null) {
  if (table_exists($pdo,'students') && col_exists($pdo,'students','school_id')) {
    $q=$pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id=:sid");
    $q->execute([':sid'=>$schoolId]); $stat_students = (int)$q->fetchColumn();
  }
  if (table_exists($pdo,'classes') && col_exists($pdo,'classes','school_id')) {
    $q=$pdo->prepare("SELECT COUNT(*) FROM classes WHERE school_id=:sid");
    $q->execute([':sid'=>$schoolId]); $stat_classes = (int)$q->fetchColumn();
  }
  if (table_exists($pdo,'subjects') && col_exists($pdo,'subjects','school_id')) {
    $q=$pdo->prepare("SELECT COUNT(*) FROM subjects WHERE school_id=:sid");
    $q->execute([':sid'=>$schoolId]); $stat_subjects = (int)$q->fetchColumn();
  }
  // Reports generated: prefer report_cards.school_id, else join via students
  if (table_exists($pdo,'report_cards')) {
    if (col_exists($pdo,'report_cards','school_id')) {
      $q=$pdo->prepare("SELECT COUNT(*) FROM report_cards WHERE school_id=:sid");
      $q->execute([':sid'=>$schoolId]); $stat_reports=(int)$q->fetchColumn();
    } elseif (col_exists($pdo,'report_cards','student_id') && table_exists($pdo,'students')) {
      $q=$pdo->prepare("SELECT COUNT(*) 
                        FROM report_cards rc 
                        JOIN students s ON s.id=rc.student_id
                        WHERE s.school_id=:sid");
      $q->execute([':sid'=>$schoolId]); $stat_reports=(int)$q->fetchColumn();
    }
  }
}

/* ---------------- Classes list (search + pagination) ---------------- */
/* ---------------- Classes list (search + pagination) ---------------- */
$classesList = [];
$classesTotal = 0;
$clsCols = table_exists($pdo,'classes') ? columns($pdo,'classes') : [];

$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 10;
$off  = ($page - 1) * $per;

if ($clsCols) {
  // Build SELECT pieces based on available columns
  $hasId      = in_array('id', $clsCols, true);
  $hasName    = in_array('name', $clsCols, true);
  $hasCName   = in_array('class_name', $clsCols, true);
  $hasCode    = in_array('code', $clsCols, true);
  $hasLevel   = in_array('level', $clsCols, true);
  $hasStream  = in_array('stream', $clsCols, true);
  $hasSection = in_array('section', $clsCols, true);
  $hasTeacher = in_array('class_teacher', $clsCols, true);
  $hasSchool  = in_array('school_id', $clsCols, true);

  $nameExpr = $hasCName ? 'c.class_name'
            : ($hasName ? 'c.name'
                        : "CONCAT('Class ', c.id)");

  $fields = [];
  $fields[] = $hasId ? 'c.id' : 'NULL AS id';
  $fields[] = $nameExpr . ' AS class_name';
  if ($hasCode)    $fields[] = 'c.code';
  if ($hasLevel)   $fields[] = 'c.level';
  if ($hasStream)  $fields[] = 'c.stream';
  if ($hasSection) $fields[] = 'c.section';
  if ($hasTeacher) $fields[] = 'c.class_teacher';

  // Student count subquery (only if students.class_id exists)
  $needsSidStu = false;
  if (table_exists($pdo,'students')) {
    $stuCols = columns($pdo,'students');
    if (in_array('class_id', $stuCols, true)) {
      $whereStu = 's.class_id = c.id';
      if (in_array('school_id', $stuCols, true) && $schoolId !== null) {
        $whereStu   .= ' AND s.school_id = :sid_stu';
        $needsSidStu = true;
      }
      $fields[] = "(SELECT COUNT(*) FROM students s WHERE {$whereStu}) AS students_count";
    } else {
      $fields[] = 'NULL AS students_count';
    }
  } else {
    $fields[] = 'NULL AS students_count';
  }

  // WHERE
  $where = [];
  $bind  = [];

  if ($hasSchool && $schoolId !== null) {
    $where[]      = 'c.school_id = :sid';
    $bind[':sid'] = $schoolId;
  }

  if ($q !== '') {
    $like = [];
    foreach (['class_name','name','code','level','stream','section','class_teacher'] as $cand) {
      if (in_array($cand, $clsCols, true)) $like[] = "c.`{$cand}` LIKE :q";
    }
    if ($like) {
      $where[]    = '(' . implode(' OR ', $like) . ')';
      $bind[':q'] = '%' . $q . '%';
    }
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // Count
  $cntSql = "SELECT COUNT(*) FROM classes c {$whereSql}";
  $c = $pdo->prepare($cntSql);
  $c->execute($bind);
  $classesTotal = (int)$c->fetchColumn();

  // Select page
  $selSql = "SELECT " . implode(', ', $fields) . "
             FROM classes c
             {$whereSql}
             ORDER BY " . ($hasId ? "c.id DESC" : "1") . "
             LIMIT :lim OFFSET :off";
  $sel = $pdo->prepare($selSql);
  foreach ($bind as $k=>$v) {
    $sel->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
  }
  $sel->bindValue(':lim', $per, PDO::PARAM_INT);
  $sel->bindValue(':off', $off, PDO::PARAM_INT);
  if ($needsSidStu) {
    $sel->bindValue(':sid_stu', (int)$schoolId, PDO::PARAM_INT);
  }
  $sel->execute();
  $classesList = $sel->fetchAll();
}




// Include header
include __DIR__ . '/../../views/layouts/header.php';

?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
        </svg>
        Dashboard
    </h1>
    <p class="page-subtitle">Welcome to the <?php echo htmlspecialchars($currentSchool); ?> administration panel.</p>
</div>

<!-- User Profile Card -->
<div class="user-profile-card">
    <div class="profile-card-content">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($currentUser, 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h3><?php echo htmlspecialchars($currentUser); ?></h3>
            <div class="profile-details">
                <span><strong>Role:</strong> <?php echo ucwords(str_replace('_', ' ', strtolower($userRole))); ?></span>
                <span><strong>School:</strong> <?php echo htmlspecialchars($currentSchool); ?></span>
                <span><strong>Login:</strong> <?php echo date('M d, Y \a\t g:i A'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container py-8">
  <!-- Welcome Section -->
  <div class="mb-8">
    <h1 class="text-3xl font-bold mb-2">Welcome to Admin Dashboard</h1>
    <p class="text-secondary">Manage your school's academic records and student results</p>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Management Actions -->
      <div class="card">
        <div class="card-header">
          <h3 class="font-semibold">School Management</h3>
        </div>
        <div class="card-body">
          <div class="grid gap-3">
            <a href="<?php echo base_url('schools.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              School Profile & Settings
            </a>
            <a href="<?php echo base_url($userRole==='SUPER_ADMIN' ? 'super/users.php' : 'admin/students.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.997 2.997 0 0 0 17.11 7H16.5l-.09-.4c-.32-1.49-1.65-2.6-3.24-2.6s-2.92 1.11-3.24 2.6L9.84 7H9.1c-1.35 0-2.53.88-2.92 2.16L3.5 16H6v6h4v-6h4v6h6z"/></svg>
              Manage Users & Staff
            </a>
            <a href="<?php echo base_url('admin/students.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
              Student Management
            </a>
            <a href="<?php echo base_url('admin/classes.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
              Classes & Subjects
            </a>
          </div>
        </div>
      </div>

      <!-- Academic Actions -->
      <div class="card">
        <div class="card-header">
          <h3 class="font-semibold">Academic Management</h3>
        </div>
        <div class="card-body">
          <div class="grid gap-3">
            <a href="<?php echo base_url('marks_grid.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
              Marks Entry
            </a>
            <a href="<?php echo base_url('admin/reports.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
              Generate Reports
            </a>
            <a href="<?php echo base_url($userRole==='SUPER_ADMIN' ? 'super/analytics.php' : 'admin/results.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.41-1.41L15 12.42V7H9v5.42l1.91 1.91 1.41-1.41L11 11.59V8h2v3.59l1.32 1.32z"/></svg>
              Analytics & Reports
            </a>
            <a href="<?php echo base_url('admin/classes.php'); ?>" class="btn btn-outline text-left justify-start">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c1.5 0 2.91-.33 4.18-.93L17 20.25V11.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v7.39c1.82-2.04 3-4.74 3-7.89 0-5.52-4.48-10-10-10z"/></svg>
              Grading Scales
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card"><div class="card-body text-center">
      <div class="text-3xl font-bold text-primary-600 mb-2"><?php echo number_format($stat_students); ?></div>
      <div class="text-sm text-secondary">Total Students</div>
    </div></div>
    <div class="card"><div class="card-body text-center">
      <div class="text-3xl font-bold text-success-600 mb-2"><?php echo number_format($stat_classes); ?></div>
      <div class="text-sm text-secondary">Active Classes</div>
    </div></div>
    <div class="card"><div class="card-body text-center">
      <div class="text-3xl font-bold text-warning-600 mb-2"><?php echo number_format($stat_subjects); ?></div>
      <div class="text-sm text-secondary">Total Subjects</div>
    </div></div>
    <div class="card"><div class="card-body text-center">
      <div class="text-3xl font-bold text-info-600 mb-2"><?php echo number_format($stat_reports); ?></div>
      <div class="text-sm text-secondary">Reports Generated</div>
    </div></div>
  </div>

  <!-- Available Classes (searchable + paginated) -->
  <div class="card mt-10">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <h3 class="font-semibold">Available Classes</h3>
      <div style="display:flex;gap:8px;align-items:center">
        <form method="get" action="" style="display:flex;gap:8px">
          <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search class name, code, level…" class="input" style="min-width:240px">
          <button class="btn" type="submit">Search</button>
          <?php if ($q !== ''): ?>
            <a class="btn btn-outline" href="?">Reset</a>
          <?php endif; ?>
        </form>
        <a class="btn btn-primary" href="<?php echo base_url('admin/classes.php'); ?>">Manage Classes</a>
      </div>
    </div>
    <div class="card-body">
      <?php if (!$clsCols): ?>
        <div class="alert alert-warning">The <strong>classes</strong> table was not found in your database.</div>
      <?php else: ?>
        <?php if ($classesTotal === 0): ?>
          <div class="alert alert-info">No classes found<?php echo $q ? ' for "<strong>'.htmlspecialchars($q).'</strong>"' : ''; ?>.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="table" style="width:100%;border-collapse:collapse">
              <thead>
                <tr>
                  <?php if (in_array('id',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">ID</th><?php endif; ?>
                  <th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Class</th>
                  <?php if (in_array('code',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Code</th><?php endif; ?>
                  <?php if (in_array('level',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Level</th><?php endif; ?>
                  <?php if (in_array('stream',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Stream</th><?php endif; ?>
                  <?php if (in_array('section',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Section</th><?php endif; ?>
                  <?php if (in_array('class_teacher',$clsCols,true)): ?><th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Class Teacher</th><?php endif; ?>
                  <th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Students</th>
                  <th style="text-align:left;padding:10px;border-bottom:1px solid #223146;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($classesList as $row): ?>
                  <tr style="border-bottom:1px solid #223146">
                    <?php if (in_array('id',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo (int)($row['id'] ?? 0); ?></td>
                    <?php endif; ?>
                    <td style="padding:10px">
                      <?php echo htmlspecialchars((string)($row['class_name'] ?? '—')); ?>
                      <?php if (in_array('stream',$clsCols,true) && !empty($row['stream'])): ?>
                        <span style="opacity:.7"> — <?php echo htmlspecialchars((string)$row['stream']); ?></span>
                      <?php endif; ?>
                    </td>
                    <?php if (in_array('code',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo htmlspecialchars((string)($row['code'] ?? '')); ?></td>
                    <?php endif; ?>
                    <?php if (in_array('level',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo htmlspecialchars((string)($row['level'] ?? '')); ?></td>
                    <?php endif; ?>
                    <?php if (in_array('stream',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo htmlspecialchars((string)($row['stream'] ?? '')); ?></td>
                    <?php endif; ?>
                    <?php if (in_array('section',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo htmlspecialchars((string)($row['section'] ?? '')); ?></td>
                    <?php endif; ?>
                    <?php if (in_array('class_teacher',$clsCols,true)): ?>
                      <td style="padding:10px"><?php echo htmlspecialchars((string)($row['class_teacher'] ?? '')); ?></td>
                    <?php endif; ?>
                    <td style="padding:10px"><?php echo isset($row['students_count']) ? (int)$row['students_count'] : '—'; ?></td>
                    <td style="padding:10px">
                      <a class="btn btn-outline" href="<?php echo base_url('admin/classes.php'); ?>">Open</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php
            $pages = (int)ceil(max(1,$classesTotal) / $per);
            if ($pages > 1):
          ?>
            <div class="pagination" style="display:flex;gap:6px;justify-content:flex-end;margin-top:12px">
              <?php for ($i=1; $i<=$pages; $i++):
                $params = ['q'=>$q,'page'=>$i];
                $url = '?'.http_build_query(array_filter($params, fn($v)=>$v!=='' && $v!==null));
              ?>
                <a class="btn<?php echo $i===$page?' btn-primary':''; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// Page-specific JavaScript
$pageJs = "console.log('Admin dashboard loaded');";

// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
