<?php
// includes/auth_guard.php
session_start();
if (!isset($_SESSION["user"])) {
  header("Location: /language-platform/auth/login.php");
  exit;
}

function current_user_id() {
  if (is_array($_SESSION["user"]) && isset($_SESSION["user"]["id"])) {
    return (int)$_SESSION["user"]["id"];
  }
  throw new Exception("Session user id not found.");
}
