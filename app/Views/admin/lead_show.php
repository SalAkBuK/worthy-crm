<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$returnPath = $_GET['return'] ?? 'admin/leads';
$safeReturn = str_starts_with($returnPath, 'http') ? 'admin/leads' : ltrim($returnPath, '/');
$user = current_user();
$role = $user['role'] ?? '';
$canDelete = in_array($role, ['ADMIN', 'CEO'], true);
?>
<div class="card">
  <div class="card-header bg-light-subtle d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border-bottom">
    <div>
      <h4 class="card-title mb-1"><?= e($lead['lead_name']) ?></h4>
      <?php
        $email = trim((string)($lead['contact_email'] ?? ''));
        $phone = trim((string)($lead['contact_phone'] ?? ''));
      ?>
      <p class="text-muted mb-0"><?= e($email !== '' ? $email : '-') ?></p>
      <p class="text-muted mb-0"><?= e($phone !== '' ? $phone : '-') ?></p>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($lead['property_type']) ?></span>
        <?php
          $s = $lead['status_overall'];
          if ($s === 'CLOSED') {
            $cls = 'success';
          } elseif ($s === 'IN_PROGRESS') {
            $cls = 'warning';
          } elseif ($s === '50/50') {
            $cls = 'info';
          } elseif ($s === 'ON_HOLD') {
            $cls = 'primary';
          } else {
            $cls = 'secondary';
          }
        ?>
        <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> fw-medium fs-13 px-2 py-1"><?= e($s) ?></span>
      </div>
    </div>
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100 w-sm-auto">
      <?php if (($lead['status_overall'] ?? '') === 'CLOSED'): ?>
        <form method="post" action="<?= e(url('admin/lead/reopen')) ?>" onsubmit="return confirm('Reopen this lead to allow more attempts?');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e((string)$lead['id']) ?>">
          <button class="btn btn-sm btn-primary w-100 w-sm-auto" type="submit">
            <i class="ri-refresh-line me-1"></i>Reopen
          </button>
        </form>
      <?php endif; ?>
      <?php if ($canDelete): ?>
        <form method="post" action="<?= e(url('admin/lead/delete')) ?>" onsubmit="return confirm('Delete this lead and all follow-ups? This cannot be undone.');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e((string)$lead['id']) ?>">
          <input type="hidden" name="return" value="<?= e($safeReturn) ?>">
          <button class="btn btn-sm btn-soft-danger w-100 w-sm-auto" type="submit">
            <i class="ri-delete-bin-line me-1"></i>Delete
          </button>
        </form>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-light w-100 w-sm-auto" href="<?= e(url($safeReturn)) ?>">
        <i class="ri-arrow-left-line me-1"></i>Back
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3 d-none d-md-flex">
      <div class="col-md-6">
        <label class="form-label text-muted mb-1">Interested In</label>
        <div class="fw-semibold"><?= e($lead['interested_in_property']) ?></div>
      </div>
      <div class="col-md-6">
        <label class="form-label text-muted mb-1">Assigned Agent</label>
        <div class="fw-semibold"><?= e($lead['agent_name']) ?></div>
      </div>
    </div>
    <div class="d-md-none">
      <div class="card border mb-2">
        <div class="card-body p-3">
          <div class="text-muted fs-12">Interested In</div>
          <div class="fw-semibold"><?= e($lead['interested_in_property']) ?></div>
        </div>
      </div>
      <div class="card border">
        <div class="card-body p-3">
          <div class="text-muted fs-12">Assigned Agent</div>
          <div class="fw-semibold"><?= e($lead['agent_name']) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="card-header border-top border-bottom">
    <h4 class="card-title mb-0">Follow-ups</h4>
  </div>

  <?php if (!$followups): ?>
    <div class="card-body">
      <div class="text-muted">No follow-ups yet.</div>
    </div>
  <?php else: ?>
    <div class="table-responsive d-none d-lg-block">
      <table class="table align-middle text-nowrap table-hover table-centered mb-0">
        <thead class="bg-light-subtle">
          <tr>
              <th>Attempt</th>
              <th>Contact Date</th>
              <th>Call Status</th>
              <th>Interested</th>
              <th>Details</th>
              <th>Notes</th>
              <th>Proof</th>
            </tr>
        </thead>
        <tbody>
          <?php foreach ($followups as $f): ?>
            <tr>
              <td>#<?= e((string)$f['attempt_no']) ?></td>
              <td><?= e($f['contact_datetime']) ?></td>
              <td><span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['call_status']) ?></span></td>
              <td>
                <?php if (!in_array($f['call_status'], ['NO_RESPONSE','ASK_CONTACT_LATER'], true)): ?>
                  <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['interested_status']) ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $details = [];
                  if (!empty($f['intent'])) $details[] = $f['intent'];
                  if (!empty($f['buy_property_type'])) $details[] = $f['buy_property_type'];
                  if (!empty($f['unit_type'])) $details[] = $f['unit_type'];
                  if (!empty($f['location'])) $details[] = 'Loc: ' . $f['location'];
                  if (!empty($f['building'])) $details[] = 'Bldg: ' . $f['building'];
                  if (!empty($f['size_sqft'])) $details[] = 'Sqft: ' . $f['size_sqft'];
                  if (!empty($f['beds'])) $details[] = 'Beds: ' . $f['beds'];
                  if (!empty($f['budget'])) $details[] = 'Budget: ' . $f['budget'] . ' AED';
                  if (!empty($f['downpayment'])) $details[] = 'Down: ' . $f['downpayment'] . '%';
                  if (!empty($f['rent_per_month'])) $details[] = 'Rent/M: ' . $f['rent_per_month'] . ' AED';
                  if (!empty($f['rent_per_year_budget'])) $details[] = 'Rent/Y: ' . $f['rent_per_year_budget'] . ' AED';
                  if (!empty($f['cheques'])) $details[] = 'Cheques: ' . $f['cheques'];
                  if (!empty($f['next_followup_at'])) $details[] = 'Next: ' . $f['next_followup_at'];
                  $detailsStr = $details ? implode(' | ', $details) : '-';
                ?>
                <div class="small text-muted"><?= e($detailsStr) ?></div>
              </td>
              <td style="white-space: pre-wrap;"><?= e((string)$f['notes']) ?></td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <?php if (!empty($f['call_screenshot_path'])): ?>
                    <a class="btn btn-sm btn-soft-primary" target="_blank" href="<?= e(url($f['call_screenshot_path'])) ?>">
                      <i class="ri-image-line me-1"></i>Call
                    </a>
                    <a target="_blank" href="<?= e(url($f['call_screenshot_path'])) ?>" aria-label="View call screenshot">
                      <img src="<?= e(url($f['call_screenshot_path'])) ?>" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;" alt="Call screenshot">
                    </a>
                  <?php else: ?>
                    <span class="text-muted">No call proof</span>
                  <?php endif; ?>
                  <?php if ((int)$f['whatsapp_contacted'] === 1 && $f['whatsapp_screenshot_path']): ?>
                    <a class="btn btn-sm btn-soft-success" target="_blank" href="<?= e(url($f['whatsapp_screenshot_path'])) ?>">
                      <i class="ri-whatsapp-line me-1"></i>WhatsApp
                    </a>
                    <a target="_blank" href="<?= e(url($f['whatsapp_screenshot_path'])) ?>" aria-label="View WhatsApp screenshot">
                      <img src="<?= e(url($f['whatsapp_screenshot_path'])) ?>" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;" alt="WhatsApp screenshot">
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-lg-none">
      <?php foreach ($followups as $f): ?>
        <?php
          $details = [];
          if (!empty($f['intent'])) $details[] = $f['intent'];
          if (!empty($f['buy_property_type'])) $details[] = $f['buy_property_type'];
          if (!empty($f['unit_type'])) $details[] = $f['unit_type'];
          if (!empty($f['location'])) $details[] = 'Loc: ' . $f['location'];
          if (!empty($f['building'])) $details[] = 'Bldg: ' . $f['building'];
          if (!empty($f['size_sqft'])) $details[] = 'Sqft: ' . $f['size_sqft'];
          if (!empty($f['beds'])) $details[] = 'Beds: ' . $f['beds'];
          if (!empty($f['budget'])) $details[] = 'Budget: ' . $f['budget'] . ' AED';
          if (!empty($f['downpayment'])) $details[] = 'Down: ' . $f['downpayment'] . '%';
          if (!empty($f['rent_per_month'])) $details[] = 'Rent/M: ' . $f['rent_per_month'] . ' AED';
          if (!empty($f['rent_per_year_budget'])) $details[] = 'Rent/Y: ' . $f['rent_per_year_budget'] . ' AED';
          if (!empty($f['cheques'])) $details[] = 'Cheques: ' . $f['cheques'];
          if (!empty($f['next_followup_at'])) $details[] = 'Next: ' . $f['next_followup_at'];
          $detailsStr = $details ? implode(' | ', $details) : '-';
        ?>
        <div class="card border mb-2">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div class="fw-semibold">Attempt #<?= e((string)$f['attempt_no']) ?></div>
              <div class="text-muted fs-12"><?= e($f['contact_datetime']) ?></div>
            </div>
            <div class="mt-2 d-flex flex-wrap gap-2">
              <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['call_status']) ?></span>
              <?php if (!in_array($f['call_status'], ['NO_RESPONSE','ASK_CONTACT_LATER'], true)): ?>
                <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['interested_status']) ?></span>
              <?php endif; ?>
            </div>
            <div class="small text-muted mt-2"><?= e($detailsStr) ?></div>
            <div class="text-muted small mt-2" style="white-space: pre-wrap;"><?= e((string)$f['notes']) ?></div>
            <div class="mt-2 d-flex flex-wrap gap-2">
              <?php if (!empty($f['call_screenshot_path'])): ?>
                <a class="btn btn-sm btn-soft-primary" target="_blank" href="<?= e(url($f['call_screenshot_path'])) ?>">
                  <i class="ri-image-line me-1"></i>Call
                </a>
              <?php else: ?>
                <span class="text-muted">No call proof</span>
              <?php endif; ?>
              <?php if ((int)$f['whatsapp_contacted'] === 1 && $f['whatsapp_screenshot_path']): ?>
                <a class="btn btn-sm btn-soft-success" target="_blank" href="<?= e(url($f['whatsapp_screenshot_path'])) ?>">
                  <i class="ri-whatsapp-line me-1"></i>WhatsApp
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
