<?php
require_once __DIR__ . '/../config/db.php';

function create_attempt($userId, $type, $durationSeconds, $part=null, $meta=[]) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO assessment_attempts (user_id,type,part,duration_seconds,meta_json) VALUES (?,?,?,?,?)");
  $stmt->execute([$userId, $type, $part, $durationSeconds, json_encode($meta)]);
  return (int)$pdo->lastInsertId();
}

function get_attempt($attemptId, $userId) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM assessment_attempts WHERE id=? AND user_id=?");
  $stmt->execute([$attemptId, $userId]);
  return $stmt->fetch();
}

function submit_attempt($attemptId, $userId) {
  global $pdo;
  $stmt = $pdo->prepare("UPDATE assessment_attempts SET status='submitted', submitted_at=NOW() WHERE id=? AND user_id=?");
  $stmt->execute([$attemptId, $userId]);
}

function save_answer($attemptId, $qIndex, $qText, $aText) {
  global $pdo;
  $stmt = $pdo->prepare("
    INSERT INTO assessment_answers (attempt_id,question_index,question_text,answer_text)
    VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text), question_text=VALUES(question_text)
  ");
  $stmt->execute([$attemptId, $qIndex, $qText, $aText]);
}

function get_answers($attemptId) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM assessment_answers WHERE attempt_id=? ORDER BY question_index ASC");
  $stmt->execute([$attemptId]);
  return $stmt->fetchAll();
}

function save_ai_result($attemptId, $model, $resultArr) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO ai_results (attempt_id,model,result_json) VALUES (?,?,?)");
  $stmt->execute([$attemptId, $model, json_encode($resultArr)]);
}
