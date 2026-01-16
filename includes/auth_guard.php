<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


if (!isset($_SESSION["user"])) {
header("Location: /Seng321/includes/login.php");
  header("Location: /Seng321/includes/login.php");
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
