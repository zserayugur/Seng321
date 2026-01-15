<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { echo "Invalid user id"; exit; }

$stmt = $pdo->prepare("SELECT id,name,email,role,active FROM users WHERE id=?");
$stmt->execute([$id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) { echo "User not found"; exit; }

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
 $role = strtoupper(trim($_POST["role"] ?? $u["role"]));

  $active = isset($_POST["active"]) ? 1 : 0;

  $allowed = ["LEARNER","INSTRUCTOR","ADMIN"];
  if (!in_array($role, $allowed, true)) $role = "LEARNER";

  // Admin kendini pasif yapamasın / rolünü düşüremesin (opsiyonel ama iyi)
  $currentId = (int)($_SESSION["user"]["id"] ?? 0);
  if ($id === $currentId) {
    $active = 1;
    $role = "ADMIN";
  }

  if (!$name) {
    $error = "Name required.";
  } else {
    $stmt = $pdo->prepare("UPDATE users SET name=?, role=?, active=? WHERE id=?");
    $stmt->execute([$name, $role, $active, $id]);
    header("Location: /SENG321/admin/users.php");
    exit;
  }
}
?>

<h2>Edit User</h2>

<?php if ($error): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
  <input name="name" value="<?= htmlspecialchars($u["name"]) ?>" required><br><br>

  <p>Email: <b><?= htmlspecialchars($u["email"]) ?></b></p>

  <select name="role">
    <option value="LEARNER" <?= $u["role"]==="LEARNER"?"selected":"" ?>>LEARNER</option>
    <option value="INSTRUCTOR" <?= $u["role"]==="INSTRUCTOR"?"selected":"" ?>>INSTRUCTOR</option>
    <option value="ADMIN" <?= $u["role"]==="ADMIN"?"selected":"" ?>>ADMIN</option>
  </select><br><br>

  <label>
    <input type="checkbox" name="active" <?= ((int)$u["active"]===1)?"checked":"" ?>> Active
  </label><br><br>

  <button type="submit">Save</button>
</form>

<p><a href="/language-platform/admin/users.php">Back</a></p>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
