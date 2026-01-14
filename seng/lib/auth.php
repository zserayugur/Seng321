<?php
// lib/auth.php
function current_user_id(): int {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  // Gerçek login yoksa demo user
  if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
  return 1;
}
