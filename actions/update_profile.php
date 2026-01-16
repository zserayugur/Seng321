<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: /Seng321/login_part/index.php");
    exit;
}

$userId = $_SESSION['user']['id'];

if (isset($_POST['update_info'])) {

    $name = trim($_POST['name'] ?? '');

    if ($name !== '') {
        $stmt = $pdo->prepare(
            "UPDATE users SET name = ? WHERE id = ?"
        );
        $stmt->execute([$name, $userId]);

        $_SESSION['user']['name'] = $name;
    }

    header("Location: /Seng321/pages/profile.php?success=info");
    exit;
}

if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm || strlen($new) < 6) {
        header("Location: /Seng321/pages/profile.php?error=pwd");
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT password_hash FROM users WHERE id = ?"
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        header("Location: /Seng321/pages/profile.php?error=current");
        exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "UPDATE users SET password_hash = ? WHERE id = ?"
    );
    $stmt->execute([$newHash, $userId]);

    header("Location: /Seng321/pages/profile.php?success=password");
    exit;
}
