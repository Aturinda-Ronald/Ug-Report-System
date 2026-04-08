\
    <?php
    declare(strict_types=1);
    /**
     * PATH: /dev/test_compute.php
     * Usage: dev/test_compute.php?student_id=1&subject_id=1&term_id=1&school_id=1
     */
    require_once __DIR__ . '/../lib/results.php';

    $student_id = (int)($_GET['student_id'] ?? 0);
    $subject_id = (int)($_GET['subject_id'] ?? 0);
    $term_id    = (int)($_GET['term_id'] ?? 0);
    $school_id  = (int)($_GET['school_id'] ?? 0);

    if (!$student_id || !$subject_id || !$term_id || !$school_id) {
        echo "Provide all params: student_id, subject_id, term_id, school_id";
        exit;
    }

    $pct = compute_weighted_percentage($student_id, $subject_id, $term_id, $school_id);
    echo "Weighted percentage = {$pct}%";
