<?php
require_once __DIR__ . "/../includes/auth_guard.php";
$path_prefix = "../";
$page = "join_class";
require_once __DIR__ . "/../includes/header.php";
?>

<h2>Join a Class</h2>

<form method="POST" action="/Seng321/pages/join_class_action.php" style="max-width:420px;">
  <input
    type="text"
    name="class_code"
    placeholder="Class Code (e.g. ENG-7F3K9A)"
    required
  >
  <br><br>
  <button type="submit">Send Join Request</button>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>