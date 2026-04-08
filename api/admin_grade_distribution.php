<?php
/**
 * PATH: /api/admin_grade_distribution.php
 * Grade distribution for latest term (bucket by grade scale if available, else coarse buckets).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/grades.php';
require_admin();
$school_id = (int)($_SESSION['school_id'] ?? 0);
$pdo = pdo();
$term_id = (int)$pdo->query("SELECT t.id FROM terms t WHERE t.school_id={$school_id} ORDER BY t.id DESC LIMIT 1")->fetchColumn();

$labels = []; $values = [];

if ($term_id) {
  // Try to pick a grade scale by sampling first student's class level
  $sid = (int)$pdo->query("SELECT student_id FROM marks WHERE school_id={$school_id} AND term_id={$term_id} LIMIT 1")->fetchColumn();
  if ($sid) {
      $cid = (int)$pdo->query("SELECT class_id FROM students WHERE id={$sid}")->fetchColumn();
      $level = class_level_from_year_group($cid ?: 0);
      $gsid = get_grade_scale_id($school_id, $level);
  } else {
      $gsid = null;
  }

  if ($gsid) {
      // Build grade buckets from scale
      $grows = $pdo->query("SELECT grade_code, min_mark, max_mark FROM grade_scale_items WHERE grade_scale_id={$gsid} ORDER BY min_mark DESC")->fetchAll();
      foreach ($grows as $g) {
          $code = $g['grade_code']; $min=(float)$g['min_mark']; $max=(float)$g['max_mark'];
          $cnt = $pdo->prepare("SELECT COUNT(*) FROM marks WHERE school_id=? AND term_id=? AND percentage BETWEEN ? AND ?");
          $cnt->execute([$school_id, $term_id, $min, $max]);
          $labels[] = $code; $values[] = (int)$cnt->fetchColumn();
      }
  } else {
      // Fallback coarse buckets
      $buckets = [['0-39',0,39],['40-59',40,59],['60-79',60,79],['80-100',80,100]];
      foreach ($buckets as $b) {
          [$label,$min,$max] = $b;
          $cnt = $pdo->prepare("SELECT COUNT(*) FROM marks WHERE school_id=? AND term_id=? AND percentage BETWEEN ? AND ?");
          $cnt->execute([$school_id,$term_id,$min,$max]);
          $labels[]=$label; $values[]=(int)$cnt->fetchColumn();
      }
  }
}
header('Content-Type: application/json'); echo json_encode(['labels'=>$labels,'values'=>$values,'term_id'=>$term_id]);
