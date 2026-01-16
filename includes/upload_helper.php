<?php
function ensureDir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function safeUpload(array $file, string $destDir, array $allowedExts, int $maxBytes): array {
  if (!isset($file) || !isset($file['error'])) throw new Exception("No file uploaded.");

  if ($file['error'] !== UPLOAD_ERR_OK) {
    throw new Exception("Upload error code: " . $file['error']);
  }

  if ($file['size'] <= 0) throw new Exception("Empty file.");
  if ($file['size'] > $maxBytes) throw new Exception("File too large.");

  $original = $file['name'] ?? 'file';
  $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExts, true)) {
    throw new Exception("Invalid file type. Allowed: " . implode(", ", $allowedExts));
  }

  // basit mime check
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']) ?: null;

  ensureDir($destDir);

  $stored = bin2hex(random_bytes(16)) . "." . $ext;
  $destPath = rtrim($destDir, "/\\") . DIRECTORY_SEPARATOR . $stored;

  if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    throw new Exception("Failed to move uploaded file.");
  }

  return [
    "original_name" => $original,
    "stored_name"   => $stored,
    "mime_type"     => $mime,
    "file_size"     => (int)$file['size'],
  ];
}