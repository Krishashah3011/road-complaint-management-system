<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/index.php'); exit; }

$error = $success = '';
$post  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post['name']  = sanitize($_POST['name']  ?? '');
    $post['email'] = sanitize($_POST['email'] ?? '');
    $post['phone'] = sanitize($_POST['phone'] ?? '');
    $password      = $_POST['password']         ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';

    if (!$post['name'] || !$post['email'] || !$password || !$confirm) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$post['email']]);
        if ($chk->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name, email, phone, password, role_id) VALUES (?,?,?,?,3)")
               ->execute([$post['name'], $post['email'], $post['phone'], $hash]);
            $success = 'Account created! You can now sign in.';
            $post    = [];
        }
    }
}

$isDark = ($_COOKIE['theme'] ?? 'light') === 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | <?= APP_NAME ?></title>
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
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 3rem 2.5rem; position: relative; overflow: hidden;
}
.auth-hero::before {
  content: ''; position: absolute; inset: 0;
  background: repeating-linear-gradient(45deg, transparent, transparent 38px, rgba(255,255,255,0.018) 38px, rgba(255,255,255,0.018) 39px);
  pointer-events: none;
}
.auth-hero::after {
  content: ''; position: absolute; top: -80px; left: -80px;
  width: 280px; height: 280px; border-radius: 50%;
  background: rgba(230,57,70,0.07); pointer-events: none;
}
.hero-icon { font-size: 4rem; color: #e63946; margin-bottom: 1.5rem; position: relative; z-index:1; filter: drop-shadow(0 0 28px rgba(230,57,70,0.3)); }
.auth-hero h2 { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:#fff; text-align:center; line-height:1.2; margin-bottom:0.9rem; position:relative; z-index:1; }
.auth-hero p  { color:rgba(255,255,255,0.58); text-align:center; font-size:0.9rem; line-height:1.75; max-width:300px; position:relative; z-index:1; }

.auth-benefits { margin-top:2rem; display:flex; flex-direction:column; gap:0.75rem; max-width:290px; width:100%; position:relative; z-index:1; }
.benefit-row { display:flex; align-items:center; gap:0.75rem; color:rgba(255,255,255,0.72); font-size:0.875rem; }
.benefit-icon { width:28px; height:28px; border-radius:8px; background:rgba(230,57,70,0.18); border:1px solid rgba(230,57,70,0.35); display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:#e63946; flex-shrink:0; }

.auth-form-panel {
  display: flex; align-items: center; justify-content: center;
  padding: 3rem 2rem; overflow-y: auto;
}

.auth-card {
  width: 100%; max-width: 420px;
  background: var(--bg-card); border-radius: 20px; padding: 2.5rem 2.25rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.1); border: 1px solid var(--border);
  transition: background 0.3s, border-color 0.3s;
}
[data-theme="dark"] .auth-card { background: #1e2035; border-color: #2e3158; box-shadow: 0 20px 60px rgba(0,0,0,0.6); }

.auth-logo { margin-bottom: 1.75rem; }
.logo-chip {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: rgba(230,57,70,0.08); border: 1px solid rgba(230,57,70,0.22);
  border-radius: 10px; padding: 0.5rem 0.9rem; font-size: 1.1rem; color: #e63946; margin-bottom: 1rem;
}
.auth-logo h1 { font-family:'Syne',sans-serif; font-size:1.45rem; font-weight:800; color:var(--text); margin-bottom:0.25rem; }
.auth-logo p  { color:var(--text-muted); font-size:0.875rem; }

.auth-divider { display:flex; align-items:center; gap:0.75rem; margin:1.5rem 0; color:var(--text-muted); font-size:0.8rem; }
.auth-divider::before, .auth-divider::after { content:''; flex:1; height:1px; background:var(--border); }

.auth-theme-btn {
  position: fixed; top:1.1rem; right:1.1rem;
  background: var(--bg-card); border: 1px solid var(--border);
  color: var(--text-muted); width:40px; height:40px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; font-size:0.9rem; transition:all 0.2s; z-index:999; box-shadow: var(--shadow);
}
.auth-theme-btn:hover { color:var(--accent); border-color:var(--accent); }

@media (max-width: 768px) {
  .auth-page { grid-template-columns: 1fr; }
  .auth-hero { display: none; }
  .auth-form-panel { padding: 2rem 1.25rem; align-items:flex-start; padding-top:4rem; }
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
    <div class="hero-icon"><i class="fa-solid fa-user-shield"></i></div>
    <h2>Join as a<br>Citizen Reporter</h2>
    <p>Create your free account and start making your roads safer for everyone.</p>
    <div class="auth-benefits">
      <div class="benefit-row"><div class="benefit-icon"><i class="fa fa-file-alt"></i></div>File unlimited road complaints with proof</div>
      <div class="benefit-row"><div class="benefit-icon"><i class="fa fa-bell"></i></div>Get real-time status updates on your cases</div>
      <div class="benefit-row"><div class="benefit-icon"><i class="fa fa-map-marker-alt"></i></div>Track issues ward-wise and area-wise</div>
      <div class="benefit-row"><div class="benefit-icon"><i class="fa fa-shield-halved"></i></div>Your data is safe and private</div>
    </div>
  </div>

  <div class="auth-form-panel">
    <div class="auth-card">

      <div class="auth-logo">
        <div class="logo-chip">
          <i class="fa-solid fa-road"></i>
          <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;"><?= APP_NAME ?></span>
        </div>
        <h1>Create your account</h1>
        <p>Free for all citizens — takes under a minute</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:1.25rem;">
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        <a href="<?= APP_URL ?>/login.php" style="font-weight:700;margin-left:0.4rem;">Sign in →</a>
      </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">

        <div class="float-group">
          <input type="text" id="name" name="name" placeholder=" "
                 value="<?= htmlspecialchars($post['name'] ?? '') ?>" autocomplete="name" required>
          <label for="name">Full name <span style="color:var(--accent);">*</span></label>
        </div>

        <div class="float-group">
          <input type="email" id="email" name="email" placeholder=" "
                 value="<?= htmlspecialchars($post['email'] ?? '') ?>" autocomplete="email" required>
          <label for="email">Email address <span style="color:var(--accent);">*</span></label>
        </div>

        <div class="float-group">
          <input type="tel" id="phone" name="phone" placeholder=" "
                 value="<?= htmlspecialchars($post['phone'] ?? '') ?>" autocomplete="tel" maxlength="15">
          <label for="phone">Phone number (optional)</label>
        </div>

        <div class="float-row">
          <div class="float-group">
            <input type="password" id="password" name="password" placeholder=" "
                   autocomplete="new-password" required>
            <label for="password">Password <span style="color:var(--accent);">*</span></label>
            <button type="button" class="pwd-toggle" id="pwdToggle" tabindex="-1">
              <i class="fa fa-eye" id="pwdIcon"></i>
            </button>
          </div>
          <div class="float-group">
            <input type="password" id="confirm_password" name="confirm_password" placeholder=" "
                   autocomplete="new-password" required>
            <label for="confirm_password">Confirm <span style="color:var(--accent);">*</span></label>
          </div>
        </div>

        <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:1.25rem;margin-top:-0.25rem;">
          <i class="fa fa-info-circle"></i> Minimum 6 characters
        </p>

        <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content:center;border-radius:10px;">
          <i class="fa fa-user-plus"></i> Create Account
        </button>
      </form>

      <div class="auth-divider">or</div>

      <p style="text-align:center;font-size:0.875rem;color:var(--text-muted);">
        Already have an account?
        <a href="<?= APP_URL ?>/login.php" style="color:var(--accent);font-weight:700;">Sign in</a>
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

document.getElementById('confirm_password').addEventListener('input', function () {
  const pw = document.getElementById('password').value;
  const ok = this.value === pw && this.value.length > 0;
  this.style.borderColor = this.value.length === 0 ? '' : (ok ? '#16a34a' : '#dc2626');
});
</script>
</body>
</html>