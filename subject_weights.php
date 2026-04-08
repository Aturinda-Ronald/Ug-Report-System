\
    <?php
    declare(strict_types=1);

    /**
     * Subject Component Weights (per subject)
     * PATH: /subject_weights.php
     */
    require_once __DIR__ . '/config/config.php';

    // --- fallback guard if require_admin() doesn't exist in your project ---
    if (function_exists('require_admin')) {
        require_admin();
    } else {
        @session_start();
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['SCHOOL_ADMIN','SUPER_ADMIN'], true)) {
            http_response_code(403);
            die('Access denied (admin only).');
        }
    }

    // Get current school_id safely
    function current_school_id(): int {
        if (function_exists('current_user_school_id')) {
            return (int) current_user_school_id();
        }
        @session_start();
        if (!empty($_SESSION['school_id'])) {
            return (int) $_SESSION['school_id'];
        }
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid > 0) {
            $stmt = pdo()->prepare("SELECT COALESCE(school_id, 0) FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            return (int)$stmt->fetchColumn();
        }
        return 0;
    }

    $school_id = current_school_id();
    $subject_id = (int)($_GET['subject_id'] ?? 0);

    // ---------- SAVE ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $weightsIn  = $_POST['weight'] ?? []; // [assessment_type_id => weight]

        // sanitize + total
        $total = 0.0;
        $clean = [];
        foreach ($weightsIn as $aid => $w) {
            $aid = (int)$aid;
            $w   = (float)$w;
            if ($w < 0)   $w = 0;
            if ($w > 100) $w = 100;
            $clean[$aid] = $w;
            $total += $w;
        }

        // must sum to 100
        if (abs($total - 100.0) > 0.01) {
            $msg = "Weights must total 100%. You entered {$total}%.";
            header('Location: subject_weights.php?subject_id='.$subject_id.'&error='.urlencode($msg));
            exit;
        }

        // clear then insert
        pdo()->beginTransaction();
        $del = pdo()->prepare("
            DELETE sa FROM subject_assessments sa
            JOIN subjects subj ON subj.id = sa.subject_id
            WHERE sa.subject_id = ? AND subj.school_id = ?
        ");
        $del->execute([$subject_id, $school_id]);

        $ins = pdo()->prepare("
            INSERT INTO subject_assessments (school_id, subject_id, assessment_type_id, weight)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($clean as $aid => $w) {
            $ins->execute([$school_id, $subject_id, $aid, $w]);
        }
        pdo()->commit();

        $msg = "Saved weights for subject ID {$subject_id} (total 100%).";
        header('Location: subject_weights.php?subject_id='.$subject_id.'&ok='.urlencode($msg));
        exit;
    }

    // ---------- DATA ----------
    $subjects = (function() use($school_id) {
        $stmt = pdo()->prepare("SELECT id, code, name FROM subjects WHERE school_id = ? ORDER BY name");
        $stmt->execute([$school_id]);
        return $stmt->fetchAll();
    })();

    $assessments = [];
    if ($subject_id) {
        $stmt = pdo()->prepare("
            SELECT
              at.id,
              at.code,
              at.name,
              COALESCE(sa.weight, at.weight) AS weight_default
            FROM assessment_types at
            LEFT JOIN subject_assessments sa
              ON sa.assessment_type_id = at.id
             AND sa.subject_id = :subject_id
             AND sa.school_id = :school_id
            WHERE at.school_id = :school_id
            ORDER BY at.name
        ");
        $stmt->execute([
            ':subject_id' => $subject_id,
            ':school_id'  => $school_id
        ]);
        $assessments = $stmt->fetchAll();
    }

    // Optional header/footer if you have them
    if (file_exists(__DIR__.'/header.php')) require __DIR__.'/header.php';
    ?>
    <div class="container" style="max-width:920px;margin:24px auto;padding:16px;background:#1112;border:1px solid #222;border-radius:12px;">
      <h1 style="margin:0 0 16px;">Subject Component Weights</h1>

      <?php if (!empty($_GET['error'])): ?>
        <div style="background:#331; border:1px solid #a44; padding:10px; margin-bottom:12px; color:#f88;">
          <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($_GET['ok'])): ?>
        <div style="background:#133; border:1px solid #4aa; padding:10px; margin-bottom:12px; color:#7fd;">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <form method="get" style="margin-bottom:14px;">
        <label for="subject_id">Subject:&nbsp;</label>
        <select id="subject_id" name="subject_id" onchange="this.form.submit()">
          <option value="">-- choose --</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $subject_id==$s['id']?'selected':'' ?>>
              <?= htmlspecialchars($s['code'].' - '.$s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <noscript><button>Load</button></noscript>
      </form>

      <?php if ($subject_id): ?>
        <form method="post" id="weightsForm">
          <input type="hidden" name="subject_id" value="<?= (int)$subject_id ?>">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid #333">
                <th style="text-align:left;padding:8px;">Component</th>
                <th style="text-align:left;padding:8px; width:220px;">Weight (%)</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($assessments as $a): ?>
              <tr style="border-bottom:1px solid #222">
                <td style="padding:8px;"><?= htmlspecialchars($a['code'].' - '.$a['name']) ?></td>
                <td style="padding:8px;">
                  <input type="number" name="weight[<?= (int)$a['id'] ?>]" step="0.01" min="0" max="100"
                         value="<?= htmlspecialchars((string)$a['weight_default']) ?>"
                         oninput="sumWeights()" style="width:160px;">
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td style="padding:8px; text-align:right; font-weight:bold;">Total:</td>
                <td style="padding:8px;">
                  <span id="totalSpan">0</span> %
                  &nbsp;&nbsp;
                  <button type="submit" style="padding:6px 12px;">Save</button>
                </td>
              </tr>
            </tfoot>
          </table>
          <p style="color:#aaa;margin-top:8px;">Tip: total must be 100%.</p>
        </form>
        <script>
          function sumWeights(){
            const inputs = document.querySelectorAll('#weightsForm input[type="number"]');
            let t = 0;
            inputs.forEach(i => { t += parseFloat(i.value||'0'); });
            document.getElementById('totalSpan').textContent = (Math.round(t*100)/100).toString();
          }
          sumWeights();
        </script>
      <?php endif; ?>
    </div>
    <?php if (file_exists(__DIR__.'/footer.php')) require __DIR__.'/footer.php'; ?>
