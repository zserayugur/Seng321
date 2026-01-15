
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h2>Login</h2>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
  <input name="email" placeholder="email" required><br><br>
  <input name="password" type="password" placeholder="password" required><br><br>
  <button type="submit">Login</button>
</form>
</body>
</html>
<?php
require_once __DIR__ . "/../config/db.php";
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = strtolower(trim($_POST["email"] ?? ""));
  $password = $_POST["password"] ?? "";

  $stmt = $pdo->prepare("SELECT id,name,email,password_hash,role,active FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if (!$u || (int)$u["active"] !== 1 || !password_verify($password, $u["password_hash"])) {
    $error = "Invalid credentials.";
  } else {
    
    session_regenerate_id(true);

    $_SESSION["user"] = [
      "id" => $u["id"],
      "name" => $u["name"],
      "email" => $u["email"],
      "role" => $u["role"]
    ];
    header("Location: /language-platform/admin/dashboard.php");
    exit;
  }
}
?>