<?php
$page = 'profile';
$path_prefix = '../';

require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$userId = (int)($_SESSION["user"]["id"] ?? 0);
if ($userId <= 0) {
  header("Location: /Seng321/login_part/index.php");
  exit;
}

$success = "";
$error = "";

// 1) Mevcut kullanıcıyı DB'den çek
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$current = $stmt->fetch();

if (!$current) {
  $error = "User not found.";
} else {

  // 2) Form gönderildiyse (POST) güncelle
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));

    // Role'ü kullanıcı değiştirmesin (güvenlik) -> DB'deki rolü koru
    $role  = $current["role"];

    // Validasyon
    if ($name === "") {
      $error = "Name cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Invalid email.";
    } else {
      // Email başka kullanıcıda var mı?
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
      $stmt->execute([$email, $userId]);
      $exists = $stmt->fetch();

      if ($exists) {
        $error = "This email is already in use.";
      } else {
        // Update
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $userId]);

        // Session güncelle (header vb. doğru göstersin)
        $_SESSION["user"]["name"] = $name;
        $_SESSION["user"]["email"] = $email;

        $success = "Profile updated successfully.";

        // Güncel veriyi tekrar çek
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $current = $stmt->fetch();
      }
    }
  }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<h2>My Profile</h2>

<?php if ($error): ?>
  <p style="color:red;"><?= h($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
  <p style="color:green;"><?= h($success) ?></p>
<?php endif; ?>

<?php if ($current): ?>
  <form method="post">
    <label>Name</label><br>
    <input name="name" value="<?= h($current["name"] ?? "") ?>" required>
    <br><br>

    <label>Email</label><br>
    <input name="email" value="<?= h($current["email"] ?? "") ?>" required>
    <br><br>

    <label>Role</label><br>
    <input value="<?= h($current["role"] ?? "") ?>" disabled>
    <br><br>

    <button type="submit">Save</button>
  </form>

  <hr style="margin:18px 0;">
  <a href="/Seng321/login_part/logout.php">Logout</a>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
