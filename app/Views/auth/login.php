<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$flashes = get_flashes();
$title = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php require __DIR__ . '/../layouts/lahomes/head.php'; ?>
</head>
<body class="authentication-bg">
  <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-xl-5">
          <div class="card auth-card">
            <div class="card-body px-3 py-5">
              <div class="mx-auto mb-4 text-center auth-logo">
                <a href="<?= e(url('login')) ?>" class="logo-dark">
                  <img src="<?= e(url('assets/lahomes/images/logo-light.png')) ?>" height="32" alt="Worthy CRM">
                </a>
                <a href="<?= e(url('login')) ?>" class="logo-light">
                  <img src="<?= e(url('assets/lahomes/images/logo-light.png')) ?>" height="32" alt="Worthy CRM">
                </a>
              </div>

              <h2 class="fw-bold text-uppercase text-center fs-18">Sign In</h2>
              <p class="text-muted text-center mt-1 mb-4">Enter your username and password to access the portal.</p>

              <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
              <?php endforeach; ?>

              <div class="px-4">
                <form method="post" action="<?= e(url('login')) ?>" class="authentication-form" novalidate>
                  <?= csrf_field() ?>
                  <div class="mb-3">
                    <label class="form-label" for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" class="form-control bg-light bg-opacity-50 border-light py-2" placeholder="Enter your username" autocomplete="username" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="login-password">Password</label>
                    <div class="input-group">
                      <input type="password" id="login-password" name="password" class="form-control bg-light bg-opacity-50 border-light py-2" placeholder="Enter your password" autocomplete="current-password" required>
                      <button type="button" class="btn btn-outline-secondary" id="toggle-login-password" aria-label="Show password" aria-pressed="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <div class="mb-1 text-center d-grid">
                    <button class="btn btn-danger py-2 fw-medium" type="submit">Sign In</button>
                  </div>
                </form>

                <div class="small text-muted text-center mt-3">
                  Contact your administrator if you need access.
                </div>
              </div>
            </div>
          </div>

          <p class="mb-0 text-center text-white">Worthy CRM</p>
        </div>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/../layouts/lahomes/vendor-scripts.php'; ?>
  <script>
    (function () {
      var toggle = document.getElementById('toggle-login-password');
      var input = document.getElementById('login-password');
      if (!toggle || !input) {
        return;
      }
      toggle.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
      });
    })();
  </script>
</body>
</html>
