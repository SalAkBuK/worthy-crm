<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\DB;
use App\Models\User;

final class CeoController extends BaseController {

  public function dashboard(): void {
    try {
      \require_role(['CEO']);

      $filters = [
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
      ];

      $pdo = DB::conn();
      $where = [];
      $params = [];
      $where[] = "l.is_active = 1";
      if ($filters['from']) { $where[] = "DATE(l.created_at) >= :from"; $params[':from'] = $filters['from']; }
      if ($filters['to']) { $where[] = "DATE(l.created_at) <= :to"; $params[':to'] = $filters['to']; }
      $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

      // Summary cards
      $st = $pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
      $st->execute($params);
      $totalLeads = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT COUNT(DISTINCT l.id)
        FROM leads l
        JOIN lead_followups f ON f.lead_id = l.id
        $whereSql");
      $st->execute($params);
      $contactedLeads = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT COUNT(*)
        FROM leads l
        WHERE (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id) >= 3
        " . ($whereSql ? "AND " . substr($whereSql, 6) : ""));
      $st->execute($params);
      $completedLeads = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT
        SUM(CASE WHEN f.call_status='RESPONDED' THEN 1 ELSE 0 END) as responded,
        COUNT(*) as total
        FROM lead_followups f
        JOIN leads l ON l.id=f.lead_id
        $whereSql");
      $st->execute($params);
      $rateRow = $st->fetch() ?: ['responded'=>0,'total'=>0];
      $responseRate = ($rateRow['total'] ?? 0) ? round(((int)$rateRow['responded'] / (int)$rateRow['total']) * 100, 1) : 0.0;

      $st = $pdo->prepare("SELECT
        SUM(CASE WHEN f.interested_status='INTERESTED' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN f.interested_status='NOT_INTERESTED' THEN 1 ELSE 0 END) as not_interested
        FROM lead_followups f
        JOIN leads l ON l.id=f.lead_id
        $whereSql");
      $st->execute($params);
      $ratio = $st->fetch() ?: ['interested'=>0,'not_interested'=>0];

      // Summary counts (read-only)
      $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='AGENT'")->fetchColumn();
      $totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='ADMIN'")->fetchColumn();
      $totalFollowups = (int)$pdo->query("SELECT COUNT(*) FROM lead_followups f JOIN leads l ON l.id=f.lead_id AND l.is_active=1")->fetchColumn();

      // Chart data: followups per agent
      $st = $pdo->prepare("SELECT u.id, u.username, COALESCE(e.employee_name, u.username) as name,
        COUNT(f.id) as followups,
        SUM(CASE WHEN f.call_status='RESPONDED' THEN 1 ELSE 0 END) as responded
        FROM users u
        LEFT JOIN employees e ON e.employee_code=u.employee_code
        LEFT JOIN leads l ON l.assigned_agent_user_id=u.id
        LEFT JOIN lead_followups f ON f.lead_id=l.id AND f.agent_user_id=u.id
        WHERE u.role='AGENT'
        " . ($where ? ("AND " . implode(' AND ', array_map(fn($w)=>str_replace('l.','l.',$w), $where))) : "") . "
        GROUP BY u.id, u.username, name
        ORDER BY name ASC");
      $st->execute($params);
      $agents = $st->fetchAll();

      // Leads created per day
      $st = $pdo->prepare("SELECT DATE(l.created_at) as day, COUNT(*) as c
        FROM leads l
        $whereSql
        GROUP BY day
        ORDER BY day ASC");
      $st->execute($params);
      $byDay = $st->fetchAll();

      // Contacted leads per day (by followup date)
      $contactWhere = [];
      $contactParams = [];
      if ($filters['from']) { $contactWhere[] = "DATE(f.contact_datetime) >= :from"; $contactParams[':from'] = $filters['from']; }
      if ($filters['to']) { $contactWhere[] = "DATE(f.contact_datetime) <= :to"; $contactParams[':to'] = $filters['to']; }
      $contactWhereSql = $contactWhere ? ('WHERE ' . implode(' AND ', $contactWhere)) : '';

      $st = $pdo->prepare("SELECT DATE(f.contact_datetime) as day, COUNT(DISTINCT f.lead_id) as c
        FROM lead_followups f
        JOIN leads l ON l.id = f.lead_id AND l.is_active = 1
        $contactWhereSql
        GROUP BY day
        ORDER BY day ASC");
      $st->execute($contactParams);
      $contactedByDay = $st->fetchAll();

      View::render('ceo/dashboard', [
        'title' => 'CEO Dashboard',
        'filters' => $filters,
        'totalLeads' => $totalLeads,
        'contactedLeads' => $contactedLeads,
        'completedLeads' => $completedLeads,
        'responseRate' => $responseRate,
        'ratio' => $ratio,
        'agents' => $agents,
        'byDay' => $byDay,
        'contactedByDay' => $contactedByDay,
        'totalUsers' => $totalUsers,
        'totalAgents' => $totalAgents,
        'totalAdmins' => $totalAdmins,
        'totalFollowups' => $totalFollowups,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function summary(): void {
    try {
      \require_role(['CEO']);
      $pdo = DB::conn();
      $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='AGENT'")->fetchColumn();
      $totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='ADMIN'")->fetchColumn();
      $totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE is_active=1")->fetchColumn();
      $totalFollowups = (int)$pdo->query("SELECT COUNT(*) FROM lead_followups f JOIN leads l ON l.id=f.lead_id AND l.is_active=1")->fetchColumn();
      $users = $pdo->query("SELECT u.*, e.employee_name
        FROM users u
        LEFT JOIN employees e ON e.employee_code = u.employee_code
        WHERE u.role <> 'CEO'
        ORDER BY u.created_at DESC
        LIMIT 200")->fetchAll();
      $leads = $pdo->query("SELECT l.*, COALESCE(e.employee_name, u.username) as agent_name
        FROM leads l
        JOIN users u ON u.id = l.assigned_agent_user_id
        LEFT JOIN employees e ON e.employee_code = u.employee_code
        WHERE l.is_active=1
        ORDER BY l.created_at DESC
        LIMIT 200")->fetchAll();
      $followups = $pdo->query("SELECT f.*, l.lead_name, l.contact_email, COALESCE(e.employee_name, u.username) as agent_name
        FROM lead_followups f
        JOIN leads l ON l.id = f.lead_id
        JOIN users u ON u.id = f.agent_user_id
        LEFT JOIN employees e ON e.employee_code = u.employee_code
        WHERE l.is_active=1
        ORDER BY f.contact_datetime DESC
        LIMIT 200")->fetchAll();

      View::render('ceo/summary', [
        'title' => 'Summary',
        'totalUsers' => $totalUsers,
        'totalAgents' => $totalAgents,
        'totalAdmins' => $totalAdmins,
        'totalLeads' => $totalLeads,
        'totalFollowups' => $totalFollowups,
        'users' => $users,
        'leads' => $leads,
        'followups' => $followups,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentPerformance(): void {
    try {
      \require_role(['CEO']);
      $agentId = (int)($_GET['agent_id'] ?? 0);
      if ($agentId <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $filters = [
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'q' => trim((string)($_GET['q'] ?? '')),
      ];

      $pdo = DB::conn();
      $agent = User::findById($agentId);
      if (!$agent || $agent['role'] !== 'AGENT') { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $where = ["l.assigned_agent_user_id = :agent"];
      $params = [':agent'=>$agentId];
      $where[] = "l.is_active = 1";
      if ($filters['from']) { $where[] = "DATE(l.created_at) >= :from"; $params[':from'] = $filters['from']; }
      if ($filters['to']) { $where[] = "DATE(l.created_at) <= :to"; $params[':to'] = $filters['to']; }
      if ($filters['q']) { $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q)"; $params[':q'] = '%' . $filters['q'] . '%'; }

      $whereSql = 'WHERE ' . implode(' AND ', $where);

      $st = $pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
      $st->execute($params);
      $assigned = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT COUNT(DISTINCT l.id)
        FROM leads l
        JOIN lead_followups f ON f.lead_id=l.id AND f.agent_user_id=:agent2
        $whereSql");
      $st->execute($params + [':agent2' => $agentId]);
      $contacted = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT COUNT(*)
        FROM leads l
        $whereSql
        AND (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:agent2) >= 3");
      $st->execute($params + [':agent2' => $agentId]);
      $completed = (int)$st->fetchColumn();

      $st = $pdo->prepare("SELECT
        SUM(CASE WHEN f.call_status='RESPONDED' THEN 1 ELSE 0 END) as responded,
        COUNT(*) as total
        FROM lead_followups f
        JOIN leads l ON l.id=f.lead_id
        $whereSql AND f.agent_user_id=:agent2");
      $st->execute($params + [':agent2' => $agentId]);
      $rr = $st->fetch() ?: ['responded'=>0,'total'=>0];
      $responseRate = ($rr['total'] ?? 0) ? round(((int)$rr['responded'] / (int)$rr['total']) * 100, 1) : 0.0;

      $st = $pdo->prepare("SELECT
        SUM(CASE WHEN f.interested_status='INTERESTED' THEN 1 ELSE 0 END) as interested,
        SUM(CASE WHEN f.interested_status='NOT_INTERESTED' THEN 1 ELSE 0 END) as not_interested
        FROM lead_followups f
        JOIN leads l ON l.id=f.lead_id
        $whereSql AND f.agent_user_id=:agent2");
      $st->execute($params + [':agent2' => $agentId]);
      $ratio = $st->fetch() ?: ['interested'=>0,'not_interested'=>0];

      $st = $pdo->prepare("SELECT l.*,
        (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:agent2) as followups,
        (SELECT MAX(f.contact_datetime) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:agent3) as last_contact,
        (SELECT f2.call_status FROM lead_followups f2 WHERE f2.lead_id=l.id AND f2.agent_user_id=:agent4 ORDER BY f2.attempt_no DESC LIMIT 1) as last_call_status,
        (SELECT f3.interested_status FROM lead_followups f3 WHERE f3.lead_id=l.id AND f3.agent_user_id=:agent5 ORDER BY f3.attempt_no DESC LIMIT 1) as last_interested_status
        FROM leads l
        $whereSql
        ORDER BY l.created_at DESC
        LIMIT 200");
      $st->execute($params + [
        ':agent2' => $agentId,
        ':agent3' => $agentId,
        ':agent4' => $agentId,
        ':agent5' => $agentId,
      ]);
      $leads = $st->fetchAll();

      // per-day leads created for this agent
      $st = $pdo->prepare("SELECT DATE(l.created_at) as day, COUNT(*) as c
        FROM leads l
        $whereSql
        GROUP BY day
        ORDER BY day ASC");
      $st->execute($params);
      $byDay = $st->fetchAll();

      View::render('ceo/agent_performance', [
        'title' => 'Agent Performance',
        'agent' => $agent,
        'filters' => $filters,
        'assigned' => $assigned,
        'contacted' => $contacted,
        'completed' => $completed,
        'responseRate' => $responseRate,
        'ratio' => $ratio,
        'leads' => $leads,
        'byDay' => $byDay,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function exportCsv(): void {
    try {
      \require_role(['CEO']);
      $agentId = (int)($_GET['agent_id'] ?? 0);
      $from = \parse_date($_GET['from'] ?? null);
      $to = \parse_date($_GET['to'] ?? null);

      $pdo = DB::conn();
      $where = ["l.assigned_agent_user_id = :agent"];
      $params = [':agent'=>$agentId];
      $where[] = "l.is_active = 1";
      if ($from) { $where[] = "DATE(l.created_at) >= :from"; $params[':from'] = $from; }
      if ($to) { $where[] = "DATE(l.created_at) <= :to"; $params[':to'] = $to; }
      $whereSql = 'WHERE ' . implode(' AND ', $where);

      $st = $pdo->prepare("SELECT l.id, l.lead_name, l.contact_email, l.property_type, l.status_overall, l.created_at,
        (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:agent2) as followups,
        (SELECT MAX(f.contact_datetime) FROM lead_followups f WHERE f.lead_id=l.id AND f.agent_user_id=:agent3) as last_contact
        FROM leads l
        $whereSql
        ORDER BY l.created_at DESC");
      $st->execute($params + [
        ':agent2' => $agentId,
        ':agent3' => $agentId,
      ]);
      $rows = $st->fetchAll();

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="agent_performance_'.$agentId.'.csv"');
      $out = fopen('php://output', 'w');
      fputcsv($out, ['Lead ID','Lead Name','Email','Property Type','Status','Created At','Followups','Last Contact']);
      foreach ($rows as $r) {
        fputcsv($out, [$r['id'],$r['lead_name'],$r['contact_email'],$r['property_type'],$r['status_overall'],$r['created_at'],$r['followups'],$r['last_contact']]);
      }
      fclose($out);
    } catch (\Throwable $e) { $this->handleException($e); }
  }
}
