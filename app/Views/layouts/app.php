<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$user = current_user();
$flashes = get_flashes();
$role = $user['role'] ?? '';
$title = $title ?? 'Dashboard';
$page_title = $page_title ?? $title;
$page_subtitle = $page_subtitle ?? $title;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-topbar-color="light" data-menu-color="dark" data-menu-size="default">
<head>
  <?php require __DIR__ . '/lahomes/head.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="wrapper">
    <?php require __DIR__ . '/lahomes/topbar.php'; ?>
    <?php require __DIR__ . '/lahomes/main-nav.php'; ?>

    <div class="page-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="page-title-box">
              <h4 class="mb-0 fw-semibold"><?= e($page_subtitle) ?></h4>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= e(url('')) ?>"><?= e($page_title) ?></a></li>
                <li class="breadcrumb-item active"><?= e($page_subtitle) ?></li>
              </ol>
            </div>
          </div>
        </div>

        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>

        <?= $content ?? '' ?>
      </div>
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12 text-center">
              <script>document.write(new Date().getFullYear())</script> &copy; Worthy CRM. Crafted by
              <iconify-icon icon="solar:hearts-bold-duotone" class="fs-18 align-middle text-danger"></iconify-icon>
              <a href="#" class="fw-bold footer-text">CodeFier</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <?php require __DIR__ . '/lahomes/vendor-scripts.php'; ?>
</body>
</html>
