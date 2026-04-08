<?php
/**
 * PATH: /api/admin_subject_averages.php
 * Average percentages per subject for the latest term.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_admin();
$school_id = (int)($_SESSION['school_id'] ?? 0);
$pdo = pdo();
$term_id = (int)$pdo->query("SELECT t.id FROM terms t WHERE t.school_id={$school_id} ORDER BY t.id DESC LIMIT 1")->fetchColumn();

$rows = [];
if ($term_id) {
  $sql = "
    SELECT subj.name, AVG(m.percentage) AS avg_pct
    FROM marks m
    JOIN subjects subj ON subj.id = m.subject_id
    WHERE m.school_id = ? AND m.term_id = ?
    GROUP BY m.subject_id
    ORDER BY subj.name
  ";
  $st = $pdo->prepare($sql); $st->execute([$school_id, $term_id]); $rows = $st->fetchAll();
}
$labels = array_map(fn($r)=>$r['name'], $rows);
$values = array_map(fn($r)=>round((float)$r['avg_pct'],2), $rows);
header('Content-Type: application/json'); echo json_encode(['labels'=>$labels,'values'=>$values,'term_id'=>$term_id]);
