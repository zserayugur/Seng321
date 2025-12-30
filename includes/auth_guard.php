<?php
// includes/auth_guard.php
session_start();
if (!isset($_SESSION["user"])) {
  header("Location: /language-platform/auth/login.php");
  exit;
}
