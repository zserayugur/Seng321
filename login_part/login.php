<?php
require_once __DIR__ . '/../config/db.php';
session_start();


$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

/* 1️⃣ Email format kontrol */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format");
}

/* 2️⃣ Kullanıcıyı DB’den çek */
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* 3️⃣ Email var mı? */
if (!$user) {
    die("No account found with this email");
}

/* 4️⃣ Şifre doğru mu? */
if (!password_verify($password, $user['password_hash'])) {
    die("Incorrect password");
}

/* 5️⃣ Session */
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

/* 6️⃣ Role-based redirect */
if ($user['role'] === 'admin') {
    header("Location: ../dashboard/admin.php");
} elseif ($user['role'] === 'instructor') {
    header("Location: ../dashboard/instructor.php");
} else {
    header("Location: ../dashboard/learner.php");
}
exit;
