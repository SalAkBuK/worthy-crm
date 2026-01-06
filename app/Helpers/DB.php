<?php
declare(strict_types=1);

namespace App\Helpers;

use PDO;
use PDOException;

final class DB {
  private static ?PDO $pdo = null;

  public static function conn(): PDO {
    if (self::$pdo) return self::$pdo;

    $cfg = require __DIR__ . '/../../config/database.php';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset']);

    try {
      $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
      self::$pdo = $pdo;
      return $pdo;
    } catch (PDOException $e) {
      throw new \RuntimeException('Database connection failed. Please verify your DB settings in .env and config/database.php');
    }
  }
}
