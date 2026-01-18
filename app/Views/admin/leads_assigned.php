<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$agents = $agents ?? [];
$reqUri = $_SERVER['REQUEST_URI'] ?? 'admin/leads/assigned';
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
$leadDisplayName = function (?string $name): string {
  $name = trim((string)$name);
  if ($name === '') return '';
  $parts = preg_split('/\s+/', $name);
  if (!$parts || count($parts) <= 3) return $name;
  return implode(' ', array_slice($parts, 0, 3)) . '...';
};
?>
<div class="card">
  <?php
    $exportQuery = build_query(['page' => null, 'assigned_only' => 1]);
    $exportUrl = url('admin/leads/export' . ($exportQuery ? '?' . $exportQuery : ''));
  ?>
  <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border-bottom">
    <div>
      <h4 class="card-title mb-1">Assigned Leads</h4>
      <p class="text-muted mb-0 fs-13">All leads currently assigned to agents.</p>
    </div>
    <a class="btn btn-outline-primary btn-sm btn-mobile-full" href="<?= e($exportUrl) ?>">
      <i class="ri-download-2-line me-1"></i>Export CSV
    </a>
  </div>
  <div class="card-body border-bottom">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/leads/assigned')) ?>">
      <input type="hidden" name="assigned_only" value="1">
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
          <option value="NONE" <?= (($filters['type'] ?? '')==='NONE')?'selected':'' ?>>None</option>
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
    <div class="text-center py-5 text-muted">No assigned leads found.</div>
  <?php else: ?>
    <style>
      @media (max-width: 991.98px) {
        .admin-assigned-cards .btn {
          width: 100%;
        }
      }
      @media (max-width: 767.98px) {
        .btn-mobile-full {
          width: 100%;
        }
      }
    </style>
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
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-lg-none admin-assigned-cards">
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
            <div class="text-muted fs-12 mt-1"><?= e($email !== '' ? $email : '-') ?></div>
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
            <div class="mt-3">
              <a class="btn btn-light btn-sm" href="<?= e(url('admin/lead?id='.$l['id'].'&return='.$returnParam)) ?>">View</a>
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
