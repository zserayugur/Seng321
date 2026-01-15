<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: /SENG321/login_part/index.php");
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$plainPassword = $_POST['password'];
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  INSERT INTO users (name, email, role, password_hash, password_plain)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $email, $role, $hash, $plainPassword]);

?>
<?php if (isset($_GET['success']) && $_GET['success'] === 'password'): ?>
  <p style="color: green;">Password successfully changed.</p>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'info'): ?>
  <p style="color: green;">Profile updated successfully.</p>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'current'): ?>
  <p style="color: red;">Current password is incorrect.</p>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'pwd'): ?>
  <p style="color: red;">New passwords do not match or are too short.</p>
<?php endif; ?>


<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
</head>
<body>

<h2>My Profile</h2>

<form method="POST" action="/SENG321/actions/update_profile.php">
    <h3>Personal Information</h3>

    <label>Full Name</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br><br>

    <label>Email (cannot be changed)</label><br>
    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled><br><br>

    <button type="submit" name="update_info">Update Profile</button>
</form>

<hr>

<form method="POST" action="/SENG321/actions/update_profile.php">
    <h3>Change Password</h3>

    <input type="password" name="current_password" placeholder="Current password" required><br><br>
    <input type="password" name="new_password" placeholder="New password" required><br><br>
    <input type="password" name="confirm_password" placeholder="Confirm new password" required><br><br>

    <button type="submit" name="change_password">Change Password</button>
</form>

<br>
<a href="/SENG321/pages/speaking.php">← Back to Dashboard</a>

</body>
</html>
