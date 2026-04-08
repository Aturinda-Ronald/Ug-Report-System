<?php
/**
 * PATH: /api/admin_overview.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/guards.php';
require_admin();
$school_id = (int)($_SESSION['school_id'] ?? 0);
$pdo = pdo();
$out = [
  'students' => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE school_id={$school_id}")->fetchColumn(),
  'subjects' => (int)$pdo->query("SELECT COUNT(*) FROM subjects WHERE school_id={$school_id}")->fetchColumn(),
  'classes'  => (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE school_id={$school_id}")->fetchColumn(),
  'terms'    => (int)$pdo->query("SELECT COUNT(*) FROM terms WHERE school_id={$school_id}")->fetchColumn(),
];
header('Content-Type: application/json'); echo json_encode($out);
