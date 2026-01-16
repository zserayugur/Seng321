<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* 🔐 LOGIN KONTROLÜ */
if (!isset($_SESSION['user'])) {
    header("Location: /Seng321/login.php");
    exit;
}

$message = '';
$messageType = ''; // success | error

/* 📩 FORM SUBMIT EDİLDİYSE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = (int) $_SESSION['user']['id'];
    $class_code = trim($_POST['class_code'] ?? '');

    if ($class_code === '') {
        $message = 'Class code is required.';
        $messageType = 'error';
    } else {

        /* 1️⃣ CLASS VAR MI */
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
        $stmt->execute([$class_code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            $message = 'Invalid class code.';
            $messageType = 'error';
        } else {

            /* 2️⃣ DAHA ÖNCE REQUEST VAR MI */
            $stmt = $pdo->prepare("
                SELECT id FROM class_join_requests
                WHERE class_id = ? AND student_id = ?
            ");
            $stmt->execute([$class['id'], $student_id]);

            if ($stmt->fetch()) {
                $message = 'You already sent a join request for this class.';
                $messageType = 'error';
            } else {

                /* 3️⃣ REQUEST OLUŞTUR */
                $stmt = $pdo->prepare("
                    INSERT INTO class_join_requests (class_id, student_id, status)
                    VALUES (?, ?, 'pending')
                ");
                $stmt->execute([$class['id'], $student_id]);

                $message = 'Join request sent successfully.';
                $messageType = 'success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join Class</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="container center">
    <div class="card auth-card">
        <h2 class="page-title">Join a Class</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        </form>
    </div>
</div>

</body>
</html>
