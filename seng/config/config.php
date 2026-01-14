<?php
// config/config.php
return [
  'db' => [
    // XAMPP/MAMP/WAMP'e göre düzenle
    'dsn'  => 'mysql:host=localhost;dbname=assessment_db;charset=utf8mb4',
    'user' => 'root',
    'pass' => '',
  ],

  // Süreler: FR17/20/21'e göre
  'tests' => [
    'GRAMMAR' => ['duration_sec' => 30 * 60, 'count' => 20],
    'VOCAB'   => ['duration_sec' => 25 * 60, 'count' => 20],
    'READING' => [
      1 => ['duration_sec' => 15 * 60, 'count' => 10],
      2 => ['duration_sec' => 15 * 60, 'count' => 10],
    ],
  ],

  // FR24 extension 4a (policy): pause çok uzarsa auto-submit.
  // Mock için 60 dk bıraktım. İstersen 0 yapıp disable edebilirsin.
  'pause_policy_limit_sec' => 60 * 60,
];
