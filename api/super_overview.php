<?php
/**
 * PATH: /api/super_overview.php
 */
require_once __DIR__ . '/../config/config.php';
@session_start();
if (($_SESSION['role'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); exit; }

$pdo = pdo();
$schools = (int)$pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$names = []; $counts = [];
$st = $pdo->query("SELECT sc.name, COUNT(stu.id) as cnt FROM schools sc LEFT JOIN students stu ON stu.school_id=sc.id GROUP BY sc.id ORDER BY sc.name");
while ($r = $st->fetch()) { $names[] = $r['name']; $counts[] = (int)$r['cnt']; }
header('Content-Type: application/json'); echo json_encode(['schools'=>$schools,'school_names'=>$names,'student_counts'=>$counts]);
