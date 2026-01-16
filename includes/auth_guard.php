<?php
// Fix: Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Load base path utility
require_once __DIR__ . '/base_path.php';

if (!isset($_SESSION["user"])) {
  // Use dynamic base path for redirect
  $basePath = get_base_path();
  header("Location: " . $basePath . "/login_part/index.php?tab=login");
  exit;
}

function current_user_id() {
  if (is_array($_SESSION["user"]) && isset($_SESSION["user"]["id"])) {
    return (int)$_SESSION["user"]["id"];
  }
  throw new Exception("Session user id not found.");
}

function current_user_role() {
  if (is_array($_SESSION["user"]) && isset($_SESSION["user"]["role"])) {
    return (string)$_SESSION["user"]["role"];
  }
  throw new Exception("Session user role not found.");
}
