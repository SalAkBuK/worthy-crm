<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';

use App\Helpers\DB;
use App\Controllers\SystemTasksController;
use App\Models\Lead;

function fail(string $message): void {
  fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
  exit(1);
}

$pdo = DB::conn();
$pdo->beginTransaction();
try {
  $agent = $pdo->query("SELECT id FROM users WHERE role='AGENT' AND is_active=1 ORDER BY id ASC LIMIT 1")->fetch();
  if (!$agent) fail('No active agent found.');
  $agentId = (int)$agent['id'];

  $leadName = 'Smoke Test Lead ' . bin2hex(random_bytes(3));
  $st = $pdo->prepare("INSERT INTO leads
    (lead_name, contact_email, contact_phone, interested_in_property, property_type, assigned_agent_user_id, created_by_user_id, created_at, status_overall)
    VALUES (:name,:email,:phone,:interest,:ptype,:agent,:creator,NOW(),'NEW')");
  $st->execute([
    ':name' => $leadName,
    ':email' => 'smoke@example.test',
    ':phone' => '0500000000',
    ':interest' => 'Test followup scheduling',
    ':ptype' => 'OFF_PLAN',
    ':agent' => $agentId,
    ':creator' => $agentId,
  ]);
  $leadId = (int)$pdo->lastInsertId();

  $nextAt = (new DateTimeImmutable('now'))->modify('+2 hours')->format('Y-m-d H:i:s');
  Lead::updateNextFollowup($leadId, $nextAt, 'Smoke test note', $agentId);

  $result = Lead::followupInbox('due_today', ['agent_id' => $agentId], 1, 50);
  $found = false;
  foreach ($result['items'] as $item) {
    if ((int)$item['id'] === $leadId) { $found = true; break; }
  }
  if (!$found) fail('Lead not found in Due Today inbox.');

  Lead::updateNextFollowup($leadId, null, null, $agentId);
  $resultAfter = Lead::followupInbox('due_today', ['agent_id' => $agentId], 1, 50);
  foreach ($resultAfter['items'] as $item) {
    if ((int)$item['id'] === $leadId) fail('Lead still present in Due Today after clearing next follow-up.');
  }

  $tz = 'UTC';
  $now = new DateTimeImmutable('now', new DateTimeZone($tz));
  putenv('NOTIFY_DAILY_SUMMARY_TZ=' . $tz);
  putenv('NOTIFY_DAILY_SUMMARY_HOUR=' . $now->format('G'));
  putenv('NOTIFY_WEEKLY_DIGEST_DAY=' . (($now->format('w') + 1) % 7 + 1));

  $controller = new SystemTasksController();
  ob_start();
  $controller->runNotifications();
  $controller->runNotifications();
  ob_end_clean();

  $dateKey = $now->setTime(0, 0, 0)->format('Y-m-d');
  $dedupKey = 'daily_followup_summary:' . $agentId . ':' . $dateKey;
  $st = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=:u AND type='daily_followup_summary' AND dedup_key=:k");
  $st->execute([':u' => $agentId, ':k' => $dedupKey]);
  $count = (int)$st->fetchColumn();
  if ($count !== 1) fail('Daily summary not idempotent (count=' . $count . ').');

  $pdo->rollBack();
  echo "OK: Smoke tests passed.\n";
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  fail($e->getMessage());
}
