<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csv"])) {
  $tmp = $_FILES["csv"]["tmp_name"];
  $handle = fopen($tmp, "r");

  $header = fgetcsv($handle);
  $inserted = 0; $failed = 0; $errors = [];

  while (($row = fgetcsv($handle)) !== false) {
    $data = array_combine($header, $row);
    $name = trim($data["name"] ?? "");
    $email = strtolower(trim($data["email"] ?? ""));
    $password = $data["password"] ?? "";
    $role = strtoupper(trim($data["role"] ?? "LEARNER"));

    try {
      if (!$name || !$email || !$password) throw new Exception("Missing fields");
      if (!in_array($role, ["ADMIN","INSTRUCTOR","LEARNER"])) throw new Exception("Invalid role");

      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
      $stmt->execute([$name,$email,$hash,$role]);
      $inserted++;
    } catch (Exception $e) {
      $failed++;
      $errors[] = ["email"=>$email, "error"=>$e->getMessage()];
    }
  }
  fclose($handle);
  $result = ["inserted"=>$inserted, "failed"=>$failed, "errors"=>$errors];
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Bulk Upload</title></head>
<body>
<h2>Bulk Upload (CSV)</h2>

<form method="post" enctype="multipart/form-data">
  <input type="file" name="csv" accept=".csv" required>
  <button type="submit">Upload</button>
</form>

<?php if ($result): ?>
  <h3>Result</h3>
  <p>Inserted: <?= $result["inserted"] ?> | Failed: <?= $result["failed"] ?></p>
  <?php if (count($result["errors"])): ?>
    <ul>
      <?php foreach ($result["errors"] as $e): ?>
        <li><?= htmlspecialchars($e["email"]) ?> — <?= htmlspecialchars($e["error"]) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php endif; ?>

<p><a href="/language-platform/admin/dashboard.php">Back</a></p>
</body>
</html>
<?php
require_once __DIR__ . "/../includes/footer.php";
?>