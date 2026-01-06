<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>419 - Session Expired</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="text-center">
      <h1 class="display-6 mb-2">419 - Session Expired</h1>
      <p class="text-muted mb-4">Your session has expired or the CSRF token is invalid. Please login again.</p>
      <a class="btn btn-brand text-white" href="<?= e(url('login')) ?>">Login</a>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
