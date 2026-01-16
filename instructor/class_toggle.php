<?php
require_once __DIR__ . "/../includes/instructor_guard.php";
require_once __DIR__ . "/../config/db.php";

$instructorId = (int)($_SESSION["user"]["id"] ?? 0);
$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("UPDATE class_codes
                       SET is_active = IF(is_active=1,0,1)
                       WHERE id=? AND instructor_id=?");
$stmt->execute([$id, $instructorId]);

header("Location: /Seng321/instructor/class_codes.php");
exit;
