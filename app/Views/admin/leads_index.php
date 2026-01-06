<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$rowErrors = $_SESSION['_lead_row_errors'] ?? [];
$oldRows = $_SESSION['_lead_old_rows'] ?? null;
unset($_SESSION['_lead_row_errors'], $_SESSION['_lead_old_rows']);

$agents = $agents ?? [];
?>
<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-1">Lead Entry (Bulk)</h4>
          <p class="text-muted mb-0 fs-13">Add multiple leads in one submission (transaction-safe).</p>
        </div>
        <button class="btn btn-sm btn-primary" data-add-row type="button">
          <i class="ri-add-line me-1"></i>Add Row
        </button>
      </div>
      <div class="card-body">
        <form method="post" action="<?= e(url('admin/leads')) ?>">
          <?= csrf_field() ?>

          <div class="table-responsive">
            <table class="table align-middle text-nowrap table-hover table-centered mb-0">
              <thead class="bg-light-subtle">
              <tr>
                <th style="width:60px;">#</th>
                <th class="form-required">Name</th>
                <th class="form-required">Email</th>
                <th class="form-required">Phone</th>
                <th class="form-required">Interested In Property</th>
                <th class="form-required">Type</th>
                <th class="form-required">Agent</th>
                <th style="width:110px;"></th>
              </tr>
              </thead>
              <tbody id="leadRows">
              <?php
                $rows = is_array($oldRows) ? $oldRows : [
                  ['lead_name'=>'','contact_email'=>'','contact_phone'=>'','interested_in_property'=>'','property_type'=>'OFF_PLAN','assigned_agent_user_id'=>'']
                ];
                foreach ($rows as $i=>$r):
              ?>
              <tr class="<?= isset($rowErrors[$i]) ? 'table-danger' : '' ?>">
                <td class="text-muted" data-idx><?= $i+1 ?></td>
                <td><input class="form-control" data-base="lead_name" value="<?= e($r['lead_name'] ?? '') ?>"></td>
                <td><input class="form-control" data-base="contact_email" value="<?= e($r['contact_email'] ?? '') ?>"></td>
                <td><input type="tel" class="form-control" data-base="contact_phone" value="<?= e($r['contact_phone'] ?? '') ?>" placeholder="+971 5x xxx xxxx"></td>
                <td><input class="form-control" data-base="interested_in_property" value="<?= e($r['interested_in_property'] ?? '') ?>"></td>
                <td>
                  <select class="form-select" data-base="property_type">
                    <option value="OFF_PLAN" <?= (($r['property_type'] ?? '')==='OFF_PLAN')?'selected':'' ?>>Off Plan</option>
                    <option value="READY_TO_MOVE" <?= (($r['property_type'] ?? '')==='READY_TO_MOVE')?'selected':'' ?>>Ready To Move</option>
                  </select>
                </td>
                <td>
                  <select class="form-select" data-base="assigned_agent_user_id">
                    <option value="">Select agent</option>
                    <?php foreach ($agents as $a): ?>
                      <option value="<?= e((string)$a['id']) ?>" <?= ((string)($r['assigned_agent_user_id'] ?? '') === (string)$a['id'])?'selected':'' ?>>
                        <?= e($a['employee_name'] ?: ucfirst($a['username'])) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <button class="btn btn-sm btn-soft-danger" data-remove-row type="button">Remove</button>
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

          <template id="leadRowTemplate">
            <tr>
              <td class="text-muted" data-idx>1</td>
              <td><input class="form-control" data-base="lead_name"></td>
              <td><input class="form-control" data-base="contact_email"></td>
              <td><input type="tel" class="form-control" data-base="contact_phone" placeholder="+971 5x xxx xxxx"></td>
              <td><input class="form-control" data-base="interested_in_property"></td>
              <td>
                <select class="form-select" data-base="property_type">
                  <option value="OFF_PLAN">Off Plan</option>
                  <option value="READY_TO_MOVE">Ready To Move</option>
                </select>
              </td>
              <td>
                <select class="form-select" data-base="assigned_agent_user_id">
                  <option value="">Select agent</option>
                  <?php foreach ($agents as $a): ?>
                    <option value="<?= e((string)$a['id']) ?>"><?= e($a['employee_name'] ?: ucfirst($a['username'])) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><button class="btn btn-sm btn-soft-danger" data-remove-row type="button">Remove</button></td>
            </tr>
          </template>

          <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" type="submit">
              <i class="ri-save-line me-1"></i>Save All Leads
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
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
        <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/leads')) ?>">
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
        <div class="table-responsive">
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
                <td class="fw-semibold"><?= e($l['lead_name']) ?></td>
                <td><?= e($l['contact_email']) ?></td>
                <td><?= e($l['contact_phone'] ?? '-') ?></td>
                <td class="text-muted"><?= e($l['interested_in_property']) ?></td>
                <td><span class="badge bg-light-subtle text-dark py-1 px-2 fs-13"><?= e($l['property_type']) ?></span></td>
                <td><?= e($l['agent_name']) ?></td>
                <td>
                  <?php
                    $s = $l['status_overall'];
                    $cls = $s==='CLOSED'?'success':($s==='IN_PROGRESS'?'warning':'secondary');
                  ?>
                  <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> py-1 px-2 fs-13"><?= e($s) ?></span>
                </td>
                <td><?= e((string)$l['followup_count']) ?></td>
                <td class="text-muted"><?= e($l['created_at']) ?></td>
                <td class="text-end">
                  <a class="btn btn-light btn-sm" href="<?= e(url('admin/lead?id='.$l['id'])) ?>">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
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

<script src="<?= e(url('assets/js/admin_leads.js')) ?>"></script>
