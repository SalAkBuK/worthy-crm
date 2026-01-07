<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
$user = current_user();
$username = $user['username'] ?? 'User';
$photo = !empty($user['photo_path']) ? url($user['photo_path']) : url('assets/lahomes/images/users/avatar-1.jpg');
$userId = (int)($user['id'] ?? 0);
$role = $user['role'] ?? '';
$notifTypes = notification_types_for_role($role);
$unreadNotifications = \App\Models\Notification::unreadCount($userId, $notifTypes);
$notifications = \App\Models\Notification::listForUser($userId, 5, $notifTypes);
$formatNotifTime = static function(?string $value): string {
  if (!$value) return '';
  $ts = strtotime($value);
  if ($ts === false) return $value;
  return date('M d, H:i', $ts);
};
$notifStyle = static function(string $type): array {
  switch ($type) {
    case 'lead_assigned':
      return ['icon' => 'ri-user-add-line', 'class' => 'bg-soft-primary text-primary'];
    case 'lead_status_changed':
      return ['icon' => 'ri-refresh-line', 'class' => 'bg-soft-info text-info'];
    case 'lead_idle':
      return ['icon' => 'ri-time-line', 'class' => 'bg-soft-warning text-warning'];
    case 'followup_due_soon':
      return ['icon' => 'ri-alarm-line', 'class' => 'bg-soft-info text-info'];
    case 'followup_overdue':
    case 'followup_overdue_escalated':
      return ['icon' => 'ri-alarm-warning-line', 'class' => 'bg-soft-danger text-danger'];
    case 'followup_missed':
      return ['icon' => 'ri-close-circle-line', 'class' => 'bg-soft-danger text-danger'];
    case 'followup_scheduled':
      return ['icon' => 'ri-calendar-event-line', 'class' => 'bg-soft-success text-success'];
    case 'bulk_import_failed':
      return ['icon' => 'ri-file-warning-line', 'class' => 'bg-soft-danger text-danger'];
    case 'agent_status_change':
      return ['icon' => 'ri-shield-user-line', 'class' => 'bg-soft-warning text-warning'];
    case 'lead_reopen':
      return ['icon' => 'ri-restart-line', 'class' => 'bg-soft-info text-info'];
    case 'agent_deleted':
      return ['icon' => 'ri-user-unfollow-line', 'class' => 'bg-soft-danger text-danger'];
    case 'weekly_summary':
      return ['icon' => 'ri-calendar-check-line', 'class' => 'bg-soft-info text-info'];
    case 'daily_closed_milestone':
      return ['icon' => 'ri-trophy-line', 'class' => 'bg-soft-success text-success'];
    default:
      return ['icon' => 'ri-notification-3-line', 'class' => 'bg-soft-secondary text-secondary'];
  }
};
?>
<header class="">
  <div class="topbar">
    <div class="container-fluid">
      <div class="navbar-header">
        <div class="d-flex align-items-center gap-2">
          <div class="topbar-item">
            <button type="button" class="button-toggle-menu topbar-button">
              <i class="ri-menu-2-line fs-24"></i>
            </button>
          </div>

          <form class="app-search d-none d-md-block me-auto" action="#">
            <div class="position-relative">
              <input type="search" class="form-control border-0" placeholder="Search..." autocomplete="off">
              <i class="ri-search-line search-widget-icon"></i>
            </div>
          </form>
        </div>

        <div class="d-flex align-items-center gap-1">
          <div class="topbar-item">
            <button type="button" class="topbar-button" id="light-dark-mode">
              <i class="ri-moon-line fs-24 light-mode"></i>
              <i class="ri-sun-line fs-24 dark-mode"></i>
            </button>
          </div>

          <div class="dropdown topbar-item d-none d-lg-flex">
            <button type="button" class="topbar-button" data-toggle="fullscreen">
              <i class="ri-fullscreen-line fs-24 fullscreen"></i>
              <i class="ri-fullscreen-exit-line fs-24 quit-fullscreen"></i>
            </button>
          </div>

          <div class="dropdown topbar-item">
            <button type="button" class="topbar-button position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="ri-notification-3-line fs-24"></i>
              <span id="notificationsBadgeTopbar" class="position-absolute topbar-badge fs-10 translate-middle badge bg-danger rounded-pill <?= $unreadNotifications > 0 ? '' : 'd-none' ?>">
                <?= e((string)$unreadNotifications) ?>
                <span class="visually-hidden">unread notifications</span>
              </span>
            </button>
            <div class="dropdown-menu py-0 dropdown-lg dropdown-menu-end" aria-labelledby="page-header-notifications-dropdown">
              <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                <div class="row align-items-center">
                  <div class="col">
                    <h6 class="m-0 fs-16 fw-semibold">Notifications</h6>
                  </div>
                  <div class="col-auto">
                    <form method="post" action="<?= e(url('notifications/read-all')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-link p-0 text-dark text-decoration-underline" type="submit">
                        <small>Clear All</small>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
              <div data-simplebar style="max-height: 280px;">
                <?php if (!$notifications): ?>
                  <div class="dropdown-item py-3 text-muted">No notifications yet.</div>
                <?php else: ?>
                  <?php foreach ($notifications as $n): ?>
                    <?php
                      $title = $n['title'] ?? 'Notification';
                      $body = $n['body'] ?? '';
                      $link = $n['link_url'] ?? '';
                      $style = $notifStyle((string)($n['type'] ?? ''));
                      $timeText = $formatNotifTime($n['created_at'] ?? null);
                    ?>
                    <a href="<?= e($link ? url($link) : 'javascript:void(0);') ?>" class="dropdown-item py-3 border-bottom text-wrap">
                      <div class="d-flex">
                        <div class="flex-shrink-0">
                          <div class="avatar-sm me-2">
                            <span class="avatar-title <?= e($style['class']) ?> fs-20 rounded-circle">
                              <i class="<?= e($style['icon']) ?>"></i>
                            </span>
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <p class="mb-0 fw-semibold"><?= e($title) ?></p>
                          <?php if ($body !== ''): ?>
                            <p class="mb-0 text-wrap"><?= e($body) ?></p>
                          <?php endif; ?>
                          <?php if ($timeText !== ''): ?>
                            <small class="text-muted d-block mt-1"><?= e($timeText) ?></small>
                          <?php endif; ?>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="p-2 text-center">
                <a href="<?= e(url('notifications')) ?>" class="btn btn-primary btn-sm">
                  View All Notification <i class="ri-arrow-right-line ms-1"></i>
                </a>
              </div>
            </div>
          </div>

          <div class="dropdown topbar-item">
            <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="d-flex align-items-center">
                <img class="rounded-circle topbar-avatar" width="32" src="<?= e($photo) ?>" alt="avatar">
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
              <h6 class="dropdown-header">Welcome <?= e($username) ?>!</h6>
              <div class="dropdown-divider my-1"></div>
              <a class="dropdown-item" href="<?= e(url('profile')) ?>">
                <iconify-icon icon="solar:user-rounded-broken" class="align-middle me-2 fs-18"></iconify-icon>
                <span class="align-middle">Profile</span>
              </a>
              <a class="dropdown-item" href="<?= e(url('notifications')) ?>">
                <iconify-icon icon="solar:bell-bing-broken" class="align-middle me-2 fs-18"></iconify-icon>
                <span class="align-middle">Notifications</span>
                <span id="notificationsBadgeMenu" class="badge bg-primary-subtle text-primary ms-2 <?= $unreadNotifications > 0 ? '' : 'd-none' ?>">
                  <?= e((string)$unreadNotifications) ?>
                </span>
              </a>
              <a class="dropdown-item text-danger" href="<?= e(url('logout')) ?>">
                <iconify-icon icon="solar:logout-3-broken" class="align-middle me-2 fs-18"></iconify-icon>
                <span class="align-middle">Logout</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
