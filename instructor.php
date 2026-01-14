<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: ../auth/index.php");
    exit;
}
?>

<h1>Instructor Dashboard</h1>
<p>Welcome instructor 👋</p>

<a href="../auth/logout.php">Logout</a>
