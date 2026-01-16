<?php
require '../config/db.php';

$request_id = $_GET['id'];

$db->prepare("
  UPDATE class_join_requests
  SET status = 'rejected'
  WHERE id = ?
")->execute([$request_id]);

echo "Request rejected";
