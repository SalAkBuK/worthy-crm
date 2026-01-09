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
?>
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
        <th>Budget (AED)</th>
        <th>Lead Status</th>
        <th>Status</th>
        <th>Followups</th>
        <th>Last Contact</th>
        <th class="text-end"></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $l): ?>
        <?php
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
        ?>
        <tr>
          <td class="fw-semibold"><?= e($leadDisplayName($l['lead_name'] ?? '')) ?></td>
          <td title="<?= e($email) ?>"><?= e($emailShort) ?></td>
          <td title="<?= e($phone) ?>"><?= e($phoneShort) ?></td>
          <td><?= e($budget) ?></td>
          <td><?= e($l['lead_status'] ?? '-') ?></td>
          <td>
            <?php
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
              } elseif ($s === 'NEW') {
                $cls = 'secondary';
                $label = 'New';
              } else {
                $cls = 'secondary';
                $label = $s ?: 'New';
              }
            ?>
            <span class="badge bg-<?= e($cls) ?>-subtle text-<?= e($cls) ?> fw-medium fs-13 px-2 py-1"><?= e($label) ?></span>
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
