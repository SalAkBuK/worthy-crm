<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';

$labels = array_map(fn($r)=>$r['name'], $agents);
$followups = array_map(fn($r)=>(int)$r['followups'], $agents);

$leadByDay = [];
foreach ($byDay as $row) {
  $leadByDay[$row['day']] = (int)$row['c'];
}
$contactByDay = [];
foreach ($contactedByDay ?? [] as $row) {
  $contactByDay[$row['day']] = (int)$row['c'];
}
$days = array_values(array_unique(array_merge(array_keys($leadByDay), array_keys($contactByDay))));
sort($days, SORT_STRING);
$dayCounts = array_map(fn($d)=>$leadByDay[$d] ?? 0, $days);
$contactedCounts = array_map(fn($d)=>$contactByDay[$d] ?? 0, $days);
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dash-users" type="button" role="tab">Users</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dash-leads" type="button" role="tab">Leads</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dash-followups" type="button" role="tab">Follow-ups</button>
          </li>
        </ul>
        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="dash-users" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Users</p>
                    <h4 class="mb-0"><?= e((string)$totalUsers) ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Agents</p>
                    <h4 class="mb-0"><?= e((string)$totalAgents) ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Admins</p>
                    <h4 class="mb-0"><?= e((string)$totalAdmins) ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="dash-leads" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border shadow-none mb-0">
                  <div class="card-body">
                    <p class="text-muted mb-1">Total Leads</p>
                    <h4 class="mb-0"><?= e((string)$totalLeads) ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="dash-followups" role="tabpanel">
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
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <form class="row gy-2 gx-2 align-items-end" method="get" action="<?= e(url('ceo/dashboard')) ?>">
          <div class="col-sm-6 col-md-3">
            <label class="form-label">From</label>
            <input type="date" class="form-control" name="from" value="<?= e($filters['from'] ?? '') ?>">
          </div>
          <div class="col-sm-6 col-md-3">
            <label class="form-label">To</label>
            <input type="date" class="form-control" name="to" value="<?= e($filters['to'] ?? '') ?>">
          </div>
          <div class="col-md-3 d-grid">
            <button class="btn btn-outline-primary">Apply Filter</button>
          </div>
          <div class="col-md-3 text-md-end text-muted small">
            Date-aware analytics
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center justify-content-between">
          <div class="col-7">
            <div class="avatar-md bg-light bg-opacity-50 rounded mb-3">
              <iconify-icon icon="solar:buildings-2-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
            </div>
            <p class="text-muted mb-1">Total Leads</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$totalLeads) ?></h3>
          </div>
          <div class="col-5 text-end">
            <div class="text-primary fw-semibold">All time</div>
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
            <p class="text-muted mb-1">Leads Contacted</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$contactedLeads) ?></h3>
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
            <p class="text-muted mb-1">Leads Completed (3+)</p>
            <h3 class="text-dark fw-bold mb-0"><?= e((string)$completedLeads) ?></h3>
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

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h4 class="card-title mb-0">Follow-ups per Agent</h4>
      </div>
      <div class="card-body">
        <canvas id="barFollowups" height="130"></canvas>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h4 class="card-title mb-0">Interested vs Not Interested</h4>
      </div>
      <div class="card-body">
        <canvas id="pieInterest" height="160"></canvas>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card overflow-hidden">
      <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h4 class="card-title mb-0">Leads Created vs Contacted</h4>
      </div>
      <div class="card-body">
        <div id="lineLeads" style="height: 260px;"></div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-0">Agents Performance</h4>
          <p class="text-muted mb-0">Click "Check Performance" for details.</p>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle text-nowrap table-hover table-centered mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th>Agent</th>
                <th>Assigned Leads</th>
                <th>Contacted Leads</th>
                <th>Completed Leads</th>
                <th>Response Rate</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($agents as $a):
              // derive counts based on leads
              $pdo = \App\Helpers\DB::conn();
              $st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_agent_user_id=:id");
              $st->execute([':id'=>$a['id']]); $assigned = (int)$st->fetchColumn();

              $st = $pdo->prepare("SELECT COUNT(DISTINCT l.id) FROM leads l JOIN lead_followups f ON f.lead_id=l.id AND f.agent_user_id=:id2 WHERE l.assigned_agent_user_id=:id");
              $st->execute([':id'=>$a['id'], ':id2'=>$a['id']]); $contacted = (int)$st->fetchColumn();

              $st = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=:id AND (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:id2) >= 3");
              $st->execute([':id'=>$a['id'], ':id2'=>$a['id']]); $completed = (int)$st->fetchColumn();

              $rr = ($a['followups']>0) ? round(($a['responded']/$a['followups'])*100,1) : 0;
              $rrClass = $rr >= 60 ? 'bg-success-subtle text-success' : ($rr >= 30 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
              $initial = strtoupper(substr((string)$a['name'], 0, 1));
            ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm rounded-circle bg-soft-primary d-flex align-items-center justify-content-center">
                      <span class="text-primary fw-semibold"><?= e($initial) ?></span>
                    </div>
                    <div>
                      <div class="text-dark fw-medium"><?= e($a['name']) ?></div>
                      <div class="text-muted fs-12">Agent ID <?= e((string)$a['id']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= e((string)$assigned) ?></td>
                <td><?= e((string)$contacted) ?></td>
                <td><?= e((string)$completed) ?></td>
                <td><span class="badge <?= e($rrClass) ?> py-1 px-2 fs-13"><?= e((string)$rr) ?>%</span></td>
                <td class="text-end">
                  <a class="btn btn-soft-primary btn-sm" href="<?= e(url('ceo/agent?agent_id='.$a['id'].'&from='.e($filters['from'] ?? '').'&to='.e($filters['to'] ?? ''))) ?>">
                    <i class="ri-eye-line me-1"></i>Check Performance
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div></div>
  </div>
</div>

<script>
const themeStyles = getComputedStyle(document.documentElement);
const primary = themeStyles.getPropertyValue('--bs-primary').trim() || '#3b82f6';
const success = themeStyles.getPropertyValue('--bs-success').trim() || '#16a34a';
const warning = themeStyles.getPropertyValue('--bs-warning').trim() || '#f59e0b';
const muted = themeStyles.getPropertyValue('--bs-secondary-color').trim() || '#6b7280';
const grid = themeStyles.getPropertyValue('--bs-border-color').trim() || '#e5e7eb';
const fontFamily = getComputedStyle(document.body).fontFamily || 'sans-serif';

Chart.defaults.font.family = fontFamily;
Chart.defaults.color = muted;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 6;

const barCtx = document.getElementById('barFollowups');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Followups',
      data: <?= json_encode($followups) ?>,
      backgroundColor: primary + 'CC',
      borderRadius: 6,
      barThickness: 22
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: muted } },
      y: { grid: { color: grid }, ticks: { color: muted } }
    }
  }
});

const pieCtx = document.getElementById('pieInterest');
new Chart(pieCtx, {
  type:'doughnut',
  data:{
    labels:['Interested','Not Interested'],
    datasets:[{
      data:[<?= (int)$ratio['interested'] ?>, <?= (int)$ratio['not_interested'] ?>],
      backgroundColor:[success, warning],
      hoverOffset: 6,
      borderWidth: 0
    }]
  },
  options:{
    responsive:true,
    cutout: '70%',
    plugins: { legend: { position: 'bottom', labels: { color: muted } } }
  }
});

const lineEl = document.getElementById('lineLeads');
const renderLeadsChart = () => {
  if (!lineEl || !window.ApexCharts) {
    if (window.ApexCharts) return;
    setTimeout(renderLeadsChart, 50);
    return;
  }
  const lineOptions = {
    chart: {
      type: 'area',
      height: 300,
      dropShadow: {
        enabled: true,
        opacity: 0.2,
        blur: 10,
        left: -7,
        top: 22
      },
      toolbar: { show: false }
    },
    series: [{
      name: 'Leads Created',
      data: <?= json_encode($dayCounts) ?>
    }, {
      name: 'Leads Contacted',
      data: <?= json_encode($contactedCounts) ?>
    }],
    xaxis: {
      categories: <?= json_encode($days) ?>,
      axisBorder: { show: false },
      axisTicks: { show: false },
      crosshairs: { show: true },
      labels: { style: { colors: muted, fontSize: '12px' } }
    },
    yaxis: {
      labels: {
        style: { colors: muted, fontSize: '12px' },
        formatter: (val) => Math.round(val)
      }
    },
    dataLabels: { enabled: false },
    stroke: {
      show: true,
      curve: 'smooth',
      width: 2,
      lineCap: 'square'
    },
    colors: [primary, success],
    fill: {
      type: 'gradient',
      gradient: {
        type: 'vertical',
        shadeIntensity: 1,
        inverseColors: false,
        opacityFrom: 0.12,
        opacityTo: 0.1,
        stops: [100, 100]
      }
    },
    grid: {
      borderColor: grid,
      strokeDashArray: 5,
      xaxis: { lines: { show: true } },
      yaxis: { lines: { show: false } },
      padding: { top: -20, right: 0, bottom: 0, left: 5 }
    },
    legend: { show: false },
    tooltip: {
      theme: 'light'
    }
  };
  new ApexCharts(lineEl, lineOptions).render();
};
if (document.readyState === 'complete') {
  renderLeadsChart();
} else {
  window.addEventListener('load', renderLeadsChart);
}
</script>
