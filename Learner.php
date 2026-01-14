<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'LEARNER') {
    header("Location: ../auth/index.php");
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
