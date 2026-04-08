<?php
// DO NOT include config or controller here. Controller will include this file.
// Expect $errors and $old_input arrays to be set by controller.

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

$token = function_exists('csrf_token')
    ? csrf_token()
    : ( $_SESSION['_token'] ?? ($_SESSION['_token'] = bin2hex(random_bytes(16))) );

$old = function(string $k) use ($old_input) {
    return htmlspecialchars($old_input[$k] ?? '', ENT_QUOTES, 'UTF-8');
};
$err = function(string $k) use ($errors) {
    return $errors[$k] ?? null;
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --bg:#0e1524;
      --card:#ffffff;
      --text:#0b1220;
      --muted:#6b7280;
      --brand:#1f6feb;
      --brand-dark:#1a5ed0;
      --danger:#b91c1c;
      --shadow:0 15px 35px rgba(0,0,0,.15), 0 5px 15px rgba(0,0,0,.08);
      --radius:16px;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; background:linear-gradient(180deg,#0b1220,#0f162c);
      font-family:system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
      color:var(--text); display:flex; align-items:center; justify-content:center; padding:24px;
    }
    .login-wrap{width:100%; max-width:420px}
    .card{
      background:var(--card); border-radius:var(--radius);
      box-shadow:var(--shadow); overflow:hidden;
    }
    .card-head{
      padding:22px 22px 6px 22px;
    }
    .title{margin:0; font-size:22px; font-weight:800; letter-spacing:.2px}
    .subtitle{margin:6px 0 0 0; color:var(--muted); font-size:13px}
    .card-body{padding:18px 22px 8px 22px}
    .field{margin:12px 0}
    label{display:block; font-size:13px; font-weight:600; margin-bottom:6px}
    input{
      width:100%; padding:11px 12px; border:1px solid #d9dde5; border-radius:12px;
      outline:none; font-size:14px;
    }
    input:focus{border-color:#b9c4f7; box-shadow:0 0 0 3px rgba(31,111,235,.12)}
    .error{margin-top:6px; color:var(--danger); font-size:12px}
    .alert{
      background:#fff1f2; color:#b91c1c; border:1px solid #fecdd3;
      border-radius:12px; padding:10px 12px; font-size:13px; margin:0 22px 10px 22px;
    }
    .actions{padding:10px 22px 20px 22px}
    .btn{
      width:100%; padding:12px 14px; border:none; border-radius:12px;
      background:#222; color:#fff; font-weight:700; font-size:14px; cursor:pointer;
      transition:filter .15s ease, transform .02s ease;
    }
    .btn:hover{filter:brightness(1.1)}
    .btn:active{transform:translateY(1px)}
    .links{
      display:flex; justify-content:space-between; gap:8px; margin-top:10px; font-size:13px;
    }
    .links a{color:var(--brand); text-decoration:none}
    .links a:hover{color:var(--brand-dark); text-decoration:underline}
    .footer-note{
      text-align:center; color:#cdd6f4; font-size:12px; margin-top:12px;
    }
  </style>
</head>
<body>
  <div class="login-wrap">
    <?php if (!empty($errors['login'])): ?>
      <div class="alert"><?php echo htmlspecialchars($errors['login'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head">
        <h1 class="title">Student Portal</h1>
        <p class="subtitle">Sign in with your Index Number and password</p>
      </div>

      <form method="post" class="card-body" autocomplete="on" novalidate>
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="field">
          <label for="index_no">Index Number</label>
          <input type="text" id="index_no" name="index_no" value="<?php echo $old('index_no'); ?>" placeholder="e.g. U001/2024">
          <?php if ($err('index_no')): ?>
            <div class="error"><?php echo htmlspecialchars($err('index_no'), ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Your password">
          <?php if ($err('password')): ?>
            <div class="error"><?php echo htmlspecialchars($err('password'), ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>

        <div class="actions">
          <button class="btn" type="submit">Login</button>
          <div class="links">
            <a href="<?php echo function_exists('base_url') ? base_url('auth/login.php') : '../auth/login.php'; ?>">Admin login</a>
            <a href="<?php echo function_exists('base_url') ? base_url('auth/forgot-password.php') : '../auth/forgot-password.php'; ?>">Forgot password?</a>
          </div>
        </div>
      </form>
    </div>

    <div class="footer-note">© <?php echo date('Y'); ?> — School Portal</div>
  </div>
</body>
</html>
