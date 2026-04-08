<?php
/**
 * PATH: /super/analytics.php
 * Cross-school charts.
 */
require_once __DIR__ . '/../config/config.php';
@session_start();
if (($_SESSION['role'] ?? '') !== 'SUPER_ADMIN') { header('Location: /public/'); exit; }
if (file_exists(__DIR__.'/../header.php')) require __DIR__.'/../header.php';
?>
<div class="container" style="max-width:1100px;margin:20px auto;padding:12px;">
  <h1>Platform Analytics</h1>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
    <canvas id="chartSchools" height="180"></canvas>
    <canvas id="chartStudentsBySchool" height="180"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function fetchJSON(url){ const r=await fetch(url); return r.json(); }
(async()=>{
  const a = await fetchJSON('/api/super_overview.php');
  new Chart(document.getElementById('chartSchools'), {
    type:'doughnut',
    data:{labels:['Schools'], datasets:[{data:[a.schools]}]}
  });
  new Chart(document.getElementById('chartStudentsBySchool'), {
    type:'bar',
    data:{labels:a.school_names, datasets:[{label:'Students', data:a.student_counts}]}
  });
})();
</script>
<?php if (file_exists(__DIR__.'/../footer.php')) require __DIR__.'/../footer.php'; ?>
