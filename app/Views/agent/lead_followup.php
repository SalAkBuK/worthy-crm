<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$formErrors = $_SESSION['_form_errors'] ?? [];
unset($_SESSION['_form_errors']);

$completed = count($followups);
$isClosed = ($lead['status_overall'] ?? '') === 'CLOSED';
$progressPct = $isClosed ? 100 : min(100, (int)round(($completed/3)*100));
$followupBlocked = $followupBlocked ?? false;
$blockReason = $blockReason ?? null;
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header bg-light-subtle d-flex justify-content-between align-items-start border-bottom">
        <div>
          <h4 class="card-title mb-1"><?= e($lead['lead_name']) ?></h4>
          <p class="text-muted mb-0"><?= e($lead['contact_email']) ?></p>
          <p class="text-muted mb-0"><?= e($lead['contact_phone'] ?? '-') ?></p>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($lead['property_type']) ?></span>
            <?php
              $s = $lead['status_overall'];
              $cls = $s==='CLOSED'?'success':($s==='IN_PROGRESS'?'warning':'secondary');
            ?>
            <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> fw-medium fs-13 px-2 py-1"><?= e($s) ?></span>
          </div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="<?= e(url('agent/leads')) ?>">
          <i class="ri-arrow-left-line me-1"></i>Back
        </a>
      </div>
      <div class="card-body">
        <label class="form-label text-muted mb-1">Interested In</label>
        <div class="fw-semibold mb-3"><?= e($lead['interested_in_property']) ?></div>

        <div class="d-flex align-items-center justify-content-between">
          <div class="fw-semibold">Completion Progress</div>
          <div class="text-muted">
            <?= $isClosed ? 'Completed' : e((string)$completed) . '/3 attempts' ?>
          </div>
        </div>
        <div class="progress mt-2">
          <div class="progress-bar <?= $isClosed ? 'bg-success' : '' ?>" role="progressbar" style="width: <?= e((string)$progressPct) ?>%" aria-valuenow="<?= e((string)$progressPct) ?>" aria-valuemin="0" aria-valuemax="100">
            <?= e((string)$progressPct) ?>%
          </div>
        </div>
      </div>
      <div class="card-header border-top border-bottom">
        <h4 class="card-title mb-0">Previous Attempts</h4>
      </div>
      <div class="card-body">
        <?php if ($followupBlocked): ?>
          <div class="alert alert-warning">
            <?= e($blockReason ?: 'Follow-ups are currently disabled for this lead.') ?>
          </div>
        <?php endif; ?>
        <?php if (!$followups): ?>
          <div class="text-muted">No attempts yet.</div>
        <?php else: ?>
          <?php foreach ($followups as $f): ?>
            <div class="border rounded-3 p-3 mb-2 bg-light-subtle">
              <div class="d-flex justify-content-between">
                <div class="fw-semibold">Attempt #<?= e((string)$f['attempt_no']) ?></div>
                <div class="text-muted small"><?= e($f['contact_datetime']) ?></div>
              </div>
              <div class="mt-2 d-flex flex-wrap gap-2">
                <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['call_status']) ?></span>
                <?php if (!in_array($f['call_status'], ['NO_RESPONSE','ASK_CONTACT_LATER'], true)): ?>
                  <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['interested_status']) ?></span>
                <?php endif; ?>
                <?php if ($f['intent']): ?><span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['intent']) ?></span><?php endif; ?>
                <?php if ($f['unit_type']): ?><span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($f['unit_type']) ?></span><?php endif; ?>
              </div>
              <?php
                $details = [];
                if (!empty($f['buy_property_type'])) $details[] = $f['buy_property_type'];
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
                $detailsStr = $details ? implode(' | ', $details) : '';
              ?>
              <?php if ($detailsStr): ?>
                <div class="small text-muted mt-2"><?= e($detailsStr) ?></div>
              <?php endif; ?>
              <div class="text-muted small mt-2"><?= e(mb_strimwidth($f['notes'], 0, 120, '...')) ?></div>
              <div class="mt-2 d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-soft-primary" target="_blank" href="<?= e(url($f['call_screenshot_path'])) ?>">
                  <i class="ri-image-line me-1"></i>Call
                </a>
                <?php if ((int)$f['whatsapp_contacted'] === 1 && $f['whatsapp_screenshot_path']): ?>
                  <a class="btn btn-sm btn-soft-success" target="_blank" href="<?= e(url($f['whatsapp_screenshot_path'])) ?>">
                    <i class="ri-whatsapp-line me-1"></i>WhatsApp
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-1">Add Follow-up</h4>
          <p class="text-muted mb-0 fs-13">Attempt #<?= e((string)$nextAttempt) ?> (sequence enforced)</p>
        </div>
      </div>
      <div class="card-body">

        <?php if ($formErrors): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix:</div>
            <ul class="mb-0">
              <?php foreach ($formErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($nextAttempt > 1 && $completed < ($nextAttempt-1)): ?>
          <div class="alert alert-warning">You must complete the previous attempt before continuing.</div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('agent/followup')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="lead_id" value="<?= e((string)$lead['id']) ?>">
          <input type="hidden" name="tz_offset" id="tz_offset" value="">
          <input type="hidden" name="client_now" id="client_now" value="">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-required">Contact Date & Time</label>
              <input type="datetime-local" class="form-control" name="contact_datetime" required <?= $followupBlocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Call Status</label>
              <select class="form-select" name="call_status" id="call_status" required <?= $followupBlocked ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <option value="NO_RESPONSE">No response</option>
                <option value="RESPONDED">Responded</option>
                <option value="ASK_CONTACT_LATER">Ask to contact later</option>
              </select>
            </div>

            <div class="col-md-6 d-none" id="nextFollowupWrap">
              <label class="form-label form-required">Next Follow-up Date & Time</label>
              <input type="datetime-local" class="form-control" name="next_followup_at" id="next_followup_at" <?= $followupBlocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-md-6" id="interestedStatusWrap">
              <label class="form-label form-required">Interested Status</label>
              <select class="form-select" name="interested_status" id="interested_status" required <?= $followupBlocked ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <option value="INTERESTED">Interested</option>
                <option value="NOT_INTERESTED">Not interested</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label form-required">Call Screenshot (proof)</label>
              <input type="file" class="form-control" name="call_screenshot" accept=".jpg,.jpeg,.png,.webp" required <?= $followupBlocked ? 'disabled' : '' ?>>
              <div class="form-text">Allowed: jpg/png/webp, max 3MB.</div>
            </div>

            <div id="ifInterested" class="col-12 d-none">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <div class="fw-semibold mb-2">If Interested</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label form-required">Intent</label>
                    <select class="form-select" name="intent" id="intent" <?= $followupBlocked ? 'disabled' : '' ?>>
                      <option value="">Select</option>
                      <option value="RENT">Rent</option>
                      <option value="BUY">Buy</option>
                    </select>
                  </div>
                  <div class="col-md-6 d-none" id="buyPropertyTypeWrap">
                    <label class="form-label form-required">Buy Property Type</label>
                    <select class="form-select" name="buy_property_type" id="buy_property_type" <?= $followupBlocked ? 'disabled' : '' ?>>
                      <option value="">Select</option>
                      <option value="READY_TO_MOVE">Ready To Move</option>
                      <option value="OFF_PLAN">Off Plan</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div id="buyReadyFields" class="col-12 d-none">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <div class="fw-semibold mb-2">BUY • Ready To Move</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label form-required">Unit Type</label>
                    <select class="form-select" name="unit_type_buy" id="unit_type_buy" <?= $followupBlocked ? 'disabled' : '' ?>>
                      <option value="">Select</option>
                      <option value="VILLA">Villa</option>
                      <option value="APARTMENT">Apartment</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" placeholder="Location" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Building</label>
                    <input type="text" class="form-control" name="building" placeholder="Building name" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Beds</label>
                    <input type="number" min="0" class="form-control" name="beds" placeholder="e.g. 2" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Size (sqft)</label>
                    <input type="number" min="0" class="form-control" name="size_sqft" placeholder="e.g. 1200" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Budget (AED)</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="budget" placeholder="e.g. 950000 AED" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Down Payment (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="form-control" name="downpayment" placeholder="e.g. 10" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                </div>
              </div>
            </div>

            <div id="buyOffplanFields" class="col-12 d-none">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <div class="fw-semibold mb-2">BUY • Off Plan</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location_offplan" placeholder="Location" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Size (sqft)</label>
                    <input type="number" min="0" class="form-control" name="size_sqft_offplan" placeholder="e.g. 1200" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Budget (AED)</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="budget_offplan" placeholder="e.g. 950000 AED" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Down Payment (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="form-control" name="downpayment_offplan" placeholder="e.g. 10" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                </div>
              </div>
            </div>

            <div id="rentFields" class="col-12 d-none">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <div class="fw-semibold mb-2">RENT</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label form-required">Unit Type</label>
                    <select class="form-select" name="unit_type_rent" id="unit_type_rent" <?= $followupBlocked ? 'disabled' : '' ?>>
                      <option value="">Select</option>
                      <option value="VILLA">Villa</option>
                      <option value="APARTMENT">Apartment</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location_rent" placeholder="Location" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Building</label>
                    <input type="text" class="form-control" name="building_rent" placeholder="Building name" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Size (sqft)</label>
                    <input type="number" min="0" class="form-control" name="size_sqft_rent" placeholder="e.g. 1200" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Beds</label>
                    <input type="number" min="0" class="form-control" name="beds_rent" placeholder="e.g. 2" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Number of Cheques</label>
                    <input type="number" min="0" class="form-control" name="cheques" placeholder="e.g. 4" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Rent per Month</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="rent_per_month" placeholder="e.g. 8000" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Yearly Budget</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="rent_per_year_budget" placeholder="e.g. 96000" <?= $followupBlocked ? 'disabled' : '' ?>>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label form-required">Notes</label>
              <textarea class="form-control" name="notes" id="notes" rows="4" minlength="50" required <?= $followupBlocked ? 'disabled' : '' ?>></textarea>
              <div class="form-text">Minimum 50 characters. <span class="mono" id="notesCount">0</span></div>
            </div>

            <div class="col-12">
              <div class="alert alert-info d-flex align-items-start gap-2 mb-0" id="followupGuidance">
                <i class="ri-information-line fs-18"></i>
                <div>
                  <div class="fw-semibold">Next step guidance</div>
                  <div class="small" id="guidanceText">Select call status to see recommended action.</div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="whatsapp_contacted" name="whatsapp_contacted" value="1" <?= $followupBlocked ? 'disabled' : '' ?>>
                <label class="form-check-label" for="whatsapp_contacted">Contacted on WhatsApp?</label>
              </div>
            </div>

            <div class="col-12 d-none" id="whatsappBox">
              <label class="form-label form-required">WhatsApp Screenshot</label>
              <input type="file" class="form-control" name="whatsapp_screenshot" accept=".jpg,.jpeg,.png,.webp" <?= $followupBlocked ? 'disabled' : '' ?>>
            </div>

            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-primary" type="submit" <?= $followupBlocked ? 'disabled' : '' ?>>
                <i class="ri-save-line me-1"></i>Save Attempt #<?= e((string)$nextAttempt) ?>
              </button>
            </div>
          </div>
        </form>

        <?php if ($isClosed): ?>
          <div class="alert alert-success mt-3 mb-0">
            This lead is completed (3+ attempts). You can still add extra attempts if needed by reopening later.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="<?= e(url('assets/js/agent_followup.js')) ?>"></script>
<script>
  (function(){
    var tz = document.getElementById('tz_offset');
    var now = document.getElementById('client_now');
    if (tz) tz.value = String(new Date().getTimezoneOffset());
    if (now) now.value = String(Date.now());
    var form = document.querySelector('form[action="<?= e(url('agent/followup')) ?>"]');
    if (form) {
      form.addEventListener('submit', function(){
        if (tz) tz.value = String(new Date().getTimezoneOffset());
        if (now) now.value = String(Date.now());
      });
    }
  })();
</script>
