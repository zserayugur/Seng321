<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = strtolower(trim($_POST["email"] ?? ""));
  $password = $_POST["password"] ?? "";
  $role = $_POST["role"] ?? "LEARNER";

  if (!$name || !$email || !$password) $error = "All fields required.";
  else {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
      $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
      $stmt->execute([$name, $email, $hash, $role]);
      header("Location: /language-platform/admin/users.php");
      exit;
    } catch (Exception $e) {
      $error = "Email already exists.";
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Create User</title></head>
<body>
<h2>Create User</h2>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
  <input name="name" placeholder="Name" required><br><br>
  <input name="email" placeholder="Email" required><br><br>
  <input name="password" type="password" placeholder="Password" required><br><br>
  <select name="role">
    <option>LEARNER</option>
    <option>INSTRUCTOR</option>
    <option>ADMIN</option>
  </select><br><br>
  <button type="submit">Create</button>
</form>
<p><a href="/language-platform/admin/users.php">Back</a></p>
</body>
</html>
