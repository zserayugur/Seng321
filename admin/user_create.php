<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = strtolower(trim($_POST["email"] ?? ""));
  $password = $_POST["password"] ?? "";
  $role = $_POST["role"] ?? "LEARNER";

  $allowed = ["LEARNER","INSTRUCTOR","ADMIN"];
  if (!in_array($role, $allowed, true)) {
    $role = "LEARNER";
  }

  if (!$name || !$email || !$password) {
    $error = "All fields required.";
  } else {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
      $stmt = $pdo->prepare(
        "INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)"
      );
      $stmt->execute([$name, $email, $hash, $role]);
      header("Location: /language-platform/admin/users.php");
      exit;
    } catch (Exception $e) {
      $error = "Email already exists.";
    }
  }
}
?>

<h2>Create User</h2>

<?php if ($error): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
  <input name="name" placeholder="Name" required><br><br>
  <input name="email" placeholder="Email" required><br><br>
  <input name="password" type="password" placeholder="Password" required><br><br>

  <select name="role">
    <option value="LEARNER">LEARNER</option>
    <option value="INSTRUCTOR">INSTRUCTOR</option>
    <option value="ADMIN">ADMIN</option>
  </select><br><br>

  <button type="submit">Create</button>
</form>

<p><a href="/language-platform/admin/users.php">Back</a></p>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
