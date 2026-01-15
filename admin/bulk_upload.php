<?php
$path_prefix = "../";
$page = "admin";

require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";

$result = null;
$error = "";

/* POST: CSV upload */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    if (!isset($_FILES["csv"]) || $_FILES["csv"]["error"] !== UPLOAD_ERR_OK) {
      throw new Exception("File upload error.");
    }

    $tmp = $_FILES["csv"]["tmp_name"];
    if (!is_uploaded_file($tmp)) {
      throw new Exception("Upload failed.");
    }

    $handle = fopen($tmp, "r");
    if (!$handle) {
      throw new Exception("Cannot open uploaded file.");
    }

    $header = fgetcsv($handle);
    if (!$header) {
      fclose($handle);
      throw new Exception("Empty CSV file.");
    }

    $header = array_map(fn($h) => strtolower(trim($h)), $header);

    $required = ["name", "email", "password", "role"];
    foreach ($required as $req) {
      if (!in_array($req, $header, true)) {
        fclose($handle);
        throw new Exception("CSV must contain columns: name,email,password,role");
      }
    }

    $inserted = 0;
    $failed = 0;
    $errors = [];

    while (($row = fgetcsv($handle)) !== false) {
      try {
        if (count($row) !== count($header)) {
          throw new Exception("Column count mismatch");
        }

        $data = array_combine($header, $row);

        $name = trim($data["name"] ?? "");
        $email = strtolower(trim($data["email"] ?? ""));
        $password = (string)($data["password"] ?? "");
        $role = strtoupper(trim($data["role"] ?? "LEARNER"));

        if ($name === "" || $email === "" || $password === "") throw new Exception("Missing fields");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email format");
        if (!in_array($role, ["ADMIN", "INSTRUCTOR", "LEARNER"], true)) throw new Exception("Invalid role");

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)");
        $stmt->execute([$name, $email, $hash, $role]);

        $inserted++;
      } catch (Exception $eRow) {
        $failed++;
        $msg = $eRow->getMessage();

        // MySQL duplicate email hata mesajlarını daha okunur yap
        if (stripos($msg, "duplicate") !== false) {
          $msg = "Email already exists";
        }

        $errors[] = ["email" => ($email ?? ""), "error" => $msg];
      }
    }

    fclose($handle);

    $result = [
      "inserted" => $inserted,
      "failed" => $failed,
      "errors" => $errors
    ];
  } catch (Exception $e) {
    $error = $e->getMessage();
  }
}
?>

<h2>Bulk Upload (CSV)</h2>

<div class="card" style="margin-top:16px;">
  <p style="margin-top:0;">
    CSV header şu şekilde olmalı:
    <b>name,email,password,role</b>
    (role: ADMIN / INSTRUCTOR / LEARNER)
  </p>

  <?php if ($error): ?>
    <div class="auth-msg auth-msg-error" style="margin-bottom:12px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
    <input type="file" name="csv" accept=".csv" required>
    <button type="submit" class="btn">Upload</button>
    <a class="btn" href="<?= $path_prefix ?>admin/dashboard.php">Back</a>
  </form>
</div>

<?php if ($result): ?>
  <div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Result</h3>
    <p>Inserted: <b><?= (int)$result["inserted"] ?></b> | Failed: <b><?= (int)$result["failed"] ?></b></p>

    <?php if (!empty($result["errors"])): ?>
      <h4>Errors</h4>
      <ul style="margin:0; padding-left:18px; line-height:1.9;">
        <?php foreach ($result["errors"] as $e): ?>
          <li>
            <?= htmlspecialchars($e["email"]) ?> — <?= htmlspecialchars($e["error"]) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
