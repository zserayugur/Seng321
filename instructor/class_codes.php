<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$userId = (int)($_SESSION["user"]["id"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["role"] ?? ""));

if ($role !== "instructor") {
  http_response_code(403);
  echo "403 Forbidden (Instructor only)";
  exit;
}

function generateClassCode(int $len = 8): string {
  // O/0 I/1 karışmasın diye sade set
  $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  $out = "";
  for ($i=0; $i<$len; $i++) {
    $out .= $chars[random_int(0, strlen($chars)-1)];
  }
  return $out;
}

$flash = "";
$error = "";

// CREATE CLASS
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_class"])) {
  $title = trim($_POST["title"] ?? "");
  if ($title === "") {
    $error = "Title is required.";
  } else {
    // unique code üret (max 10 deneme)
    $code = "";
    for ($i=0; $i<10; $i++) {
      $try = generateClassCode(8);
      $chk = $pdo->prepare("SELECT id FROM classes WHERE class_code=? LIMIT 1");
      $chk->execute([$try]);
      if (!$chk->fetch()) { $code = $try; break; }
    }
    if ($code === "") {
      $error = "Could not generate unique class code. Try again.";
    } else {
      $ins = $pdo->prepare("INSERT INTO classes (instructor_id, title, class_code, is_active) VALUES (?,?,?,1)");
      $ins->execute([$userId, $title, $code]);
      $flash = "Class created. Code: " . htmlspecialchars($code);
    }
  }
}

// TOGGLE ACTIVE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_active"])) {
  $classId = (int)($_POST["class_id"] ?? 0);

  // sadece kendi class’ını değiştirebilsin
  $q = $pdo->prepare("SELECT id, is_active FROM classes WHERE id=? AND instructor_id=? LIMIT 1");
  $q->execute([$classId, $userId]);
  $row = $q->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $error = "Class not found or not yours.";
  } else {
    $newVal = ((int)$row["is_active"] === 1) ? 0 : 1;
    $u = $pdo->prepare("UPDATE classes SET is_active=? WHERE id=? AND instructor_id=?");
    $u->execute([$newVal, $classId, $userId]);
    $flash = "Class status updated.";
  }
}

// LIST CLASSES
$stmt = $pdo->prepare("SELECT id, title, class_code, is_active, created_at
                       FROM classes
                       WHERE instructor_id=?
                       ORDER BY id DESC");
$stmt->execute([$userId]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Class Codes</h2>

<?php if ($flash): ?>
  <p style="color:#9fffb0;"><?= $flash ?></p>
<?php endif; ?>

<?php if ($error): ?>
  <p style="color:#ffb3b3;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<div style="margin: 14px 0; padding: 14px; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; max-width: 520px;">
  <h3 style="margin-top:0;">Create New Class</h3>
  <form method="post">
    <input type="text" name="title" placeholder="Class title (e.g., CENG321-Section1)" required
           style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: #fff;">
    <div style="margin-top: 10px;">
      <button class="btn btn-sm" type="submit" name="create_class">Generate Code</button>
    </div>
  </form>
</div>

<table border="1" cellpadding="8" cellspacing="0" style="margin-top:12px;">
  <tr>
    <th>ID</th>
    <th>Title</th>
    <th>Class Code</th>
    <th>Active</th>
    <th>Created</th>
    <th>Actions</th>
  </tr>

  <?php foreach ($classes as $c): ?>
    <tr>
      <td><?= (int)$c["id"] ?></td>
      <td><?= htmlspecialchars($c["title"] ?? "") ?></td>
      <td><b><?= htmlspecialchars($c["class_code"] ?? "") ?></b></td>
      <td><?= ((int)$c["is_active"] === 1) ? "Yes" : "No" ?></td>
      <td><?= htmlspecialchars($c["created_at"] ?? "") ?></td>
      <td>
        <form method="post" style="display:inline;">
          <input type="hidden" name="class_id" value="<?= (int)$c["id"] ?>">
          <button class="btn btn-sm" type="submit" name="toggle_active">
            Toggle Active
          </button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
