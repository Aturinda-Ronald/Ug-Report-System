<?php
/**
 * PATH: /admin/students_add.php
 * Add a single student.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_admin();

$school_id = (int)($_SESSION['school_id'] ?? 0);
$errors = []; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index_no = trim($_POST['index_no'] ?? '');
    $reg_no   = trim($_POST['reg_no'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    $stream_id= (int)($_POST['stream_id'] ?? 0);
    $guardian = trim($_POST['guardian_name'] ?? '');
    $phone    = trim($_POST['guardian_phone'] ?? '');

    if ($name === '') $errors[] = 'Name is required';
    if ($index_no === '' && $reg_no === '') $errors[] = 'Index No or Reg No is required';
    if (!$class_id) $errors[] = 'Class is required';

    if (!$errors) {
        $stmt = pdo()->prepare("
            INSERT INTO students (school_id, index_no, reg_no, name, gender, class_id, stream_id, guardian_name, guardian_phone, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$school_id, $index_no ?: null, $reg_no ?: null, $name, $gender ?: null, $class_id ?: null, $stream_id ?: null, $guardian ?: null, $phone ?: null]);
        $ok = 'Student added.';
    }
}

$classes = pdo()->prepare("SELECT id, name FROM classes WHERE school_id=? ORDER BY name");
$classes->execute([$school_id]); $classes = $classes->fetchAll();

$streams = [];
if (!empty($_GET['class_id'])) {
    $cid = (int)$_GET['class_id'];
    $st = pdo()->prepare("SELECT id, name FROM streams WHERE class_id=? ORDER BY name");
    $st->execute([$cid]); $streams = $st->fetchAll();
}

if (file_exists(__DIR__.'/../header.php')) require __DIR__.'/../header.php';
?>
<div class="container" style="max-width:900px;margin:20px auto;padding:12px;">
  <h1>Add Student</h1>
  <?php if ($ok): ?><div style="background:#133;border:1px solid #4aa;color:#7fd;padding:8px;margin-bottom:12px;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if ($errors): ?>
    <div style="background:#331;border:1px solid #a44;color:#f88;padding:8px;margin-bottom:12px;">
      <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
      <label>Index No<input type="text" name="index_no"></label>
      <label>Reg No<input type="text" name="reg_no"></label>
      <label>Full Name<input type="text" name="name" required></label>
      <label>Gender
        <select name="gender">
          <option value="">--</option>
          <option value="M">Male</option>
          <option value="F">Female</option>
        </select>
      </label>
      <label>Class
        <select name="class_id" id="class_id">
          <option value="">-- select --</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Stream
        <select name="stream_id" id="stream_id">
          <option value="">-- select --</option>
        </select>
      </label>
      <label>Guardian Name<input type="text" name="guardian_name"></label>
      <label>Guardian Phone<input type="text" name="guardian_phone"></label>
    </div>
    <div style="margin-top:12px;">
      <button class="btn ripple">Save</button>
      <a class="btn ghost ripple" href="/admin/index.php">Back</a>
    </div>
  </form>
</div>
<script>
document.getElementById('class_id').addEventListener('change', async function(){
  const cid = this.value; const sel = document.getElementById('stream_id');
  sel.innerHTML = '<option>Loading...</option>';
  const res = await fetch('/api/streams_by_class.php?class_id='+encodeURIComponent(cid));
  const data = await res.json();
  sel.innerHTML = '<option value="">-- select --</option>';
  (data.rows||[]).forEach(r=>{
    const o=document.createElement('option'); o.value=r.id; o.textContent=r.name; sel.appendChild(o);
  });
});
</script>
<?php if (file_exists(__DIR__.'/../footer.php')) require __DIR__.'/../footer.php'; ?>
