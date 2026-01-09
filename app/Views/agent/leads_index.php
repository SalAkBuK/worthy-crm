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
      <h4 class="card-title mb-1">Assigned Leads</h4>
      <p class="text-muted mb-0 fs-13">Goal: complete at least 3 follow-up attempts per lead.</p>
    </div>
  </div>
  <div class="card-body border-bottom">
    <?php $currentStatus = $filters['status'] ?? ''; ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <span class="text-muted fw-semibold">Quick status:</span>
      <div class="btn-group btn-group-sm" role="group" aria-label="Lead status filters">
        <a class="btn btn-outline-secondary <?= $currentStatus === '' ? 'active' : '' ?>" href="?<?= e(build_query(['status' => null, 'page' => 1])) ?>">All</a>
        <a class="btn btn-outline-secondary <?= $currentStatus === 'NEW' ? 'active' : '' ?>" href="?<?= e(build_query(['status' => 'NEW', 'page' => 1])) ?>">New</a>
        <a class="btn btn-outline-secondary <?= $currentStatus === 'IN_PROGRESS' ? 'active' : '' ?>" href="?<?= e(build_query(['status' => 'IN_PROGRESS', 'page' => 1])) ?>">In Progress</a>
        <a class="btn btn-outline-secondary <?= $currentStatus === '50/50' ? 'active' : '' ?>" href="?<?= e(build_query(['status' => '50/50', 'page' => 1])) ?>">50/50</a>
        <a class="btn btn-outline-secondary <?= $currentStatus === 'CLOSED' ? 'active' : '' ?>" href="?<?= e(build_query(['status' => 'CLOSED', 'page' => 1])) ?>">Closed</a>
      </div>
    </div>
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
          <option value="">All statuses</option>
          <option value="NEW" <?= (($filters['status'] ?? '')==='NEW')?'selected':'' ?>>New</option>
          <option value="IN_PROGRESS" <?= (($filters['status'] ?? '')==='IN_PROGRESS')?'selected':'' ?>>In Progress</option>
          <option value="50/50" <?= (($filters['status'] ?? '')==='50/50')?'selected':'' ?>>50/50</option>
          <option value="CLOSED" <?= (($filters['status'] ?? '')==='CLOSED')?'selected':'' ?>>Closed</option>
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

  <div id="agentLeadsTable" data-refresh-url="<?= e(url('agent/leads/partial')) ?>">
    <?php require __DIR__ . '/leads_table.php'; ?>
  </div>
</div>
