\
    <?php
    declare(strict_types=1);

    /**
     * Results helpers
     * PATH: /lib/results.php
     */
    require_once __DIR__ . '/../config/config.php';

    /**
     * Compute weighted percentage for (student, subject, term, school).
     * Falls back to assessment_types.weight when a subject does not have its own weights.
     */
    function compute_weighted_percentage(int $student_id, int $subject_id, int $term_id, int $school_id): float {
        $sql = "
          SELECT
            at.id AS assessment_type_id,
            COALESCE(sa.weight, at.weight) AS weight,
            m.marks_obtained,
            m.marks_possible
          FROM assessment_types at
          LEFT JOIN subject_assessments sa
            ON sa.assessment_type_id = at.id
           AND sa.subject_id = :subject_id
           AND sa.school_id = :school_id
          LEFT JOIN marks m
            ON m.assessment_type_id = at.id
           AND m.subject_id = :subject_id
           AND m.student_id = :student_id
           AND m.term_id = :term_id
          WHERE at.school_id = :school_id
          ORDER BY at.name
        ";
        $stmt = pdo()->prepare($sql);
        $stmt->execute([
            ':subject_id' => $subject_id,
            ':school_id'  => $school_id,
            ':student_id' => $student_id,
            ':term_id'    => $term_id,
        ]);
        $rows = $stmt->fetchAll();

        if (!$rows) return 0.0;

        // normalize weights to sum to 100
        $sumW = 0.0;
        foreach ($rows as $r) { $sumW += (float)$r['weight']; }
        if ($sumW <= 0.0) return 0.0;

        $pct = 0.0;
        foreach ($rows as $r) {
            $wNorm = ((float)$r['weight']) * (100.0 / $sumW);
            $got = isset($r['marks_obtained']) ? (float)$r['marks_obtained'] : 0.0;
            $pos = isset($r['marks_possible']) ? max(1.0, (float)$r['marks_possible']) : 100.0; // avoid /0
            $componentPct = ($got / $pos) * 100.0;
            $pct += $componentPct * ($wNorm / 100.0);
        }
        if ($pct < 0) $pct = 0;
        if ($pct > 100) $pct = 100;
        return round($pct, 2);
    }
