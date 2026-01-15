<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>

    <!-- SENİN MEVCUT CSS -->
    <link rel="stylesheet" href="/SENG321/login_part/login.css?v=<?= time() ?>">
</head>
<body>

<div class="container">

    <h1>Password Reset</h1>

    <div class="auth-success">
        If the email exists, a reset link has been sent.
    </div>

    <?php if (isset($resetLink)): ?>
        <div class="auth-success" style="margin-top:12px;">
            <strong>DEV MODE LINK</strong><br>
            <a href="<?= htmlspecialchars($resetLink) ?>" style="color:#fff; word-break:break-all;">
                <?= htmlspecialchars($resetLink) ?>
            </a>
        </div>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center;">
        <a href="/SENG321/login_part/index.php" style="color:#fff; opacity:0.85;">
            ← Back to Login
        </a>
    </div>

</div>

</body>
</html>
