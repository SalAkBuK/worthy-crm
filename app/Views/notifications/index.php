<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$items = $items ?? [];
$unread = (int)($unread ?? 0);
$formatNotifTime = static function(?string $value): string {
  if (!$value) return '';
  $ts = strtotime($value);
  if ($ts === false) return $value;
  return date('M d, H:i', $ts);
};
$notifStyle = static function(string $type): array {
  switch ($type) {
    case 'lead_assigned':
      return ['label' => 'Assigned', 'class' => 'bg-primary-subtle text-primary'];
    case 'lead_status_changed':
      return ['label' => 'Status', 'class' => 'bg-info-subtle text-info'];
    case 'lead_idle':
      return ['label' => 'Idle', 'class' => 'bg-warning-subtle text-warning'];
    case 'followup_due_soon':
      return ['label' => 'Due Soon', 'class' => 'bg-info-subtle text-info'];
    case 'followup_overdue':
    case 'followup_overdue_escalated':
      return ['label' => 'Overdue', 'class' => 'bg-danger-subtle text-danger'];
    case 'followup_missed':
      return ['label' => 'Missed', 'class' => 'bg-danger-subtle text-danger'];
    case 'followup_scheduled':
      return ['label' => 'Scheduled', 'class' => 'bg-success-subtle text-success'];
    case 'bulk_import_failed':
      return ['label' => 'Import', 'class' => 'bg-danger-subtle text-danger'];
    case 'agent_status_change':
      return ['label' => 'Agent', 'class' => 'bg-warning-subtle text-warning'];
    case 'agent_deleted':
      return ['label' => 'Agent', 'class' => 'bg-danger-subtle text-danger'];
    case 'lead_reopen':
      return ['label' => 'Reopen', 'class' => 'bg-info-subtle text-info'];
    case 'lead_closed':
      return ['label' => 'Won', 'class' => 'bg-success-subtle text-success'];
    case 'agent_inactive':
      return ['label' => 'Inactive', 'class' => 'bg-warning-subtle text-warning'];
    case 'weekly_closed_milestone':
    case 'daily_closed_milestone':
      return ['label' => 'Milestone', 'class' => 'bg-success-subtle text-success'];
    case 'weekly_summary':
      return ['label' => 'Weekly', 'class' => 'bg-info-subtle text-info'];
    default:
      return ['label' => 'Info', 'class' => 'bg-secondary-subtle text-secondary'];
  }
};
?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Notifications</h4>
        <?php if ($unread > 0): ?>
          <form method="post" action="<?= e(url('notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-primary" type="submit">Mark all read</button>
          </form>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$items): ?>
          <p class="text-muted mb-0">No notifications yet.</p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($items as $n): ?>
              <?php
                $title = $n['title'] ?? 'Notification';
                $body = $n['body'] ?? '';
                $created = $n['created_at'] ?? '';
                $link = $n['link_url'] ?? '';
                $isUnread = empty($n['read_at']);
                $style = $notifStyle((string)($n['type'] ?? ''));
                $timeText = $formatNotifTime($created);
              ?>
              <div class="list-group-item d-flex justify-content-between align-items-start">
                <div class="me-3">
                  <div class="fw-medium text-dark"><?= e($title) ?></div>
                  <?php if ($body !== ''): ?>
                    <div class="text-muted"><?= e($body) ?></div>
                  <?php endif; ?>
                  <div class="small text-muted mt-1"><?= e($timeText !== '' ? $timeText : $created) ?></div>
                  <?php if ($link): ?>
                    <div class="mt-2">
                      <a href="<?= e(url($link)) ?>" class="btn btn-sm btn-outline-secondary">Open</a>
                    </div>
                  <?php endif; ?>
                </div>
                <span class="badge <?= e($style['class']) ?> me-2"><?= e($style['label']) ?></span>
                <?php if ($isUnread): ?>
                  <span class="badge bg-primary-subtle text-primary">New</span>
                <?php else: ?>
                  <span class="badge bg-light text-muted">Read</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
