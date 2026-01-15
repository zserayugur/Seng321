<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth_guard.php";

if (current_user_role() !== "LEARNER") {
  http_response_code(403);
  exit("Forbidden");
}

$student_id = current_user_id();
$assignment_id = (int)($_GET['id'] ?? 0);

if ($assignment_id <= 0) {
  header("Location: /Seng321/dashboard/learner.php");
  exit;
}

// assignment gerçekten bu öğrenciye mi ait ve pending mi?
$stmt = $pdo->prepare("SELECT id, type FROM assignments WHERE id=? AND student_id=? AND status='pending' LIMIT 1");
$stmt->execute([$assignment_id, $student_id]);
$row = $stmt->fetch();

if (!$row) {
  header("Location: /Seng321/dashboard/learner.php");
  exit;
}

$type = $row['type'];

// ✅ SENİN GERÇEK SAYFA YOLLARIN (pages klasörü!)
$routes = [
  'writing'    => '/Seng321/pages/writing.php',
  'speaking'   => '/Seng321/pages/speaking.php',
  'listening'  => '/Seng321/pages/listening.php',

  // Bunlar sende varsa açılır; yoksa şimdilik learner’a döndürür
  'vocabulary' => '/Seng321/pages/vocabulary.php',
  'grammar'    => '/Seng321/pages/grammar.php',
  'reading'    => '/Seng321/pages/reading.php',
];

$target = $routes[$type] ?? '/Seng321/dashboard/learner.php';

// assignment_id'yi sınava taşı
header("Location: {$target}?assignment_id={$assignment_id}");
exit;
