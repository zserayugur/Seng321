<?php
require_once __DIR__ . "/../db.php";

$token = $_GET["token"] ?? "";
$message = "";

$stmt = $pdo->prepare("
    SELECT id FROM users 
    WHERE reset_token = ? 
    AND reset_token_expires > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired token.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    if ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires = NULL
            WHERE id = ?
        ");
        $update->execute([$hashed, $user["id"]]);

        $message = "Password successfully reset. <a href='../auth/login.php'>Login</a>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<form method="POST">
    <input type="password" name="password" placeholder="New password" required>
    <input type="password" name="confirm" placeholder="Confirm password" required>
    <button type="submit">Reset Password</button>
</form>

<p><?= $message ?></p>

</body>
</html>
