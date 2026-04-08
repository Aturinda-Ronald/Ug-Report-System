<?php
/**
 * PATH: /admin/analytics.php
 * Charts for current school.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_admin();
if (file_exists(__DIR__.'/../header.php')) require __DIR__.'/../header.php';
?>
<div class="container" style="max-width:1100px;margin:20px auto;padding:12px;">
  <h1>Analytics</h1>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
    <canvas id="chartOverview" height="180"></canvas>
    <canvas id="chartSubjects" height="180"></canvas>
    <canvas id="chartGrades" height="180"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function fetchJSON(url){ const r=await fetch(url); return r.json(); }
(async()=>{
  const ov = await fetchJSON('/api/admin_overview.php');
  const sb = await fetchJSON('/api/admin_subject_averages.php');
  const gd = await fetchJSON('/api/admin_grade_distribution.php');

  new Chart(document.getElementById('chartOverview'), {
    type:'bar',
    data:{labels:['Students','Subjects','Classes','Terms'],
          datasets:[{label:'Counts', data:[ov.students, ov.subjects, ov.classes, ov.terms]}]},
  });

  new Chart(document.getElementById('chartSubjects'), {
    type:'line',
    data:{labels:sb.labels, datasets:[{label:'Average %', data:sb.values, tension:.3, fill:false}]},
  });

  new Chart(document.getElementById('chartGrades'), {
    type:'pie',
    data:{labels:gd.labels, datasets:[{data:gd.values}]},
  });
})();
</script>
<?php if (file_exists(__DIR__.'/../footer.php')) require __DIR__.'/../footer.php'; ?>
