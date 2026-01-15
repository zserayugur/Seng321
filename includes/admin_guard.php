<?php
// includes/admin_guard.php
require_once __DIR__ . "/auth_guard.php";
if (strtoupper($_SESSION["user"]["role"] ?? "") !== "ADMIN") {
  http_response_code(403);
  echo "403 Forbidden (Admin only)";
  exit;
}
