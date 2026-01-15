<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth_guard.php";

if (current_user_role() !== "LEARNER") {
  http_response_code(403);
  exit("Forbidden");
}

$student_id = current_user_id();
$assignment_id = (int)($_GET['id'] ?? 0);

if ($assignment_id <= 0) {
  header("Location: /Seng321/dashboard/student_assignments.php");
  exit;
}

$stmt = $conn->prepare("SELECT id, type FROM assignments WHERE id=? AND student_id=? AND status='pending' LIMIT 1");
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  header("Location: /Seng321/dashboard/student_assignments.php");
  exit;
}

$type = $row['type'];

/**
 * BURASI: senin projedeki gerçek sınav sayfalarına göre düzenlenecek.
 * Şimdilik örnek path verdim. Eğer senin sınavlar /assessment/... altındaysa değiştir.
 */
$routes = [
  'writing'    => '/Seng321/exams/writing.php',
  'speaking'   => '/Seng321/exams/speaking.php',
  'listening'  => '/Seng321/exams/listening.php',
  'vocabulary' => '/Seng321/exams/vocabulary.php',
  'grammar'    => '/Seng321/exams/grammar.php',
  'reading'    => '/Seng321/exams/reading.php',
];

$target = $routes[$type] ?? '/Seng321/dashboard/student_assignments.php';

// assignment_id'yi sınava taşı
header("Location: {$target}?assignment_id={$assignment_id}");
exit;
