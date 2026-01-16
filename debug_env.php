<?php
require_once __DIR__ . '/init.php';

echo "DB_HOST env: " . var_export(getenv('DB_HOST'), true) . "\n";
echo "DB_NAME env: " . var_export(getenv('DB_NAME'), true) . "\n";
echo "DB_USER env: " . var_export(getenv('DB_USER'), true) . "\n";
echo "DB_PASS env: " . (getenv('DB_PASS') ? '[SET]' : '[NOT SET]') . "\n";

$cfg = require __DIR__ . '/config/database.php';
echo "Config Host: " . $cfg['host'] . "\n";
echo "Config Name: " . $cfg['name'] . "\n";
echo "Config User: " . $cfg['user'] . "\n";
