<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin')     header('Location: ' . APP_URL . '/modules/admin/dashboard.php');
    elseif ($user['role'] === 'staff') header('Location: ' . APP_URL . '/modules/staff/dashboard.php');
    else                               header('Location: ' . APP_URL . '/modules/users/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Both email and password are required.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            if ($user['role_name'] === 'admin')     header('Location: ' . APP_URL . '/modules/admin/dashboard.php');
            elseif ($user['role_name'] === 'staff') header('Location: ' . APP_URL . '/modules/staff/dashboard.php');
            else                                    header('Location: ' . APP_URL . '/modules/users/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$savedEmail = htmlspecialchars($_POST['email'] ?? '');
$isDark     = ($_COOKIE['theme'] ?? 'light') === 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<style>
html, body { height: 100%; margin: 0; }

.auth-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg);
  transition: background 0.3s;
}

.auth-hero {
  background: linear-gradient(145deg, #12122b 0%, #0f3460 65%, #1a1a40 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2.5rem;
  position: relative;
  overflow: hidden;
}
.auth-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(45deg, transparent, transparent 38px, rgba(255,255,255,0.018) 38px, rgba(255,255,255,0.018) 39px);
  pointer-events: none;
}
.auth-hero::after {
  content: '';
  position: absolute;
  bottom: -80px; right: -80px;
  width: 320px; height: 320px;
  border-radius: 50%;
  background: rgba(230,57,70,0.08);
  pointer-events: none;
}
.hero-icon {
  font-size: 4.5rem; color: #e63946; margin-bottom: 1.75rem;
  position: relative; z-index: 1;
  filter: drop-shadow(0 0 32px rgba(230,57,70,0.35));
}
.auth-hero h2 {
  font-family: 'Syne', sans-serif; font-size: 2.1rem; font-weight: 800;
  color: #fff; text-align: center; line-height: 1.2; margin-bottom: 0.9rem;
  position: relative; z-index: 1;
}
.auth-hero p {
  color: rgba(255,255,255,0.58); text-align: center; font-size: 0.925rem;
  line-height: 1.75; max-width: 300px; position: relative; z-index: 1;
}
.hero-steps {
  margin-top: 2.5rem; display: flex; flex-direction: column; gap: 0.9rem;
  width: 100%; max-width: 300px; position: relative; z-index: 1;
}
.hero-step { display: flex; align-items: center; gap: 0.8rem; color: rgba(255,255,255,0.72); font-size: 0.875rem; }
.hero-step-dot {
  width: 30px; height: 30px; border-radius: 50%;
  background: rgba(230,57,70,0.18); border: 1px solid rgba(230,57,70,0.45);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; color: #e63946; flex-shrink: 0;
}

.auth-form-panel {
  display: flex; align-items: center; justify-content: center;
  padding: 3rem 2rem; transition: background 0.3s;
}

.auth-card {
  width: 100%; max-width: 390px;
  background: var(--bg-card);
  border-radius: 20px; padding: 2.5rem 2.25rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.1);
  border: 1px solid var(--border);
  transition: background 0.3s, border-color 0.3s, box-shadow 0.3s;
}
[data-theme="dark"] .auth-card {
  background: #1e2035; border-color: #2e3158;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}

.auth-logo { margin-bottom: 2rem; }
.logo-chip {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: rgba(230,57,70,0.08); border: 1px solid rgba(230,57,70,0.22);
  border-radius: 10px; padding: 0.5rem 0.9rem;
  font-size: 1.2rem; color: #e63946; margin-bottom: 1.1rem;
}
.auth-logo h1 { font-family: 'Syne', sans-serif; font-size: 1.55rem; font-weight: 800; color: var(--text); margin-bottom: 0.3rem; }
.auth-logo p  { color: var(--text-muted); font-size: 0.875rem; }

.auth-divider {
  display: flex; align-items: center; gap: 0.75rem;
  margin: 1.5rem 0; color: var(--text-muted); font-size: 0.8rem;
}
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.auth-theme-btn {
  position: fixed; top: 1.1rem; right: 1.1rem;
  background: var(--bg-card); border: 1px solid var(--border);
  color: var(--text-muted); width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 0.9rem; transition: all 0.2s; z-index: 999;
  box-shadow: var(--shadow);
}
.auth-theme-btn:hover { color: var(--accent); border-color: var(--accent); }

@media (max-width: 768px) {
  .auth-page { grid-template-columns: 1fr; }
  .auth-hero { display: none; }
  .auth-form-panel { padding: 2rem 1.25rem; align-items: flex-start; padding-top: 4rem; }
}
</style>
</head>
<body>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<button class="auth-theme-btn" id="authThemeToggle" title="Toggle dark mode">
  <i class="fa <?= $isDark ? 'fa-sun' : 'fa-moon' ?>" id="authThemeIcon"></i>
</button>

<div class="auth-page">

  <div class="auth-hero">
    <div class="hero-icon"><i class="fa-solid fa-road"></i></div>
    <h2>Report. Track.<br>Resolve.</h2>
    <p>A citizen portal for reporting road surface damage and tracking resolution in real-time.</p>
    <div class="hero-steps">
      <div class="hero-step">
        <div class="hero-step-dot"><i class="fa fa-file-alt"></i></div>
        File a road damage complaint with photos
      </div>
      <div class="hero-step">
        <div class="hero-step-dot"><i class="fa fa-user-plus"></i></div>
        Admin assigns to field staff instantly
      </div>
      <div class="hero-step">
        <div class="hero-step-dot"><i class="fa fa-check"></i></div>
        Track resolution status in real-time
      </div>
    </div>
  </div>

  <div class="auth-form-panel">
    <div class="auth-card">

      <div class="auth-logo">
        <div class="logo-chip">
          <i class="fa-solid fa-road"></i>
          <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;"><?= APP_NAME ?></span>
        </div>
        <h1>Welcome back</h1>
        <p>Sign in to your account to continue</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">

        <div class="float-group">
          <input type="email" id="email" name="email" placeholder=" "
                 value="<?= $savedEmail ?>" autocomplete="email" required>
          <label for="email">Email address</label>
        </div>

        <div class="float-group" style="margin-bottom:1.5rem;">
          <input type="password" id="password" name="password" placeholder=" "
                 autocomplete="current-password" required>
          <label for="password">Password</label>
          <button type="button" class="pwd-toggle" id="pwdToggle" tabindex="-1">
            <i class="fa fa-eye" id="pwdIcon"></i>
          </button>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content:center;border-radius:10px;">
          <i class="fa fa-sign-in-alt"></i> Sign In
        </button>
      </form>

      <div class="auth-divider">or</div>

      <p style="text-align:center;font-size:0.875rem;color:var(--text-muted);">
        Don't have an account?
        <a href="<?= APP_URL ?>/register.php" style="color:var(--accent);font-weight:700;">Register free</a>
      </p>
      <p style="text-align:center;margin-top:0.75rem;">
        <a href="<?= APP_URL ?>/index.php" style="font-size:0.8rem;color:var(--text-muted);">
          <i class="fa fa-arrow-left" style="font-size:0.7rem;"></i> Back to home
        </a>
      </p>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<script>
document.getElementById('pwdToggle').addEventListener('click', function () {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('pwdIcon');
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.classList.toggle('fa-eye',       !show);
  icon.classList.toggle('fa-eye-slash',  show);
});

document.getElementById('authThemeToggle').addEventListener('click', function () {
  const html = document.documentElement;
  const icon = document.getElementById('authThemeIcon');
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  icon.className = 'fa ' + (next === 'dark' ? 'fa-sun' : 'fa-moon');
  document.cookie = 'theme=' + next + ';path=/;max-age=' + (60*60*24*365);
  $.post(window.APP_URL + '/api/save_preference.php', { theme: next });
});
</script>
</body>
</html>