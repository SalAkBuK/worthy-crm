<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$leadDisplayName = function (?string $name): string {
  $name = trim((string)$name);
  if ($name === '') return '';
  $parts = preg_split('/\s+/', $name);
  if (!$parts || count($parts) <= 3) return $name;
  return implode(' ', array_slice($parts, 0, 3)) . '...';
};
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center border-bottom">
    <div>
      <h4 class="card-title mb-1">Your Assigned Leads</h4>
      <p class="text-muted mb-0 fs-13">Complete minimum 3 follow-up attempts per lead.</p>
    </div>
  </div>
  <div class="card-body border-bottom">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('agent/leads')) ?>">
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input class="form-control" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search name/email">
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
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All status</option>
          <option value="NEW" <?= (($filters['status'] ?? '')==='NEW')?'selected':'' ?>>NEW</option>
          <option value="IN_PROGRESS" <?= (($filters['status'] ?? '')==='IN_PROGRESS')?'selected':'' ?>>IN_PROGRESS</option>
          <option value="CLOSED" <?= (($filters['status'] ?? '')==='CLOSED')?'selected':'' ?>>CLOSED</option>
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
      <div class="col-md-12 d-flex justify-content-end">
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
          <th>Lead</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Type</th>
          <th>Status</th>
          <th>Followups</th>
          <th>Last Contact</th>
          <th class="text-end"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $l): ?>
          <tr>
            <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
            <td><?= e($l['contact_email']) ?></td>
            <td><?= e($l['contact_phone'] ?? '-') ?></td>
            <td><span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($l['property_type']) ?></span></td>
            <td>
              <?php $s=$l['status_overall']; $cls=$s==='CLOSED'?'success':($s==='IN_PROGRESS'?'warning':'secondary'); ?>
              <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> fw-medium fs-13 px-2 py-1"><?= e($s) ?></span>
            </td>
            <td><?= e((string)$l['followup_count']) ?>/3</td>
            <td class="text-muted"><?= e($l['last_contact_at'] ?? '-') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-soft-primary" href="<?= e(url('agent/lead?id='.$l['id'])) ?>">
                <i class="ri-eye-line me-1"></i>Open
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php $m=$meta; $page=$m['page']; $pages=$m['pages']; ?>
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
