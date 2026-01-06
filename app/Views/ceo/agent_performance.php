<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';

$agentName = $agent['employee_name'] ?: ucfirst((string)$agent['username']);
$agentInitial = strtoupper(substr($agentName, 0, 1));
$leadDisplayName = function (?string $name): string {
  $name = trim((string)$name);
  if ($name === '') return '';
  $parts = preg_split('/\s+/', $name);
  if (!$parts || count($parts) <= 3) return $name;
  return implode(' ', array_slice($parts, 0, 3)) . '...';
};

$days = array_map(fn($r)=>$r['day'], $byDay);
$dayCounts = array_map(fn($r)=>(int)$r['c'], $byDay);
?>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3">
          <div class="position-relative">
            <div class="avatar-xl user-img img-thumbnail rounded-circle d-flex align-items-center justify-content-center bg-light">
              <span class="text-primary fw-semibold fs-2"><?= e($agentInitial) ?></span>
            </div>
            <div class="badge bg-success rounded-2 position-absolute bottom-0 start-50 translate-middle-x mb-n1 fs-11">Agent</div>
          </div>
          <div class="d-block">
            <div class="text-dark fw-medium fs-16"><?= e($agentName) ?></div>
            <p class="mb-0 text-muted">@<?= e($agent['username']) ?></p>
          </div>
          <div class="ms-lg-auto d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('ceo/dashboard')) ?>">Back</a>
            <a class="btn btn-outline-success" href="<?= e(url('ceo/export?agent_id='.$agent['id'].'&from='.e($filters['from'] ?? '').'&to='.e($filters['to'] ?? ''))) ?>">Export CSV</a>
          </div>
        </div>

        <div class="mt-4">
          <form class="row gy-2 gx-2 align-items-end" method="get" action="<?= e(url('ceo/agent')) ?>">
            <input type="hidden" name="agent_id" value="<?= e((string)$agent['id']) ?>">
            <div class="col-sm-6 col-md-3">
              <label class="form-label">Search</label>
              <input class="form-control" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="lead name/email">
            </div>
            <div class="col-sm-6 col-md-3">
              <label class="form-label">From</label>
              <input type="date" class="form-control" name="from" value="<?= e($filters['from'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-md-3">
              <label class="form-label">To</label>
              <input type="date" class="form-control" name="to" value="<?= e($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-grid">
              <button class="btn btn-outline-primary">Apply</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center justify-content-between">
          <div class="col-7">
            <div class="avatar-md bg-light bg-opacity-50 rounded mb-3">
              <iconify-icon icon="solar:buildings-2-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
            </div>
            <p class="text-muted mb-1">Assigned Leads</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$assigned) ?></h3>
          </div>
          <div class="col-5 text-end">
            <div class="text-primary fw-semibold">Assigned</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center justify-content-between">
          <div class="col-7">
            <div class="avatar-md bg-light bg-opacity-50 rounded mb-3">
              <iconify-icon icon="solar:users-group-two-rounded-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
            </div>
            <p class="text-muted mb-1">Contacted Leads</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$contacted) ?></h3>
          </div>
          <div class="col-5 text-end">
            <div class="text-success fw-semibold">Touched</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center justify-content-between">
          <div class="col-7">
            <div class="avatar-md bg-light bg-opacity-50 rounded mb-3">
              <iconify-icon icon="solar:shield-user-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
            </div>
            <p class="text-muted mb-1">Completed (3+)</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$completed) ?></h3>
          </div>
          <div class="col-5 text-end">
            <div class="text-info fw-semibold">Closed</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center justify-content-between">
          <div class="col-7">
            <div class="avatar-md bg-light bg-opacity-50 rounded mb-3">
              <iconify-icon icon="solar:chart-2-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
            </div>
            <p class="text-muted mb-1">Response Rate</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$responseRate) ?>%</h3>
          </div>
          <div class="col-5 text-end">
            <div class="text-warning fw-semibold">Avg</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h4 class="card-title mb-0">Interested vs Not Interested</h4>
      </div>
      <div class="card-body">
        <div id="pieInterestAgent" style="height: 260px;"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h4 class="card-title mb-0">Leads Created Per Day</h4>
      </div>
      <div class="card-body">
        <div id="lineLeadsAgent" style="height: 260px;"></div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-0">Leads Detail</h4>
          <p class="text-muted mb-0">Lead drilldown for this agent.</p>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if (!$leads): ?>
          <div class="text-muted text-center py-5">No leads found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle text-nowrap table-hover table-centered mb-0">
              <thead class="bg-light-subtle">
                <tr>
                  <th>Lead</th>
                  <th>Email</th>
                  <th>Type</th>
                  <th>Created</th>
                  <th>Followups</th>
                  <th>Last Call</th>
                  <th>Interested</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($leads as $l):
                  $callStatus = $l['last_call_status'] ?? '-';
                  $interestStatus = $l['last_interested_status'] ?? '-';
                  $callClass = $callStatus === 'RESPONDED' ? 'bg-success-subtle text-success' : 'bg-light text-muted';
                  $interestClass = $interestStatus === 'INTERESTED' ? 'bg-success-subtle text-success' : ($interestStatus === 'NOT_INTERESTED' ? 'bg-danger-subtle text-danger' : 'bg-light text-muted');
                ?>
                  <tr>
                    <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
                    <td><?= e($l['contact_email']) ?></td>
                    <td><span class="badge bg-light-subtle text-muted border"><?= e($l['property_type']) ?></span></td>
                    <td class="text-muted"><?= e($l['created_at']) ?></td>
                    <td><?= e((string)$l['followups']) ?></td>
                    <td><span class="badge <?= e($callClass) ?> py-1 px-2 fs-13"><?= e($callStatus) ?></span></td>
                    <td><span class="badge <?= e($interestClass) ?> py-1 px-2 fs-13"><?= e($interestStatus) ?></span></td>
                    <td class="text-end">
                      <a class="btn btn-soft-primary btn-sm" href="<?= e(url('admin/lead?id='.$l['id'].'&return='.urlencode('ceo/agent?agent_id='.$agent['id'].'&from='.($filters['from'] ?? '').'&to='.($filters['to'] ?? '').'&q='.($filters['q'] ?? '')))) ?>">
                        <i class="ri-eye-line me-1"></i>View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
const agentTheme = getComputedStyle(document.documentElement);
const primary = agentTheme.getPropertyValue('--bs-primary').trim() || '#3b82f6';
const success = agentTheme.getPropertyValue('--bs-success').trim() || '#16a34a';
const warning = agentTheme.getPropertyValue('--bs-warning').trim() || '#f59e0b';
const muted = agentTheme.getPropertyValue('--bs-secondary-color').trim() || '#6b7280';
const grid = agentTheme.getPropertyValue('--bs-border-color').trim() || '#e5e7eb';

const renderAgentCharts = () => {
  if (!window.ApexCharts) {
    setTimeout(renderAgentCharts, 50);
    return;
  }

  const pieEl = document.getElementById('pieInterestAgent');
  if (pieEl) {
    const pieOptions = {
      chart: { type: 'donut', height: 260 },
      series: [<?= (int)$ratio['interested'] ?>, <?= (int)$ratio['not_interested'] ?>],
      labels: ['Interested', 'Not Interested'],
      colors: [success, warning],
      legend: { position: 'bottom', labels: { colors: muted } },
      dataLabels: { enabled: false },
      stroke: { width: 0 }
    };
    new ApexCharts(pieEl, pieOptions).render();
  }

  const lineEl = document.getElementById('lineLeadsAgent');
  if (lineEl) {
    const lineOptions = {
      chart: {
        type: 'area',
        height: 260,
        toolbar: { show: false }
      },
      series: [{
        name: 'Leads',
        data: <?= json_encode($dayCounts) ?>
      }],
      xaxis: {
        categories: <?= json_encode($days) ?>,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: muted, fontSize: '12px' } }
      },
      yaxis: {
        labels: { style: { colors: muted, fontSize: '12px' } }
      },
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      colors: [primary],
      fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.25, opacityTo: 0.05, stops: [0, 90, 100] }
      },
      grid: { borderColor: grid, strokeDashArray: 4 },
      legend: { show: false }
    };
    new ApexCharts(lineEl, lineOptions).render();
  }
};

if (document.readyState === 'complete') {
  renderAgentCharts();
} else {
  window.addEventListener('load', renderAgentCharts);
}
</script>
