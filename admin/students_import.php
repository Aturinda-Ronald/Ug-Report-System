<?php
/**
 * PATH: /admin/students_import.php
 * Import students from CSV (columns: name,index_no,reg_no,gender,class_id,stream_id,guardian_name,guardian_phone)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_admin();
$school_id = (int)($_SESSION['school_id'] ?? 0);

$ok=''; $err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv']) && $_FILES['csv']['error']===UPLOAD_ERR_OK) {
    $tmp = $_FILES['csv']['tmp_name'];
    $h = fopen($tmp, 'r');
    if ($h) {
        $pdo = pdo();
        $ins = $pdo->prepare("INSERT INTO students (school_id,name,index_no,reg_no,gender,class_id,stream_id,guardian_name,guardian_phone,created_at)
                              VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $line=0; $added=0;
        while (($row=fgetcsv($h))!==false) {
            $line++;
            if ($line===1 && preg_match('/name/i', $row[0]??'')) continue; // header
            $name=$row[0]??''; if (!$name) continue;
            $index_no=$row[1]??null; $reg_no=$row[2]??null; $gender=$row[3]??null;
            $class_id=(int)($row[4]??0); $stream_id=(int)($row[5]??0);
            $guardian=$row[6]??null; $phone=$row[7]??null;
            try{
                $ins->execute([$school_id,$name,$index_no,$reg_no,$gender?:null,($class_id ?: null),($stream_id ?: null),$guardian,$phone]);
                $added++;
            }catch(Exception $e){ /* skip bad row */ }
        }
        fclose($h);
        $ok="Imported {$added} students.";
    } else {
        $err='Failed to open CSV.';
    }
}
if (file_exists(__DIR__.'/../header.php')) require __DIR__.'/../header.php';
?>
<div class="container" style="max-width:900px;margin:20px auto;padding:12px;">
  <h1>Import Students (CSV)</h1>
  <?php if ($ok): ?><div style="background:#133;border:1px solid #4aa;color:#7fd;padding:8px;margin-bottom:12px;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:#331;border:1px solid #a44;color:#f88;padding:8px;margin-bottom:12px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="csv" accept=".csv" required>
    <button class="btn ripple">Upload</button>
  </form>
  <p class="text-muted" style="color:#aaa">Columns: name,index_no,reg_no,gender,class_id,stream_id,guardian_name,guardian_phone</p>
</div>
<?php if (file_exists(__DIR__.'/../footer.php')) require __DIR__.'/../footer.php'; ?>
