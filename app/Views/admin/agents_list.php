<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$filters = $filters ?? [];
$q = $filters['q'] ?? '';
$statusFilter = $filters['status'] ?? '';
$scopeFilter = $filters['scope'] ?? '';
$leadsFilter = $filters['leads'] ?? '';
$agents = $agents ?? [];
$activeAgents = array_values(array_filter($agents, static function ($agent) {
  return (int) ($agent['is_active'] ?? 1) === 1;
}));
?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header border-0">
        <div class="row justify-content-between">
          <div class="col-lg-6">
            <div class="row align-items-center">
              <div class="col-lg-6">
                <form class="app-search d-none d-md-block me-auto" method="get" action="<?= e(url('admin/agents')) ?>">
                  <div class="position-relative">
                    <input type="search" class="form-control" name="q" placeholder="Search Agent" autocomplete="off"
                      value="<?= e($q) ?>">
                    <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                  </div>
                </form>
              </div>
              <div class="col-lg-4">
                <h5 class="text-dark fw-medium mb-0"><?= e((string) count($agents)) ?> <span class="text-muted">
                    Agents</span></h5>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="text-md-end mt-3 mt-md-0">
              <?php
              $exportQuery = build_query(['page' => null]);
              $exportUrl = url('admin/agents/export' . ($exportQuery ? '?' . $exportQuery : ''));
              ?>
              <div class="dropdown d-inline-block me-1">
                <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="ri-settings-2-line me-1"></i>More Setting
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="<?= e($exportUrl) ?>">
                    <i class="ri-download-2-line me-1"></i>Export Agents CSV
                  </a>
                  <form method="post" action="<?= e(url('admin/agents/bulk-reset')) ?>" id="bulkResetForm">
                    <?= csrf_field() ?>
                    <button class="dropdown-item" type="submit">
                      <i class="ri-key-2-line me-1"></i>Reset Password (Selected)
                    </button>
                  </form>
                </div>
              </div>
              <button type="button" class="btn btn-outline-primary me-1" data-bs-toggle="collapse"
                data-bs-target="#agentFilters" aria-expanded="false" aria-controls="agentFilters">
                <i class="ri-filter-line me-1"></i> Filters
              </button>
              <a class="btn btn-success me-1" href="<?= e(url('admin/agent/add')) ?>"><i class="ri-add-line"></i> New
                Agent</a>
            </div>
          </div>
        </div>
      </div>
      <div class="collapse" id="agentFilters">
        <div class="card-body border-top">
          <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/agents')) ?>">
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="">All</option>
                <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                <option value="INACTIVE" <?= $statusFilter === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Properties Scope</label>
              <select class="form-select" name="scope">
                <option value="">All</option>
                <option value="OFF_PLAN" <?= $scopeFilter === 'OFF_PLAN' ? 'selected' : '' ?>>OFF-PLAN</option>
                <option value="SECONDARY" <?= $scopeFilter === 'SECONDARY' ? 'selected' : '' ?>>SECONDARY</option>
                <option value="BOTH" <?= $scopeFilter === 'BOTH' ? 'selected' : '' ?>>BOTH</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Has Leads</label>
              <select class="form-select" name="leads">
                <option value="">All</option>
                <option value="0" <?= $leadsFilter === '0' ? 'selected' : '' ?>>0</option>
                <option value="1-5" <?= $leadsFilter === '1-5' ? 'selected' : '' ?>>1-5</option>
                <option value="6+" <?= $leadsFilter === '6+' ? 'selected' : '' ?>>6+</option>
              </select>
            </div>
            <div class="col-md-3 d-grid">
              <div class="d-grid gap-2">
                <button class="btn btn-primary">Apply Filters</button>
                <a class="btn btn-outline-light" href="<?= e(url('admin/agents')) ?>">Clear Filters</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-0">All Agent List</h4>
        </div>
        <div class="dropdown">
          <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown"
            aria-expanded="false">
            This Month
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <a href="#!" class="dropdown-item">Download</a>
            <a href="#!" class="dropdown-item">Export</a>
            <a href="#!" class="dropdown-item">Import</a>
          </div>
        </div>
      </div>
      <form method="post" action="<?= e(url('admin/agents/bulk')) ?>" id="agentsBulkForm">
        <?= csrf_field() ?>
        <div class="table-responsive">
          <table class="table align-middle text-nowrap table-hover table-centered mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th style="width: 32px;">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="agentsCheckAll">
                    <label class="form-check-label" for="agentsCheckAll"></label>
                  </div>
                </th>
                <th>Agent</th>
                <th>Username</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Assigned Leads</th>
                <th>Followups</th>
                <th>Last Contact</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$agents): ?>
                <tr>
                  <td colspan="9" class="text-center text-muted py-4">No agents found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($agents as $a): ?>
                  <?php
                  $display = $a['agent_name'] ?: ($a['employee_name'] ?: ucfirst($a['username']));
                  $isActive = (int) ($a['is_active'] ?? 1) === 1;
                  $status = $isActive ? 'Active' : 'Inactive';
                  ?>
                  <tr>
                    <td>
                      <div class="form-check">
                        <input type="checkbox" class="form-check-input agent-check" name="ids[]"
                          value="<?= e((string) $a['id']) ?>">
                        <label class="form-check-label"></label>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div>
                          <?php $photo = $a['photo_path'] ? url($a['photo_path']) : url('assets/lahomes/images/users/avatar-1.jpg'); ?>
                          <img src="<?= e($photo) ?>" alt="avatar" class="avatar-sm rounded-circle">
                        </div>
                        <div>
                          <div class="text-dark fw-medium fs-15"><?= e($display) ?></div>
                          <div class="text-muted fs-12"><?= e($a['employee_code'] ?? '-') ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= e($a['username']) ?></td>
                    <td><?= e($a['email'] ?? '-') ?></td>
                    <td><?= e($a['contact_phone'] ?? '-') ?></td>
                    <td><?= e((string) ($a['leads_count'] ?? 0)) ?></td>
                    <td><?= e((string) ($a['followups_count'] ?? 0)) ?></td>
                    <td class="text-muted"><?= e($a['last_contact'] ?? '-') ?></td>
                    <td>
                      <?php if ($isActive): ?>
                        <span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Active</span>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger py-1 px-2 fs-13">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <div class="d-flex gap-2 justify-content-end">
                        <a class="btn btn-light btn-sm" href="<?= e(url('admin/agent?id=' . $a['id'])) ?>">
                          <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
                        </a>
                        <button class="btn btn-soft-primary btn-sm" type="button" data-bs-toggle="modal"
                          data-bs-target="#agentEditModal" data-agent-id="<?= e((string) $a['id']) ?>"
                          data-agent-email="<?= e($a['email'] ?? '') ?>"
                          data-agent-phone="<?= e($a['contact_phone'] ?? '') ?>"
                          data-agent-username="<?= e($a['username']) ?>">
                          <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                        </button>
                        <form method="post" action="<?= e(url('admin/agent/reset-password')) ?>"
                          onsubmit="return confirm('Reset password for this agent?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= e((string) $a['id']) ?>">
                          <button class="btn btn-soft-warning btn-sm" type="submit">
                            <iconify-icon icon="solar:key-square-broken" class="align-middle fs-18"></iconify-icon>
                          </button>
                        </form>
                        <button class="btn btn-soft-danger btn-sm" type="button" data-bs-toggle="modal"
                          data-bs-target="#agentDeactivateModal" data-agent-id="<?= e((string) $a['id']) ?>"
                          data-agent-name="<?= e($display) ?>"
                          data-agent-leads="<?= e((string) ($a['leads_count'] ?? 0)) ?>">
                          <iconify-icon icon="solar:trash-bin-minimalistic-2-broken"
                            class="align-middle fs-18"></iconify-icon>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-body d-flex justify-content-end align-items-center">
          <div class="text-muted fs-12">Select agents to reset passwords</div>
        </div>
      </form>
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
          <input type="hidden" name="id" id="agentEditId" value="">

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="agentEditEmail" placeholder="name@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Phone</label>
            <input type="tel" class="form-control" name="contact_phone" id="agentEditPhone"
              placeholder="+971 5x xxx xxxx">
          </div>
          <div class="mb-0">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" id="agentEditUsername" disabled>
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

<div class="modal fade" id="agentDeactivateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= e(url('admin/agent/delete')) ?>" id="agentDeactivateForm">
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="agentDeactivateId" value="">

          <div class="alert alert-warning">
            <div class="fw-semibold">Agent: <span id="agentDeactivateName">Agent</span></div>
            <div class="small">Assigned leads: <span id="agentDeactivateLeadCount" data-count="0">0</span></div>
            <div class="small text-muted">Choose how to handle this agent's leads.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Lead handling</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="lead_action" id="leadActionDelete" value="delete" checked>
              <label class="form-check-label" for="leadActionDelete">Delete agent and leads</label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="lead_action" id="leadActionReassign" value="reassign">
              <label class="form-check-label" for="leadActionReassign">Reassign leads to another agent</label>
            </div>
          </div>

          <div class="mb-3" id="leadReassignWrap" style="display:none;">
            <label class="form-label">Reassign to</label>
            <select class="form-select" name="reassign_agent_id" id="reassignAgentSelect">
              <option value="">Select an agent</option>
              <?php foreach ($activeAgents as $opt): ?>
                <?php $optName = $opt['agent_name'] ?: ($opt['employee_name'] ?: ucfirst($opt['username'])); ?>
                <option value="<?= e((string) $opt['id']) ?>"><?= e($optName) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-muted">Only active agents are listed.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Agent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (function () {
    var modalEl = document.getElementById('agentEditModal');
    if (!modalEl) return;
    modalEl.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) return;
      var id = btn.getAttribute('data-agent-id') || '';
      var email = btn.getAttribute('data-agent-email') || '';
      var phone = btn.getAttribute('data-agent-phone') || '';
      var username = btn.getAttribute('data-agent-username') || '';
      var idEl = document.getElementById('agentEditId');
      var emailEl = document.getElementById('agentEditEmail');
      var phoneEl = document.getElementById('agentEditPhone');
      var userEl = document.getElementById('agentEditUsername');
      if (idEl) idEl.value = id;
      if (emailEl) emailEl.value = email;
      if (phoneEl) phoneEl.value = phone;
      if (userEl) userEl.value = username;
    });
  })();
</script>
<script>
  (function () {
    var modalEl = document.getElementById('agentDeactivateModal');
    if (!modalEl) return;
    var idInput = document.getElementById('agentDeactivateId');
    var nameEl = document.getElementById('agentDeactivateName');
    var leadCountEl = document.getElementById('agentDeactivateLeadCount');
    var reassignWrap = document.getElementById('leadReassignWrap');
    var reassignSelect = document.getElementById('reassignAgentSelect');
    var radios = document.querySelectorAll('input[name="lead_action"]');

    function currentAction() {
      var checked = document.querySelector('input[name="lead_action"]:checked');
      return checked ? checked.value : 'keep';
    }

    function syncAction() {
      var showReassign = currentAction() === 'reassign';
      if (reassignWrap) reassignWrap.style.display = showReassign ? 'block' : 'none';
      if (reassignSelect) {
        reassignSelect.required = showReassign;
        if (!showReassign) reassignSelect.value = '';
      }
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) return;
      var agentId = btn.getAttribute('data-agent-id') || '';
      var agentName = btn.getAttribute('data-agent-name') || 'Agent';
      var leadCount = btn.getAttribute('data-agent-leads') || '0';
      if (idInput) idInput.value = agentId;
      if (nameEl) nameEl.textContent = agentName;
      if (leadCountEl) {
        leadCountEl.textContent = leadCount;
        leadCountEl.setAttribute('data-count', leadCount);
      }
      if (reassignSelect) {
        reassignSelect.value = '';
        Array.prototype.forEach.call(reassignSelect.options, function (opt) {
          if (!opt.value) return;
          opt.disabled = false;
          opt.hidden = false;
          if (opt.value === agentId) {
            opt.disabled = true;
            opt.hidden = true;
          }
        });
      }
      var deleteRadio = document.getElementById('leadActionDelete');
      if (deleteRadio) deleteRadio.checked = true;
      syncAction();
    });

    Array.prototype.forEach.call(radios, function (radio) {
      radio.addEventListener('change', syncAction);
    });

    var form = document.getElementById('agentDeactivateForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        var action = currentAction();
        if (action === 'reassign') {
          if (!reassignSelect || !reassignSelect.value) {
            e.preventDefault();
            alert('Select an agent to reassign leads.');
            return;
          }
          if (idInput && reassignSelect.value === idInput.value) {
            e.preventDefault();
            alert('Select a different agent to reassign leads.');
            return;
          }
        }
        if (action === 'delete') {
          var count = 0;
          if (leadCountEl) {
            count = parseInt(leadCountEl.getAttribute('data-count') || '0', 10);
          }
          if (count > 0 && !confirm('Delete all leads assigned to this agent? This will also remove followups.')) {
            e.preventDefault();
            return;
          }
        }
        if (!confirm('Delete this agent?')) {
          e.preventDefault();
        }
      });
    }
  })();
</script>
<script>
  (function () {
    var checkAll = document.getElementById('agentsCheckAll');
    if (!checkAll) return;
    checkAll.addEventListener('change', function () {
      document.querySelectorAll('.agent-check').forEach(function (cb) {
        cb.checked = checkAll.checked;
      });
    });
    var bulkForm = document.getElementById('agentsBulkForm');
    if (!bulkForm) return;
    bulkForm.addEventListener('submit', function (e) {
      e.preventDefault();
    });
  })();
</script>
<script>
  (function () {
    var resetForm = document.getElementById('bulkResetForm');
    if (!resetForm) return;
    resetForm.addEventListener('submit', function (e) {
      var any = false;
      document.querySelectorAll('.agent-check').forEach(function (cb) {
        if (cb.checked) any = true;
      });
      if (!any) {
        e.preventDefault();
        alert('Select at least one agent.');
        return;
      }
      if (!confirm('Reset passwords for selected agents?')) {
        e.preventDefault();
      }
    });
  })();
</script>
