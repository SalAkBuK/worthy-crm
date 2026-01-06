<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$rowErrors = $_SESSION['_lead_bulk_row_errors'] ?? [];
$oldRows = $_SESSION['_lead_bulk_old_rows'] ?? null;
$importErrors = $_SESSION['_lead_bulk_import_errors'] ?? [];

$agents = $agents ?? [];
?>
<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <div>
          <h4 class="card-title mb-0">Bulk Leads</h4>
        </div>
        <form method="post" action="<?= e(url('admin/leads/bulk/clear')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-secondary" type="submit">Clear Imported Rows</button>
        </form>
      </div>
      <div class="card-body">
        <?php if ($importErrors): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">CSV import errors</div>
            <div class="small text-muted">Fix the rows listed below and try again.</div>
            <ul class="mb-0 mt-2 ps-3">
              <?php foreach ($importErrors as $err): ?>
                <li class="small"><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form class="mb-4" method="post" action="<?= e(url('admin/leads/import')) ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label form-required">CSV/XLSX File</label>
              <input type="file" class="form-control" name="leads_csv" accept=".csv,.xlsx" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Default Property Type</label>
              <select class="form-select" name="default_property_type">
                <option value="">No default (leave empty)</option>
                <option value="OFF_PLAN">Off Plan</option>
                <option value="READY_TO_MOVE">Ready To Move</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Assignment</label>
              <div class="d-flex flex-wrap gap-3">
                <label class="form-check">
                  <input class="form-check-input" type="radio" name="assign_mode" value="single" checked>
                  <span class="form-check-label">Assign all to agent</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="radio" name="assign_mode" value="unassigned">
                  <span class="form-check-label">Leave unassigned (assign later)</span>
                </label>
              </div>
              <select class="form-select mt-2" name="assigned_agent_user_id">
                <option value="">Select agent</option>
                <?php foreach ($agents as $a): ?>
                  <option value="<?= e((string)$a['id']) ?>"><?= e($a['employee_name'] ?: ucfirst($a['username'])) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-outline-primary" type="submit">
                <i class="ri-upload-2-line me-1"></i>Import CSV
              </button>
            </div>
          </div>
        </form>

        <form method="post" action="<?= e(url('admin/leads')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="bulk">

          <div class="d-flex flex-wrap gap-3 justify-content-end mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-pill bg-light-subtle text-dark border bulk-assign-pill">Assign selected</span>
              <select class="form-select" data-assign-selected>
                <option value="">Select agent</option>
                <?php foreach ($agents as $a): ?>
                  <option value="<?= e((string)$a['id']) ?>"><?= e($a['employee_name'] ?: ucfirst($a['username'])) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-outline-primary" type="button" data-apply-selected>Apply</button>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-pill bg-light-subtle text-dark border bulk-assign-pill">Assign all</span>
              <select class="form-select" data-assign-all>
                <option value="">Select agent</option>
                <?php foreach ($agents as $a): ?>
                  <option value="<?= e((string)$a['id']) ?>"><?= e($a['employee_name'] ?: ucfirst($a['username'])) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-outline-primary" type="button" data-apply-assign>Apply</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle text-nowrap table-hover table-centered mb-0">
              <thead class="bg-light-subtle">
              <tr>
                <th style="width:32px;">
                  <input class="form-check-input" type="checkbox" data-bulk-select-all>
                </th>
                <th style="width:60px;">#</th>
                <th class="form-required">Name</th>
                <th class="form-required">Email</th>
                <th class="form-required">Phone</th>
                <th class="form-required">Interested In Property</th>
                <th>Interest Types</th>
                <th class="form-required">Type</th>
                <th>Area</th>
                <th>Budget (AED)</th>
                <th>Lead Status</th>
                <th class="form-required">Agent</th>
                <th style="width:110px;"></th>
              </tr>
              </thead>
              <tbody id="leadRows">
              <?php
                $rows = is_array($oldRows) ? $oldRows : [
                  ['lead_name'=>'','contact_email'=>'','contact_phone'=>'','interested_in_property'=>'','property_type'=>'','assigned_agent_user_id'=>'']
                ];
                foreach ($rows as $i=>$r):
              ?>
              <tr class="<?= isset($rowErrors[$i]) ? 'table-danger' : '' ?>">
                <td>
                  <input class="form-check-input" type="checkbox" data-bulk-select-item>
                </td>
                <td class="text-muted" data-idx><?= $i+1 ?></td>
                <td><input class="form-control" data-base="lead_name" value="<?= e($r['lead_name'] ?? '') ?>"></td>
                <td><input class="form-control" data-base="contact_email" value="<?= e($r['contact_email'] ?? '') ?>"></td>
                <td><input type="tel" class="form-control" data-base="contact_phone" value="<?= e($r['contact_phone'] ?? '') ?>" placeholder="+971 5x xxx xxxx"></td>
                <td><input class="form-control" data-base="interested_in_property" value="<?= e($r['interested_in_property'] ?? '') ?>"></td>
                <td><input class="form-control" data-base="property_interest_types" value="<?= e($r['property_interest_types'] ?? '') ?>" placeholder="Unit, Villa, Land"></td>
                <td>
                  <select class="form-select" data-base="property_type">
                    <option value="" <?= (($r['property_type'] ?? '')==='')?'selected':'' ?>>Select type</option>
                    <option value="OFF_PLAN" <?= (($r['property_type'] ?? '')==='OFF_PLAN')?'selected':'' ?>>Off Plan</option>
                    <option value="READY_TO_MOVE" <?= (($r['property_type'] ?? '')==='READY_TO_MOVE')?'selected':'' ?>>Ready To Move</option>
                  </select>
                </td>
                <td><input class="form-control" data-base="area" value="<?= e($r['area'] ?? '') ?>"></td>
                <td><input class="form-control" data-base="budget_aed_range" value="<?= e($r['budget_aed_range'] ?? '') ?>" placeholder="600,000 - 900,000"></td>
                <td><input class="form-control" data-base="lead_status" value="<?= e($r['lead_status'] ?? '') ?>"></td>
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
              <td>
                <input class="form-check-input" type="checkbox" data-bulk-select-item>
              </td>
              <td class="text-muted" data-idx>1</td>
              <td><input class="form-control" data-base="lead_name"></td>
              <td><input class="form-control" data-base="contact_email"></td>
              <td><input type="tel" class="form-control" data-base="contact_phone" placeholder="+971 5x xxx xxxx"></td>
              <td><input class="form-control" data-base="interested_in_property"></td>
              <td><input class="form-control" data-base="property_interest_types" placeholder="Unit, Villa, Land"></td>
              <td>
                <select class="form-select" data-base="property_type">
                  <option value="">Select type</option>
                  <option value="OFF_PLAN">Off Plan</option>
                  <option value="READY_TO_MOVE">Ready To Move</option>
                </select>
              </td>
              <td><input class="form-control" data-base="area"></td>
              <td><input class="form-control" data-base="budget_aed_range" placeholder="600,000 - 900,000"></td>
              <td><input class="form-control" data-base="lead_status"></td>
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
</div>

<script src="<?= e(url('assets/js/admin_leads.js')) ?>"></script>
<script>
  (function () {
    var assignBtn = document.querySelector('[data-apply-selected]');
    var assignSelect = document.querySelector('[data-assign-selected]');
    var tbody = document.getElementById('leadRows');
    var tpl = document.getElementById('leadRowTemplate');
    var csrf = document.querySelector('input[name="_csrf"]');
    if (!assignBtn || !assignSelect || !tbody || !tpl || !csrf) return;

    function updateRowNames() {
      Array.from(tbody.querySelectorAll('tr')).forEach(function (tr, idx) {
        tr.querySelectorAll('input,select').forEach(function (input) {
          var base = input.getAttribute('data-base');
          if (!base) return;
          input.name = 'rows[' + idx + '][' + base + ']';
        });
        var idxCell = tr.querySelector('[data-idx]');
        if (idxCell) idxCell.textContent = String(idx + 1);
      });
    }

    function addBlankRow() {
      var frag = tpl.content.cloneNode(true);
      tbody.appendChild(frag);
      updateRowNames();
    }

    function getSelectedRows() {
      return Array.from(tbody.querySelectorAll('tr')).filter(function (tr) {
        var chk = tr.querySelector('[data-bulk-select-item]');
        return chk && chk.checked;
      });
    }

    function getRowData(tr) {
      var row = {};
      tr.querySelectorAll('input,select').forEach(function (input) {
        var base = input.getAttribute('data-base');
        if (!base) return;
        row[base] = input.value;
      });
      return row;
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-apply-selected]');
      if (!btn) return;
      e.preventDefault();
      e.stopImmediatePropagation();

      var agentId = assignSelect.value;
      var selectedRows = getSelectedRows();
      if (!agentId) {
        alert('Select an agent to assign.');
        return;
      }
      if (!selectedRows.length) {
        alert('Select at least one row.');
        return;
      }
      if (!confirm('Assign the selected leads to this agent and save them now?')) {
        return;
      }

      selectedRows.forEach(function (tr) {
        tr.classList.remove('table-danger');
        Array.from(tr.querySelectorAll('.bulk-row-error')).forEach(function (el) {
          el.remove();
        });
      });

      var rows = selectedRows.map(getRowData);
      var remainingRows = Array.from(tbody.querySelectorAll('tr'))
        .filter(function (tr) { return selectedRows.indexOf(tr) === -1; })
        .map(getRowData);

      fetch("<?= e(url('admin/leads/assign-selected')) ?>", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          _csrf: csrf.value,
          agent_id: agentId,
          rows: rows,
          remaining_rows: remainingRows
        })
      }).then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, data: data };
        });
      }).then(function (result) {
        if (!result.data || !result.data.ok) {
          if (result.data && result.data.row_errors) {
            Object.keys(result.data.row_errors).forEach(function (key) {
              var idx = parseInt(key, 10);
              var errs = result.data.row_errors[key];
              if (Number.isNaN(idx) || !selectedRows[idx]) return;
              var tr = selectedRows[idx];
              tr.classList.add('table-danger');
              if (Array.isArray(errs) && errs.length) {
                var msg = document.createElement('div');
                msg.className = 'small text-danger mt-1 bulk-row-error';
                msg.textContent = errs.join(', ');
                tr.lastElementChild && tr.lastElementChild.appendChild(msg);
              }
            });
          }
          alert((result.data && result.data.error) ? result.data.error : 'Unable to assign leads.');
          return;
        }
        selectedRows.forEach(function (tr) { tr.remove(); });
        updateRowNames();
        if (tbody.querySelectorAll('tr').length === 0) {
          addBlankRow();
        }
      }).catch(function () {
        alert('Unable to assign leads. Please try again.');
      });
    }, true);

    tbody.addEventListener('click', function (e) {
      var target = e.target;
      if (!target) return;
      if (target.closest('input,select,textarea,button,a,label')) return;
      var tr = target.closest('tr');
      if (!tr) return;
      var chk = tr.querySelector('[data-bulk-select-item]');
      if (!chk) return;
      chk.checked = !chk.checked;
    });
  })();
</script>
