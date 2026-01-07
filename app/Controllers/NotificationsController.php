<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Models\Notification;

final class NotificationsController extends BaseController {
  public function index(): void {
    try {
      \require_login();
      $user = current_user();
      $userId = (int)($user['id'] ?? 0);
      $types = notification_types_for_role($user['role'] ?? '');
      $items = Notification::listForUser($userId, 50, $types);
      $unread = Notification::unreadCount($userId, $types);
      View::render('notifications/index', [
        'title' => 'Notifications',
        'items' => $items,
        'unread' => $unread,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function markAllRead(): void {
    try {
      \require_login();
      \verify_csrf();
      $user = current_user();
      $userId = (int)($user['id'] ?? 0);
      Notification::markAllRead($userId);
      flash('success', 'Notifications marked as read.');
      redirect('notifications');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function stream(): void {
    try {
      \require_login();
      $user = current_user();
      $userId = (int)($user['id'] ?? 0);
      session_write_close();

      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache');
      header('X-Accel-Buffering: no');

      @ini_set('output_buffering', 'off');
      @ini_set('zlib.output_compression', '0');
      while (ob_get_level() > 0) { ob_end_flush(); }
      ob_implicit_flush(true);

      $lastId = (int)($_GET['last_id'] ?? 0);
      $types = notification_types_for_role($user['role'] ?? '');
      $lastUnread = Notification::unreadCount($userId, $types);
      $start = time();
      $maxSeconds = 60;

      while ((time() - $start) < $maxSeconds) {
        $latest = Notification::latestAfterId($userId, $lastId, $types);
        $unread = Notification::unreadCount($userId, $types);

        if ($latest || $unread !== $lastUnread) {
          if ($latest) {
            $lastId = (int)$latest['id'];
          }
          $payload = [
            'unread' => $unread,
            'latest' => $latest ? [
              'id' => (int)$latest['id'],
              'type' => (string)($latest['type'] ?? ''),
              'title' => (string)($latest['title'] ?? ''),
              'body' => (string)($latest['body'] ?? ''),
              'link' => (string)($latest['link_url'] ?? ''),
              'created_at' => (string)($latest['created_at'] ?? ''),
            ] : null,
          ];
          echo "event: notification\n";
          echo 'data: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n\n";
          $lastUnread = $unread;
        } else {
          echo "event: ping\n";
          echo "data: {}\n\n";
        }
        flush();
        sleep(5);
      }
    } catch (\Throwable $e) { $this->handleException($e); }
  }
}
