<?php
session_start();
require '../config/db.php'; // DB bağlantın

$student_id = $_SESSION['user_id'];
$class_code = $_POST['class_code'];

/* 1. Class var mı? */
$class = $db->prepare("SELECT id FROM classes WHERE class_code = ?");
$class->execute([$class_code]);
$class = $class->fetch();

if (!$class) {
    die("Invalid class code");
}

/* 2. Join request oluştur */
$stmt = $db->prepare("
  INSERT INTO class_join_requests (class_id, student_id)
  VALUES (?, ?)
");
$stmt->execute([$class['id'], $student_id]);

echo "Join request sent!";
