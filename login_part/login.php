<?php
require_once __DIR__ . "/../db.php";
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    die("Login failed");
}

/* ROLE'U KÜÇÜK HARFE SABİTLE */
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = strtolower(trim($user['role'])); // <<< ÖNEMLİ

if ($_SESSION['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
}

if ($_SESSION['role'] === 'instructor') {
    header("Location: instructor.php");
    exit;
}

/* learner */
header("Location: Learner.php");
exit;
