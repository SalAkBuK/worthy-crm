<?php
declare(strict_types=1);

return [
  'host' => getenv('DB_HOST') ?: 'localhost',
  'name' => getenv('DB_NAME') ?: 'wortuckd_attendance',
  'user' => getenv('DB_USER') ?: 'wortuckd_admin',
  'pass' => getenv('DB_PASS') ?: 'Mughees100++',
  'charset' => 'utf8mb4',
];
