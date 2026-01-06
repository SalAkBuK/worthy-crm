<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Logger;

abstract class BaseController {
  protected function handleException(\Throwable $e): void {
    Logger::error($e->getMessage(), ['trace' => app_debug() ? $e->getTraceAsString() : null]);
    http_response_code(500);
    require __DIR__ . '/../Views/errors/500.php';
  }
}
