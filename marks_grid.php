\
    <?php
/**
     * Teacher Marks Grid
     * PATH: /marks_grid.php
     *
     * - Select Term + Class (+ optional Stream) + Subject
     * - Shows students x assessment components
     * - Save writes (delete-then-insert) marks for each cell
     * - "Recalculate" calls /api/recompute_subject_total.php to show current weighted % per student
     */
    require_once __DIR__ . '/config/config.php';

    // --- Guard: allow SCHOOL_ADMIN and STAFF/TEACHER ---
    @session_start();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['SCHOOL_ADMIN','SUPER_ADMIN','STAFF'], true)) {
        http_response_code(403);
        die('Access denied.');
    }

    // helpers
    function current_school_id(): int {
        if (function_exists('current_user_school_id')) return (int) current_user_school_id();
        @session_start();
        return (int)($_SESSION['school_id'] ?? 0);
    }
    $school_id = current_school_id();

    // incoming selectors
    $term_id    = (int)($_GET['term_id']    ?? 0);
    $class_id   = (int)($_GET['class_id']   ?? 0);
    $stream_id  = (int)($_GET['stream_id']  ?? 0);
    $subject_id = (int)($_GET['subject_id'] ?? 0);

    // ----- Save handler -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
        $term_id    = (int)($_POST['term_id']    ?? 0);
        $class_id   = (int)($_POST['class_id']   ?? 0);
        $stream_id  = (int)($_POST['stream_id']  ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $marks      = $_POST['marks'] ?? []; // marks[student_id][assessment_type_id] = "got|pos"

        if ($term_id && $class_id && $subject_id) {
            pdo()->beginTransaction();

            // Build delete list of affected (subject, term, students)
            $student_ids = array_keys($marks);
            if (!empty($student_ids)) {
                $in = implode(',', array_fill(0, count($student_ids), '?'));
                $del = pdo()->prepare("DELETE FROM marks WHERE subject_id=? AND term_id=? AND student_id IN ($in)");
                $params = array_merge([$subject_id, $term_id], array_map('intval',$student_ids));
                $del->execute($params);
            }

            // Insert all filled cells
            $ins = pdo()->prepare("
                INSERT INTO marks (school_id, student_id, subject_id, assessment_type_id, term_id, marks_obtained, marks_possible, percentage, entered_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $entered_by = (int)($_SESSION['user_id'] ?? null);
            foreach ($marks as $sid => $row) {
                $sid = (int)$sid;
                foreach ($row as $aid => $val) {
                    $aid = (int)$aid;
                    if ($val === '') continue;
                    $parts = explode('|', (string)$val, 2);
                    $got = (float)($parts[0] ?? 0);
                    $pos = max(1.0, (float)($parts[1] ?? 100));
                    $pct = ($got / $pos) * 100.0;
                    if ($pct < 0) $pct = 0; if ($pct > 100) $pct = 100;
                    $ins->execute([$school_id, $sid, $subject_id, $aid, $term_id, $got, $pos, round($pct,2), $entered_by ?: null]);
                }
            }

            pdo()->commit();
            header("Location: marks_grid.php?term_id=$term_id&class_id=$class_id&stream_id=$stream_id&subject_id=$subject_id&ok=1");
            exit;
        } else {
            header("Location: marks_grid.php?error=Missing+selectors");
            exit;
        }
    }

    // ---- Load dropdowns ----
    $terms = (function() use($school_id){
        $st = pdo()->prepare("SELECT id, name FROM terms WHERE school_id=? ORDER BY id DESC");
        $st->execute([$school_id]); return $st->fetchAll();
    })();
    $classes = (function() use($school_id){
        $st = pdo()->prepare("SELECT id, name FROM classes WHERE school_id=? ORDER BY name");
        $st->execute([$school_id]); return $st->fetchAll();
    })();
    $streams = [];
    if ($class_id) {
        $st = pdo()->prepare("SELECT id, name FROM streams WHERE class_id=? ORDER BY name");
        $st->execute([$class_id]); $streams = $st->fetchAll();
    }
    $subjects = (function() use($school_id){
        $st = pdo()->prepare("SELECT id, code, name FROM subjects WHERE school_id=? ORDER BY name");
        $st->execute([$school_id]); return $st->fetchAll();
    })();

    // ---- Load matrix: students x assessment types ----
    $students = [];
    $assessments = [];
    $existing = []; // [student_id][assessment_type_id] => ['got'=>..., 'pos'=>...]
    if ($term_id && $class_id && $subject_id) {
        $params = [$school_id, $class_id];
        $q = "SELECT id, index_no, name, class_id, stream_id FROM students WHERE school_id=? AND class_id=?";
        if ($stream_id) { $q .= " AND stream_id=?"; $params[] = $stream_id; }
        $q .= " ORDER BY name";
        $st = pdo()->prepare($q); $st->execute($params); $students = $st->fetchAll();

        $st = pdo()->prepare("SELECT id, code, name FROM assessment_types WHERE school_id=? ORDER BY name");
        $st->execute([$school_id]); $assessments = $st->fetchAll();

        if ($students) {
            $in = implode(',', array_fill(0, count($students), '?'));
            $ids = array_map(fn($r)=>(int)$r['id'], $students);
            $sql = "SELECT student_id, assessment_type_id, marks_obtained, marks_possible
                    FROM marks
                    WHERE school_id=? AND subject_id=? AND term_id=? AND student_id IN ($in)";
            $st2 = pdo()->prepare($sql);
            $params2 = array_merge([$school_id, $subject_id, $term_id], $ids);
            $st2->execute($params2);
            while ($r = $st2->fetch()) {
                $existing[(int)$r['student_id']][(int)$r['assessment_type_id']] = [
                    'got' => (float)$r['marks_obtained'],
                    'pos' => (float)$r['marks_possible'],
                ];
            }
        }
    }

    if (file_exists(__DIR__.'/header.php')) require __DIR__.'/header.php';
    ?>
    <div class="container" style="max-width:1200px;margin:24px auto;padding:16px;background:#1112;border:1px solid #222;border-radius:12px;">
      <h1 style="margin:0 0 12px;">Marks Grid</h1>
      <?php if (!empty($_GET['ok'])): ?>
        <div style="background:#133; border:1px solid #4aa; padding:8px; margin-bottom:12px; color:#7fd;">Saved.</div>
      <?php endif; ?>
      <?php if (!empty($_GET['error'])): ?>
        <div style="background:#331; border:1px solid #a44; padding:8px; margin-bottom:12px; color:#f88;"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
        <label>Term
          <select name="term_id" onchange="this.form.submit()">
            <option value="">-- term --</option>
            <?php foreach ($terms as $t): ?>
              <option value="<?= (int)$t['id'] ?>" <?= $term_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Class
          <select name="class_id" onchange="this.form.submit()">
            <option value="">-- class --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $class_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Stream
          <select name="stream_id" onchange="this.form.submit()">
            <option value="">-- any --</option>
            <?php foreach ($streams as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $stream_id==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Subject
          <select name="subject_id" onchange="this.form.submit()">
            <option value="">-- subject --</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $subject_id==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['code'].' - '.$s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <noscript><button>Load</button></noscript>
      </form>

      <?php if ($term_id && $class_id && $subject_id): ?>
        <form method="post" id="marksForm">
          <input type="hidden" name="term_id" value="<?= (int)$term_id ?>">
          <input type="hidden" name="class_id" value="<?= (int)$class_id ?>">
          <input type="hidden" name="stream_id" value="<?= (int)$stream_id ?>">
          <input type="hidden" name="subject_id" value="<?= (int)$subject_id ?>">

          <div style="overflow:auto;">
            <table style="width:max-content; min-width:100%; border-collapse:collapse;">
              <thead>
                <tr style="border-bottom:1px solid #333; background:#0a0a0a;">
                  <th style="padding:8px; position:sticky; left:0; background:#0a0a0a; z-index:2;">Student</th>
                  <?php foreach ($assessments as $a): ?>
                    <th style="padding:8px; white-space:nowrap; text-align:center;"><?= htmlspecialchars($a['code']) ?><br><small><?= htmlspecialchars($a['name']) ?></small><br><small>got/pos</small></th>
                  <?php endforeach; ?>
                  <th style="padding:8px; text-align:center;">Weighted %</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $st): $sid=(int)$st['id']; ?>
                  <tr style="border-bottom:1px solid #222;">
                    <td style="padding:8px; position:sticky; left:0; background:#0d0d0d; z-index:1;">
                      <div><strong><?= htmlspecialchars($st['name']) ?></strong></div>
                      <div style="color:#aaa; font-size:12px;"><?= htmlspecialchars($st['index_no']) ?></div>
                    </td>
                    <?php foreach ($assessments as $a): $aid=(int)$a['id'];
                      $got = $existing[$sid][$aid]['got'] ?? '';
                      $pos = $existing[$sid][$aid]['pos'] ?? '';
                    ?>
                      <td style="padding:6px; text-align:center;">
                        <input type="text" inputmode="decimal" style="width:60px" placeholder="got"
                          name="marks[<?= $sid ?>][<?= $aid ?>]_got" value="<?= htmlspecialchars($got === '' ? '' : (string)$got) ?>">
                        <span>/</span>
                        <input type="text" inputmode="decimal" style="width:60px" placeholder="pos"
                          name="marks[<?= $sid ?>][<?= $aid ?>]_pos" value="<?= htmlspecialchars($pos === '' ? '' : (string)$pos) ?>">
                      </td>
                    <?php endforeach; ?>
                    <td style="padding:6px; text-align:center;">
                      <button type="button" class="recalc" data-sid="<?= $sid ?>">Recalc</button>
                      <span id="pct-<?= $sid ?>" style="margin-left:6px; color:#9cf;"></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Hidden serializer: converts *_got/_pos pairs to single "got|pos" fields for PHP -->
          <input type="hidden" name="save_marks" value="1">
          <div style="margin-top:12px; display:flex; gap:8px;">
            <button type="submit" onclick="serializeMarks()">Save Marks</button>
          </div>
        </form>

        <script>
          function serializeMarks(){
            const form = document.getElementById('marksForm');
            // Remove previous compact fields
            [...form.querySelectorAll('input[name^=\"marks[\"][name$=\"]\"]')].forEach(e=>e.remove());
            const gotInputs = form.querySelectorAll('input[name$=\"_got\"]');
            gotInputs.forEach(got => {
              const pos = form.querySelector('input[name=\"'+got.name.replace('_got','_pos')+'\"]');
              const compactName = got.name.replace('_got','').replace('][','][').replace(']','').replace('marks[','marks[');
              const m = document.createElement('input');
              m.type='hidden'; m.name=compactName; m.value = (got.value||'') + '|' + (pos && pos.value ? pos.value : '');
              form.appendChild(m);
            });
          }

          function recalcFor(studentId){
            const form = document.getElementById('marksForm');
            const url = 'api/recompute_subject_total.php?student_id='+studentId+
                        '&subject_id='+form.subject_id.value+
                        '&term_id='+form.term_id.value;
            fetch(url).then(r=>r.json()).then(d=>{
              const el = document.getElementById('pct-'+studentId);
              el.textContent = (d && typeof d.percent !== 'undefined') ? (d.percent + '%') : '—';
            }).catch(()=>{
              document.getElementById('pct-'+studentId).textContent = '—';
            });
          }

          document.querySelectorAll('button.recalc').forEach(btn=>{
            btn.addEventListener('click', ()=>recalcFor(btn.dataset.sid));
          });
        </script>
      <?php else: ?>
        <div style="color:#aaa;">Pick term, class (and optional stream), and subject to load the grid.</div>
      <?php endif; ?>
    </div>
    <?php if (file_exists(__DIR__.'/footer.php')) require __DIR__.'/footer.php'; ?>
