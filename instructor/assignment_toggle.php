<?php
require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../config/db.php";

$role = strtoupper($_SESSION["user"]["role"] ?? "");
if ($role !== "INSTRUCTOR") { http_response_code(403); die("403 Forbidden (Instructor only)"); }

$instructor_id = (int)$_SESSION["user"]["id"];
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { header("Location: /Seng321/instructor/assignments.php"); exit; }

// güvenlik: bu assignment bu instructor'a mı ait?
$chk = $pdo->prepare("SELECT is_published FROM assignments WHERE id=? AND instructor_id=? LIMIT 1");
$chk->execute([$id, $instructor_id]);
$row = $chk->fetch(PDO::FETCH_ASSOC);

if (!$row) { http_response_code(404); die("Not found"); }

$new = ((int)$row["is_published"] === 1) ? 0 : 1;
$upd = $pdo->prepare("UPDATE assignments SET is_published=? WHERE id=? AND instructor_id=?");
$upd->execute([$new, $id, $instructor_id]);

header("Location: /Seng321/instructor/assignments.php");
exit;