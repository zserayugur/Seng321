<?php
session_start();
session_destroy();
header("Location: /language-platform/auth/login.php");
exit;
