<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
$page = "admin";
require_once __DIR__ . "/../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = strtolower(trim($_POST["email"] ?? ""));
  $password = $_POST["password"] ?? "";
  $role = strtoupper(trim($_POST["role"] ?? "LEARNER"));

  $allowed = ["LEARNER","INSTRUCTOR","ADMIN"];
  if (!in_array($role, $allowed, true)) $role = "LEARNER";

  if ($name === "" || $email === "" || $password === "") {
    $error = "All fields required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format.";
  } elseif (strlen($password) < 6) {
    $error = "Password too short (min 6).";
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
      $stmt = $pdo->prepare(
        "INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)"
      );
      $stmt->execute([$name, $email, $hash, $role]);

      header("Location: /SENG321/admin/users.php?created=1");
      exit;

    } catch (PDOException $e) {
      // Duplicate email yakala
      if (stripos($e->getMessage(), "Duplicate") !== false) {
        $error = "Email already exists.";
      } else {
        // Debug için geçici bırak; her şey çalışınca bunu sadeleştirirsin
        $error = "DB error: " . $e->getMessage();
      }
    }
  }
}
?>

<h2>Create User</h2>

<div class="card" style="margin-top:16px; padding:16px;">
  <?php if ($error): ?>
    <p style="color:#ffb3b3; margin-bottom:10px;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post" style="display:grid; gap:12px; max-width:420px;">
    <input name="name" placeholder="Name" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Password (min 6)" required>

    <select name="role">
      <option value="LEARNER">LEARNER</option>
      <option value="INSTRUCTOR">INSTRUCTOR</option>
      <option value="ADMIN">ADMIN</option>
    </select>

    <button type="submit" class="btn">Create</button>
  </form>

  <p style="margin-top:14px;">
    <a href="/Seng321/admin/users.php">Back</a>
  </p>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
