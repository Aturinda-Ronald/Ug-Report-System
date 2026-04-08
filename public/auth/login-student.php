<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

/* ---------- small helper to get /public/ root URL (fixes 404) ---------- */
function public_root_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script= $_SERVER['SCRIPT_NAME'] ?? '/';
    $pos   = strpos($script, '/public/');
    $base  = $pos !== false ? substr($script, 0, $pos + 8) : rtrim(dirname($script), '/\\') . '/';

    // Fix: Ensure base path includes /public/ even when not in script name
    if (!str_ends_with($base, '/public/')) {
        $base = rtrim($base, '/') . '/public/';
    }

    return $https . '://' . $host . $base;        // ends with '/public/'
}
function go_student_home(): void {
    header('Location: ' . public_root_url() . 'student/');
    exit;
}

/* ---------- already logged in? ---------- */
if (function_exists('is_logged_in') && is_logged_in() && (get_user_role() === 'STUDENT')) {
    go_student_home();
}

/* ---------- POST handling ---------- */
$errors   = [];
$email    = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF (use your helpers if present; otherwise skip gracefully)
    if (function_exists('verify_csrf_token')) {
        $ok = verify_csrf_token($_POST['_token'] ?? '');
        if (!$ok) $errors['login'] = 'Security token invalid. Please try again.';
    }

    // Input
    $email    = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = (string)($_POST['password'] ?? '');

    // Validate
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    // Auth
    if (!$errors) {
        try {
            /** @var PDO $db */
            $db = Database::getInstance();

            // Get user + linked student (if any)
            $sql = "
                SELECT
                    u.id AS user_id, u.email, u.password_hash, u.role, u.is_active, u.last_login,
                    s.id AS student_id, s.school_id, s.first_name, s.last_name, s.status AS student_status,
                    sc.name AS school_name
                FROM users u
                LEFT JOIN students s ON s.user_id = u.id
                LEFT JOIN schools  sc ON sc.id = s.school_id
                WHERE u.email = :em
                LIMIT 1
            ";
            $st = $db->prepare($sql);
            $st->execute([':em' => $email]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $errors['login'] = 'Invalid email or password.';
            } elseif ((int)$row['is_active'] !== 1) {
                $errors['login'] = 'Your account is inactive. Please contact the school.';
            } elseif (empty($row['password_hash']) || !password_verify($password, $row['password_hash'])) {
                $errors['login'] = 'Invalid email or password.';
            } else {
                // If the role is not explicitly STUDENT but there is a student row, treat as student
                $isStudent = ($row['role'] === 'STUDENT') || !empty($row['student_id']);
                if (!$isStudent) {
                    $errors['login'] = 'This account is not a student account.';
                } elseif (!empty($row['student_id']) && strtoupper((string)$row['student_status']) !== 'ACTIVE') {
                    $errors['login'] = 'Your student record is not active.';
                } else {
                    // Success: set session
                    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                    session_regenerate_id(true);

                    $_SESSION['user_id']       = (int)$row['user_id'];
                    $_SESSION['user_email']    = (string)$row['email'];
                    $_SESSION['user_role']     = 'STUDENT';
                    $_SESSION['student_id']    = $row['student_id'] ? (int)$row['student_id'] : null;
                    $_SESSION['school_id']     = $row['school_id']  ? (int)$row['school_id']  : null;
                    $_SESSION['school_name']   = $row['school_name'] ?? '';
                    $_SESSION['user_name']     = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $_SESSION['last_activity'] = time();

                    // Update last_login if column exists
                    try {
                        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")->execute([':id' => (int)$row['user_id']]);
                    } catch (Throwable $e) { /* ignore */ }

                    // (Optional) activity log if your table exists
                    try {
                        $db->prepare("
                            INSERT INTO activity_logs (school_id, user_id, action, description, ip_address, user_agent)
                            VALUES (:sid, :uid, 'LOGIN', 'Student logged in via email', :ip, :ua)
                        ")->execute([
                            ':sid' => $row['school_id'] ?? null,
                            ':uid' => (int)$row['user_id'],
                            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                            ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } catch (Throwable $e) { /* ignore */ }

                    go_student_home();
                }
            }
        } catch (Throwable $e) {
            error_log('Student email login error: ' . $e->getMessage());
            $errors['login'] = 'A system error occurred. Please try again.';
        }
    }
}

// Simple view (compact CSS, uses your CSRF generator if available)
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$token = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root { --bg:#0d1320; --card:#fff; --text:#0b1220; --muted:#6b7280; --primary:#111827; --primaryHover:#1f2937; --ring:#94a3b8; --err:#b91c1c; }
    *{box-sizing:border-box} body{margin:0;background:var(--bg);font:16px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial}
    .wrap{min-height:100dvh;display:grid;place-items:center;padding:24px}
    .card{width:100%;max-width:460px;background:var(--card);border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.25);padding:26px}
    h1{margin:0 0 4px;font-size:22px;color:var(--text)} p.lead{margin:0 0 18px;color:var(--muted);font-size:14px}
    .field{margin:12px 0}
    label{display:block;margin:0 0 6px;font-size:12px;color:#374151}
    input{width:100%;padding:12px 12px;border-radius:10px;border:1px solid #d1d5db;outline:none}
    input:focus{border-color:var(--ring);box-shadow:0 0 0 3px rgba(148,163,184,.35)}
    .err{color:var(--err);font-size:12px;margin-top:6px}
    .actions{margin-top:14px}
    .btn{width:100%;padding:12px;border-radius:10px;border:0;background:var(--primary);color:#fff;font-weight:700;cursor:pointer}
    .btn:hover{background:var(--primaryHover)}
    .links{display:flex;justify-content:space-between;margin-top:10px}
    .links a{color:#2563eb;text-decoration:none;font-size:14px}
    .links a:hover{text-decoration:underline}
    footer{margin-top:14px;text-align:center;color:#cbd5e1;font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <form class="card" method="post" novalidate>
      <h1>Student Portal</h1>
      <p class="lead">Sign in with your email and password.</p>

      <?php if (!empty($errors['login'])): ?>
        <div class="err" role="alert"><?= e($errors['login']) ?></div>
      <?php endif; ?>

      <input type="hidden" name="_token" value="<?= e($token) ?>">

      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email" placeholder="you@example.com">
        <?php if (!empty($errors['email'])): ?><div class="err"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password" placeholder="Your password">
        <?php if (!empty($errors['password'])): ?><div class="err"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>

      <div class="actions"><button class="btn" type="submit">Login</button></div>

      <div class="links">
        <a href="<?= e(public_root_url() . 'auth/login-admin.php') ?>">Admin login</a>
        <a href="<?= e(public_root_url() . 'auth/forgot-password.php') ?>">Forgot password?</a>
      </div>

      <footer>© <?= date('Y') ?> — School Portal</footer>
    </form>
  </div>
</body>
</html>
