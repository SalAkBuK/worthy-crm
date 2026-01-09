<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$users = $users ?? [];
$adminUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'ADMIN');
$agentUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'AGENT');
$leads = $leads ?? [];
$followups = $followups ?? [];
$leadStatuses = ['NEW', 'IN_PROGRESS', '50/50', 'CLOSED'];
$leadsByStatus = [
  'NEW' => array_values(array_filter($leads, fn($l) => ($l['status_overall'] ?? '') === 'NEW')),
  'IN_PROGRESS' => array_values(array_filter($leads, fn($l) => ($l['status_overall'] ?? '') === 'IN_PROGRESS')),
  '50/50' => array_values(array_filter($leads, fn($l) => ($l['status_overall'] ?? '') === '50/50')),
  'CLOSED' => array_values(array_filter($leads, fn($l) => ($l['status_overall'] ?? '') === 'CLOSED')),
];
  $leadStatusTabId = function (string $status): string {
    return strtolower(preg_replace('/[^a-z0-9]+/', '-', $status));
  };
$followupStatuses = ['INTERESTED', 'NOT_INTERESTED'];
$followupsByStatus = [
  'INTERESTED' => array_values(array_filter($followups, fn($f) => ($f['interested_status'] ?? '') === 'INTERESTED')),
  'NOT_INTERESTED' => array_values(array_filter($followups, fn($f) => ($f['interested_status'] ?? '') === 'NOT_INTERESTED')),
];
$renderLeadsTable = function(array $rows): void {
  if (!$rows) {
    echo '<div class="text-center text-muted py-4">No leads found.</div>';
    return;
  }
  echo '<div class="table-responsive">';
  echo '<table class="table align-middle text-nowrap table-hover table-centered mb-0">';
  echo '<thead class="bg-light-subtle">';
  echo '<tr><th>Lead</th><th>Email</th><th>Phone</th><th>Type</th><th>Status</th><th>Assigned Agent</th><th>Created</th></tr>';
  echo '</thead><tbody>';
  foreach ($rows as $l) {
    echo '<tr>';
    echo '<td class="fw-medium">' . e($l['lead_name'] ?? '-') . '</td>';
    echo '<td>' . e($l['contact_email'] ?? '-') . '</td>';
    echo '<td>' . e($l['contact_phone'] ?? '-') . '</td>';
    echo '<td>' . e($l['property_type'] ?? '-') . '</td>';
    echo '<td>' . e($l['status_overall'] ?? '-') . '</td>';
    echo '<td>' . e($l['agent_name'] ?? '-') . '</td>';
    echo '<td class="text-muted">' . e($l['created_at'] ?? '-') . '</td>';
    echo '</tr>';
  }
  echo '</tbody></table></div>';
};
$renderFollowupsTable = function(array $rows): void {
  if (!$rows) {
    echo '<div class="text-center text-muted py-4">No follow-ups found.</div>';
    return;
  }
  echo '<div class="table-responsive">';
  echo '<table class="table align-middle text-nowrap table-hover table-centered mb-0">';
  echo '<thead class="bg-light-subtle">';
  echo '<tr><th>Lead</th><th>Agent</th><th>Attempt</th><th>Call Status</th><th>Interested</th><th>Contact Date</th><th>Next Follow-up</th></tr>';
  echo '</thead><tbody>';
  foreach ($rows as $f) {
    echo '<tr>';
    echo '<td class="fw-medium">' . e($f['lead_name'] ?? '-') . '</td>';
    echo '<td>' . e($f['agent_name'] ?? '-') . '</td>';
    echo '<td>#' . e((string)($f['attempt_no'] ?? '')) . '</td>';
    echo '<td>' . e($f['call_status'] ?? '-') . '</td>';
    echo '<td>' . e($f['interested_status'] ?? '-') . '</td>';
    echo '<td class="text-muted">' . e($f['contact_datetime'] ?? '-') . '</td>';
    echo '<td class="text-muted">' . e($f['next_followup_at'] ?? '-') . '</td>';
    echo '</tr>';
  }
  echo '</tbody></table></div>';
};
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-1">Read-only Summary</h4>
          <p class="text-muted mb-0 fs-13">High-level metrics for leadership.</p>
        </div>
        <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1">Read Only</span>
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#summary-admins" type="button" role="tab">Admins</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#summary-agents" type="button" role="tab">Agents</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#summary-leads" type="button" role="tab">Leads</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#summary-followups" type="button" role="tab">Follow-ups</button>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="summary-admins" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Admins</p>
                    <h4 class="mb-0"><?= e((string)$totalAdmins) ?></h4>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <div class="table-responsive">
                <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                  <thead class="bg-light-subtle">
                    <tr>
                      <th>Name</th>
                      <th>Username</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Created At</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!$adminUsers): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No admins found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($adminUsers as $u): ?>
                      <?php
                        $name = $u['agent_name'] ?: ($u['employee_name'] ?: ucfirst((string)$u['username']));
                        $active = (int)($u['is_active'] ?? 1) === 1;
                      ?>
                      <tr>
                        <td class="fw-medium"><?= e($name) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['email'] ?? '-') ?></td>
                        <td>
                          <?php if ($active): ?>
                            <span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Active</span>
                          <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger py-1 px-2 fs-13">Inactive</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e($u['created_at'] ?? '-') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="summary-agents" role="tabpanel">
            <div class="row g-3 align-items-center">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Agents</p>
                    <h4 class="mb-0"><?= e((string)$totalAgents) ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-8 text-md-end">
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/agents/export')) ?>">Export Agents CSV</a>
              </div>
            </div>
            <div class="mt-4">
              <div class="table-responsive">
                <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                  <thead class="bg-light-subtle">
                    <tr>
                      <th>Name</th>
                      <th>Username</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Created At</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!$agentUsers): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No agents found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($agentUsers as $u): ?>
                      <?php
                        $name = $u['agent_name'] ?: ($u['employee_name'] ?: ucfirst((string)$u['username']));
                        $active = (int)($u['is_active'] ?? 1) === 1;
                      ?>
                      <tr>
                        <td class="fw-medium"><?= e($name) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e($u['email'] ?? '-') ?></td>
                        <td>
                          <?php if ($active): ?>
                            <span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Active</span>
                          <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger py-1 px-2 fs-13">Inactive</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e($u['created_at'] ?? '-') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="summary-leads" role="tabpanel">
            <div class="row g-3 align-items-center">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Leads</p>
                    <h4 class="mb-0"><?= e((string)$totalLeads) ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-8 text-md-end">
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/leads/export')) ?>">Export Leads CSV</a>
              </div>
            </div>
            <div class="mt-4">
              <ul class="nav nav-pills nav-pills-sm gap-2 mb-3" role="tablist">
                <?php foreach ($leadStatuses as $i => $st): ?>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#summary-leads-<?= e($leadStatusTabId($st)) ?>" type="button" role="tab">
                      <?= e(str_replace('_', ' ', $st)) ?>
                      <span class="badge bg-light-subtle text-muted border ms-1"><?= e((string)count($leadsByStatus[$st])) ?></span>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
              <div class="tab-content">
                <?php foreach ($leadStatuses as $i => $st): ?>
                  <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="summary-leads-<?= e($leadStatusTabId($st)) ?>" role="tabpanel">
                    <?php $renderLeadsTable($leadsByStatus[$st]); ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="summary-followups" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Follow-ups</p>
                    <h4 class="mb-0"><?= e((string)$totalFollowups) ?></h4>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <ul class="nav nav-pills nav-pills-sm gap-2 mb-3" role="tablist">
                <?php foreach ($followupStatuses as $i => $st): ?>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#summary-followups-<?= e(strtolower($st)) ?>" type="button" role="tab">
                      <?= e(str_replace('_', ' ', $st)) ?>
                      <span class="badge bg-light-subtle text-muted border ms-1"><?= e((string)count($followupsByStatus[$st])) ?></span>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
              <div class="tab-content">
                <?php foreach ($followupStatuses as $i => $st): ?>
                  <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="summary-followups-<?= e(strtolower($st)) ?>" role="tabpanel">
                    <?php $renderFollowupsTable($followupsByStatus[$st]); ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
