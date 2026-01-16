<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") { http_response_code(403); die("403 Forbidden (Instructor only)"); }

$path_prefix = "../";
$page = "assignments";
require_once __DIR__ . "/../includes/header.php";

$instructor_id = (int)$_SESSION["user"]["id"];

// Instructor'ın class listesi (dropdown için)
$cls = $pdo->prepare("SELECT id, title, class_code FROM classes WHERE instructor_id=? ORDER BY id DESC");
$cls->execute([$instructor_id]);
$classes = $cls->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $class_id = (int)($_POST["class_id"] ?? 0);
  $title = trim($_POST["title"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $due_date = trim($_POST["due_date"] ?? ""); // "YYYY-MM-DDTHH:MM" gelir

  if ($class_id <= 0 || $title === "") {
    $error = "Class and title are required.";
  } else {
    // class gerçekten instructor'a mı ait?
    $own = $pdo->prepare("SELECT 1 FROM classes WHERE id=? AND instructor_id=? LIMIT 1");
    $own->execute([$class_id, $instructor_id]);
    if (!$own->fetchColumn()) {
      $error = "Invalid class selection.";
    } else {
      $due = null;
      if ($due_date !== "") {
        $due = str_replace("T", " ", $due_date) . ":00";
      }

      $ins = $pdo->prepare("
        INSERT INTO assignments (class_id, instructor_id, title, description, due_date, status)
        VALUES (?, ?, ?, ?, ?, 'draft')
      ");
      $ins->execute([$class_id, $instructor_id, $title, $description ?: null, $due]);

      header("Location: /Seng321/instructor/assignments.php?created=1");
      exit;
    }
  }
}
?>

<h2>Create Assignment</h2>

<?php if ($error): ?>
  <p style="color:#ffb3b3;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post" style="max-width:520px;">
  <label>Class</label><br>
  <select name="class_id" required>
    <option value="">Select class...</option>
    <?php foreach ($classes as $c): ?>
      <option value="<?= (int)$c["id"] ?>">
        <?= htmlspecialchars($c["title"]) ?> (<?= htmlspecialchars($c["class_code"]) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <br><br>

  <label>Title</label><br>
  <input name="title" required style="width:100%;" placeholder="e.g., Week 3 Writing Task">

  <br><br>

  <label>Description</label><br>
  <textarea name="description" rows="5" style="width:100%;" placeholder="Instructions..."></textarea>

  <br><br>

  <label>Due Date (optional)</label><br>
  <input type="datetime-local" name="due_date">

  <br><br>

  <button type="submit" class="btn">Save as Draft</button>
  <a class="btn btn-sm" href="/Seng321/instructor/assignments.php">Back</a>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>