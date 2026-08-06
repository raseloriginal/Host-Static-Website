<?php
require_once __DIR__ . '/config/config.php';

ini_set('session.cookie_httponly', 1);
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['authenticated'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login – HostSW</title>
  <meta name="description" content="Sign in to your HostSW static website hosting dashboard.">
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    /* Login-page only tweaks */
    .input-icon-wrap { position: relative; }
    .input-icon-wrap .input-icon {
      position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
      color: var(--text-muted); pointer-events: none; font-size: .95rem;
    }
    .input-icon-wrap .form-input { padding-left: 2.4rem; }
    .toggle-pw {
      position: absolute; right: .85rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--text-muted);
      font-size: .95rem; padding: 0; transition: color .15s;
    }
    .toggle-pw:hover { color: var(--text-primary); }
  </style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-orbs">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
</div>

<main class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon">🚀</div>
      <div class="login-logo-text">Host<span>SW</span></div>
    </div>

    <h1 class="login-title">Welcome back</h1>
    <p class="login-subtitle">Sign in to manage your hosted websites</p>

    <div id="login-error" class="login-error hidden" role="alert">
      <span>⚠️</span>
      <span id="login-error-msg"></span>
    </div>

    <form id="login-form" class="login-form" novalidate autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-icon-wrap">
          <span class="input-icon">👤</span>
          <input
            class="form-input"
            type="text"
            id="username"
            name="username"
            placeholder="Enter your username"
            autocomplete="username"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">🔒</span>
          <input
            class="form-input"
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
            style="padding-right:2.8rem"
          >
          <button type="button" class="toggle-pw" id="toggle-pw" title="Toggle password visibility">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary login-submit" id="login-btn">
        Sign In
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;font-size:.78rem;color:var(--text-muted)">
      HostSW · Static Website Hosting Platform
    </p>
  </div>
</main>

<!-- Toast container -->
<div id="toast-container" class="toast-container"></div>

<script>
(function() {
  // Theme
  const t = localStorage.getItem('hostsw_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);

  // Password toggle
  const pw   = document.getElementById('password');
  const tBtn = document.getElementById('toggle-pw');
  tBtn.addEventListener('click', () => {
    pw.type = pw.type === 'password' ? 'text' : 'password';
    tBtn.textContent = pw.type === 'password' ? '👁️' : '🙈';
  });

  // Login form
  const form   = document.getElementById('login-form');
  const errBox = document.getElementById('login-error');
  const errMsg = document.getElementById('login-error-msg');
  const btn    = document.getElementById('login-btn');

  form.addEventListener('submit', async e => {
    e.preventDefault();
    errBox.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Signing in…';

    const fd = new FormData(form);
    try {
      const res  = await fetch('api/login.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        errMsg.textContent = data.error || 'Login failed.';
        errBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = 'Sign In';
        document.getElementById('password').value = '';
        document.getElementById('password').focus();
      }
    } catch {
      errMsg.textContent = 'Network error. Please try again.';
      errBox.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = 'Sign In';
    }
  });
})();
</script>
</body>
</html>
