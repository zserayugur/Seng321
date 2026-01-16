<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") {
  http_response_code(403);
  die("403 Forbidden (Instructor only)");
}

$path_prefix = "../";
$page = "class_codes";
require_once __DIR__ . "/../includes/header.php";

$instructor_id = (int)$_SESSION["user"]["id"];

function generateClassCode(): string {
  // ENG-XXXXXX gibi
  $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  $code = "ENG-";
  for ($i=0; $i<6; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
  return $code;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST["title"] ?? "");
  if ($title === "") $title = "Untitled Class";

  // unique üret (2-3 deneme yeter)
  for ($i=0; $i<5; $i++) {
    $code = generateClassCode();
    $chk = $pdo->prepare("SELECT 1 FROM classes WHERE class_code=? LIMIT 1");
    $chk->execute([$code]);
    if (!$chk->fetchColumn()) break;
  }

  $ins = $pdo->prepare("INSERT INTO classes (instructor_id, title, class_code, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
  $ins->execute([$instructor_id, $title, $code]);

  header("Location: /Seng321/instructor/class_codes.php?created=1");
  exit;
}

$stmt = $pdo->prepare("SELECT id,title,class_code,is_active,created_at FROM classes WHERE instructor_id=? ORDER BY id DESC");
$stmt->execute([$instructor_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Class Codes</h2>

<form method="POST" style="max-width:420px;">
  <input name="title" placeholder="Class title (e.g., CENG321 Section A)" required>
  <button type="submit">Create Class</button>
</form>
<p>
  <a href="/Seng321/instructor/requests.php">Go to Join Requests</a>
</p>
<br>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
  <th>Title</th>
  <th>Code</th>
  <th>Active</th>
  <th>Created</th>
  <th>Actions</th>
</tr>

  <?php foreach ($classes as $c): ?>
    <tr>
  <td><?= htmlspecialchars($c["title"]) ?></td>
  <td><b><?= htmlspecialchars($c["class_code"]) ?></b></td>
  <td><?= (int)$c["is_active"] ?></td>
  <td><?= htmlspecialchars($c["created_at"]) ?></td>
  <td>
    <a href="/Seng321/instructor/requests.php?class_id=<?= (int)$c["id"] ?>">
      Join Requests
    </a>
    |
    <a href="/Seng321/instructor/class_members.php?class_id=<?= (int)$c["id"] ?>">
      Members
    </a>
  </td>
</tr>

  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>