<?php
declare(strict_types=1);
require_once __DIR__ . '/../Helpers/functions.php';
$profileErrors = $_SESSION['_profile_errors'] ?? [];
$passwordErrors = $_SESSION['_password_errors'] ?? [];
$old = $_SESSION['_profile_old'] ?? [];
unset($_SESSION['_profile_errors'], $_SESSION['_password_errors'], $_SESSION['_profile_old']);

$user = $user ?? [];
$role = $user['role'] ?? '';
$displayName = $user['agent_name'] ?: ($user['employee_name'] ?: $user['username'] ?? '');
$photo = !empty($user['photo_path']) ? url($user['photo_path']) : url('assets/lahomes/images/users/avatar-1.jpg');
?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header border-bottom">
        <h4 class="card-title mb-0">Profile Details</h4>
      </div>
      <div class="card-body">
        <form method="post" action="<?= e(url('profile/update')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3 d-flex align-items-center gap-3">
            <img src="<?= e($photo) ?>" alt="Profile photo" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover;">
            <div>
              <label class="form-label">Profile Photo</label>
              <input type="file" class="form-control" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Agent Name</label>
            <input class="form-control" name="name" value="<?= e($old['name'] ?? $displayName) ?>" placeholder="Your name">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= e($old['email'] ?? ($user['email'] ?? '')) ?>" placeholder="name@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control" name="contact_phone" value="<?= e($old['contact_phone'] ?? ($user['contact_phone'] ?? '')) ?>" placeholder="+971 5x xxx xxxx">
          </div>
          <?php if ($role === 'AGENT'): ?>
            <div class="mb-3">
              <label class="form-label">RERA Number</label>
              <input class="form-control" name="rera_number" value="<?= e($old['rera_number'] ?? ($user['rera_number'] ?? '')) ?>" placeholder="RERA number">
            </div>
          <?php endif; ?>

          <?php if ($profileErrors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">
              <i class="ri-save-line me-1"></i>Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header border-bottom">
        <h4 class="card-title mb-0">Change Password</h4>
      </div>
      <div class="card-body">
        <form method="post" action="<?= e(url('profile/password')) ?>" id="passwordForm">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" id="current_password" autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" id="new_password" autocomplete="new-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" id="confirm_password" autocomplete="new-password">
          </div>
          <div id="passwordLiveErrors" class="text-danger small mb-3"></div>

          <?php if ($passwordErrors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($passwordErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">
              <i class="ri-lock-password-line me-1"></i>Update Password
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var form = document.getElementById('passwordForm');
    var current = document.getElementById('current_password');
    var next = document.getElementById('new_password');
    var confirm = document.getElementById('confirm_password');
    var errorsEl = document.getElementById('passwordLiveErrors');
    if (!form || !current || !next || !confirm || !errorsEl) return;

    function validate() {
      var errs = [];
      if (!current.value) errs.push('Current password is required.');
      if (next.value.length > 0 && next.value.length < 8) errs.push('New password must be at least 8 characters.');
      if (next.value && confirm.value && next.value !== confirm.value) errs.push('New password and confirmation do not match.');
      errorsEl.textContent = errs.join(' ');
      return errs.length === 0;
    }

    ['input', 'blur'].forEach(function (evt) {
      form.addEventListener(evt, validate, true);
    });

    form.addEventListener('submit', function (e) {
      if (!validate()) {
        e.preventDefault();
      }
    });
  })();
</script>
