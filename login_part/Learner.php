<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'learner') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Learner Dashboard</title>
</head>
<body>

<h1>Learner Dashboard</h1>
<p>Welcome learner 👋😛</p>

<a href="../auth/logout.php">Logout</a>

</body>
</html>
