<?php
// includes/instructor_guard.php
require_once __DIR__ . "/auth_guard.php";

$role = strtolower(trim($_SESSION["user"]["role"] ?? ""));
if ($role !== "instructor") {
  http_response_code(403);
  echo "403 Forbidden (Instructor only)";
  exit;
}