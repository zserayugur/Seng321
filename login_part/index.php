<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Authentication</title>

  <link rel="stylesheet" href="login.css">

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
  <form id="login" class="form" method="POST" action="login.php">
    <h2>Login</h2>

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

    <select name="role">
    <option value="learner">Learner</option>
    <option value="instructor">Instructor</option>
    <option value="admin">Admin</option>
    </select>

    <button type="submit">Login</button>
  </form>

  <!-- REGISTER FORM -->
  <form id="register" class="form hidden" method="POST" action="register.php">
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

    <select name="role">
      <option value="learner">Learner</option>
      <option value="instructor">Instructor</option>
    </select>

    <button type="submit">Create Account</button>
  </form>

  <form id="forgot" class="form hidden" method="POST" action="forgot_password.php">
    <h2>Forgot Password</h2>

    <input
      type="email"
      name="email"
      placeholder="Enter your email"
      required>

    <button type="submit">Send Reset Link</button>
  </form>

</div>

<script>
  function showForm(formId) {
    console.log("Switching to:", formId);

    document.querySelectorAll('.form').forEach(function(form) {
      form.classList.add('hidden');
    });

    document.getElementById(formId).classList.remove('hidden');
  }
</script>

</body>
</html>
