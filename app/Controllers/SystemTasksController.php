<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DB;
use App\Models\Notification;
use App\Models\User;

final class SystemTasksController extends BaseController {
  public function runNotifications(): void {
    try {
      $this->requireCronToken();
      header('Content-Type: application/json; charset=utf-8');

      $idleDays = $this->getIntEnv('NOTIFY_IDLE_DAYS', 3);
      $dueSoonHours = $this->getIntEnv('NOTIFY_DUE_SOON_HOURS', 24);
      $overdueGraceHours = $this->getIntEnv('NOTIFY_OVERDUE_GRACE_HOURS', 4);
      $missedHours = $this->getIntEnv('NOTIFY_MISSED_HOURS', 24);
      $inactiveDays = $this->getIntEnv('NOTIFY_AGENT_INACTIVE_DAYS', 7);
      $weeklyDigestDay = $this->getIntEnv('NOTIFY_WEEKLY_DIGEST_DAY', 1);
      $weeklyClosedThresholds = $this->getThresholdsEnv('NOTIFY_WEEKLY_CLOSED_THRESHOLDS', []);
      if (!$weeklyClosedThresholds) {
        $weeklyClosedThresholds = $this->getThresholdsEnv('NOTIFY_DAILY_CLOSED_THRESHOLDS', [5, 10, 20]);
      }
      $purgeDays = $this->getIntEnv('NOTIFY_PURGE_READ_DAYS', 30);

      $counts = [
        'idle' => 0,
        'due_soon' => 0,
        'overdue' => 0,
        'overdue_escalated' => 0,
        'missed' => 0,
        'inactive_agents' => 0,
        'weekly_milestones' => 0,
        'weekly_summary' => 0,
        'purged' => 0,
      ];

      $this->notifyIdleLeads($idleDays, $counts);
      $this->notifyFollowups($dueSoonHours, $overdueGraceHours, $missedHours, $counts);
      $this->notifyInactiveAgents($inactiveDays, $counts);
      if ($this->isWeeklyDigestDay($weeklyDigestDay)) {
        $this->notifyWeeklyClosedMilestones($weeklyClosedThresholds, $counts);
        $this->notifyWeeklySummary($idleDays, $counts);
      }
      $this->purgeReadNotifications($purgeDays, $counts);

      echo json_encode(['ok' => true, 'counts' => $counts]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  private function notifyIdleLeads(int $idleDays, array &$counts): void {
    if ($idleDays <= 0) return;
    $pdo = DB::conn();
    $sql = "SELECT l.id, l.lead_name, l.assigned_agent_user_id,
      COALESCE(MAX(f.contact_datetime), l.created_at) AS last_activity
      FROM leads l
      LEFT JOIN lead_followups f ON f.lead_id=l.id
      WHERE l.assigned_agent_user_id IS NOT NULL
        AND l.assigned_agent_user_id > 0
        AND l.status_overall <> 'CLOSED'
      GROUP BY l.id
      HAVING last_activity <= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $st = $pdo->prepare($sql);
    $st->execute([$idleDays]);
    $rows = $st->fetchAll();
    if (!$rows) return;

    $admins = User::userIdsByRoles(['ADMIN']);
    foreach ($rows as $row) {
      $leadId = (int)$row['id'];
      $lastActivity = (string)$row['last_activity'];
      $dedupKey = 'lead_idle:' . $leadId . ':' . str_replace([' ', ':', '-'], '', $lastActivity);
      $title = 'Lead idle for ' . $idleDays . ' day(s)';
      $body = 'Lead "' . ($row['lead_name'] ?? 'Unknown') . '" has no activity since ' . $lastActivity . '.';

      Notification::createMany(
        $admins,
        'lead_idle',
        $title,
        $body,
        'admin/lead?id=' . $leadId,
        ['lead_id' => $leadId, 'last_activity' => $lastActivity, 'idle_days' => $idleDays],
        $dedupKey
      );
      $counts['idle']++;
    }
  }

  private function notifyFollowups(int $dueSoonHours, int $overdueGraceHours, int $missedHours, array &$counts): void {
    $pdo = DB::conn();
    $sql = "SELECT f.lead_id, f.agent_user_id, f.next_followup_at, l.lead_name
      FROM lead_followups f
      JOIN (
        SELECT lead_id, agent_user_id, MAX(attempt_no) AS max_attempt
        FROM lead_followups
        GROUP BY lead_id, agent_user_id
      ) latest
        ON latest.lead_id=f.lead_id AND latest.agent_user_id=f.agent_user_id AND latest.max_attempt=f.attempt_no
      JOIN leads l ON l.id=f.lead_id
      WHERE f.next_followup_at IS NOT NULL
        AND l.status_overall <> 'CLOSED'";
    $st = $pdo->prepare($sql);
    $st->execute();
    $rows = $st->fetchAll();
    if (!$rows) return;

    $admins = User::userIdsByRoles(['ADMIN']);
    $now = new \DateTimeImmutable('now');
    foreach ($rows as $row) {
      $leadId = (int)$row['lead_id'];
      $agentId = (int)$row['agent_user_id'];
      $nextAtRaw = (string)$row['next_followup_at'];
      if ($nextAtRaw === '') continue;
      $nextAt = new \DateTimeImmutable($nextAtRaw);
      $diffSeconds = $nextAt->getTimestamp() - $now->getTimestamp();
      $leadName = $row['lead_name'] ?? 'Unknown';

      if ($diffSeconds > 0) {
        if ($dueSoonHours > 0 && $diffSeconds <= ($dueSoonHours * 3600)) {
          $dedupKey = 'followup_due:' . $leadId . ':' . $nextAtRaw;
          Notification::create(
            $agentId,
            'followup_due_soon',
            'Follow-up due soon',
            'Follow-up for "' . $leadName . '" is due at ' . $nextAtRaw . '.',
            'agent/lead?id=' . $leadId,
            ['lead_id' => $leadId, 'next_followup_at' => $nextAtRaw],
            $dedupKey
          );
          $counts['due_soon']++;
        }
        continue;
      }

      $overdueSeconds = abs($diffSeconds);
      if ($missedHours > 0 && $overdueSeconds >= ($missedHours * 3600)) {
        $dedupKey = 'followup_missed:' . $leadId . ':' . $nextAtRaw;
        Notification::create(
          $agentId,
          'followup_missed',
          'Follow-up missed',
          'Follow-up for "' . $leadName . '" was missed (due ' . $nextAtRaw . ').',
          'agent/lead?id=' . $leadId,
          ['lead_id' => $leadId, 'next_followup_at' => $nextAtRaw],
          $dedupKey
        );
        Notification::createMany(
          $admins,
          'followup_missed',
          'Follow-up missed',
          'Follow-up for "' . $leadName . '" was missed (due ' . $nextAtRaw . ').',
          'admin/lead?id=' . $leadId,
          ['lead_id' => $leadId, 'next_followup_at' => $nextAtRaw],
          $dedupKey
        );
        $counts['missed']++;
        continue;
      }

      $dedupKey = 'followup_overdue:' . $leadId . ':' . $nextAtRaw;
      Notification::create(
        $agentId,
        'followup_overdue',
        'Follow-up overdue',
        'Follow-up for "' . $leadName . '" is overdue (due ' . $nextAtRaw . ').',
        'agent/lead?id=' . $leadId,
        ['lead_id' => $leadId, 'next_followup_at' => $nextAtRaw],
        $dedupKey
      );
      $counts['overdue']++;

      if ($overdueGraceHours > 0 && $overdueSeconds >= ($overdueGraceHours * 3600)) {
        $escKey = 'followup_overdue_escalated:' . $leadId . ':' . $nextAtRaw;
        Notification::createMany(
          $admins,
          'followup_overdue_escalated',
          'Follow-up overdue (escalated)',
          'Follow-up for "' . $leadName . '" is overdue (due ' . $nextAtRaw . ').',
          'admin/lead?id=' . $leadId,
          ['lead_id' => $leadId, 'next_followup_at' => $nextAtRaw],
          $escKey
        );
        $counts['overdue_escalated']++;
      }
    }
  }

  private function notifyInactiveAgents(int $inactiveDays, array &$counts): void {
    if ($inactiveDays <= 0) return;
    $pdo = DB::conn();
    $sql = "SELECT u.id, COALESCE(e.employee_name, u.username) as agent_name,
      COALESCE(MAX(f.contact_datetime), u.created_at) AS last_activity
      FROM users u
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      LEFT JOIN lead_followups f ON f.agent_user_id=u.id
      WHERE u.role='AGENT' AND u.is_active=1
      GROUP BY u.id, agent_name
      HAVING last_activity <= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $st = $pdo->prepare($sql);
    $st->execute([$inactiveDays]);
    $rows = $st->fetchAll();
    if (!$rows) return;

    $admins = User::userIdsByRoles(['ADMIN']);
    foreach ($rows as $row) {
      $agentId = (int)$row['id'];
      $agentName = (string)($row['agent_name'] ?? 'Agent');
      $lastActivity = (string)$row['last_activity'];
      $dedupKey = 'agent_inactive:' . $agentId . ':' . str_replace([' ', ':', '-'], '', $lastActivity);
      $title = 'Agent inactive for ' . $inactiveDays . ' day(s)';
      $body = 'Agent "' . $agentName . '" has no activity since ' . $lastActivity . '.';

      Notification::createMany(
        $admins,
        'agent_inactive',
        $title,
        $body,
        'admin/agent?id=' . $agentId,
        ['agent_id' => $agentId, 'last_activity' => $lastActivity, 'inactive_days' => $inactiveDays],
        $dedupKey
      );
      $counts['inactive_agents']++;
    }
  }

  private function notifyWeeklyClosedMilestones(array $thresholds, array &$counts): void {
    if (!$thresholds) return;
    $pdo = DB::conn();
    [$start, $end] = $this->weeklyWindow();
    $startStr = $start->format('Y-m-d H:i:s');
    $endStr = $end->format('Y-m-d H:i:s');
    $weekKey = $start->format('o-W');
    $sql = "SELECT l.assigned_agent_user_id AS agent_id, COUNT(*) AS closed_count
      FROM leads l
      JOIN (
        SELECT f.lead_id, f.agent_user_id, MAX(f.created_at) AS last_followup_at
        FROM lead_followups f
        GROUP BY f.lead_id, f.agent_user_id
      ) lastf ON lastf.lead_id=l.id AND lastf.agent_user_id=l.assigned_agent_user_id
      WHERE l.status_overall='CLOSED'
        AND l.assigned_agent_user_id > 0
        AND lastf.last_followup_at >= :start
        AND lastf.last_followup_at < :end
      GROUP BY l.assigned_agent_user_id";
    $st = $pdo->prepare($sql);
    $st->execute([':start' => $startStr, ':end' => $endStr]);
    $rows = $st->fetchAll();
    if (!$rows) return;

    foreach ($rows as $row) {
      $agentId = (int)$row['agent_id'];
      $count = (int)$row['closed_count'];
      foreach ($thresholds as $threshold) {
        if ($count < $threshold) continue;
        $dedupKey = 'weekly_closed:' . $agentId . ':' . $weekKey . ':' . $threshold;
        Notification::create(
          $agentId,
          'weekly_closed_milestone',
          'Weekly KPI milestone',
          'You closed ' . $count . ' lead(s) this week. Milestone: ' . $threshold . '.',
          'agent/leads',
          ['count' => $count, 'threshold' => $threshold, 'week' => $weekKey],
          $dedupKey
        );
        $counts['weekly_milestones']++;
      }
    }
  }

  private function notifyWeeklySummary(int $idleDays, array &$counts): void {
    $ceos = User::userIdsByRoles(['CEO']);
    if (!$ceos) return;

    [$start, $end] = $this->weeklyWindow();
    $startStr = $start->format('Y-m-d H:i:s');
    $endStr = $end->format('Y-m-d H:i:s');
    $weekKey = $start->format('o-W');

    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE created_at >= :start AND created_at < :end");
    $st->execute([':start' => $startStr, ':end' => $endStr]);
    $created = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM lead_followups WHERE created_at >= :start AND created_at < :end");
    $st->execute([':start' => $startStr, ':end' => $endStr]);
    $followups = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM leads l
      JOIN (
        SELECT f.lead_id, f.agent_user_id, MAX(f.created_at) AS last_followup_at
        FROM lead_followups f
        GROUP BY f.lead_id, f.agent_user_id
      ) lastf ON lastf.lead_id=l.id AND lastf.agent_user_id=l.assigned_agent_user_id
      WHERE l.status_overall='CLOSED'
        AND lastf.last_followup_at >= :start
        AND lastf.last_followup_at < :end");
    $st->execute([':start' => $startStr, ':end' => $endStr]);
    $wins = (int)$st->fetchColumn();

    $idleLeads = 0;
    if ($idleDays > 0) {
      $st = $pdo->prepare("SELECT COUNT(*) FROM (
        SELECT l.id, COALESCE(MAX(f.contact_datetime), l.created_at) AS last_activity
        FROM leads l
        LEFT JOIN lead_followups f ON f.lead_id=l.id
        WHERE l.assigned_agent_user_id IS NOT NULL
          AND l.assigned_agent_user_id > 0
          AND l.status_overall <> 'CLOSED'
        GROUP BY l.id
        HAVING last_activity <= DATE_SUB(NOW(), INTERVAL ? DAY)
      ) t");
      $st->execute([$idleDays]);
      $idleLeads = (int)$st->fetchColumn();
    }

    $title = 'Weekly summary';
    $rangeText = $start->format('M d') . ' - ' . $end->format('M d');
    $body = 'Week (' . $rangeText . '): ' . $created . ' new leads, ' . $followups . ' follow-ups, ' . $wins . ' wins, ' . $idleLeads . ' idle leads.';
    $dedupKey = 'weekly_summary:' . $weekKey;

    Notification::createMany(
      $ceos,
      'weekly_summary',
      $title,
      $body,
      'ceo/dashboard',
      ['start' => $startStr, 'end' => $endStr, 'wins' => $wins, 'idle_leads' => $idleLeads],
      $dedupKey
    );
    $counts['weekly_summary']++;
  }

  private function purgeReadNotifications(int $days, array &$counts): void {
    if ($days <= 0) return;
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM notifications WHERE read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $st->execute([$days]);
    $counts['purged'] = $st->rowCount();
  }

  private function requireCronToken(): void {
    if (php_sapi_name() === 'cli') return;
    $expected = getenv('CRON_TOKEN') ?: \app_key();
    $token = (string)($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $token)) {
      http_response_code(403);
      echo 'Forbidden';
      exit;
    }
  }

  private function getIntEnv(string $key, int $default): int {
    $val = getenv($key);
    if ($val === false || $val === '') return $default;
    $num = (int)$val;
    return $num > 0 ? $num : $default;
  }

  private function getThresholdsEnv(string $key, array $default): array {
    $raw = getenv($key);
    if ($raw === false || trim($raw) === '') return $default;
    $parts = array_map('trim', explode(',', $raw));
    $nums = [];
    foreach ($parts as $part) {
      $val = (int)$part;
      if ($val > 0) $nums[] = $val;
    }
    $nums = array_values(array_unique($nums));
    sort($nums);
    return $nums;
  }

  private function weeklyWindow(): array {
    $end = new \DateTimeImmutable('now');
    $start = $end->modify('-7 days');
    return [$start, $end];
  }

  private function isWeeklyDigestDay(int $weekday): bool {
    if ($weekday >= 1 && $weekday <= 7) {
      $weekday = $weekday % 7;
    }
    if ($weekday < 0 || $weekday > 6) $weekday = 1;
    $today = (int)(new \DateTimeImmutable('now'))->format('w');
    return $today === $weekday;
  }
}
