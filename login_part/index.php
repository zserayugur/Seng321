
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
  <form id="login" class="form" method="POST" action="/Seng321/login_part/login.php">
    <h2>Login</h2>

    <?php if (isset($_GET['registered'])): ?>
  <p class="auth-success">Registered successfully. Please log in.</p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
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
  <form id="register" class="form hidden" method="POST" action="/Seng321/login_part/register.php">
    <h2>Register</h2>

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
      required>

    <select name="role" required>
  <option value="LEARNER">Learner</option>
  <option value="INSTRUCTOR">Instructor</option>
</select>

    <button type="submit">Create Account</button>
  </form>


 <form id="forgot" class="form hidden" method="POST" action="/Seng321/actions/forgot_password.php">



    <!-- FORGOT PASSWORD FORMDUR GIRLS-->
    <h2>Forgot Password</h2>

    <input
      type="email"
      name="email"
       value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
       placeholder="Email" required>

    <button type="submit">Send Reset Link</button>
  </form>

</div>

<script>
  function showForm(formId) {
    document.querySelectorAll('.form').forEach(function(form) {
      form.classList.add('hidden');
    });
    document.getElementById(formId).classList.remove('hidden');
  }

  // ✅ URL tab parametresi varsa onu aç
  (function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'login';
    showForm(tab);
  })();
</script>

</body>
</html>
