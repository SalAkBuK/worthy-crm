<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class Lead {
  public static function createBulk(array $rows, int $assignedBy, array &$rowErrors): bool {
    $pdo = DB::conn();
    $pdo->beginTransaction();
    try {
      $ins = $pdo->prepare("INSERT INTO leads
        (lead_name, contact_email, contact_phone, interested_in_property, property_type, property_interest_types, area, budget_aed_min, budget_aed_max, budget_aed_range, lead_status, assigned_agent_user_id, created_by_user_id, created_at, status_overall)
        VALUES (:n,:e,:ph,:p,:t,:pit,:area,:bmin,:bmax,:brange,:ls,:a,:c,NOW(),'NEW')");
      foreach ($rows as $i => $r) {
        $email = strtolower(trim((string)($r['contact_email'] ?? '')));
        $name = trim((string)($r['lead_name'] ?? ''));
        $phone = trim((string)($r['contact_phone'] ?? ''));
        $prop = trim((string)($r['interested_in_property'] ?? ''));
        $type = (string)($r['property_type'] ?? '');
        $interestTypes = trim((string)($r['property_interest_types'] ?? ''));
        $interestTypes = $interestTypes !== '' ? $interestTypes : null;
        $area = trim((string)($r['area'] ?? ''));
        $area = $area !== '' ? $area : null;
        $budgetMin = $r['budget_aed_min'] ?? null;
        $budgetMin = $budgetMin !== '' ? $budgetMin : null;
        $budgetMax = $r['budget_aed_max'] ?? null;
        $budgetMax = $budgetMax !== '' ? $budgetMax : null;
        $budgetRange = trim((string)($r['budget_aed_range'] ?? ''));
        $budgetRange = $budgetRange !== '' ? $budgetRange : null;
        $leadStatus = trim((string)($r['lead_status'] ?? ''));
        $leadStatus = $leadStatus !== '' ? $leadStatus : null;
        $agentId = (int)($r['assigned_agent_user_id'] ?? 0);
        $allowUnassigned = (bool)($r['allow_unassigned'] ?? false);
        $allowMissingType = (bool)($r['allow_missing_type'] ?? false);
        $allowMissingName = (bool)($r['allow_missing_name'] ?? false);
        $allowMissingPhone = (bool)($r['allow_missing_phone'] ?? false);
        $allowMissingEmail = (bool)($r['allow_missing_email'] ?? false);

        $errs = [];
        if ($name === '' && !$allowMissingName) $errs[] = 'Name required';
        if ($email === '') {
          if (!$allowMissingEmail) $errs[] = 'Valid email required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $errs[] = 'Valid email required';
        }
        if ($phone === '') {
          if (!$allowMissingPhone) {
          $errs[] = 'Phone number required';
          }
        } elseif (!preg_match('/^[0-9 +().-]{6,20}$/', $phone)) {
          $errs[] = 'Phone number must be 6-20 chars and digits/+()-.';
        }
        if ($type === '' || $type === null) {
          if ($allowMissingType) {
            $type = 'NONE';
          } else {
            $type = null;
          }
        } elseif (!in_array($type, ['OFF_PLAN','READY_TO_MOVE','NONE'], true)) {
          $errs[] = 'Property type must be Off Plan or Ready To Move';
        }
        if ($agentId <= 0 && !$allowUnassigned) $errs[] = 'Agent required';
        if ($errs) { $rowErrors[$i] = $errs; continue; }

        $ins->execute([
          ':n'=>$name,
          ':e'=>$email,
          ':ph'=>$phone,
          ':p'=>$prop,
          ':t'=>$type,
          ':pit'=>$interestTypes,
          ':area'=>$area,
          ':bmin'=>$budgetMin,
          ':bmax'=>$budgetMax,
          ':brange'=>$budgetRange,
          ':ls'=>$leadStatus,
          ':a'=>$agentId > 0 ? $agentId : null,
          ':c'=>$assignedBy,
        ]);
      }

      if ($rowErrors) {
        $pdo->rollBack();
        return false;
      }
      $pdo->commit();
      return true;
    } catch (\Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  public static function searchAdmin(array $filters, int $page, int $perPage): array {
    $pdo = DB::conn();
    $where = [];
    $params = [];

    $where[] = "l.is_active = 1";
    if (!empty($filters['q'])) {
      $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q)";
      $params[':q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['agent'])) {
      $where[] = "l.assigned_agent_user_id = :agent";
      $params[':agent'] = (int)$filters['agent'];
    }
    if (!empty($filters['assigned_only'])) {
      $where[] = "l.assigned_agent_user_id > 0";
    }
    if (!empty($filters['type'])) {
      $where[] = "l.property_type = :type";
      $params[':type'] = $filters['type'];
    }
    if (!empty($filters['from'])) {
      $where[] = "DATE(l.created_at) >= :from";
      $params[':from'] = $filters['from'];
    }
    if (!empty($filters['to'])) {
      $where[] = "DATE(l.created_at) <= :to";
      $params[':to'] = $filters['to'];
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $allowedSort = ['created_at','lead_name','contact_email','property_type','agent_name','status_overall'];
    $sort = in_array(($filters['sort'] ?? 'created_at'), $allowedSort, true) ? $filters['sort'] : 'created_at';
    $dir = (($filters['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

    $order = "l.created_at DESC";
    if ($sort === 'agent_name') $order = "agent_name $dir";
    else $order = "l.$sort $dir";

    $count = $pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $meta = \paginate_meta($total, $page, $perPage);

    $sql = "SELECT l.*, u.username as agent_username, COALESCE(e.employee_name, u.username) as agent_name,
      (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id) as followup_count
      FROM leads l
      LEFT JOIN users u ON u.id = l.assigned_agent_user_id
      LEFT JOIN employees e ON e.employee_code=u.employee_code
      $whereSql
      ORDER BY $order
      LIMIT :limit OFFSET :offset";

    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    return ['items'=>$st->fetchAll(), 'meta'=>$meta];
  }

  public static function searchAgent(int $agentId, array $filters, int $page, int $perPage): array {
    $pdo = DB::conn();
    $where = ["l.assigned_agent_user_id = :agent", "l.is_active = 1"];
    $params = [':agent'=>$agentId];

    if (!empty($filters['q'])) {
      $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q)";
      $params[':q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['type'])) {
      $where[] = "l.property_type = :type";
      $params[':type'] = $filters['type'];
    }
    if (!empty($filters['status'])) {
      $where[] = "l.status_overall = :status";
      $params[':status'] = $filters['status'];
    }
    if (!empty($filters['from'])) {
      $where[] = "DATE(l.created_at) >= :from";
      $params[':from'] = $filters['from'];
    }
    if (!empty($filters['to'])) {
      $where[] = "DATE(l.created_at) <= :to";
      $params[':to'] = $filters['to'];
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $allowedSort = ['created_at','lead_name','status_overall','property_type'];
    $sort = in_array(($filters['sort'] ?? 'created_at'), $allowedSort, true) ? $filters['sort'] : 'created_at';
    $dir = (($filters['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

    $count = $pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $meta = \paginate_meta($total, $page, $perPage);

    $sql = "SELECT l.*, 
      (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id) as followup_count,
      (SELECT MAX(f.contact_datetime) FROM lead_followups f WHERE f.lead_id=l.id) as last_contact_at
      FROM leads l
      $whereSql
      ORDER BY l.$sort $dir
      LIMIT :limit OFFSET :offset";
    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    return ['items'=>$st->fetchAll(), 'meta'=>$meta];
  }

  public static function assignBulk(array $ids, int $agentId): int {
    $pdo = DB::conn();
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (!$ids) return 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("UPDATE leads SET assigned_agent_user_id=? WHERE id IN ($placeholders)");
    $params = array_merge([$agentId], $ids);
    $st->execute($params);
    return $st->rowCount();
  }

  public static function assignmentsForIds(array $ids): array {
    $pdo = DB::conn();
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, assigned_agent_user_id FROM leads WHERE id IN ($placeholders)");
    $st->execute($ids);
    $rows = $st->fetchAll();
    $map = [];
    foreach ($rows as $row) {
      $map[(int)$row['id']] = $row['assigned_agent_user_id'] !== null ? (int)$row['assigned_agent_user_id'] : 0;
    }
    return $map;
  }

  public static function reassignByAgent(int $fromAgentId, int $toAgentId): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE leads SET assigned_agent_user_id=:to, is_active=1 WHERE assigned_agent_user_id=:from");
    $st->execute([':to' => $toAgentId, ':from' => $fromAgentId]);
    return $st->rowCount();
  }

  public static function setActiveByAgent(int $agentId, bool $active): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE leads SET is_active=:active WHERE assigned_agent_user_id=:id");
    $st->execute([':active' => $active ? 1 : 0, ':id' => $agentId]);
    return $st->rowCount();
  }

  public static function setActiveByAgents(array $agentIds, bool $active): int {
    $pdo = DB::conn();
    $agentIds = array_values(array_filter(array_map('intval', $agentIds), fn($v) => $v > 0));
    if (!$agentIds) return 0;
    $placeholders = implode(',', array_fill(0, count($agentIds), '?'));
    $st = $pdo->prepare("UPDATE leads SET is_active=? WHERE assigned_agent_user_id IN ($placeholders)");
    $params = array_merge([$active ? 1 : 0], $agentIds);
    $st->execute($params);
    return $st->rowCount();
  }

  public static function deleteByAgent(int $agentId): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM leads WHERE assigned_agent_user_id=:id");
    $st->execute([':id' => $agentId]);
    return $st->rowCount();
  }

  public static function delete(int $id): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM leads WHERE id=:id");
    $st->execute([':id' => $id]);
    return $st->rowCount();
  }

  public static function findWithAgent(int $id): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT l.*, u.username as agent_username, COALESCE(e.employee_name, u.username) as agent_name
      FROM leads l
      LEFT JOIN users u ON u.id=l.assigned_agent_user_id
      LEFT JOIN employees e ON e.employee_code=u.employee_code
      WHERE l.id=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function updateStatusByLeadId(int $leadId): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT attempt_no, call_status, interested_status FROM lead_followups WHERE lead_id=:id ORDER BY attempt_no DESC LIMIT 1");
    $st->execute([':id'=>$leadId]);
    $last = $st->fetch();

    $status = 'NEW';
    if ($last) {
      $attempt = (int)$last['attempt_no'];
      $call = (string)$last['call_status'];
      $interested = (string)($last['interested_status'] ?? '');
      if ($call === 'NO_RESPONSE' || $call === 'ASK_CONTACT_LATER') {
        $status = ($attempt >= 3) ? 'CLOSED' : 'IN_PROGRESS';
      } elseif ($call === 'RESPONDED' && $interested === '50/50') {
        $status = '50/50';
      } elseif ($call === 'RESPONDED' && $interested === 'FUTURE_INTEREST') {
        $status = 'ON_HOLD';
      } else {
        $status = 'CLOSED';
      }
    }
    $up = $pdo->prepare("UPDATE leads SET status_overall=:s WHERE id=:id");
    $up->execute([':s'=>$status, ':id'=>$leadId]);
  }

  public static function setStatus(int $leadId, string $status): void {
    $pdo = DB::conn();
    $up = $pdo->prepare("UPDATE leads SET status_overall=:s WHERE id=:id");
    $up->execute([':s'=>$status, ':id'=>$leadId]);
  }

  public static function updateNextFollowup(int $leadId, ?string $nextAt, ?string $note, int $userId): void {
    $pdo = DB::conn();
    $nextAt = $nextAt !== null && trim($nextAt) !== '' ? $nextAt : null;
    $note = $note !== null ? trim($note) : null;
    if ($note === '') $note = null;
    if ($note !== null && mb_strlen($note) > 255) {
      $note = mb_substr($note, 0, 255);
    }
    $status = $nextAt ? 'scheduled' : 'cleared';

    $st = $pdo->prepare("UPDATE leads
      SET next_followup_at=:next_at,
          next_followup_note=:note,
          next_followup_set_by_user_id=:user_id,
          next_followup_status=:status
      WHERE id=:id");
    $st->execute([
      ':next_at' => $nextAt,
      ':note' => $note,
      ':user_id' => $userId,
      ':status' => $status,
      ':id' => $leadId,
    ]);
  }

  public static function followupInbox(string $tab, array $filters, int $page, int $perPage): array {
    $pdo = DB::conn();
    $tzName = getenv('FOLLOWUP_TZ') ?: (getenv('NOTIFY_DAILY_SUMMARY_TZ') ?: 'Asia/Karachi');
    try {
      $tz = new \DateTimeZone($tzName);
    } catch (\Throwable $e) {
      $tz = new \DateTimeZone('UTC');
    }
    $now = new \DateTimeImmutable('now', $tz);
    $todayStart = $now->setTime(0, 0, 0);
    $tomorrowStart = $todayStart->modify('+1 day');
    $defaultHours = (int)(getenv('FOLLOWUP_DUE_SOON_HOURS') ?: 48);
    $dueSoonHours = (int)($filters['due_soon_hours'] ?? $defaultHours);
    if ($dueSoonHours <= 0) $dueSoonHours = $defaultHours;
    $dueSoonEnd = $now->modify('+' . $dueSoonHours . ' hours');

    $params = [];
    $where = ["l.is_active = 1", "l.next_followup_at IS NOT NULL", "l.assigned_agent_user_id > 0"];

    if (!empty($filters['agent_id'])) {
      $where[] = "l.assigned_agent_user_id = :agent";
      $params[':agent'] = (int)$filters['agent_id'];
    }
    if (!empty($filters['q'])) {
      $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q OR l.contact_phone LIKE :q)";
      $params[':q'] = '%' . $filters['q'] . '%';
    }

    $tabWhere = [];
    if ($tab === 'due_today') {
      $tabWhere[] = "l.next_followup_at >= :today_start AND l.next_followup_at < :tomorrow_start";
      $params[':today_start'] = $todayStart->format('Y-m-d H:i:s');
      $params[':tomorrow_start'] = $tomorrowStart->format('Y-m-d H:i:s');
    } elseif ($tab === 'due_soon') {
      $tabWhere[] = "l.next_followup_at >= :due_soon_start AND l.next_followup_at <= :due_soon_end";
      $params[':due_soon_start'] = $tomorrowStart->format('Y-m-d H:i:s');
      $params[':due_soon_end'] = $dueSoonEnd->format('Y-m-d H:i:s');
    } else {
      $tabWhere[] = "l.next_followup_at >= :tomorrow_start";
      $params[':tomorrow_start'] = $tomorrowStart->format('Y-m-d H:i:s');
    }

    $whereSql = 'WHERE ' . implode(' AND ', array_merge($where, $tabWhere));
    $baseFrom = "FROM leads l JOIN users u ON u.id = l.assigned_agent_user_id AND u.is_active = 1";

    $count = $pdo->prepare("SELECT COUNT(*) $baseFrom $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $meta = \paginate_meta($total, $page, $perPage);

    $sql = "SELECT l.*, u.username as agent_username, COALESCE(e.employee_name, u.username) as agent_name
      $baseFrom
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      $whereSql
      ORDER BY l.next_followup_at ASC
      LIMIT :limit OFFSET :offset";
    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    $items = $st->fetchAll();
    foreach ($items as &$item) {
      $raw = (string)($item['next_followup_at'] ?? '');
      if ($raw !== '') {
        try {
          $dt = new \DateTimeImmutable($raw, $tz);
          $item['next_followup_at_display'] = $dt->format('M d, H:i');
        } catch (\Throwable $e) {
          $item['next_followup_at_display'] = $raw;
        }
      } else {
        $item['next_followup_at_display'] = null;
      }
    }
    unset($item);

    $baseWhereSql = 'WHERE ' . implode(' AND ', $where);
    $baseParams = $params;
    unset($baseParams[':today_start'], $baseParams[':tomorrow_start'], $baseParams[':due_soon_start'], $baseParams[':due_soon_end']);

    $counts = [
      'due_today' => 0,
      'due_soon' => 0,
      'scheduled' => 0,
    ];

    $countDueToday = $pdo->prepare("SELECT COUNT(*) $baseFrom
      $baseWhereSql AND l.next_followup_at >= :today_start AND l.next_followup_at < :tomorrow_start");
    $countDueToday->execute($baseParams + [
      ':today_start' => $todayStart->format('Y-m-d H:i:s'),
      ':tomorrow_start' => $tomorrowStart->format('Y-m-d H:i:s'),
    ]);
    $counts['due_today'] = (int)$countDueToday->fetchColumn();

    $countDueSoon = $pdo->prepare("SELECT COUNT(*) $baseFrom
      $baseWhereSql AND l.next_followup_at >= :due_soon_start AND l.next_followup_at <= :due_soon_end");
    $countDueSoon->execute($baseParams + [
      ':due_soon_start' => $tomorrowStart->format('Y-m-d H:i:s'),
      ':due_soon_end' => $dueSoonEnd->format('Y-m-d H:i:s'),
    ]);
    $counts['due_soon'] = (int)$countDueSoon->fetchColumn();

    $countScheduled = $pdo->prepare("SELECT COUNT(*) $baseFrom
      $baseWhereSql AND l.next_followup_at >= :tomorrow_start");
    $countScheduled->execute($baseParams + [
      ':tomorrow_start' => $tomorrowStart->format('Y-m-d H:i:s'),
    ]);
    $counts['scheduled'] = (int)$countScheduled->fetchColumn();

    $counts['total_inbox'] = $counts['due_today'] + $counts['due_soon'];

    return [
      'items' => $items,
      'meta' => $meta,
      'counts' => $counts,
      'due_soon_hours' => $dueSoonHours,
      'timezone' => $tz->getName(),
    ];
  }
}
