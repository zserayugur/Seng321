<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$path_prefix = "../";
require_once __DIR__ . "/../includes/header.php";

$role = $_GET['role'] ?? 'ALL';
$params = [];
$sql = "SELECT id,name,email,role,active,created_at FROM users";

if (in_array($role, ['LEARNER','INSTRUCTOR','ADMIN'], true)) {
  $sql .= " WHERE role = ?";
  $params[] = $role;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentId = (int)($_SESSION['user']['id'] ?? 0);
?>
<?php if (isset($_GET['deleted'])): ?>
  <p style="color:#9fffb0;">User deleted successfully.</p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <p style="color:#ffb3b3;"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<h2>Manage Users</h2>

<p>
  <a href="/SENG321/admin/user_create.php">+ Create User</a>
 |
  <a href="/SENG321/admin/bulk_upload.php">Bulk Upload</a>
 |
  <a href="/SENG321/admin/dashboard.php">Back</a>

</p>

<p>
  Filter:
  <a href="/SENG321/admin/users.php">All</a> |
  <a href="/SENG321/admin/users.php?role=LEARNER">Learners</a> |
  <a href="/SENG321/admin/users.php?role=INSTRUCTOR">Instructors</a> |
  <a href="/SENG321/admin/users.php?role=ADMIN">Admins</a>
</p>

<table border="1" cellpadding="6" cellspacing="0" style="margin-top:10px;">
  <tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th>
  </tr>

  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u["id"] ?></td>
      <td><?= htmlspecialchars($u["name"] ?? "") ?></td>
      <td><?= htmlspecialchars($u["email"] ?? "") ?></td>
      <td><?= htmlspecialchars($u["role"] ?? "") ?></td>
      <td><?= (int)($u["active"] ?? 0) ?></td>
      <td>
        <a href="/SENG321/admin/user_edit.php?id=<?= (int)$u["id"] ?>">Edit</a> |

        <?php if ((int)$u["id"] !== $currentId): ?>
         <a href="/SENG321/admin/user_delete.php?id=<?= (int)$u['id'] ?>"
   onclick="return confirm('Delete user?')">
   Delete
</a>
        <?php else: ?>
          <span style="color:#777;">Delete</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>
