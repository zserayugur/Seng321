<?php
require_once __DIR__ . "/../config/db.php"; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$email = trim($_POST["email"]);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email.");
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $update = $pdo->prepare("
        UPDATE users
        SET reset_token = ?, reset_token_expires = ?
        WHERE id = ?
    ");
    $update->execute([$token, $expires, $user["id"]]);

    $resetLink = "http://localhost/your_project/reset_password.php?token=$token";

    echo "<h3>Password Reset</h3>";
    echo "<p>A reset link has been sent to your email.</p>";
    echo "<p><b>DEV MODE LINK:</b><br>";
    echo "<a href='$resetLink'>$resetLink</a></p>";

} else {
    echo "<p>If the email exists, a reset link has been sent.</p>";
}
$plainPassword = $_POST['password'];
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  INSERT INTO users (name, email, role, password_hash, password_plain)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $email, $role, $hash, $plainPassword]);

echo "<br><a href='index.html'>Back to Login</a>";
