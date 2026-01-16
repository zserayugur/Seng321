<?php
require '../config/db.php';

$request_id = $_GET['id'];

$db->prepare("
  UPDATE class_join_requests
  SET status = 'approved'
  WHERE id = ?
")->execute([$request_id]);

$db->prepare("
  INSERT INTO enrollments (class_id, student_id)
  SELECT class_id, student_id
  FROM class_join_requests
  WHERE id = ?
")->execute([$request_id]);

echo "Student approved!";
