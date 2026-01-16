<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$page = 'profile';
$path_prefix = '../';
$base = "/Seng321";

if (!isset($_SESSION['user'])) {
    header("Location: {$base}/login_part/index.php");
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$message = "";
$error = "";

$stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: {$base}/login_part/logout.php");
    exit;
}
if (isset($_POST['update_info'])) {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = "Name cannot be empty.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);

        $_SESSION['user']['name'] = $name;
        $user['name'] = $name;

        $message = "Profile updated successfully.";
    }
}

if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        $message = "Password successfully changed.";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="profile-page">
  <div class="page-title">
    <h2>My Profile</h2>
    <p class="muted">Update your personal information and password.</p>
  </div>
<br>
  <?php if ($message): ?>
    <div class="alert success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="profile-grid">
    <section class="card">
      <div class="card-header">
        <h3>Personal Information</h3>
      </div>

      <form method="POST" class="form">
        <div class="form-row">
          <label class="form-label">Full Name</label>
          <input class="form-input" type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>

        <div class="form-row">
          <label class="form-label">Email (cannot be changed)</label>
          <input class="form-input" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>
<br>
        <button class="btn primary" type="submit" name="update_info">Update Profile</button>
      </form>
    </section>

    <section class="card">
      <div class="card-header">
        <h3>Change Password</h3>
      </div>

      <form method="POST" class="form">
        <div class="form-row">
          <label class="form-label">Current password</label>
          <input class="form-input" type="password" name="current_password" required>
        </div>

        <div class="form-row">
          <label class="form-label">New password</label>
          <input class="form-input" type="password" name="new_password" required>
        </div>

        <div class="form-row">
          <label class="form-label">Confirm new password</label>
          <input class="form-input" type="password" name="confirm_password" required>
        </div>
<br>
        <button class="btn" type="submit" name="change_password">Change Password</button>
      </form>
    </section>
  </div>

  <div class="profile-footer">
    <a class="link" href="<?= $base ?>/dashboard/learner.php">← Back to Dashboard</a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
