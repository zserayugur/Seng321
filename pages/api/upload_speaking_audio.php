<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$userId = current_user_id();
$attemptId = (int)($_POST['attempt_id'] ?? 0);

if (!isset($_FILES['audio'])) {
  http_response_code(400); echo json_encode(["error"=>"audio missing"]); exit;
}

$audio = $_FILES['audio'];
if ($audio['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(["error"=>"upload error"]); exit;
}

$ext = pathinfo($audio['name'], PATHINFO_EXTENSION) ?: "webm";
$dir = __DIR__ . "/../../uploads/speaking";
if (!is_dir($dir)) mkdir($dir, 0775, true);

$filename = "spk_" . $userId . "_" . $attemptId . "_" . time() . "." . $ext;
$path = $dir . "/" . $filename;

if (!move_uploaded_file($audio['tmp_name'], $path)) {
  http_response_code(500); echo json_encode(["error"=>"cannot save"]); exit;
}

$mime = $audio['type'] ?: "audio/webm";
$stmt = $pdo->prepare("INSERT INTO assessment_uploads (attempt_id, kind, file_path, mime_type) VALUES (?,?,?,?)");
$stmt->execute([$attemptId, 'speaking_audio', $filename, $mime]);

$publicUrl = "/SENG321/uploads/speaking/" . $filename;
echo json_encode(["ok"=>true, "public_url"=>$publicUrl]);
