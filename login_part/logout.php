<?php
// Seng321/login_part/logout.php

session_start();

/*
  TÜM SESSION VERİLERİNİ TEMİZLE
*/
$_SESSION = [];

/*
  Session cookie varsa onu da sil
*/
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/*
  Session'ı tamamen yok et
*/
session_destroy();

/*
  Login sayfasına gönder
*/
header("Location: /Seng321/login_part/index.php");
exit;
