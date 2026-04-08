<?php
/**
 * PATH: /lib/grades.php
 * Helpers: class level, grade scale lookup, grade by percent
 */
require_once __DIR__ . '/../config/config.php';

/** Return 'O_LEVEL' or 'A_LEVEL' for a given class_id using classes.year_group (<=4 => O) */
function class_level_from_year_group(int $class_id): string {
    $st = pdo()->prepare("SELECT year_group FROM classes WHERE id=?");
    $st->execute([$class_id]);
    $yg = (int)$st->fetchColumn();
    return ($yg >= 5 ? 'A_LEVEL' : 'O_LEVEL');
}

/** Return grade_scale id for school + level (first match if multiple) */
function get_grade_scale_id(int $school_id, string $level): ?int {
    $st = pdo()->prepare("SELECT id FROM grade_scales WHERE school_id=? AND level=? ORDER BY id ASC LIMIT 1");
    $st->execute([$school_id, $level]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

/** Return grade row (grade_code, points, remarks) for percent using a grade_scale */
function lookup_grade_for_percent(int $grade_scale_id, float $percent): ?array {
    $st = pdo()->prepare("
        SELECT grade_code, points, remarks
        FROM grade_scale_items
        WHERE grade_scale_id=? AND ? BETWEEN min_mark AND max_mark
        ORDER BY min_mark DESC
        LIMIT 1
    ");
    $st->execute([$grade_scale_id, $percent]);
    $row = $st->fetch();
    return $row ?: null;
}
