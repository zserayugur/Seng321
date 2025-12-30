<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/db.php";

$users = $pdo->query("SELECT id,name,email,role,active,created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Manage Users</title></head>
<body>
<h2>Manage Users</h2>
<a href="/language-platform/admin/user_create.php">+ Create User</a> |
<a href="/language-platform/admin/dashboard.php">Back</a>

<table border="1" cellpadding="6" cellspacing="0" style="margin-top:10px;">
  <tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th>
  </tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u["id"] ?></td>
      <td><?= htmlspecialchars($u["name"]) ?></td>
      <td><?= htmlspecialchars($u["email"]) ?></td>
      <td><?= htmlspecialchars($u["role"]) ?></td>
      <td><?= (int)$u["active"] ?></td>
      <td>
        <a href="/language-platform/admin/user_edit.php?id=<?= (int)$u["id"] ?>">Edit</a> |
        <a href="/language-platform/admin/user_delete.php?id=<?= (int)$u["id"] ?>"
           onclick="return confirm('Delete user?')">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
