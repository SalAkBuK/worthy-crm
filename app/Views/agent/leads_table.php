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
$items = $items ?? [];
$meta = $meta ?? ['page' => 1, 'pages' => 1];
$prepared = [];
foreach ($items as $l) {
  $budget = '-';
  $range = trim((string)($l['budget_aed_range'] ?? ''));
  $min = $l['budget_aed_min'] ?? null;
  $max = $l['budget_aed_max'] ?? null;
  if ($range !== '') {
    $budget = $range;
  } elseif ($min !== null || $max !== null) {
    if ($min !== null && $max !== null) {
      $budget = $min . ' - ' . $max;
    } else {
      $budget = (string)($min ?? $max);
    }
  }
  $email = (string)($l['contact_email'] ?? '');
  $phone = (string)($l['contact_phone'] ?? '');
  $emailShort = $email !== '' ? mb_strimwidth($email, 0, 18, '...') : '-';
  $phoneShort = $phone !== '' ? mb_strimwidth($phone, 0, 12, '...') : '-';
  $s = $l['status_overall'] ?? '';
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
  } elseif ($s === 'NEW') {
    $cls = 'secondary';
    $label = 'New';
  } else {
    $cls = 'secondary';
    $label = $s !== '' ? $s : 'New';
  }
  $prepared[] = [
    'lead' => $l,
    'budget' => $budget,
    'email' => $email,
    'phone' => $phone,
    'email_short' => $emailShort,
    'phone_short' => $phoneShort,
    'status_class' => $cls,
    'status_label' => $label,
  ];
}
?>
<?php if (!$items): ?>
  <div class="text-center py-5 text-muted">No assigned leads found.</div>
<?php else: ?>
  <div class="d-none d-md-block table-responsive">
    <table class="table align-middle text-nowrap table-hover table-centered mb-0">
      <thead class="bg-light-subtle">
      <tr>
        <th>Lead</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Budget (AED)</th>
        <th>Lead Status</th>
        <th>Status</th>
        <th>Followups</th>
        <th>Last Contact</th>
        <th class="text-end"></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($prepared as $row): ?>
        <?php
          $l = $row['lead'];
        ?>
        <tr>
          <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
          <td title="<?= e($row['email']) ?>"><?= e($row['email_short']) ?></td>
          <td title="<?= e($row['phone']) ?>"><?= e($row['phone_short']) ?></td>
          <td><?= e($row['budget']) ?></td>
          <td><?= e($l['lead_status'] ?? '-') ?></td>
          <td>
            <span class="badge bg-<?= e($row['status_class']) ?>-subtle text-<?= e($row['status_class']) ?> fw-medium fs-13 px-2 py-1">
              <?= e($row['status_label']) ?>
            </span>
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

  <div class="d-md-none">
    <?php foreach ($prepared as $row): ?>
      <?php $l = $row['lead']; ?>
      <div class="card border mb-2">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></div>
              <div class="text-muted fs-12" style="word-break: break-word;">
                <?= e($row['email'] !== '' ? $row['email'] : '-') ?>
              </div>
              <div class="text-muted fs-12"><?= e($row['phone'] !== '' ? $row['phone'] : '-') ?></div>
            </div>
            <span class="badge bg-<?= e($row['status_class']) ?>-subtle text-<?= e($row['status_class']) ?> fw-medium fs-13 px-2 py-1">
              <?= e($row['status_label']) ?>
            </span>
          </div>
          <div class="row g-2 mt-2 small text-muted">
            <div class="col-6"><span class="text-dark fw-semibold">Lead:</span> <?= e($l['lead_status'] ?? '-') ?></div>
            <div class="col-6"><span class="text-dark fw-semibold">Budget:</span> <?= e($row['budget']) ?></div>
            <div class="col-6"><span class="text-dark fw-semibold">Followups:</span> <?= e((string)$l['followup_count']) ?>/3</div>
            <div class="col-6"><span class="text-dark fw-semibold">Last:</span> <?= e($l['last_contact_at'] ?? '-') ?></div>
          </div>
          <div class="mt-3">
            <a class="btn btn-soft-primary btn-sm w-100" href="<?= e(url('agent/lead?id='.$l['id'])) ?>">
              <i class="ri-eye-line me-1"></i>Open Lead
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
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
