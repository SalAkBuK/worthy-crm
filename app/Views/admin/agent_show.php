<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$agent = $agent ?? [];
$name = $agent['agent_name'] ?: ($agent['employee_name'] ?: ucfirst((string)($agent['username'] ?? '')));
$formErrors = $_SESSION['_form_errors'] ?? [];
unset($_SESSION['_form_errors']);
$autoOpenEdit = ($_GET['edit'] ?? '') === '1';
?>
<div class="row g-4">
  <div class="col-xl-4">
    <div class="card">
      <div class="card-body text-center">
        <?php $photo = $agent['photo_path'] ? url($agent['photo_path']) : url('assets/lahomes/images/users/avatar-1.jpg'); ?>
        <img src="<?= e($photo) ?>" alt="avatar" class="avatar-xl rounded-circle border border-light">
        <h4 class="mt-3 mb-1"><?= e($name) ?></h4>
        <p class="text-muted mb-0"><?= e($agent['email'] ?? '-') ?></p>
        <p class="text-muted mb-0"><?= e($agent['contact_phone'] ?? '-') ?></p>
        <div class="d-flex justify-content-center gap-2 mt-3">
          <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#agentEditModal">
            <i class="ri-edit-line me-1"></i>Edit
          </button>
          <a class="btn btn-light btn-sm" href="<?= e(url('admin/leads?agent=' . $agent['id'])) ?>">
            <i class="ri-eye-line me-1"></i>Leads
          </a>
          <a class="btn btn-outline-light btn-sm" href="<?= e(url('admin/agents')) ?>">
            <i class="ri-arrow-left-line me-1"></i>Back
          </a>
        </div>
      </div>
      <div class="card-footer bg-light-subtle">
        <div class="row text-center">
          <div class="col-6">
            <div class="text-muted fs-12">Assigned Leads</div>
            <div class="fw-semibold"><?= e((string)($agent['leads_count'] ?? 0)) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted fs-12">Followups</div>
            <div class="fw-semibold"><?= e((string)($agent['followups_count'] ?? 0)) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h4 class="card-title mb-0">Agent Details</h4>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted fs-12">Username</div>
            <div class="fw-semibold"><?= e($agent['username'] ?? '-') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-12">Employee Code</div>
            <div class="fw-semibold"><?= e($agent['employee_code'] ?? '-') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-12">Role</div>
            <div class="fw-semibold"><?= e($agent['role'] ?? 'AGENT') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-12">Properties Scope</div>
            <div class="fw-semibold"><?= e($agent['properties_scope'] ?? '-') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-12">RERA Number</div>
            <div class="fw-semibold"><?= e($agent['rera_number'] ?? '-') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-12">Last Contact</div>
            <div class="fw-semibold"><?= e($agent['last_contact'] ?? '-') ?></div>
          </div>
          <div class="col-md-12">
            <div class="text-muted fs-12">Created At</div>
            <div class="fw-semibold"><?= e($agent['created_at'] ?? '-') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="agentEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= e(url('admin/agent/update')) ?>">
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e((string)($agent['id'] ?? 0)) ?>">

          <?php if ($formErrors): ?>
            <div class="alert alert-danger">
              <div class="fw-semibold mb-1">Please fix:</div>
              <ul class="mb-0">
                <?php foreach ($formErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= e($agent['email'] ?? '') ?>" placeholder="name@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Phone</label>
            <input type="tel" class="form-control" name="contact_phone" value="<?= e($agent['contact_phone'] ?? '') ?>" placeholder="+971 5x xxx xxxx">
          </div>
          <div class="mb-0">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= e($agent['username'] ?? '') ?>" disabled>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($autoOpenEdit): ?>
  <script>
    window.addEventListener('load', function () {
      var modalEl = document.getElementById('agentEditModal');
      if (!modalEl || typeof bootstrap === 'undefined') return;
      var modal = new bootstrap.Modal(modalEl);
      modal.show();
    });
  </script>
<?php endif; ?>
