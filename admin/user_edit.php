<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";

$id = (int)($_GET["id"] ?? 0);
$stmt = $pdo->prepare("SELECT id,name,email,role,active FROM users WHERE id=?");
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { echo "User not found"; exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $role = $_POST["role"] ?? $u["role"];
  $active = isset($_POST["active"]) ? 1 : 0;

  if (!$name) $error = "Name required.";
  else {
    $stmt = $pdo->prepare("UPDATE users SET name=?, role=?, active=? WHERE id=?");
    $stmt->execute([$name, $role, $active, $id]);
    header("Location: /language-platform/admin/users.php");
    exit;
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit User</title></head>
<body>
<h2>Edit User</h2>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
  <input name="name" value="<?= htmlspecialchars($u["name"]) ?>" required><br><br>
  <p>Email: <b><?= htmlspecialchars($u["email"]) ?></b></p>
  <select name="role">
    <option <?= $u["role"]==="LEARNER"?"selected":"" ?>>LEARNER</option>
    <option <?= $u["role"]==="INSTRUCTOR"?"selected":"" ?>>INSTRUCTOR</option>
    <option <?= $u["role"]==="ADMIN"?"selected":"" ?>>ADMIN</option>
  </select><br><br>
  <label>
    <input type="checkbox" name="active" <?= ((int)$u["active"]===1)?"checked":"" ?>> Active
  </label><br><br>
  <button type="submit">Save</button>
</form>
<p><a href="/language-platform/admin/users.php">Back</a></p>
</body>
</html>
<?php
require_once __DIR__ . "/../includes/footer.php";
?>