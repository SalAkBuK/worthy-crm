<?php
declare(strict_types=1);

namespace App\Helpers;

final class View {
  public static function render(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    $viewPath = __DIR__ . '/../Views/' . $view . '.php';
    if (!file_exists($viewPath)) {
      throw new \RuntimeException('View not found: ' . $view);
    }
    ob_start();
    require $viewPath;
    $content = ob_get_clean();
    require __DIR__ . '/../Views/layouts/app.php';
  }

  public static function partial(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../Views/' . $view . '.php';
  }
}
