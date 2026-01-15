<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";

$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csv"])) {
if (!isset($_FILES["csv"]) || $_FILES["csv"]["error"] !== UPLOAD_ERR_OK) {
  die("File upload error.");
}

$tmp = $_FILES["csv"]["tmp_name"];

if (!is_uploaded_file($tmp)) {
  die("Upload failed.");
}  

  $tmp = $_FILES["csv"]["tmp_name"];
  $handle = fopen($tmp, "r");
  if (!$handle) {
  die("Cannot open uploaded file.");
}

  $header = fgetcsv($handle);
  if (!$header) die("Empty CSV file.");

$header = array_map(fn($h) => strtolower(trim($h)), $header);

$required = ["name", "email", "password", "role"];
foreach ($required as $req) {
  if (!in_array($req, $header, true)) {
    die("CSV must contain columns: name,email,password,role");
  }
}

  $inserted = 0; $failed = 0; $errors = [];

  while (($row = fgetcsv($handle)) !== false) {
    if (count($row) !== count($header)) {
  throw new Exception("Column count mismatch");
}

    $data = array_combine($header, $row);
    $name = trim($data["name"] ?? "");
    $email = strtolower(trim($data["email"] ?? ""));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  throw new Exception("Invalid email format");
}

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

  $msg = $e->getMessage();
  if (stripos($msg, "Duplicate") !== false) {
    $msg = "Email already exists";
  }

  $errors[] = ["email"=>$email, "error"=>$msg];
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