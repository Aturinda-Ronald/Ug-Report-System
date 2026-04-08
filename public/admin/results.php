<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

/* ------------------------ Auth ------------------------ */
if (!function_exists('is_logged_in') || !is_logged_in()) {
    redirect(base_url('public/'));
    exit;
}
$role = function_exists('get_user_role') ? get_user_role() : null;
$allowedRoles = ['SCHOOL_ADMIN','STAFF','SUPER_ADMIN'];
if (!$role || !in_array($role, $allowedRoles, true)) {
    redirect(base_url());
    exit;
}
$isSuper       = ($role === 'SUPER_ADMIN');
$restrictStaff = (strtoupper((string)$role) === 'STAFF');

/* ------------------------ Page meta ------------------------ */
$pageTitle       = 'Results — Admin';
$pageDescription = 'Enter, import, edit and delete assessment results.';
$bodyClass       = 'dashboard results-page';

/* ------------------------ DB helpers ------------------------ */
function __pdo(): PDO {
    if (function_exists('db'))    { $x = db();     if ($x instanceof PDO) return $x; }
    if (function_exists('get_db')){ $x = get_db(); if ($x instanceof PDO) return $x; }
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
    if (isset($GLOBALS['db'])  && $GLOBALS['db']  instanceof PDO) return $GLOBALS['db'];
    $dsn  = defined('DB_DSN')  ? DB_DSN  : ('mysql:host='.(defined('DB_HOST')?DB_HOST:'127.0.0.1').';dbname='.(defined('DB_NAME')?DB_NAME:'').';charset=utf8mb4');
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
function __table_exists(PDO $pdo, string $name): bool {
    $q = $pdo->prepare("SHOW TABLES LIKE :t");
    $q->execute([':t'=>$name]);
    return (bool)$q->fetchColumn();
}
function __cols(PDO $pdo, string $table): array {
    $cols=[]; foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $r) $cols[]=$r['Field']; return $cols;
}
function __pick(array $haystack, array $candidates, ?string $fallback=null): ?string {
    foreach ($candidates as $c) if (in_array($c, $haystack, true)) return $c;
    return $fallback;
}
function __label_expr(array $cols, array $preferred, string $fallbackPrefix): string {
    foreach ($preferred as $c) if (in_array($c, $cols, true)) return "`{$c}`";
    return "CONCAT('{$fallbackPrefix} ', `id`)";
}

/* ------------------------ Session/CSRF/Flash ------------------------ */
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf(): string { return $_SESSION['csrf']; }
function csrf_ok(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

$flash = $_SESSION['flash'] ?? null; // PRG flash
unset($_SESSION['flash']);

/* ------------------------ Current school ------------------------ */
function __current_school_id(PDO $pdo): ?int {
    if (!empty($_SESSION['school_id'])) return (int)$_SESSION['school_id'];
    if (!empty($_SESSION['user_id']) && __table_exists($pdo,'users')) {
        $uc = __cols($pdo,'users');
        if (in_array('school_id',$uc,true)) {
            $q=$pdo->prepare("SELECT school_id FROM users WHERE id=:id LIMIT 1");
            $q->execute([':id'=>(int)$_SESSION['user_id']]);
            $sid = $q->fetchColumn();
            if ($sid!==false && $sid!==null) { $_SESSION['school_id']=(int)$sid; return (int)$sid; }
        }
    }
    return null;
}

/* ------------------------ Discover results table/columns ------------------------ */
$pdo = __pdo();
$resultsTable = __table_exists($pdo,'marks') ? 'marks' : (__table_exists($pdo,'results') ? 'results' : 'marks');
$resultsCols  = __table_exists($pdo,$resultsTable) ? __cols($pdo,$resultsTable) : [];

// Tolerant column mapping
$colId        = __pick($resultsCols, ['id']);
$colStu       = __pick($resultsCols, ['student_id','student','studentId'], 'student_id');
$colSub       = __pick($resultsCols, ['subject_id','subject','subj_id'], 'subject_id');
$colExam      = __pick($resultsCols, ['assessment_type_id','assessment_id','exam_id','test_id','assessment'], 'assessment_type_id');
$colTerm      = __pick($resultsCols, ['term_id','term'], 'term_id');
$colYear      = __pick($resultsCols, ['year','academic_year','year_id'], 'year');
$colSchool    = __pick($resultsCols, ['school_id'], 'school_id');

$colMarkOb    = __pick($resultsCols, ['marks_obtained','marks','score','marks_scored'], 'marks_obtained');
$colMarkPos   = __pick($resultsCols, ['marks_possible','total','out_of','max_mark','max_marks','full_mark'], 'marks_possible');
$colPct       = __pick($resultsCols, ['percentage','percent'], 'percentage');
$colGrade     = __pick($resultsCols, ['grade_code','grade','letter_grade'], 'grade_code');
$colPoints    = __pick($resultsCols, ['points','point','score_point'], 'points');
$colEnteredBy = __pick($resultsCols, ['entered_by','recorded_by','user_id']);
$colEnteredAt = __pick($resultsCols, ['entered_at','created_at']);
$colUpdatedAt = __pick($resultsCols, ['updated_at']);

$hasId = (bool)$colId;

$schoolId = __current_school_id($pdo);

/* ------------------------ Staff subject restriction ------------------------ */
function __staff_subject_ids(PDO $pdo, int $userId): array {
    $cands = [
        ['table'=>'teacher_subjects','uid'=>'teacher_id','sid'=>'subject_id'],
        ['table'=>'staff_subjects','uid'=>'staff_id','sid'=>'subject_id'],
        ['table'=>'subject_teachers','uid'=>'user_id','sid'=>'subject_id'],
        ['table'=>'user_subjects','uid'=>'user_id','sid'=>'subject_id'],
        ['table'=>'subject_assignments','uid'=>'user_id','sid'=>'subject_id'],
    ];
    foreach ($cands as $c) {
        if (!__table_exists($pdo,$c['table'])) continue;
        $cols = __cols($pdo,$c['table']);
        if (!in_array($c['uid'],$cols,true) || !in_array($c['sid'],$cols,true)) continue;
        $q = $pdo->prepare("SELECT `{$c['sid']}` AS sid FROM `{$c['table']}` WHERE `{$c['uid']}`=:u");
        $q->execute([':u'=>$userId]);
        $ids = array_map('intval', array_column($q->fetchAll(),'sid'));
        if ($ids) return $ids;
    }
    return [];
}
$staffSubjectIds = [];
if ($restrictStaff && !empty($_SESSION['user_id'])) {
    $staffSubjectIds = __staff_subject_ids($pdo, (int)$_SESSION['user_id']);
}

/* ------------------------ Classes ------------------------ */
$classes = [];
if (__table_exists($pdo,'classes')) {
    $cc = __cols($pdo,'classes');
    $labelExpr = __label_expr($cc, ['class_name','name','code'], 'Class');
    $hasStream = in_array('stream',$cc,true);
    $sql  = "SELECT `id`, {$labelExpr} AS label".($hasStream?", `stream` AS stream":"") ." FROM `classes`";
    $bind = [];
    $where = [];
    if (in_array('school_id',$cc,true) && $schoolId) { $where[]='`school_id`=:sid'; $bind[':sid']=$schoolId; }
    if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
    $sql .= ' ORDER BY label';
    $st = $pdo->prepare($sql); $st->execute($bind); $classes = $st->fetchAll();
}

/* ------------------------ Subjects ------------------------ */
$subjects = [];
if (__table_exists($pdo,'subjects')) {
    $sc = __cols($pdo,'subjects');
    $labelExpr = __label_expr($sc, ['subject_name','name','code','short_code','short_name'], 'Subject');
    $sql  = "SELECT `id`, {$labelExpr} AS label FROM `subjects`";
    $bind = [];
    $where = [];
    if (in_array('school_id',$sc,true) && $schoolId) { $where[]='`school_id`=:sid'; $bind[':sid']=$schoolId; }
    if ($restrictStaff) {
        if ($staffSubjectIds) {
            $in=[]; foreach ($staffSubjectIds as $i=>$v){ $in[]=":S{$i}"; $bind[":S{$i}"]=(int)$v; }
            $where[] = '`id` IN ('.implode(',',$in).')';
        } else {
            $where[] = '0=1';
        }
    }
    if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
    $sql .= ' ORDER BY label';
    $st = $pdo->prepare($sql); $st->execute($bind); $subjects = $st->fetchAll();
}

/* ------------------------ Assessments (exams) ------------------------ */
$assessments = [];
$assessmentTable = __table_exists($pdo,'assessment_types') ? 'assessment_types' : null;
if ($assessmentTable) {
    $ac = __cols($pdo,$assessmentTable);
    $labelExpr = __label_expr($ac, ['name','code','title'], 'Exam');
    $sql  = "SELECT `id`, {$labelExpr} AS label FROM `{$assessmentTable}`";
    $bind = [];
    $where = [];
    if (in_array('school_id',$ac,true) && $schoolId) { $where[]='`school_id`=:sid'; $bind[':sid']=$schoolId; }
    if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
    $sql .= ' ORDER BY id';
    $st = $pdo->prepare($sql); $st->execute($bind); $assessments = $st->fetchAll();
} else {
    $assessments = [['id'=>1,'label'=>'Mid-Term'],['id'=>2,'label'=>'End-Term']];
}
$termOptions = [1=>'Term 1',2=>'Term 2',3=>'Term 3'];

/* ------------------------ Grade calculator ------------------------ */
function compute_grade(PDO $pdo, ?int $schoolId, float $pct): array {
    $hasItems  = __table_exists($pdo,'grade_scale_items');
    $hasScales = __table_exists($pdo,'grade_scales');
    if ($hasItems) {
        $ic = __cols($pdo,'grade_scale_items');
        $pick = fn($a)=>__pick($ic,$a);
        $cMin   = $pick(['min_percentage','min_mark','min_score','lower_bound','from_mark','from_score','from','min']);
        $cMax   = $pick(['max_percentage','max_mark','max_score','upper_bound','to_mark','to_score','to','max']);
        $cGrade = $pick(['grade_code','grade','code','letter','band']);
        $cPts   = $pick(['points','point','value','score','weight']);
        $cItemScale = $pick(['grade_scale_id','scale_id']);
        $scaleId = null;

        if ($hasScales && $cItemScale) {
            $sc = __cols($pdo,'grade_scales');
            $where=[]; $bind=[];
            if (in_array('school_id',$sc,true) && $schoolId) { $where[]='school_id=:sid'; $bind[':sid']=$schoolId; }
            $sql = "SELECT id FROM grade_scales".($where?' WHERE '.implode(' AND ',$where):'');
            $sql .= in_array('is_default',$sc,true) ? " ORDER BY is_default DESC, id ASC" : " ORDER BY id ASC";
            $sql .= " LIMIT 1";
            $s=$pdo->prepare($sql); $s->execute($bind); $scaleId=$s->fetchColumn();
        }

        if ($cMin && $cMax && $cGrade) {
            $bind=[]; $sql="SELECT * FROM grade_scale_items";
            if ($scaleId && $cItemScale) { $sql .= " WHERE `{$cItemScale}`=:gid"; $bind[':gid']=(int)$scaleId; }
            $sql .= " ORDER BY `{$cMin}` DESC";
            $st=$pdo->prepare($sql); $st->execute($bind);
            foreach ($st->fetchAll() as $r) {
                $lo=(float)$r[$cMin]; $hi=(float)$r[$cMax]; if ($hi<$lo){$t=$hi;$hi=$lo;$lo=$t;}
                if ($pct >= $lo && $pct <= $hi) {
                    $grade = (string)$r[$cGrade];
                    $pts   = $cPts && $r[$cPts]!=='' ? (float)$r[$cPts] : (['D1'=>1,'D2'=>2,'C3'=>3,'C4'=>4,'C5'=>5,'C6'=>6,'P7'=>7,'P8'=>8,'F9'=>9][$grade] ?? null);
                    return ['grade'=>$grade, 'points'=>$pts];
                }
            }
        }
    }
    $bands=[80=>['D1',1],75=>['D2',2],70=>['C3',3],65=>['C4',4],60=>['C5',5],55=>['C6',6],50=>['P7',7],45=>['P8',8],-INF=>['F9',9]];
    foreach ($bands as $min=>$gp) if ($pct>=$min) return ['grade'=>$gp[0],'points'=>$gp[1]];
    return ['grade'=>'F9','points'=>9];
}

/* ------------------------ Upsert result ------------------------ */
function upsert_result(PDO $pdo, string $table, array $tc,
                       string $cId, string $cStu, string $cSub, string $cExam, string $cTerm, ?string $cYear, ?string $cSchool,
                       string $cOb, string $cPos, string $cPct, string $cGrade, ?string $cPts, ?string $cBy, ?string $cAt, ?string $cUpd,
                       int $studentId, int $subjectId, int $assessmentId, int $termId, int $year, float $obt, float $possible,
                       ?int $schoolId, ?int $enteredBy): void
{
    $pct = $possible > 0 ? round(($obt/$possible)*100,2) : 0.0;
    $g   = compute_grade($pdo, $schoolId, $pct);
    $now = date('Y-m-d H:i:s');

    $where = "`{$cStu}`=:stu AND `{$cSub}`=:sub AND `{$cExam}`=:ass AND `{$cTerm}`=:term";
    $bind  = [':stu'=>$studentId, ':sub'=>$subjectId, ':ass'=>$assessmentId, ':term'=>$termId];
    if ($cYear && in_array($cYear,$tc,true))   { $where.=" AND `{$cYear}`=:yr"; $bind[':yr']=$year; }
    if ($cSchool && in_array($cSchool,$tc,true) && $schoolId!==null) { $where.=" AND `{$cSchool}`=:sid"; $bind[':sid']=$schoolId; }

    $existingId = null;
    if ($cId && in_array($cId,$tc,true)) {
        $q=$pdo->prepare("SELECT `{$cId}` FROM `{$table}` WHERE {$where} LIMIT 1");
        $q->execute($bind);
        $existingId = $q->fetchColumn();
    }

    if ($existingId) {
        $sets = "`{$cOb}`=:mo, `{$cPos}`=:mp, `{$cPct}`=:pct, `{$cGrade}`=:gc";
        $par  = [':mo'=>$obt, ':mp'=>$possible, ':pct'=>$pct, ':gc'=>$g['grade'], ':id'=>$existingId];
        if ($cPts && in_array($cPts,$tc,true)) { $sets.=", `{$cPts}`=:pts"; $par[':pts']=$g['points']; }
        if ($cBy  && in_array($cBy,$tc,true) && $enteredBy) { $sets.=", `{$cBy}`=:eby"; $par[':eby']=$enteredBy; }
        if ($cUpd && in_array($cUpd,$tc,true)) { $sets.=", `{$cUpd}`=:now"; $par[':now']=$now; }
        $pdo->prepare("UPDATE `{$table}` SET {$sets} WHERE `{$cId}`=:id LIMIT 1")->execute($par);
    } else {
        $cols = [$cStu,$cSub,$cExam,$cTerm,$cOb,$cPos,$cPct,$cGrade];
        $par  = [':stu'=>$studentId, ':sub'=>$subjectId, ':ass'=>$assessmentId, ':term'=>$termId, ':mo'=>$obt, ':mp'=>$possible, ':pct'=>$pct, ':gc'=>$g['grade']];
        $ph   = [':stu',':sub',':ass',':term',':mo',':mp',':pct',':gc'];

        if ($cPts && in_array($cPts,$tc,true))   { $cols[]=$cPts;   $ph[]=':pts'; $par[':pts']=$g['points']; }
        if ($cYear && in_array($cYear,$tc,true)) { $cols[]=$cYear;  $ph[]=':yr';  $par[':yr']=$year; }
        if ($cSchool && in_array($cSchool,$tc,true) && $schoolId!==null) { $cols[]=$cSchool; $ph[]=':sid'; $par[':sid']=$schoolId; }
        if ($cBy && in_array($cBy,$tc,true) && $enteredBy) { $cols[]=$cBy; $ph[]=':eby'; $par[':eby']=$enteredBy; }
        if ($cAt && in_array($cAt,$tc,true))  { $cols[]=$cAt;  $ph[]=':now'; $par[':now']=$now; }
        if ($cUpd && in_array($cUpd,$tc,true)) { $cols[]=$cUpd; $ph[]=':now2'; $par[':now2']=$now; }

        $sql = "INSERT INTO `{$table}` (`".implode('`,`',$cols)."`) VALUES (".implode(',',$ph).")";
        $pdo->prepare($sql)->execute($par);
    }
}

/* ------------------------ Selection (GET) ------------------------ */
$sel_class      = (int)($_GET['class_id'] ?? 0);
$sel_subject    = (int)($_GET['subject_id'] ?? 0);
$sel_assessment = (int)($_GET['assessment_type_id'] ?? 0);
$sel_term       = (int)($_GET['term_id'] ?? 1);
$sel_year       = (int)($_GET['year'] ?? (int)date('Y'));

/* ------------------------ POST (PRG) ------------------------ */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_ok($_POST['__csrf'] ?? null)) {
        $_SESSION['flash'] = ['type'=>'bad','msg'=>'Invalid form token. Refresh and try again.'];
        redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // keep selection through redirect
    $sel_class      = (int)($_POST['class_id'] ?? $sel_class);
    $sel_subject    = (int)($_POST['subject_id'] ?? $sel_subject);
    $sel_assessment = (int)($_POST['assessment_type_id'] ?? $sel_assessment);
    $sel_term       = (int)($_POST['term_id'] ?? $sel_term);
    $sel_year       = (int)($_POST['year'] ?? $sel_year);

    // staff safety: cannot write for other subjects
    if ($restrictStaff && $sel_subject && $staffSubjectIds && !in_array($sel_subject,$staffSubjectIds,true)) {
        $_SESSION['flash'] = ['type'=>'bad','msg'=>'You are not allowed to record results for this subject.'];
        $qs = http_build_query(['class_id'=>$sel_class,'subject_id'=>$sel_subject,'assessment_type_id'=>$sel_assessment,'term_id'=>$sel_term,'year'=>$sel_year]);
        redirect('?'.$qs); exit;
    }

    $action = (string)($_POST['__action'] ?? '');

    try {
        if ($action === 'save_batch') {
            $possible = (float)($_POST['marks_possible'] ?? 100);
            $students = is_array($_POST['students'] ?? null) ? array_map('intval', $_POST['students']) : [];
            $marks    = is_array($_POST['marks'] ?? null)    ? $_POST['marks'] : [];
            $saved=0; $eby=(int)($_SESSION['user_id'] ?? 0);

            foreach ($students as $sid) {
                $k=(string)$sid;
                if (!array_key_exists($k,$marks) || $marks[$k]==='') continue;
                $obt = (float)$marks[$k]; if ($obt<0) $obt=0;
                upsert_result(
                    $pdo,$resultsTable,$resultsCols,
                    $colId,$colStu,$colSub,$colExam,$colTerm,$colYear,$colSchool,
                    $colMarkOb,$colMarkPos,$colPct,$colGrade,$colPoints,$colEnteredBy,$colEnteredAt,$colUpdatedAt,
                    $sid,$sel_subject,$sel_assessment,$sel_term,$sel_year,$obt,$possible,$schoolId,$eby
                );
                $saved++;
            }
            $_SESSION['flash'] = ['type'=>'ok','msg'=>"Saved results for {$saved} student(s)."];
        }

        if ($action === 'edit_one' && $hasId) {
            $rid = (int)($_POST['id'] ?? 0);
            $obt = (float)($_POST['marks_obtained'] ?? 0);
            $mp  = (float)($_POST['marks_possible'] ?? 100);
            if ($rid>0) {
                $pct = $mp>0 ? round(($obt/$mp)*100,2) : 0.0;
                $g   = compute_grade($pdo,$schoolId,$pct);
                $sets = "`{$colMarkOb}`=:mo, `{$colMarkPos}`=:mp, `{$colPct}`=:pct, `{$colGrade}`=:gc";
                $bind = [':mo'=>$obt, ':mp'=>$mp, ':pct'=>$pct, ':gc'=>$g['grade'], ':id'=>$rid];
                if ($colPoints && in_array($colPoints,$resultsCols,true)) { $sets.=", `{$colPoints}`=:pts"; $bind[':pts']=$g['points']; }
                if ($colUpdatedAt && in_array($colUpdatedAt,$resultsCols,true)) { $sets.=", `{$colUpdatedAt}`=:now"; $bind[':now']=date('Y-m-d H:i:s'); }
                $pdo->prepare("UPDATE `{$resultsTable}` SET {$sets} WHERE `{$colId}`=:id LIMIT 1")->execute($bind);
                $_SESSION['flash'] = ['type'=>'ok','msg'=>'Result updated.'];
            }
        }

        if ($action === 'delete_one' && $hasId) {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid>0) {
                $pdo->prepare("DELETE FROM `{$resultsTable}` WHERE `{$colId}`=:id LIMIT 1")->execute([':id'=>$rid]);
                $_SESSION['flash'] = ['type'=>'ok','msg'=>'Result deleted.'];
            }
        }

        if ($action === 'import_csv') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = ['type'=>'bad','msg'=>'CSV upload failed.'];
            } else {
                $possibleDefault = (float)($_POST['marks_possible'] ?? 100);
                $eby = (int)($_SESSION['user_id'] ?? 0);
                $fh = fopen($_FILES['csv_file']['tmp_name'],'r');
                if (!$fh) {
                    $_SESSION['flash']=['type'=>'bad','msg'=>'Cannot read uploaded file.'];
                } else {
                    $header = fgetcsv($fh) ?: [];
                    $cols = array_map('strtolower',$header);
                    $iStu = array_search('student_id',$cols);
                    if ($iStu===false) $iStu = array_search('admission_no',$cols);
                    if ($iStu===false) $iStu = array_search('index_no',$cols);
                    $iMark = array_search('marks',$cols);
                    if ($iMark===false) $iMark = array_search('marks_obtained',$cols);
                    if ($iMark===false) $iMark = array_search('score',$cols);
                    $iPossible = array_search('marks_possible',$cols);

                    if ($iStu===false || $iMark===false) {
                        $_SESSION['flash'] = ['type'=>'bad','msg'=>'CSV must include student_id (or admission_no/index_no) and marks.'];
                    } else {
                        $resolver = function(string $v) use($pdo){
                            if (__table_exists($pdo,'students')) {
                                $sc = __cols($pdo,'students');
                                if (in_array('admission_no',$sc,true)) {
                                    $s=$pdo->prepare("SELECT id FROM students WHERE admission_no=:x LIMIT 1");
                                    $s->execute([':x'=>$v]); $id=$s->fetchColumn(); if ($id!==false) return (int)$id;
                                }
                                if (in_array('index_no',$sc,true)) {
                                    $s=$pdo->prepare("SELECT id FROM students WHERE index_no=:x LIMIT 1");
                                    $s->execute([':x'=>$v]); $id=$s->fetchColumn(); if ($id!==false) return (int)$id;
                                }
                            }
                            return (int)$v;
                        };

                        $count=0;
                        while (($row=fgetcsv($fh))!==false) {
                            $sidRaw = (string)($row[$iStu] ?? '');
                            $sidStu = is_numeric($sidRaw) ? (int)$sidRaw : $resolver($sidRaw);
                            if ($sidStu<=0) continue;
                            $obt    = (float)($row[$iMark] ?? 0);
                            $possible = $iPossible!==false ? (float)$row[$iPossible] : $possibleDefault;

                            upsert_result(
                                $pdo,$resultsTable,$resultsCols,
                                $colId,$colStu,$colSub,$colExam,$colTerm,$colYear,$colSchool,
                                $colMarkOb,$colMarkPos,$colPct,$colGrade,$colPoints,$colEnteredBy,$colEnteredAt,$colUpdatedAt,
                                $sidStu,$sel_subject,$sel_assessment,$sel_term,$sel_year,$obt,$possible,$schoolId,$eby
                            );
                            $count++;
                        }
                        fclose($fh);
                        $_SESSION['flash'] = ['type'=>'ok','msg'=>"Imported/updated {$count} row(s) from CSV."];
                    }
                }
            }
        }

    } catch (Throwable $e) {
        $_SESSION['flash'] = ['type'=>'bad','msg'=>'Error: '.htmlspecialchars($e->getMessage())];
    }

    $qs = http_build_query([
        'class_id'=>$sel_class,
        'subject_id'=>$sel_subject,
        'assessment_type_id'=>$sel_assessment,
        'term_id'=>$sel_term,
        'year'=>$sel_year
    ]);
    redirect('?'.$qs);
    exit;
}

/* ------------------------ Students in the selected class ------------------------ */
$students = [];
if ($sel_class && __table_exists($pdo,'students')) {
    $sc = __cols($pdo,'students');
    $fields = ['id'];
    foreach (['admission_no','index_no','first_name','last_name','full_name','name','stream'] as $f)
        if (in_array($f,$sc,true)) $fields[]="`{$f}`";
    $whereCol = in_array('class_id',$sc,true) ? 'class_id' : (in_array('class',$sc,true) ? 'class' : null);
    if ($whereCol) {
        $sql = "SELECT ".implode(',', $fields)." FROM students WHERE `{$whereCol}`=:cid";
        $bind = [':cid'=>$sel_class];
        if (in_array('school_id',$sc,true) && $schoolId) { $sql .= " AND school_id=:sid"; $bind[':sid']=$schoolId; }
        $order = in_array('first_name',$sc,true) || in_array('last_name',$sc,true) ? "first_name,last_name,id" : "id";
        $sql .= " ORDER BY {$order}";
        $st = $pdo->prepare($sql); $st->execute($bind); $students = $st->fetchAll();
    }
}

/* ------------------------ Existing results for the selection ------------------------ */
$existing = [];
if ($sel_class && $sel_subject && $sel_assessment && $sel_term && __table_exists($pdo,$resultsTable)) {
    $ids = array_map(fn($r)=>(int)$r['id'], $students);
    if ($ids) {
        $bind = [':sub'=>$sel_subject, ':ass'=>$sel_assessment, ':term'=>$sel_term];
        if ($colYear && in_array($colYear,$resultsCols,true)) { $bind[':yr']=$sel_year; }
        $in = [];
        foreach ($ids as $i=>$v){ $in[]=":st{$i}"; $bind[":st{$i}"]=$v; }

        $select = "r.*";
        $join   = "";
        if (__table_exists($pdo,'students')) {
            $sc = __cols($pdo,'students');
            $join = "LEFT JOIN students s ON s.id = r.`{$colStu}`";
            if (in_array('full_name',$sc,true))         $select .= ", s.full_name AS student_name";
            elseif (in_array('name',$sc,true))          $select .= ", s.name AS student_name";
            elseif (in_array('first_name',$sc,true) || in_array('last_name',$sc,true))
                                                       $select .= ", TRIM(CONCAT(COALESCE(s.first_name,''),' ',COALESCE(s.last_name,''))) AS student_name";
            elseif (in_array('username',$sc,true))      $select .= ", s.username AS student_name";
            else                                         $select .= ", NULL AS student_name";

            $select .= in_array('admission_no',$sc,true) ? ", s.admission_no" : ", NULL AS admission_no";
            $select .= in_array('index_no',$sc,true)     ? ", s.index_no"     : ", NULL AS index_no";
        } else {
            $select .= ", NULL AS student_name, NULL AS admission_no, NULL AS index_no";
        }

        $sql = "SELECT {$select}
                FROM `{$resultsTable}` r
                {$join}
                WHERE r.`{$colSub}`=:sub
                  AND r.`{$colExam}`=:ass
                  AND r.`{$colTerm}`=:term"
             . ($colYear && in_array($colYear,$resultsCols,true) ? " AND r.`{$colYear}`=:yr" : "")
             . " AND r.`{$colStu}` IN (".implode(',',$in).")";
        if ($colSchool && in_array($colSchool,$resultsCols,true) && $schoolId) { $sql .= " AND r.`{$colSchool}`=:sid"; $bind[':sid']=$schoolId; }

        $rs = $pdo->prepare($sql);
        $rs->execute($bind);
        foreach ($rs->fetchAll() as $r) {
            $existing[(int)$r[$colStu]] = $r;
        }
    }
}

/* ------------------------ Render ------------------------ */
include __DIR__ . '/../../views/layouts/header.php';
?>
<style>
/* ===== Tokens (match Classes/Students/Subjects) ===== */
:root{
  --ink:#0d2136; --muted:#3b5166;
  --border:#e7eef8; --row-sep:#eef3fb; --row-hover:#0e223f;
  --btn:#1a2c46; --btn-hover:#0b1729; --danger:#b12a37; --danger-hover:#8e2430;
}

/* Page head consistent spacing (no search strip here) */
.results-page .page-head{display:flex;align-items:center;justify-content:space-between;padding:16px;border-bottom:1px solid var(--border);background:#fff}
.results-page h1{margin:0;font-size:20px;font-weight:800;color:var(--ink)}

/* Cards (used for filters, tables, import) */
.results-page .card{
  margin:16px;background:#fff;border:1px solid var(--border);border-radius:16px;
  box-shadow:0 12px 28px rgba(2,19,46,.08),0 2px 6px rgba(2,19,46,.06);
}
.results-page .card-body{padding:16px}

/* Inputs/selects */
.results-page .input, .results-page .select{
  width:100%;background:#fff;border:1px solid #d8e4f0;color:var(--ink);border-radius:10px;padding:10px 12px
}
.results-page .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}

/* Outlined buttons */
.results-page .btn{
  appearance:none;background:transparent !important;color:var(--btn);border:1.6px solid var(--btn);
  border-radius:12px;padding:8px 12px;font-weight:700;cursor:pointer;transition:color .15s,border-color .15s;text-decoration:none;
  display:inline-flex;align-items:center;gap:8px
}
.results-page .btn:hover{color:var(--btn-hover);border-color:var(--btn-hover)}
.results-page .btn-danger{color:var(--danger);border-color:var(--danger);background:transparent !important}
.results-page .btn-danger:hover{color:var(--danger-hover);border-color:var(--danger-hover)}

/* Table */
.results-page .table-wrap{overflow:auto}
.results-page table{width:100%;min-width:960px;border-collapse:separate;border-spacing:0}
.results-page thead th{
  background:#f7f9fc;color:var(--muted);font-size:12px;letter-spacing:.06em;text-transform:uppercase;
  padding:14px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:1
}
.results-page tbody td{padding:14px 16px;border-bottom:1px solid var(--row-sep);color:var(--ink);vertical-align:middle}
.results-page tbody tr:nth-child(even){background:#fbfdff}

/* Hover band -> dark with white text (and buttons invert) */
.results-page tbody tr:hover{background:var(--row-hover) !important}
.results-page tbody tr:hover td, .results-page tbody tr:hover th, .results-page tbody tr:hover a{color:#fff !important}
.results-page tbody tr:hover .btn{color:#fff !important;border-color:#fff !important}
.results-page tbody tr:hover .btn-danger{color:#ffd6db !important;border-color:#ffd6db !important}

/* Inline inputs inside tables */
.results-page td .input{min-width:100px}

/* Alerts */
.alert{border-radius:12px;padding:10px 12px;margin:16px}
.alert.ok{background:#f3fffa;border:1px solid #1e7f5d;color:#145c46}
.alert.bad{background:#fff6f7;border:1px solid #7f2a33;color:#7f2a33}
.alert.warn{background:#fffdf3;border:1px solid #80630f;color:#6a540c}

/* Section titles inside cards */
.results-page .section-title{margin:0 0 10px;font-size:16px;font-weight:800;color:var(--ink)}
.results-page .note{color:var(--muted);font-size:12px}
</style>

<section class="results-page">
  <div class="page-head">
    <h1>Results</h1>
  </div>

  <!-- Filters -->
  <div class="card">
    <div class="card-body">
      <h2 class="section-title">Enter Results</h2>
      <form method="get" class="grid" action="">
        <div><label class="note">Class</label>
          <select name="class_id" class="select" onchange="this.form.submit()">
            <option value="">— Select class —</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>" <?php echo $sel_class===(int)$c['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($c['label'].(!empty($c['stream'])?' — '.$c['stream']:'')); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="note">Subject</label>
          <select name="subject_id" class="select" onchange="this.form.submit()">
            <option value="">— Select subject —</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>" <?php echo $sel_subject===(int)$s['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($s['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="note">Exam / Assessment</label>
          <select name="assessment_type_id" class="select" onchange="this.form.submit()">
            <option value="">— Select exam —</option>
            <?php foreach ($assessments as $a): ?>
              <option value="<?php echo (int)$a['id']; ?>" <?php echo $sel_assessment===(int)$a['id']?'selected':''; ?>>
                <?php echo htmlspecialchars($a['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="note">Term</label>
          <select name="term_id" class="select" onchange="this.form.submit()">
            <?php foreach ($termOptions as $tid=>$lbl): ?>
              <option value="<?php echo $tid; ?>" <?php echo $sel_term===$tid?'selected':''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="note">Year</label>
          <input class="input" type="number" name="year" value="<?php echo $sel_year; ?>" min="2000" max="2100" onchange="this.form.submit()">
        </div>
      </form>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert <?php echo htmlspecialchars($flash['type']); ?>">
      <?php echo $flash['msg']; ?>
    </div>
  <?php endif; ?>

  <?php if ($sel_class && $sel_subject && $sel_assessment && $sel_term): ?>
    <!-- Batch entry -->
    <div class="card">
      <div class="card-body">
        <form method="post" id="formBatch">
          <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
          <input type="hidden" name="__action" value="save_batch">
          <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
          <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
          <input type="hidden" name="assessment_type_id" value="<?php echo $sel_assessment; ?>">
          <input type="hidden" name="term_id" value="<?php echo $sel_term; ?>">
          <input type="hidden" name="year" value="<?php echo $sel_year; ?>">

          <div class="grid" style="margin-bottom:10px">
            <div>
              <label class="note">Marks possible (default 100)</label>
              <input class="input" type="number" step="0.01" name="marks_possible" value="100">
            </div>
            <div style="display:flex; align-items:flex-end; gap:10px;">
              <button class="btn" type="submit" id="btnSaveAll">Save all</button>
              <span class="note">Type each mark; grade & points auto-computed.</span>
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Admission/Index</th>
                  <th>Mark</th>
                  <th>Existing</th>
                  <th>Grade</th>
                  <th>Points</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$students): ?>
                  <tr><td colspan="7" class="note">No students found for this class.</td></tr>
                <?php else: $n=0; foreach ($students as $stu): $n++;
                      $eid = (int)$stu['id'];
                      $have = $existing[$eid] ?? null;

                      $name = '';
                      if (!empty($stu['first_name']) || !empty($stu['last_name'])) $name = trim(($stu['first_name'] ?? '').' '.($stu['last_name'] ?? ''));
                      if ($name==='') $name = $stu['full_name'] ?? ($stu['name'] ?? ('Student #'.$eid));

                      $adm = $stu['admission_no'] ?? ($stu['index_no'] ?? '');
                ?>
                  <tr>
                    <td><?php echo $n; ?></td>
                    <td>
                      <input type="hidden" name="students[]" value="<?php echo $eid; ?>">
                      <?php echo htmlspecialchars($name); ?>
                    </td>
                    <td><?php echo htmlspecialchars($adm); ?></td>
                    <td style="min-width:120px">
                      <input class="input" type="number" step="0.01" name="marks[<?php echo $eid; ?>]" value="">
                    </td>
                    <td>
                      <?php if ($have): ?>
                        <?php echo htmlspecialchars((string)$have[$colMarkOb]); ?> / <?php echo htmlspecialchars((string)$have[$colMarkPos]); ?> (<?php echo htmlspecialchars((string)$have[$colPct]); ?>%)
                      <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)($have[$colGrade] ?? '—')); ?></td>
                    <td><?php echo isset($have[$colPoints]) ? (int)$have[$colPoints] : '—'; ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </form>
      </div>
    </div>

    <!-- Existing results -->
    <div class="card">
      <div class="card-body">
        <h2 class="section-title">Existing Results (Current Selection)</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Student</th><th>Mark / Out of</th><th>%</th><th>Grade</th><th>Points</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$existing): ?>
                <tr><td colspan="7" class="note">No records yet for this selection.</td></tr>
              <?php else: foreach ($existing as $row):
                  $displayName = trim((string)($row['student_name'] ?? ''));
                  if ($displayName==='') $displayName = $row['admission_no'] ?? ($row['index_no'] ?? ('ID '.(int)$row[$colStu]));
              ?>
                <tr>
                  <td><?php echo $hasId ? (int)$row[$colId] : '—'; ?></td>
                  <td><?php echo htmlspecialchars($displayName); ?></td>
                  <td>
                    <?php if ($hasId): ?>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
                        <input type="hidden" name="__action" value="edit_one">
                        <input type="hidden" name="id" value="<?php echo (int)$row[$colId]; ?>">
                        <input class="input" style="width:100px" type="number" step="0.01" name="marks_obtained" value="<?php echo htmlspecialchars((string)$row[$colMarkOb]); ?>">
                        <input class="input" style="width:110px" type="number" step="0.01" name="marks_possible" value="<?php echo htmlspecialchars((string)$row[$colMarkPos]); ?>">
                        <button class="btn" type="submit" title="Update">Save</button>
                      </form>
                    <?php else: ?>
                      <?php echo htmlspecialchars((string)$row[$colMarkOb]); ?> / <?php echo htmlspecialchars((string)$row[$colMarkPos]); ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars((string)$row[$colPct]); ?>%</td>
                  <td><?php echo htmlspecialchars((string)($row[$colGrade] ?? '')); ?></td>
                  <td><?php echo isset($row[$colPoints]) ? (int)$row[$colPoints] : '—'; ?></td>
                  <td>
                    <?php if ($hasId): ?>
                      <form method="post" style="display:inline" onsubmit="return confirm('Delete this result?');">
                        <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
                        <input type="hidden" name="__action" value="delete_one">
                        <input type="hidden" name="id" value="<?php echo (int)$row[$colId]; ?>">
                        <button class="btn btn-danger" type="submit" title="Delete">Delete</button>
                      </form>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CSV import -->
    <div class="card">
      <div class="card-body">
        <h2 class="section-title">Import from CSV (for current Class/Subject/Exam/Term/Year)</h2>
        <form method="post" enctype="multipart/form-data" class="grid" id="formImport">
          <input type="hidden" name="__csrf" value="<?php echo csrf(); ?>">
          <input type="hidden" name="__action" value="import_csv">
          <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
          <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
          <input type="hidden" name="assessment_type_id" value="<?php echo $sel_assessment; ?>">
          <input type="hidden" name="term_id" value="<?php echo $sel_term; ?>">
          <input type="hidden" name="year" value="<?php echo $sel_year; ?>">

          <div>
            <label class="note">CSV file</label>
            <input class="input" type="file" name="csv_file" accept=".csv" required>
          </div>
          <div>
            <label class="note">Marks possible in CSV (blank = 100)</label>
            <input class="input" type="number" step="0.01" name="marks_possible" value="100">
          </div>
          <div style="display:flex; align-items:flex-end;">
            <button class="btn" type="submit" id="btnImport">Import CSV</button>
          </div>
          <div style="grid-column:1/-1" class="note">
            CSV headers: <code>student_id</code> (or <code>admission_no</code>/<code>index_no</code>) and <code>marks</code> (or <code>marks_obtained</code>/<code>score</code>). Optional: <code>marks_possible</code>.
          </div>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="card"><div class="card-body"><span class="note">Choose Class, Subject, Exam, Term and Year to begin.</span></div></div>
  <?php endif; ?>
</section>

<script>
  document.getElementById('formBatch')?.addEventListener('submit', ()=>{ const b=document.getElementById('btnSaveAll'); if(b) b.disabled=true; });
  document.getElementById('formImport')?.addEventListener('submit', ()=>{ const b=document.getElementById('btnImport'); if(b) b.disabled=true; });
</script>

<?php
include __DIR__ . '/../../views/layouts/footer.php';
