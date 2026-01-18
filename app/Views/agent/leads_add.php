<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$rowErrors = $_SESSION['_lead_agent_row_errors'] ?? [];
$oldRows = $_SESSION['_lead_agent_old_rows'] ?? null;
unset($_SESSION['_lead_agent_row_errors'], $_SESSION['_lead_agent_old_rows']);
?>
<div class="card">
  <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border-bottom">
    <div>
      <h4 class="card-title mb-1">Add Lead</h4>
      <p class="text-muted mb-0 fs-13">Create a lead assigned to you.</p>
    </div>
    <button class="btn btn-sm btn-primary btn-mobile-full" type="button" data-add-lead-row>
      <i class="ri-add-line me-1"></i>Add Row
    </button>
  </div>
  <div class="card-body">
    <form method="post" action="<?= e(url('agent/leads/create')) ?>">
      <?= csrf_field() ?>
      <div class="d-flex justify-content-end small text-muted mb-2">
        <span class="d-inline-flex align-items-center gap-1">
          <span>Tip</span>
          <i class="ri-question-line hint-icon" title="Press Alt+S to save."></i>
        </span>
      </div>
      <style>
        @media (max-width: 767.98px) {
          .btn-mobile-full {
            width: 100%;
          }
          .agent-leads-table thead {
            display: none;
          }
          .agent-leads-table,
          .agent-leads-table tbody,
          .agent-leads-table tr,
          .agent-leads-table td {
            display: block;
            width: 100%;
          }
          .agent-leads-table tr {
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
            background: #fff;
          }
          .agent-leads-table td {
            padding: 6px 0;
          }
          .agent-leads-table td::before {
            content: attr(data-label);
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
          }
          .agent-leads-table td.actions {
            padding-top: 10px;
          }
          .agent-leads-table td.actions::before {
            content: '';
            margin: 0;
          }
          .agent-leads-table td.actions .btn {
            width: 100%;
          }
        }
      </style>
      <div class="table-responsive">
        <table class="table align-middle text-nowrap table-hover table-centered mb-0 agent-leads-table">
          <thead class="bg-light-subtle">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th class="form-required">Interested In Property</th>
            <th>Interest Types</th>
            <th class="form-required">Type</th>
            <th>Area</th>
            <th>Budget (AED)</th>
            <th>Lead Status</th>
            <th class="text-end">Actions</th>
          </tr>
          </thead>
          <tbody id="agentLeadRows">
            <?php
              $rows = is_array($oldRows) ? $oldRows : [
                ['lead_name'=>'','contact_email'=>'','contact_phone'=>'','interested_in_property'=>'','property_type'=>'','assigned_agent_user_id'=>'']
              ];
              foreach ($rows as $i => $r):
            ?>
            <tr class="<?= isset($rowErrors[$i]) ? 'table-danger' : '' ?>">
              <td data-label="Name"><input class="form-control" data-base="lead_name" name="rows[<?= $i ?>][lead_name]" value="<?= e($r['lead_name'] ?? '') ?>" <?= $i === 0 ? 'autofocus' : '' ?>></td>
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
              <td class="actions" data-label="Actions">
                <button class="btn btn-sm btn-soft-danger" type="button" data-remove-lead-row>Remove</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-grid d-sm-none mt-2">
        <button class="btn btn-outline-primary" type="button" data-add-lead-row>
          <i class="ri-add-line me-1"></i>Add Row
        </button>
      </div>

      <template id="agentLeadRowTemplate">
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
          <td class="actions" data-label="Actions">
            <button class="btn btn-sm btn-soft-danger" type="button" data-remove-lead-row>Remove</button>
          </td>
        </tr>
      </template>

      <?php if ($rowErrors): ?>
        <div class="mt-3 alert alert-danger py-2">
          <div class="fw-semibold mb-1">Fix the highlighted row(s):</div>
          <ul class="mb-0 ps-3 small">
            <?php foreach ($rowErrors as $errs): ?>
              <li><?= e(implode(', ', $errs)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="d-grid d-sm-flex justify-content-sm-end mt-3">
        <button class="btn btn-primary btn-mobile-full" type="submit">
          <i class="ri-save-line me-1"></i>Save Lead
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    var addBtns = document.querySelectorAll('[data-add-lead-row]');
    var tbody = document.getElementById('agentLeadRows');
    var tpl = document.getElementById('agentLeadRowTemplate');
    if (!addBtns.length || !tbody || !tpl) return;

    function updateRowNames() {
      Array.from(tbody.querySelectorAll('tr')).forEach(function (tr, idx) {
        tr.querySelectorAll('input,select').forEach(function (input) {
          var base = input.getAttribute('data-base');
          if (!base) return;
          input.name = 'rows[' + idx + '][' + base + ']';
        });
      });
    }

    function resetRowInputs(tr) {
      tr.querySelectorAll('input').forEach(function (input) {
        input.value = '';
      });
      tr.querySelectorAll('select').forEach(function (select) {
        select.selectedIndex = 0;
      });
    }

    function addRow() {
      var frag = tpl.content.cloneNode(true);
      tbody.appendChild(frag);
      updateRowNames();
      var lastRow = tbody.lastElementChild;
      if (lastRow) {
        var firstInput = lastRow.querySelector('input,select');
        if (firstInput) firstInput.focus();
      }
    }

    addBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        addRow();
      });
    });

    tbody.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var target = e.target;
      if (!target || !target.closest('tr')) return;
      e.preventDefault();
      var row = target.closest('tr');
      var inputs = Array.from(row.querySelectorAll('input,select'));
      var idx = inputs.indexOf(target);
      if (idx === -1) return;
      if (idx < inputs.length - 1) {
        inputs[idx + 1].focus();
        return;
      }
      var nextRow = row.nextElementSibling;
      if (nextRow) {
        var nextInput = nextRow.querySelector('input,select');
        if (nextInput) nextInput.focus();
      } else {
        addRow();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (!e.altKey) return;
      if (e.key && e.key.toLowerCase() !== 's') return;
      var form = document.querySelector('form[action="<?= e(url('agent/leads/create')) ?>"]');
      if (!form) return;
      e.preventDefault();
      form.requestSubmit();
    });

    tbody.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove-lead-row]');
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
