<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$rowErrors = $_SESSION['_lead_individual_row_errors'] ?? [];
$oldRows = $_SESSION['_lead_individual_old_rows'] ?? null;
unset($_SESSION['_lead_individual_row_errors'], $_SESSION['_lead_individual_old_rows']);
$agents = $agents ?? [];
$reqUri = $_SERVER['REQUEST_URI'] ?? 'admin/leads/individual';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
$path = parse_url($reqUri, PHP_URL_PATH) ?? '';
$query = parse_url($reqUri, PHP_URL_QUERY);
if ($base !== '' && str_starts_with($path, $base)) {
  $path = substr($path, strlen($base));
}
if ($path === '') $path = '/';
$returnPath = ltrim($path, '/');
if ($query) $returnPath .= '?' . $query;
$returnParam = urlencode($returnPath);
$user = current_user();
$role = $user['role'] ?? '';
$canDelete = in_array($role, ['ADMIN', 'CEO'], true);
$leadDisplayName = function (?string $name): string {
  $name = trim((string)$name);
  if ($name === '') return '';
  $parts = preg_split('/\s+/', $name);
  if (!$parts || count($parts) <= 3) return $name;
  return implode(' ', array_slice($parts, 0, 3)) . '...';
};
?>
<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border-bottom">
        <div>
          <h4 class="card-title mb-1">Individual Leads</h4>
          <p class="text-muted mb-0 fs-13">Add a single lead and assign an agent.</p>
        </div>
        <button class="btn btn-sm btn-primary btn-mobile-full" type="button" data-add-individual-row>
          <i class="ri-add-line me-1"></i>Add Row
        </button>
      </div>
      <div class="card-body">
        <form method="post" action="<?= e(url('admin/leads')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="individual">
          <style>
            @media (max-width: 767.98px) {
              .btn-mobile-full {
                width: 100%;
              }
              .admin-individual-table thead {
                display: none;
              }
              .admin-individual-table,
              .admin-individual-table tbody,
              .admin-individual-table tr,
              .admin-individual-table td {
                display: block;
                width: 100%;
              }
              .admin-individual-table tr {
                border: 1px solid var(--bs-border-color, #dee2e6);
                border-radius: 12px;
                padding: 12px;
                margin-bottom: 12px;
                background: #fff;
              }
              .admin-individual-table td {
                padding: 6px 0;
              }
              .admin-individual-table td::before {
                content: attr(data-label);
                display: block;
                font-weight: 600;
                font-size: 12px;
                color: #6c757d;
                margin-bottom: 4px;
              }
              .admin-individual-table td.actions {
                padding-top: 10px;
              }
              .admin-individual-table td.actions::before {
                content: '';
                margin: 0;
              }
              .admin-individual-table td.actions .btn {
                width: 100%;
              }
            }
          </style>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap table-hover table-centered mb-0 admin-individual-table">
              <thead class="bg-light-subtle">
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Interested In Property</th>
                <th>Interest Types</th>
                <th>Type</th>
                <th>Area</th>
                <th>Budget (AED)</th>
                <th>Lead Status</th>
                <th class="form-required">Agent</th>
                <th class="text-end">Actions</th>
              </tr>
              </thead>
              <tbody id="individualLeadRows">
                <?php
                  $rows = is_array($oldRows) ? $oldRows : [
                    ['lead_name'=>'','contact_email'=>'','contact_phone'=>'','interested_in_property'=>'','property_type'=>'','assigned_agent_user_id'=>'']
                  ];
                  foreach ($rows as $i => $r):
                ?>
                <tr class="<?= isset($rowErrors[$i]) ? 'table-danger' : '' ?>">
                  <td data-label="Name"><input class="form-control" data-base="lead_name" name="rows[<?= $i ?>][lead_name]" value="<?= e($r['lead_name'] ?? '') ?>"></td>
                  <td data-label="Email"><input class="form-control" data-base="contact_email" name="rows[<?= $i ?>][contact_email]" value="<?= e($r['contact_email'] ?? '') ?>"></td>
                  <td data-label="Phone"><input type="tel" class="form-control" data-base="contact_phone" name="rows[<?= $i ?>][contact_phone]" value="<?= e($r['contact_phone'] ?? '') ?>" placeholder="+971 5x xxx xxxx"></td>
                  <td data-label="Interested In Property"><input class="form-control" data-base="interested_in_property" name="rows[<?= $i ?>][interested_in_property]" value="<?= e($r['interested_in_property'] ?? '') ?>"></td>
                  <td data-label="Interest Types"><input class="form-control" data-base="property_interest_types" name="rows[<?= $i ?>][property_interest_types]" value="<?= e($r['property_interest_types'] ?? '') ?>" placeholder="Unit, Villa, Land"></td>
                  <td data-label="Type">
                    <select class="form-select" data-base="property_type" name="rows[<?= $i ?>][property_type]">
                      <option value="" <?= (($r['property_type'] ?? '')==='')?'selected':'' ?>>Select type</option>
                      <option value="OFF_PLAN" <?= (($r['property_type'] ?? '')==='OFF_PLAN')?'selected':'' ?>>Off Plan</option>
                      <option value="READY_TO_MOVE" <?= (($r['property_type'] ?? '')==='READY_TO_MOVE')?'selected':'' ?>>Ready To Move</option>
                    </select>
                  </td>
                  <td data-label="Area"><input class="form-control" data-base="area" name="rows[<?= $i ?>][area]" value="<?= e($r['area'] ?? '') ?>"></td>
                  <td data-label="Budget (AED)"><input class="form-control" data-base="budget_aed_range" name="rows[<?= $i ?>][budget_aed_range]" value="<?= e($r['budget_aed_range'] ?? '') ?>" placeholder="600,000 - 900,000"></td>
                  <td data-label="Lead Status"><input class="form-control" data-base="lead_status" name="rows[<?= $i ?>][lead_status]" value="<?= e($r['lead_status'] ?? '') ?>"></td>
                  <td data-label="Agent">
                    <select class="form-select" data-base="assigned_agent_user_id" name="rows[<?= $i ?>][assigned_agent_user_id]">
                      <option value="">Select agent</option>
                      <?php foreach ($agents as $a): ?>
                        <option value="<?= e((string)$a['id']) ?>" <?= ((string)($r['assigned_agent_user_id'] ?? '') === (string)$a['id'])?'selected':'' ?>>
                          <?= e($a['employee_name'] ?: ucfirst($a['username'])) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="actions" data-label="Actions">
                    <button class="btn btn-sm btn-soft-danger" type="button" data-remove-individual-row>Remove</button>
                    <?php if (isset($rowErrors[$i])): ?>
                      <div class="small text-danger mt-1">
                        <?= e(implode(', ', $rowErrors[$i])) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="d-grid d-sm-none mt-2">
            <button class="btn btn-outline-primary" type="button" data-add-individual-row>
              <i class="ri-add-line me-1"></i>Add Row
            </button>
          </div>
          <template id="individualLeadRowTemplate">
            <tr>
              <td data-label="Name"><input class="form-control" data-base="lead_name"></td>
              <td data-label="Email"><input class="form-control" data-base="contact_email"></td>
              <td data-label="Phone"><input type="tel" class="form-control" data-base="contact_phone" placeholder="+971 5x xxx xxxx"></td>
              <td data-label="Interested In Property"><input class="form-control" data-base="interested_in_property"></td>
              <td data-label="Interest Types"><input class="form-control" data-base="property_interest_types" placeholder="Unit, Villa, Land"></td>
              <td data-label="Type">
                <select class="form-select" data-base="property_type">
                  <option value="">Select type</option>
                  <option value="OFF_PLAN">Off Plan</option>
                  <option value="READY_TO_MOVE">Ready To Move</option>
                </select>
              </td>
              <td data-label="Area"><input class="form-control" data-base="area"></td>
              <td data-label="Budget (AED)"><input class="form-control" data-base="budget_aed_range" placeholder="600,000 - 900,000"></td>
              <td data-label="Lead Status"><input class="form-control" data-base="lead_status"></td>
              <td data-label="Agent">
                <select class="form-select" data-base="assigned_agent_user_id">
                  <option value="">Select agent</option>
                  <?php foreach ($agents as $a): ?>
                    <option value="<?= e((string)$a['id']) ?>"><?= e($a['employee_name'] ?: ucfirst($a['username'])) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="actions" data-label="Actions">
                <button class="btn btn-sm btn-soft-danger" type="button" data-remove-individual-row>Remove</button>
              </td>
            </tr>
          </template>

          <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
            <button class="btn btn-primary btn-mobile-full" type="submit">
              <i class="ri-save-line me-1"></i>Save Lead
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3">
      <?php
        $exportQuery = build_query(['page' => null]);
        $exportUrl = url('admin/leads/export' . ($exportQuery ? '?' . $exportQuery : ''));
      ?>
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-1">Search & Monitor</h4>
          <p class="text-muted mb-0 fs-13">View lead list and follow-up status.</p>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="<?= e($exportUrl) ?>">
          <i class="ri-download-2-line me-1"></i>Export CSV
        </a>
      </div>
      <div class="card-body border-bottom">
        <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/leads/individual')) ?>">
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search name/email">
          </div>
          <div class="col-md-2">
            <label class="form-label">Agent</label>
            <select class="form-select" name="agent">
              <option value="">All agents</option>
              <?php foreach ($agents as $a): ?>
                <option value="<?= e((string)$a['id']) ?>" <?= ((string)($filters['agent'] ?? '') === (string)$a['id'])?'selected':'' ?>>
                  <?= e($a['employee_name'] ?: ucfirst($a['username'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Type</label>
            <select class="form-select" name="type">
              <option value="">All types</option>
              <option value="OFF_PLAN" <?= (($filters['type'] ?? '')==='OFF_PLAN')?'selected':'' ?>>Off Plan</option>
              <option value="READY_TO_MOVE" <?= (($filters['type'] ?? '')==='READY_TO_MOVE')?'selected':'' ?>>Ready To Move</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" class="form-control" name="from" value="<?= e($filters['from'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" class="form-control" name="to" value="<?= e($filters['to'] ?? '') ?>">
          </div>
          <div class="col-md-1 d-grid">
            <button class="btn btn-outline-primary">Filter</button>
          </div>
        </form>
      </div>

      <?php if (!$items): ?>
        <div class="text-center py-5 text-muted">No leads found for current filters.</div>
      <?php else: ?>
        <div class="table-responsive d-none d-lg-block">
          <table class="table align-middle text-nowrap table-hover table-centered mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th><a href="?<?= e(build_query(['sort'=>'lead_name','dir'=>($filters['dir']==='asc'?'desc':'asc')])) ?>">Lead</a></th>
                <th>Email</th>
                <th>Phone</th>
                <th>Interested</th>
                <th>Type</th>
                <th><a href="?<?= e(build_query(['sort'=>'agent_name','dir'=>($filters['dir']==='asc'?'desc':'asc')])) ?>">Agent</a></th>
                <th>Status</th>
                <th>Followups</th>
                <th>Created</th>
                <th class="text-end"></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $l): ?>
              <tr>
                <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
                <?php
                  $email = trim((string)($l['contact_email'] ?? ''));
                  $phone = trim((string)($l['contact_phone'] ?? ''));
                ?>
                <td><?= e($email !== '' ? $email : '-') ?></td>
                <td><?= e($phone !== '' ? $phone : '-') ?></td>
                <?php
                  $interest = trim((string)($l['interested_in_property'] ?? ''));
                  $interestShort = $interest !== '' ? mb_strimwidth($interest, 0, 24, '...') : '-';
                ?>
                <td class="text-muted" title="<?= e($interest) ?>"><?= e($interestShort) ?></td>
                <?php $ptype = $l['property_type'] !== null && $l['property_type'] !== '' ? (string)$l['property_type'] : 'NONE'; ?>
                <td><span class="badge bg-light-subtle text-dark py-1 px-2 fs-13"><?= e($ptype) ?></span></td>
                <td><?= e($l['agent_name'] ?: '-') ?></td>
                <td>
                  <?php
                    $s = $l['status_overall'];
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
                  <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> py-1 px-2 fs-13"><?= e($s) ?></span>
                </td>
                <td><?= e((string)$l['followup_count']) ?></td>
                <td class="text-muted"><?= e($l['created_at']) ?></td>
                <td class="text-end">
                  <a class="btn btn-light btn-sm" href="<?= e(url('admin/lead?id='.$l['id'].'&return='.$returnParam)) ?>" title="View lead">
                    <i class="ri-eye-line"></i>
                  </a>
                  <?php if ($canDelete): ?>
                    <form class="d-inline" method="post" action="<?= e(url('admin/lead/delete')) ?>" onsubmit="return confirm('Delete this lead and all follow-ups? This cannot be undone.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= e((string)$l['id']) ?>">
                      <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                      <button class="btn btn-soft-danger btn-sm ms-1" type="submit" title="Delete lead">
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-lg-none">
          <?php foreach ($items as $l): ?>
            <?php
              $email = trim((string)($l['contact_email'] ?? ''));
              $phone = trim((string)($l['contact_phone'] ?? ''));
              $interest = trim((string)($l['interested_in_property'] ?? ''));
              $ptype = $l['property_type'] !== null && $l['property_type'] !== '' ? (string)$l['property_type'] : 'NONE';
              $s = $l['status_overall'];
              if ($s === 'CLOSED') {
                $cls = 'success';
                $label = 'Closed';
              } elseif ($s === 'IN_PROGRESS') {
                $cls = 'warning';
                $label = 'In Progress';
              } elseif ($s === '50/50') {
                $cls = 'info';
                $label = '50/50';
              } elseif ($s === 'ON_HOLD') {
                $cls = 'primary';
                $label = 'On Hold';
              } else {
                $cls = 'secondary';
                $label = $s ?: 'New';
              }
            ?>
            <div class="card border mb-2">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="fw-semibold" style="word-break: break-word;"><?= e($l['lead_name'] ?? '-') ?></div>
                  <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> py-1 px-2 fs-13"><?= e($label) ?></span>
                </div>
                <div class="text-muted fs-12 mt-1">
                  <?= e($email !== '' ? $email : '-') ?>
                </div>
                <div class="text-muted fs-12"><?= e($phone !== '' ? $phone : '-') ?></div>
                <div class="mt-2 d-flex flex-wrap gap-2">
                  <span class="badge bg-light-subtle text-dark py-1 px-2 fs-13"><?= e($ptype) ?></span>
                  <?php if ($l['agent_name']): ?>
                    <span class="badge bg-light-subtle text-dark py-1 px-2 fs-13"><?= e($l['agent_name']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="small text-muted mt-2">
                  <div><span class="text-dark fw-semibold">Interested:</span> <?= e($interest !== '' ? $interest : '-') ?></div>
                  <div><span class="text-dark fw-semibold">Followups:</span> <?= e((string)$l['followup_count']) ?></div>
                  <div><span class="text-dark fw-semibold">Created:</span> <?= e($l['created_at']) ?></div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                  <a class="btn btn-light btn-sm flex-fill" href="<?= e(url('admin/lead?id='.$l['id'].'&return='.$returnParam)) ?>">
                    View
                  </a>
                  <?php if ($canDelete): ?>
                    <form class="flex-fill" method="post" action="<?= e(url('admin/lead/delete')) ?>" onsubmit="return confirm('Delete this lead and all follow-ups? This cannot be undone.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= e((string)$l['id']) ?>">
                      <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                      <button class="btn btn-soft-danger btn-sm w-100" type="submit">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php
          $m = $meta;
          $page = $m['page']; $pages = $m['pages'];
        ?>
        <div class="card-body">
          <nav>
            <ul class="pagination pagination-rounded mb-0">
              <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?<?= e(build_query(['page'=>$page-1])) ?>">Prev</a></li>
              <?php for($p=max(1,$page-2); $p<=min($pages,$page+2); $p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?<?= e(build_query(['page'=>$p])) ?>"><?= $p ?></a></li>
              <?php endfor; ?>
              <li class="page-item <?= $page>=$pages?'disabled':'' ?>"><a class="page-link" href="?<?= e(build_query(['page'=>$page+1])) ?>">Next</a></li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  (function () {
    var addBtns = document.querySelectorAll('[data-add-individual-row]');
    var tbody = document.getElementById('individualLeadRows');
    var tpl = document.getElementById('individualLeadRowTemplate');
    if (!addBtns.length || !tbody || !tpl) return;

    function resetRowInputs(tr) {
      tr.querySelectorAll('input').forEach(function (input) {
        input.value = '';
      });
      tr.querySelectorAll('select').forEach(function (select) {
        select.selectedIndex = 0;
      });
    }

    function updateRowNames() {
      Array.from(tbody.querySelectorAll('tr')).forEach(function (tr, idx) {
        tr.querySelectorAll('input,select').forEach(function (input) {
          var base = input.getAttribute('data-base');
          if (!base) return;
          input.name = 'rows[' + idx + '][' + base + ']';
        });
      });
    }

    addBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var frag = tpl.content.cloneNode(true);
        tbody.appendChild(frag);
        updateRowNames();
      });
    });

    tbody.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove-individual-row]');
      if (!btn) return;
      var rows = tbody.querySelectorAll('tr');
      if (rows.length <= 1) {
        resetRowInputs(rows[0]);
        return;
      }
      var tr = btn.closest('tr');
      if (tr) tr.remove();
      updateRowNames();
    });

    updateRowNames();
  })();
</script>
