<?php
require_once __DIR__ . "/../includes/instructor_guard.php";
require_once __DIR__ . "/../config/db.php";

$instructorId = (int)($_SESSION["user"]["id"] ?? 0);
$className = trim($_POST["class_name"] ?? "");

if ($className === "") {
  header("Location: /Seng321/instructor/class_codes.php");
  exit;
}

function generateClassCode(int $len = 8): string {
  $alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // I,O,1,0 yok
  $out = "";
  for ($i=0; $i<$len; $i++) {
    $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
  }
  return $out;
}

for ($try=0; $try<5; $try++) {
  $code = generateClassCode(8);

  try {
    $stmt = $pdo->prepare("INSERT INTO class_codes (code, class_name, instructor_id, is_active)
                           VALUES (?, ?, ?, 1)");
    $stmt->execute([$code, $className, $instructorId]);

    header("Location: /Seng321/instructor/class_codes.php");
    exit;
  } catch (PDOException $e) {
    // unique çakışırsa tekrar dene
    if ((int)$e->getCode() === 23000) continue;
    throw $e;
  }
}

die("Could not generate unique class code. Try again.");
