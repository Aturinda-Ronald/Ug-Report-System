<?php
/**
 * PATH: /api/streams_by_class.php
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');
$class_id = (int)($_GET['class_id'] ?? 0);
$rows = [];
if ($class_id) {
    $st = pdo()->prepare("SELECT id, name FROM streams WHERE class_id=? ORDER BY name");
    $st->execute([$class_id]); $rows = $st->fetchAll();
}
echo json_encode(['rows'=>$rows]);
