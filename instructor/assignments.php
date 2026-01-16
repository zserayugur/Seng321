<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/upload_helper.php";

// Instructor guard
$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") {
  http_response_code(403);
  die("403 Forbidden (Instructor only)");
}

$path_prefix = "../";
$page = "assignments";
require_once __DIR__ . "/../includes/header.php";

$instructor_id = (int)($_SESSION["user"]["id"] ?? 0);

// Instructor'ın class'larını çek (assignment create için)
$stmt = $pdo->prepare("SELECT id, title, class_code FROM classes WHERE instructor_id=? ORDER BY id DESC");
$stmt->execute([$instructor_id]);
$myClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * PUBLISH / UNPUBLISH
 */
if (isset($_GET["toggle_id"])) {
  $assignment_id = (int)$_GET["toggle_id"];

  $sel = $pdo->prepare("SELECT is_published FROM assignments WHERE id=? AND instructor_id=? LIMIT 1");
  $sel->execute([$assignment_id, $instructor_id]);
  $row = $sel->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $newVal = ((int)$row["is_published"] === 1) ? 0 : 1;
    $up = $pdo->prepare("UPDATE assignments SET is_published=? WHERE id=? AND instructor_id=?");
    $up->execute([$newVal, $assignment_id, $instructor_id]);
    header("Location: /Seng321/instructor/assignments.php?toggled=1");
    exit;
  }

  header("Location: /Seng321/instructor/assignments.php?error=" . urlencode("Assignment not found."));
  exit;
}

/**
 * CREATE ASSIGNMENT + OPTIONAL FILE UPLOAD
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create") {
  $class_id = (int)($_POST["class_id"] ?? 0);
  $title = trim($_POST["title"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $due_date = $_POST["due_date"] ?? null; // YYYY-MM-DD (empty olabilir)

  if ($class_id <= 0 || $title === "") {
    header("Location: /Seng321/instructor/assignments.php?error=" . urlencode("Class and title are required."));
    exit;
  }

  // Güvenlik: class gerçekten bu instructor'a mı ait?
  $chk = $pdo->prepare("SELECT 1 FROM classes WHERE id=? AND instructor_id=? LIMIT 1");
  $chk->execute([$class_id, $instructor_id]);
  if (!$chk->fetchColumn()) {
    header("Location: /Seng321/instructor/assignments.php?error=" . urlencode("Invalid class."));
    exit;
  }

  try {
    $pdo->beginTransaction();

    // 1) Assignment insert
    $ins = $pdo->prepare("
      INSERT INTO assignments (class_id, instructor_id, title, description, due_date, is_published, created_at)
      VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");
    $ins->execute([$class_id, $instructor_id, $title, $description, ($due_date ?: null)]);

    // 2) Assignment ID
    $assignmentId = (int)$pdo->lastInsertId();

    // 3) File varsa upload + DB insert
    if (!empty($_FILES['attachment']['name'])) {
      $meta = safeUpload(
        $_FILES['attachment'],
        __DIR__ . "/../uploads/assignments",
        ['pdf','doc','docx','png','jpg','jpeg'],
        10 * 1024 * 1024 // 10MB
      );

      $insF = $pdo->prepare("
        INSERT INTO assignment_files (assignment_id, uploaded_by, original_name, stored_name, mime_type, file_size, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
      ");
      $insF->execute([
        $assignmentId,
        $instructor_id,
        $meta["original_name"],
        $meta["stored_name"],
        $meta["mime_type"],
        $meta["file_size"],
      ]);
    }

    $pdo->commit();

    header("Location: /Seng321/instructor/assignments.php?created=1");
    exit;

  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: /Seng321/instructor/assignments.php?error=" . urlencode("Create failed: " . $e->getMessage()));
    exit;
  }
}

/**
 * LIST ASSIGNMENTS
 */
$list = $pdo->prepare("
  SELECT a.id, a.title, a.description, a.due_date, a.is_published, a.created_at,
         c.title AS class_title, c.class_code
  FROM assignments a
  JOIN classes c ON c.id = a.class_id
  WHERE a.instructor_id=?
  ORDER BY a.id DESC
");
$list->execute([$instructor_id]);
$assignments = $list->fetchAll(PDO::FETCH_ASSOC);

// Assignment files (toplu çekelim)
$filesByAssignment = [];
if (count($assignments) > 0) {
  $ids = array_map(fn($x) => (int)$x["id"], $assignments);
  $placeholders = implode(",", array_fill(0, count($ids), "?"));

  $q = $pdo->prepare("
    SELECT id, assignment_id, original_name, stored_name, mime_type, file_size, created_at
    FROM assignment_files
    WHERE assignment_id IN ($placeholders)
    ORDER BY id DESC
  ");
  $q->execute($ids);
  $files = $q->fetchAll(PDO::FETCH_ASSOC);

  foreach ($files as $f) {
    $aid = (int)$f["assignment_id"];
    if (!isset($filesByAssignment[$aid])) $filesByAssignment[$aid] = [];
    $filesByAssignment[$aid][] = $f;
  }
}
?>

<h2>Assignments</h2>

<?php if (isset($_GET["created"])): ?>
  <p style="color:#9fffb0;">Assignment created.</p>
<?php endif; ?>

<?php if (isset($_GET["toggled"])): ?>
  <p style="color:#9fffb0;">Publish status updated.</p>
<?php endif; ?>

<?php if (isset($_GET["error"])): ?>
  <p style="color:#ffb3b3;"><?= htmlspecialchars($_GET["error"]) ?></p>
<?php endif; ?>

<div class="card" style="max-width:720px; padding:16px; margin-bottom:18px;">
  <h3 style="margin-top:0;">Create Assignment</h3>

  <?php if (count($myClasses) === 0): ?>
    <p style="color:#ffd7a3;">You have no classes yet. Create a class code first.</p>
  <?php else: ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="create">

      <label>Class</label><br>
      <select name="class_id" required style="width:100%; padding:10px; margin:6px 0 12px;">
        <?php foreach ($myClasses as $c): ?>
          <option value="<?= (int)$c["id"] ?>">
            <?= htmlspecialchars($c["title"]) ?> (<?= htmlspecialchars($c["class_code"]) ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <label>Title</label><br>
      <input name="title" required style="width:100%; padding:10px; margin:6px 0 12px;" placeholder="e.g., Week 1 Homework">

      <label>Description</label><br>
      <textarea name="description" rows="4" style="width:100%; padding:10px; margin:6px 0 12px;" placeholder="Instructions..."></textarea>

      <label>Due date</label><br>
      <input type="date" name="due_date" style="padding:10px; margin:6px 0 12px;">

      <label>Attachment (optional)</label><br>
      <input type="file" name="attachment" style="margin:6px 0 12px;" />

      <br>
      <button class="btn" type="submit">Create</button>
    </form>
  <?php endif; ?>
</div>

<div class="card" style="padding:16px;">
  <h3 style="margin-top:0;">My Assignments</h3>

  <?php if (count($assignments) === 0): ?>
    <p style="color: var(--text-muted);">No assignments yet.</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0" style="margin-top:10px; width:100%;">
      <tr>
        <th>ID</th>
        <th>Class</th>
        <th>Title</th>
        <th>Due</th>
        <th>Published</th>
        <th>Attachments</th>
        <th>Actions</th>
      </tr>

      <?php foreach ($assignments as $a): ?>
        <?php $aid = (int)$a["id"]; ?>
        <tr>
          <td><?= $aid ?></td>
          <td><?= htmlspecialchars($a["class_title"]) ?> (<?= htmlspecialchars($a["class_code"]) ?>)</td>
          <td><?= htmlspecialchars($a["title"]) ?></td>
          <td><?= htmlspecialchars($a["due_date"] ?? "-") ?></td>
          <td><?= ((int)$a["is_published"] === 1) ? "Yes" : "No" ?></td>

          <td>
            <?php if (!empty($filesByAssignment[$aid])): ?>
              <ul style="margin:0; padding-left:18px;">
                <?php foreach ($filesByAssignment[$aid] as $f): ?>
                  <li>
                    <?= htmlspecialchars($f["original_name"]) ?>
                    <span style="color:#9ca3af; font-size:12px;">
                      (<?= (int)$f["file_size"] ?> bytes)
                    </span>
                    <!-- download link (optional):
                         Eğer download endpoint yazmadıysan sadece isim göster kalsın.
                         Yazarsan şuna bağlayabilirsin:
                         /Seng321/instructor/download_assignment_file.php?id=FILE_ID
                    -->
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <span style="color:#9ca3af;">-</span>
            <?php endif; ?>
          </td>

          <td>
            <a href="/Seng321/instructor/assignments.php?toggle_id=<?= $aid ?>">
              <?= ((int)$a["is_published"] === 1) ? "Unpublish" : "Publish" ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>