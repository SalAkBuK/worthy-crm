<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$formErrors = $_SESSION['_form_errors'] ?? [];
$old = $_SESSION['_form_old'] ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
?>
<div class="row g-4">
  <div class="col-xl-3 col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 border-bottom pb-3">
          <img src="<?= e(url('assets/lahomes/images/users/avatar-1.jpg')) ?>" alt="avatar" class="avatar-lg rounded-3 border border-light border-3">
          <div class="d-block">
            <div class="text-dark fw-medium fs-16">New Agent</div>
            <p class="mb-0 text-muted">Add details and save</p>
          </div>
        </div>
        <p class="mt-3 d-flex align-items-center gap-2 mb-2">
          <iconify-icon icon="solar:home-bold-duotone" class="fs-18 text-primary"></iconify-icon>
          Property Scope: OFF-PLAN / SECONDARY / BOTH
        </p>
        <p class="d-flex align-items-center gap-2 mt-2">
          <iconify-icon icon="solar:shield-check-bold-duotone" class="fs-18 text-primary"></iconify-icon>
          Keep RERA number accurate
        </p>
      </div>
      <div class="card-footer bg-light-subtle">
        <div class="row g-2">
          <div class="col-lg-6">
            <button form="agentAddForm" class="btn btn-primary w-100" type="submit">Save</button>
          </div>
          <div class="col-lg-6">
            <a href="<?= e(url('admin/agents')) ?>" class="btn btn-outline-primary w-100">Cancel</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-9 col-lg-8">
    <form id="agentAddForm" method="post" action="<?= e(url('admin/agent/create')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <?php if ($formErrors): ?>
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Please fix:</div>
          <ul class="mb-0">
            <?php foreach ($formErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Add Agent Photo</h4>
        </div>
        <div class="card-body">
          <div class="mb-0">
            <label class="form-label">Agent Photo</label>
            <input type="file" class="form-control" name="agent_photo" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Allowed: jpg/png/webp, max 3MB.</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Agent Information</h4>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-lg-6">
              <label class="form-label form-required">Agent Name</label>
              <input type="text" class="form-control" name="agent_name" value="<?= e($old['agent_name'] ?? '') ?>" placeholder="Full name" required>
            </div>
            <div class="col-lg-6">
              <label class="form-label form-required">Agent Email</label>
              <input type="email" class="form-control" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="name@example.com" required>
            </div>
            <div class="col-lg-6">
              <label class="form-label form-required">Agent Number</label>
              <input type="tel" class="form-control" name="contact_phone" value="<?= e($old['contact_phone'] ?? '') ?>" placeholder="+971 5x xxx xxxx" required>
            </div>
            <div class="col-lg-6">
              <label class="form-label form-required">Properties Scope</label>
              <select class="form-select" name="properties_scope" required>
                <option value="">Select</option>
                <option value="OFF_PLAN" <?= (($old['properties_scope'] ?? '') === 'OFF_PLAN') ? 'selected' : '' ?>>OFF-PLAN</option>
                <option value="SECONDARY" <?= (($old['properties_scope'] ?? '') === 'SECONDARY') ? 'selected' : '' ?>>SECONDARY</option>
                <option value="BOTH" <?= (($old['properties_scope'] ?? '') === 'BOTH') ? 'selected' : '' ?>>BOTH</option>
              </select>
            </div>
            <div class="col-lg-6">
              <label class="form-label form-required">RERA Number</label>
              <input type="text" class="form-control" name="rera_number" value="<?= e($old['rera_number'] ?? '') ?>" placeholder="RERA number" required>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
