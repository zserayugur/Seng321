<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$result = null;
$errorTop = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    if (!isset($_FILES["csv"]) || $_FILES["csv"]["error"] !== UPLOAD_ERR_OK) {
      throw new Exception("File upload error. Please select a valid CSV file.");
    }

    $tmp = $_FILES["csv"]["tmp_name"];
    if (!is_uploaded_file($tmp)) {
      throw new Exception("Upload failed (not an uploaded file).");
    }

    $handle = fopen($tmp, "r");
    if (!$handle) {
      throw new Exception("Cannot open uploaded file.");
    }

    /* --- delimiter tespit et ( , mi ; mi ) --- */
    $firstLine = fgets($handle);
    if ($firstLine === false) {
      fclose($handle);
      throw new Exception("Empty CSV file.");
    }

    $commaCount = substr_count($firstLine, ",");
    $semiCount  = substr_count($firstLine, ";");
    $delimiter  = ($semiCount > $commaCount) ? ";" : ",";

    rewind($handle);

    /* header oku */
    $header = fgetcsv($handle, 0, $delimiter);
    if (!$header) {
      fclose($handle);
      throw new Exception("Empty CSV file.");
    }

    /* BOM temizle + normalize et */
    $header = array_map(function($h) {
      $h = (string)$h;
      $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // UTF-8 BOM sil
      return strtolower(trim($h));
    }, $header);

    /* gerekli kolonlar */
    $required = ["name","email","password","role"];
    foreach ($required as $req) {
      if (!in_array($req, $header, true)) {
        fclose($handle);
        throw new Exception("CSV must contain columns: name,email,password,role (Detected delimiter: {$delimiter})");
      }
    }

    $inserted = 0;
    $failed = 0;
    $errors = [];

    /* SATIRLARI delimiter ile oku ✅ */
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
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
        if (!in_array($role, ["ADMIN","INSTRUCTOR","LEARNER"], true)) throw new Exception("Invalid role");

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)");
        $stmt->execute([$name, $email, $hash, $role]);

        $inserted++;
      } catch (Exception $eRow) {
        $failed++;
        $msg = $eRow->getMessage();
        if (stripos($msg, "duplicate") !== false) $msg = "Email already exists";

        $errors[] = ["email" => $email ?: "(unknown)", "error" => $msg];
      }
    }

    fclose($handle);

    $result = ["inserted" => $inserted, "failed" => $failed, "errors" => $errors];

  } catch (Exception $e) {
    $errorTop = $e->getMessage();
  }
}
?>

<h2>Bulk Upload (CSV)</h2>

<div class="card">
  <p>CSV header şu şekilde olmalı: <b>name,email,password,role</b> (role: ADMIN / INSTRUCTOR / LEARNER)</p>

  <?php if ($errorTop): ?>
    <div class="auth-msg auth-msg-error"><?= htmlspecialchars($errorTop) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
    <input type="file" name="csv" accept=".csv,text/csv" required>
    <button type="submit" class="btn">Upload</button>
    <a class="btn" href="/SENG321/admin/dashboard.php">Back</a>
  </form>

  <?php if ($result): ?>
    <hr>
    <p><b>Inserted:</b> <?= (int)$result["inserted"] ?> | <b>Failed:</b> <?= (int)$result["failed"] ?></p>

    <?php if (!empty($result["errors"])): ?>
      <ul>
        <?php foreach ($result["errors"] as $e): ?>
          <li><?= htmlspecialchars($e["email"]) ?> — <?= htmlspecialchars($e["error"]) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
