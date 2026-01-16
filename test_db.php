<?php
// Load environment manually to simulate init.php
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        if ($k) putenv($k . '=' . $v);
    }
} else {
    echo "ERROR: .env file not found at $envPath\n";
    exit(1);
}

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "Attempting connection to:\n";
echo "Host: $host\n";
echo "DB:   $name\n";
echo "User: $user\n";
echo "Pass: [hidden]\n";

try {
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "SUCCESS: Database connection established.\n";
} catch (PDOException $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
