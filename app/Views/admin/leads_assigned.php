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
  <div class="card-header d-flex justify-content-between align-items-center border-bottom">
    <div>
      <h4 class="card-title mb-1">Assigned Leads</h4>
      <p class="text-muted mb-0 fs-13">All leads currently assigned to agents.</p>
    </div>
    <a class="btn btn-outline-primary btn-sm" href="<?= e($exportUrl) ?>">
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
            <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
            <td><?= e($l['contact_email']) ?></td>
            <td><?= e($l['contact_phone'] ?? '-') ?></td>
            <td class="text-muted"><?= e($l['interested_in_property']) ?></td>
            <?php $ptype = $l['property_type'] !== null && $l['property_type'] !== '' ? (string)$l['property_type'] : 'NONE'; ?>
            <td><span class="badge bg-light-subtle text-dark py-1 px-2 fs-13"><?= e($ptype) ?></span></td>
            <td><?= e($l['agent_name'] ?: '-') ?></td>
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
              <a class="btn btn-light btn-sm" href="<?= e(url('admin/lead?id='.$l['id'].'&return='.$returnParam)) ?>" title="View lead">
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
