<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Authentication</title>

  <link rel="stylesheet" href="login.css?v=<?= time() ?>">

  <style>
    .hidden {
      display: none !important;
    }
    .auth-error {
      color: red;
      font-size: 14px;
      margin-bottom: 10px;
    }
    .auth-success {
      color: green;
      font-size: 14px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="container">
  <h1>Welcome to the Platform</h1>

  <div class="tabs">
    <button type="button" onclick="showForm('login')">Login</button>
    <button type="button" onclick="showForm('register')">Register</button>
    <button type="button" onclick="showForm('forgot')">Forgot Password</button>
  </div>

  <!-- LOGIN FORM -->
  <?php
  require_once __DIR__ . '/../includes/base_path.php';
  $basePath = get_base_path();
  ?>
  <form id="login" class="form" method="POST" action="<?php echo htmlspecialchars($basePath); ?>/login_part/login.php">
    <h2>Login</h2>

    <?php if (isset($_GET['registered'])): ?>
      <p class="auth-success">Registered successfully. Please log in.</p>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && ($_GET['tab'] ?? 'login') === 'login'): ?>
      <p class="auth-error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>

    <input
      type="email"
      name="email"
      placeholder="Email"
      required
      value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">

    <input
      type="password"
      name="password"
      placeholder="Password"
      required>

    <select name="role" required>
      <option value="LEARNER">Learner</option>
      <option value="INSTRUCTOR">Instructor</option>
      <option value="ADMIN">Admin</option>
    </select>

    <button type="submit">Login</button>
  </form>

  <!-- REGISTER FORM -->
  <form id="register" class="form hidden" method="POST" action="<?php echo htmlspecialchars($basePath); ?>/login_part/register.php">
    <h2>Register</h2>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'weak_password'): ?>
      <p class="auth-error">
        Password must be at least 8 characters long and include
        at least one uppercase letter and one number.
      </p>
    <?php endif; ?>

    <input
      type="text"
      name="full_name"
      placeholder="Full Name">

    <input
      type="email"
      name="email"
      placeholder="Email"
      required>

    <input
      type="password"
      name="password"
      placeholder="Password"
      required
      pattern="^(?=.*[A-Z])(?=.*\d).{8,}$"
      title="Password must be at least 8 characters long and include at least one uppercase letter and one number">

      <small class="password-hint" style=" opacity : 0.7; font-size: 12px;">
  Password must be at least 8 characters, include 1 uppercase letter and 1 number.
  </small> 
    <select name="role" required>
      <option value="LEARNER">Learner</option>
      <option value="INSTRUCTOR">Instructor</option>
    </select>

    <button type="submit">Create Account</button>
  </form>

  <!-- FORGOT PASSWORD FORM -->
  <form id="forgot" class="form hidden" method="POST" action="<?php echo htmlspecialchars($basePath); ?>/actions/forgot_password.php">
    <h2>Forgot Password</h2>

    <input
      type="email"
      name="email"
      placeholder="Email"
      value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
      required>

    <button type="submit">Send Reset Link</button>
  </form>

</div>

<script>
  function showForm(formId) {
    document.querySelectorAll('.form').forEach(form => {
      form.classList.add('hidden');
    });
    document.getElementById(formId).classList.remove('hidden');
  }

  // URL'den tab parametresi varsa otomatik aç
  (function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'login';
    showForm(tab);
  })();
</script>

</body>
</html>
