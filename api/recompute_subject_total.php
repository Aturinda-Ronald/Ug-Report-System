\
    <?php
/**
     * PATH: /api/recompute_subject_total.php
     * Returns JSON { percent: number } using compute_weighted_percentage()
     */
    header('Content-Type: application/json');
    require_once __DIR__ . '/../lib/results.php';

    $student_id = (int)($_GET['student_id'] ?? 0);
    $subject_id = (int)($_GET['subject_id'] ?? 0);
    $term_id    = (int)($_GET['term_id'] ?? 0);

    // Determine school_id from student (safer than trusting client)
    $school_id = 0;
    if ($student_id) {
        $st = pdo()->prepare("SELECT school_id FROM students WHERE id=?");
        $st->execute([$student_id]);
        $school_id = (int)$st->fetchColumn();
    }

    if (!$student_id || !$subject_id || !$term_id || !$school_id) {
        echo json_encode(['percent' => null, 'error' => 'missing params']);
        exit;
    }

    $pct = compute_weighted_percentage($student_id, $subject_id, $term_id, $school_id);
    echo json_encode(['percent' => $pct]);
