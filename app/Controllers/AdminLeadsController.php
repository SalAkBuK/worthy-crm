<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\DB;
use App\Models\Lead;
use App\Models\User;
use App\Models\Followup;
use App\Models\AuditLog;
use App\Models\Notification;

final class AdminLeadsController extends BaseController {

  public function index(): void {
    try {
      $this->individual();
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function individual(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $agents = User::activeAgents();
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'agent' => $_GET['agent'] ?? '',
        'type' => $_GET['type'] ?? '',
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc',
      ];
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 10;

      $result = Lead::searchAdmin($filters, $page, $perPage);

      View::render('admin/leads_individual', [
        'title' => 'Individual Leads',
        'agents' => $agents,
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function bulk(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $agents = User::activeAgents();
      View::render('admin/leads_bulk', [
        'title' => 'Bulk Leads',
        'agents' => $agents,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function assigned(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $agents = User::activeAgents();
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'agent' => $_GET['agent'] ?? '',
        'type' => $_GET['type'] ?? '',
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc',
        'assigned_only' => true,
      ];
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 10;

      $result = Lead::searchAdmin($filters, $page, $perPage);

      View::render('admin/leads_assigned', [
        'title' => 'Assigned Leads',
        'agents' => $agents,
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function storeBulk(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();

      $formType = (string)($_POST['form_type'] ?? 'bulk');
      $rows = $_POST['rows'] ?? [];
      if (!is_array($rows) || count($rows) === 0) {
        flash('danger', 'Please add at least one lead row.');
        redirect($formType === 'individual' ? 'admin/leads/individual' : 'admin/leads/bulk');
      }
      $rowErrors = [];
      foreach ($rows as $i => $row) {
        $type = trim((string)($row['property_type'] ?? ''));
        if ($type === '') {
          $rows[$i]['allow_missing_type'] = true;
        }
      }
      $ok = Lead::createBulk($rows, (int)current_user()['id'], $rowErrors);

      if (!$ok) {
        Notification::create(
          (int)current_user()['id'],
          'bulk_import_failed',
          'Lead import failed',
          'Fix the highlighted rows and try again.',
          $formType === 'individual' ? 'admin/leads/individual' : 'admin/leads/bulk',
          ['rows' => count($rows)]
        );
        if ($formType === 'individual') {
          $_SESSION['_lead_individual_row_errors'] = $rowErrors;
          $_SESSION['_lead_individual_old_rows'] = $rows;
        } else {
          $_SESSION['_lead_bulk_row_errors'] = $rowErrors;
          $_SESSION['_lead_bulk_old_rows'] = $rows;
        }
        flash('danger', 'Fix the highlighted rows and try again.');
        redirect($formType === 'individual' ? 'admin/leads/individual' : 'admin/leads/bulk');
      }

      AuditLog::log((int)current_user()['id'], 'LEADS_BULK_CREATE', ['count'=>count($rows)]);
      if ($formType === 'individual') {
        $assignedCounts = [];
        foreach ($rows as $row) {
          $agentId = (int)($row['assigned_agent_user_id'] ?? 0);
          if ($agentId > 0) {
            $assignedCounts[$agentId] = ($assignedCounts[$agentId] ?? 0) + 1;
          }
        }
        foreach ($assignedCounts as $agentId => $count) {
          Notification::create(
            (int)$agentId,
            'lead_assigned',
            'New leads assigned',
            'You have been assigned ' . $count . ' lead(s).',
            'agent/leads',
            ['count' => $count]
          );
        }
      }
      flash('success', 'Leads saved successfully.');
      redirect($formType === 'individual' ? 'admin/leads/individual' : 'admin/leads/bulk');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function importCsv(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();

      $file = $_FILES['leads_csv'] ?? null;
      if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        flash('danger', 'Please choose a CSV or XLSX file to import.');
        redirect('admin/leads/bulk');
      }
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        flash('danger', 'File upload failed.');
        redirect('admin/leads/bulk');
      }
      if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        flash('danger', 'File too large (max 3MB).');
        redirect('admin/leads/bulk');
      }
      $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
      if (!in_array($ext, ['csv', 'xlsx'], true)) {
        flash('danger', 'Unsupported file type. Please upload CSV or XLSX.');
        redirect('admin/leads/bulk');
      }

      $assignMode = (string)($_POST['assign_mode'] ?? 'single');
      if (!in_array($assignMode, ['single', 'per_row', 'unassigned'], true)) {
        flash('danger', 'Invalid assignment mode.');
        redirect('admin/leads/bulk');
      }

      $defaultAgentId = 0;
      if ($assignMode === 'single') {
        $defaultAgentId = (int)($_POST['assigned_agent_user_id'] ?? 0);
        if ($defaultAgentId <= 0) {
          flash('danger', 'Select an agent to assign all leads.');
          redirect('admin/leads/bulk');
        }
      }

      $defaultType = trim((string)($_POST['default_property_type'] ?? ''));
      if (!in_array($defaultType, ['OFF_PLAN', 'READY_TO_MOVE'], true)) {
        $defaultType = null;
      }

      $dataRows = $this->readSpreadsheetRows($file['tmp_name'], $ext);
      if (!$dataRows) {
        flash('danger', 'File is empty or unreadable.');
        redirect('admin/leads/bulk');
      }
      $header = array_shift($dataRows);
      if (!$header) {
        flash('danger', 'File is missing a header row.');
        redirect('admin/leads/bulk');
      }

      $headerMap = [];
      foreach ($header as $i => $name) {
        $key = strtolower(trim((string)$name));
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
        if ($key !== '') $headerMap[$key] = $i;
      }
      $getIdx = static function(array $map, array $keys): ?int {
        foreach ($keys as $k) {
          if (array_key_exists($k, $map)) return $map[$k];
        }
        return null;
      };

      $colName = $getIdx($headerMap, ['lead_name','name','lead']);
      $colEmail = $getIdx($headerMap, ['contact_email','email']);
      $colPhone = $getIdx($headerMap, ['contact_phone','phone','mobile']);
      $colInterested = $getIdx($headerMap, ['interested_in_property','interested','interested_property','service category','service_category']);
      $colInterestTypes = $getIdx($headerMap, ['property_type','type','property type']);
      $colArea = $getIdx($headerMap, ['area']);
      $colBudget = $getIdx($headerMap, ['budget (aed)','budget','budget aed']);
      $colLeadStatus = $getIdx($headerMap, ['lead status','lead_status','status']);
      $colAgentId = $getIdx($headerMap, ['assigned_agent_user_id','agent_id']);
      $colAgentUser = $getIdx($headerMap, ['agent_username','username','agent_user']);
      $colAgentEmail = $getIdx($headerMap, ['agent_email','agent_mail']);

      if ($colName === null || $colEmail === null || $colPhone === null) {
        flash('danger', 'CSV must include columns for lead_name/name, contact_email/email, contact_phone/phone.');
        redirect('admin/leads/bulk');
      }
      if ($assignMode === 'per_row' && $colAgentId === null && $colAgentUser === null && $colAgentEmail === null) {
        flash('danger', 'CSV must include agent_id, agent_username, or agent_email when assigning individually.');
        redirect('admin/leads/bulk');
      }

      $agents = User::allAgents();
      $agentByUsername = [];
      $agentByEmail = [];
      foreach ($agents as $a) {
        if (!empty($a['username'])) $agentByUsername[strtolower($a['username'])] = (int)$a['id'];
        if (!empty($a['email'])) $agentByEmail[strtolower($a['email'])] = (int)$a['id'];
      }

      $rows = [];
      foreach ($dataRows as $data) {
        $allEmpty = true;
        foreach ($data as $cell) {
          if (trim((string)$cell) !== '') { $allEmpty = false; break; }
        }
        if ($allEmpty) continue;

        $row = [
          'lead_name' => $data[$colName] ?? '',
          'contact_email' => $data[$colEmail] ?? '',
          'contact_phone' => $data[$colPhone] ?? '',
          'interested_in_property' => $data[$colInterested] ?? '',
          'property_type' => null,
          'property_interest_types' => $colInterestTypes !== null ? ($data[$colInterestTypes] ?? '') : '',
          'area' => $colArea !== null ? ($data[$colArea] ?? '') : '',
          'budget_aed_min' => null,
          'budget_aed_max' => null,
          'budget_aed_range' => $colBudget !== null ? trim((string)($data[$colBudget] ?? '')) : '',
          'lead_status' => $colLeadStatus !== null ? ($data[$colLeadStatus] ?? '') : '',
          'assigned_agent_user_id' => $defaultAgentId,
          'allow_unassigned' => $assignMode === 'unassigned',
          'allow_missing_type' => true,
        ];
        if ($colBudget !== null) {
          $budget = $this->parseBudgetRange((string)($data[$colBudget] ?? ''));
          $row['budget_aed_min'] = $budget['min'];
          $row['budget_aed_max'] = $budget['max'];
          $row['budget_aed_range'] = $budget['range'];
        }
        if ($colInterestTypes !== null && isset($data[$colInterestTypes])) {
          $rawType = strtoupper(trim((string)$data[$colInterestTypes]));
          if ($rawType === 'OFF_PLAN') $row['property_type'] = 'OFF_PLAN';
          elseif ($rawType === 'READY_TO_MOVE' || $rawType === 'READY TO MOVE') $row['property_type'] = 'READY_TO_MOVE';
          elseif ($rawType === 'OFF PLAN') $row['property_type'] = 'OFF_PLAN';
          elseif ($rawType === '') $row['property_type'] = null;
          else $row['property_type'] = null;
        }

        if ($assignMode === 'per_row') {
          $agentId = 0;
          if ($colAgentId !== null && isset($data[$colAgentId])) {
            $agentId = (int)trim((string)$data[$colAgentId]);
          }
          if ($agentId <= 0 && $colAgentUser !== null && isset($data[$colAgentUser])) {
            $key = strtolower(trim((string)$data[$colAgentUser]));
            $agentId = $agentByUsername[$key] ?? 0;
          }
          if ($agentId <= 0 && $colAgentEmail !== null && isset($data[$colAgentEmail])) {
            $key = strtolower(trim((string)$data[$colAgentEmail]));
            $agentId = $agentByEmail[$key] ?? 0;
          }
          $row['assigned_agent_user_id'] = $agentId;
        }
        if ($assignMode === 'unassigned') {
          $row['assigned_agent_user_id'] = 0;
        }

        $rows[] = $row;
      }
      if (!$rows) {
        flash('danger', 'No valid rows found in CSV.');
        redirect('admin/leads/bulk');
      }

      $_SESSION['_lead_bulk_old_rows'] = $rows;
      AuditLog::log((int)current_user()['id'], 'LEADS_CSV_IMPORT', [
        'count' => count($rows),
        'mode' => $assignMode,
        'deferred' => true,
      ]);
      flash('success', 'Imported ' . count($rows) . ' rows. Review and click "Save All Leads" to commit.');
      redirect('admin/leads/bulk');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function show(): void {
    try {
      \require_role(['ADMIN','CEO']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $lead = Lead::findWithAgent($id);
      if (!$lead) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $followups = Followup::listForLead($id);
      View::render('admin/lead_show', [
        'title' => 'Lead Details',
        'lead' => $lead,
        'followups' => $followups,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function assignBulk(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $ids = $_POST['ids'] ?? [];
      $agentId = (int)($_POST['assigned_agent_user_id'] ?? 0);
      if (!is_array($ids) || !$ids) {
        flash('danger', 'Select at least one lead.');
        redirect('admin/leads');
      }
      if ($agentId <= 0) {
        flash('danger', 'Select an agent to assign.');
        redirect('admin/leads');
      }
      $assignments = Lead::assignmentsForIds($ids);
      $assignedCount = 0;
      foreach ($assignments as $prevAgentId) {
        if ($prevAgentId !== $agentId) $assignedCount++;
      }
      $updated = Lead::assignBulk($ids, $agentId);
      AuditLog::log((int)current_user()['id'], 'LEADS_BULK_ASSIGN', [
        'count' => $updated,
        'agent_id' => $agentId,
      ]);
      if ($assignedCount > 0) {
        Notification::create(
          $agentId,
          'lead_assigned',
          'New leads assigned',
          'You have been assigned ' . $assignedCount . ' lead(s).',
          'agent/leads',
          ['count' => $assignedCount, 'lead_ids' => array_values(array_keys($assignments))]
        );
      }
      flash('success', 'Assigned ' . $updated . ' lead(s) to agent.');
      redirect('admin/leads');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function assignSelected(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      header('Content-Type: application/json; charset=utf-8');
      $payload = json_decode((string)file_get_contents('php://input'), true);
      if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
        return;
      }
      $token = (string)($payload['_csrf'] ?? '');
      if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token.']);
        return;
      }

      $agentId = (int)($payload['agent_id'] ?? 0);
      $rows = $payload['rows'] ?? [];
      $remainingRows = $payload['remaining_rows'] ?? null;
      if ($agentId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Select an agent to assign.']);
        return;
      }
      if (!is_array($rows) || !$rows) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Select at least one row.']);
        return;
      }

      foreach ($rows as $i => $row) {
        if (!is_array($row)) {
          http_response_code(422);
          echo json_encode(['ok' => false, 'error' => 'Invalid row data.']);
          return;
        }
        $rows[$i]['assigned_agent_user_id'] = $agentId;
        $type = trim((string)($row['property_type'] ?? ''));
        if ($type === '') {
          $rows[$i]['allow_missing_type'] = true;
        }
      }

      $rowErrors = [];
      $ok = Lead::createBulk($rows, (int)current_user()['id'], $rowErrors);
      if (!$ok) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Fix the highlighted rows and try again.', 'row_errors' => $rowErrors]);
        return;
      }

      if (is_array($remainingRows)) {
        $_SESSION['_lead_bulk_old_rows'] = $remainingRows;
        unset($_SESSION['_lead_bulk_row_errors']);
        if (!$remainingRows) unset($_SESSION['_lead_bulk_old_rows']);
      }

      AuditLog::log((int)current_user()['id'], 'LEADS_BULK_ASSIGN_CREATE', [
        'count' => count($rows),
        'agent_id' => $agentId,
      ]);
      Notification::create(
        $agentId,
        'lead_assigned',
        'New leads assigned',
        'You have been assigned ' . count($rows) . ' lead(s).',
        'agent/leads',
        ['count' => count($rows)]
      );
      echo json_encode(['ok' => true, 'count' => count($rows)]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function clearBulkDraft(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      unset($_SESSION['_lead_bulk_row_errors'], $_SESSION['_lead_bulk_old_rows'], $_SESSION['_lead_bulk_import_errors']);
      flash('success', 'Imported rows cleared.');
      redirect('admin/leads/bulk');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function agents(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $status = array_key_exists('status', $_GET) ? (string)($_GET['status'] ?? '') : 'ACTIVE';
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => $status,
        'scope' => $_GET['scope'] ?? '',
        'leads' => $_GET['leads'] ?? '',
      ];
      $agents = User::agentsWithStats($filters);
      View::render('admin/agents_list', [
        'title' => 'Agent List',
        'agents' => $agents,
        'filters' => $filters,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentShow(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $agent = User::agentWithStats($id);
      if (!$agent) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      View::render('admin/agent_show', [
        'title' => 'Agent Details',
        'agent' => $agent,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentUpdate(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $agent = User::agentWithStats($id);
      if (!$agent) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $email = trim((string)($_POST['email'] ?? ''));
      $phone = trim((string)($_POST['contact_phone'] ?? ''));

      $errors = [];
      if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
      }
      if ($phone !== '' && !preg_match('/^[0-9 +().-]{6,20}$/', $phone)) {
        $errors[] = 'Phone number must be 6-20 chars and digits/+()-.';
      }

      if ($errors) {
        $_SESSION['_form_errors'] = $errors;
        redirect('admin/agent?id=' . $id . '&edit=1');
      }

      User::updateAgentContact($id, $email !== '' ? $email : null, $phone !== '' ? $phone : null);
      AuditLog::log((int)current_user()['id'], 'AGENT_UPDATE', ['agent_id'=>$id]);
      flash('success', 'Agent details updated.');
      redirect('admin/agent?id=' . $id);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentDelete(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $agent = User::agentWithStats($id);
      if (!$agent) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $leadAction = (string)($_POST['lead_action'] ?? 'delete');
      if (!in_array($leadAction, ['delete', 'reassign'], true)) {
        flash('danger', 'Invalid lead action.');
        redirect('admin/agents');
      }

      $leadCount = 0;
      $reassignAgent = null;
      if ($leadAction === 'delete') {
        $leadCount = Lead::deleteByAgent($id);
      } elseif ($leadAction === 'reassign') {
        $newAgentId = (int)($_POST['reassign_agent_id'] ?? 0);
        if ($newAgentId <= 0) {
          flash('danger', 'Select an agent to reassign leads.');
          redirect('admin/agents');
        }
        if ($newAgentId === $id) {
          flash('danger', 'Select a different agent to reassign leads.');
          redirect('admin/agents');
        }
        $reassignAgent = User::agentWithStats($newAgentId);
        if (!$reassignAgent) {
          flash('danger', 'Reassignment agent not found.');
          redirect('admin/agents');
        }
        if ((int)($reassignAgent['is_active'] ?? 1) !== 1) {
          flash('danger', 'Select an active agent to reassign leads.');
          redirect('admin/agents');
        }
        $leadCount = Lead::reassignByAgent($id, $newAgentId);
        if ($leadCount > 0) {
          Notification::create(
            $newAgentId,
            'lead_assigned',
            'New leads assigned',
            'You have been assigned ' . $leadCount . ' lead(s).',
            'agent/leads',
            ['count' => $leadCount, 'from_agent_id' => $id]
          );
        }
      }

      $wasActive = (int)($agent['is_active'] ?? 1) === 1;
      if ($wasActive) {
        User::setAgentsActive([$id], false);
      }

      AuditLog::log((int)current_user()['id'], 'AGENT_DEACTIVATE', [
        'agent_id' => $id,
        'lead_action' => $leadAction,
        'lead_count' => $leadCount,
        'reassign_agent_id' => $reassignAgent ? (int)$reassignAgent['id'] : null,
        'was_active' => $wasActive,
      ]);

      $agentName = User::agentDisplayName($agent);
      $body = $agentName . ' was deleted.';
      if ($leadAction === 'delete') {
        $body .= ' Deleted ' . $leadCount . ' lead(s).';
      } elseif ($leadAction === 'reassign' && $reassignAgent) {
        $body .= ' Reassigned ' . $leadCount . ' lead(s) to ' . User::agentDisplayName($reassignAgent) . '.';
      }

      $recipients = User::userIdsByRoles(['ADMIN', 'CEO']);
      Notification::createMany(
        $recipients,
        'agent_deleted',
        'Agent deleted',
        $body,
        'admin/agents',
        [
          'agent_id' => $id,
          'agent_name' => $agentName,
          'lead_action' => $leadAction,
          'lead_count' => $leadCount,
          'reassign_agent_id' => $reassignAgent ? (int)$reassignAgent['id'] : null,
        ]
      );
      flash('success', $body);
      redirect('admin/agents');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentAdd(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      View::render('admin/agent_add', [
        'title' => 'Add Agent',
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentCreate(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();

      $name = trim((string)($_POST['agent_name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $phone = trim((string)($_POST['contact_phone'] ?? ''));
      $scope = (string)($_POST['properties_scope'] ?? '');
      $rera = trim((string)($_POST['rera_number'] ?? ''));

      $errors = [];
      if ($name === '') $errors[] = 'Agent name is required.';
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
      if ($phone === '' || !preg_match('/^[0-9 +().-]{6,20}$/', $phone)) $errors[] = 'Phone number must be 6-20 chars and digits/+()-.';
      if (!in_array($scope, ['OFF_PLAN','SECONDARY','BOTH'], true)) $errors[] = 'Properties scope is required.';
      if ($rera === '') $errors[] = 'RERA number is required.';

      $photo = $_FILES['agent_photo'] ?? null;
      if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          $errors[] = 'Agent photo upload failed.';
        }
      }

      if ($errors) {
        $_SESSION['_form_errors'] = $errors;
        $_SESSION['_form_old'] = [
          'agent_name' => $name,
          'email' => $email,
          'contact_phone' => $phone,
          'properties_scope' => $scope,
          'rera_number' => $rera,
        ];
        redirect('admin/agent/add');
      }

      $base = $email !== '' ? strstr($email, '@', true) : $name;
      $username = $this->generateUsername($base ?: 'agent');
      $password = bin2hex(random_bytes(4));
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $agentId = User::createAgent([
        'username' => $username,
        'password_hash' => $hash,
        'employee_code' => null,
        'email' => $email,
        'contact_phone' => $phone,
        'agent_name' => $name,
        'rera_number' => $rera,
        'properties_scope' => $scope,
        'photo_path' => null,
      ]);

      if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $photoPath = $this->saveAgentPhoto($agentId, $photo);
        User::updateAgentPhoto($agentId, $photoPath);
      }

      AuditLog::log((int)current_user()['id'], 'AGENT_CREATE', ['agent_id'=>$agentId]);
      flash('success', 'Agent created. Username: ' . $username . ' | Temp Password: ' . $password);
      redirect('admin/agents');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function exportAgents(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => $_GET['status'] ?? '',
        'scope' => $_GET['scope'] ?? '',
        'leads' => $_GET['leads'] ?? '',
      ];
      $agents = User::agentsWithStats($filters);
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="agents_export.csv"');
      $out = fopen('php://output', 'w');
      fputcsv($out, ['Agent ID','Name','Username','Email','Contact','Properties Scope','RERA','Leads','Followups','Last Contact','Status']);
      foreach ($agents as $a) {
        $name = User::agentDisplayName($a);
        fputcsv($out, [
          $a['id'],
          $name,
          $a['username'],
          $a['email'],
          $a['contact_phone'],
          $a['properties_scope'],
          $a['rera_number'],
          $a['leads_count'],
          $a['followups_count'],
          $a['last_contact'],
          ((int)($a['is_active'] ?? 1) === 1) ? 'Active' : 'Inactive',
        ]);
      }
      fclose($out);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentsBulk(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $action = (string)($_POST['action'] ?? '');
      $ids = $_POST['ids'] ?? [];
      if (!is_array($ids) || !$ids) {
        flash('danger', 'Select at least one agent.');
        redirect('admin/agents');
      }
      if (!in_array($action, ['activate','deactivate'], true)) {
        flash('danger', 'Invalid bulk action.');
        redirect('admin/agents');
      }
      $updated = User::setAgentsActive($ids, $action === 'activate');
      if ($updated > 0) {
        Lead::setActiveByAgents($ids, $action === 'activate');
      }
      if ($updated > 0) {
        $verb = $action === 'activate' ? 'activated' : 'deactivated';
        $recipients = User::userIdsByRoles(['ADMIN', 'CEO']);
        Notification::createMany(
          $recipients,
          'agent_status_change',
          'Agents ' . $verb,
          'Updated ' . $updated . ' agent(s).',
          'admin/agents',
          ['count' => $updated, 'action' => $action]
        );
      }
      flash('success', 'Updated ' . $updated . ' agent(s).');
      redirect('admin/agents');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentsBulkResetPassword(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $ids = $_POST['ids'] ?? [];
      if (!is_array($ids) || !$ids) {
        flash('danger', 'Select at least one agent.');
        redirect('admin/agents');
      }
      $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
      $reset = 0;
      $password = 'agent123';
      $hash = password_hash($password, PASSWORD_DEFAULT);
      foreach ($ids as $id) {
        if (User::resetAgentPassword($id, $hash)) {
          $reset++;
          AuditLog::log((int)current_user()['id'], 'AGENT_RESET_PASSWORD', ['agent_id'=>$id]);
        }
      }
      if ($reset === 0) {
        flash('danger', 'No agents were reset.');
      } else {
        $msg = 'Reset ' . $reset . ' agent(s). New password: agent123';
        flash('success', $msg);
      }
      redirect('admin/agents');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function agentResetPassword(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      \verify_csrf();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $agent = User::agentWithStats($id);
      if (!$agent) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $password = 'agent123';
      $hash = password_hash($password, PASSWORD_DEFAULT);
      if (!User::resetAgentPassword($id, $hash)) {
        flash('danger', 'Failed to reset password.');
        redirect('admin/agents');
      }
      AuditLog::log((int)current_user()['id'], 'AGENT_RESET_PASSWORD', ['agent_id'=>$id]);
      flash('success', 'Password reset to agent123.');
      redirect('admin/agents');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function exportLeads(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'agent' => $_GET['agent'] ?? '',
        'type' => $_GET['type'] ?? '',
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc',
        'assigned_only' => !empty($_GET['assigned_only']),
      ];

      $pdo = DB::conn();
      $where = [];
      $params = [];
      $where[] = "l.is_active = 1";
      if (!empty($filters['q'])) {
        $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q OR l.contact_phone LIKE :q)";
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

      $allowedSort = ['created_at','lead_name','contact_email','contact_phone','property_type','agent_name','status_overall'];
      $sort = in_array(($filters['sort'] ?? 'created_at'), $allowedSort, true) ? $filters['sort'] : 'created_at';
      $dir = (($filters['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
      $order = "l.created_at DESC";
      if ($sort === 'agent_name') $order = "agent_name $dir";
      else $order = "l.$sort $dir";

      $sql = "SELECT l.*, u.username as agent_username, COALESCE(e.employee_name, u.username) as agent_name,
        (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id=l.id) as followup_count,
        (SELECT f.intent FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_intent,
        (SELECT f.buy_property_type FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_buy_type,
        (SELECT f.unit_type FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_unit_type,
        (SELECT f.location FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_location,
        (SELECT f.building FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_building,
        (SELECT f.size_sqft FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_size_sqft,
        (SELECT f.beds FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_beds,
        (SELECT f.budget FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_budget,
        (SELECT f.downpayment FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_downpayment,
        (SELECT f.cheques FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_cheques,
        (SELECT f.rent_per_month FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_rent_per_month,
        (SELECT f.rent_per_year_budget FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_rent_per_year_budget,
        (SELECT f.next_followup_at FROM lead_followups f WHERE f.lead_id=l.id ORDER BY f.attempt_no DESC LIMIT 1) as last_next_followup
        FROM leads l
        LEFT JOIN users u ON u.id = l.assigned_agent_user_id
        LEFT JOIN employees e ON e.employee_code=u.employee_code
        $whereSql
        ORDER BY $order";
      $st = $pdo->prepare($sql);
      foreach ($params as $k=>$v) $st->bindValue($k, $v);
      $st->execute();
      $rows = $st->fetchAll();

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="admin_leads_export.csv"');
      $out = fopen('php://output', 'w');
      fputcsv($out, ['Lead ID','Lead Name','Email','Phone','Interested','Property Type','Agent','Status','Followups','Created At','Intent','Buy Type','Unit Type','Location','Building','Size (sqft)','Beds','Budget (AED)','Down Payment (%)','Cheques','Rent/Month (AED)','Yearly Rent Budget (AED)','Next Follow-up']);
      foreach ($rows as $r) {
        $size = $r['last_size_sqft'] !== null && $r['last_size_sqft'] !== '' ? $r['last_size_sqft'] . ' sqft' : '';
        $budget = $r['last_budget'] !== null && $r['last_budget'] !== '' ? $r['last_budget'] . ' AED' : '';
        $down = $r['last_downpayment'] !== null && $r['last_downpayment'] !== '' ? $r['last_downpayment'] . '%' : '';
        $rentPm = $r['last_rent_per_month'] !== null && $r['last_rent_per_month'] !== '' ? $r['last_rent_per_month'] . ' AED' : '';
        $rentPy = $r['last_rent_per_year_budget'] !== null && $r['last_rent_per_year_budget'] !== '' ? $r['last_rent_per_year_budget'] . ' AED' : '';
        fputcsv($out, [
          $r['id'],
          $r['lead_name'],
          $r['contact_email'],
          $r['contact_phone'],
          $r['interested_in_property'],
          $r['property_type'],
          $r['agent_name'],
          $r['status_overall'],
          $r['followup_count'],
          $r['created_at'],
          $r['last_intent'],
          $r['last_buy_type'],
          $r['last_unit_type'],
          $r['last_location'],
          $r['last_building'],
          $size,
          $r['last_beds'],
          $budget,
          $down,
          $r['last_cheques'],
          $rentPm,
          $rentPy,
          $r['last_next_followup'],
        ]);
      }
      fclose($out);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  private function generateUsername(string $base): string {
    $base = strtolower(preg_replace('/[^a-z0-9]+/', '', $base));
    if ($base === '') $base = 'agent';
    $candidate = $base;
    $i = 0;
    while (User::findByUsername($candidate)) {
      $i++;
      $candidate = $base . $i;
      if ($i > 500) {
        throw new \RuntimeException('Unable to generate a unique username.');
      }
    }
    return $candidate;
  }

  private function saveAgentPhoto(int $agentId, array $file): string {
    $max = 3 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max) {
      throw new \RuntimeException('File too large (max 3MB).');
    }
    $tmp = $file['tmp_name'] ?? '';
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
      throw new \RuntimeException('Invalid file type. Only jpg, png, webp allowed.');
    }
    $ext = $allowed[$mime];
    $dir = __DIR__ . '/../../public/uploads/agents/' . $agentId;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
      throw new \RuntimeException('Upload failed.');
    }
    return 'uploads/agents/' . $agentId . '/' . $name;
  }

  private function parseBudgetRange(string $raw): array {
    $range = trim($raw);
    if ($range === '') return ['min' => null, 'max' => null, 'range' => null];
    $nums = [];
    if (preg_match_all('/\d[\d,]*/', $range, $m)) {
      foreach ($m[0] as $val) {
        $num = str_replace(',', '', $val);
        if (is_numeric($num)) $nums[] = (float)$num;
      }
    }
    $min = $nums[0] ?? null;
    $max = $nums[1] ?? null;
    return ['min' => $min, 'max' => $max, 'range' => $range];
  }

  private function readSpreadsheetRows(string $path, string $ext): array {
    if ($ext === 'csv') {
      $rows = [];
      $fh = fopen($path, 'r');
      if (!$fh) return [];
      while (($row = fgetcsv($fh)) !== false) {
        $rows[] = $row;
      }
      fclose($fh);
      return $rows;
    }

    return $this->readXlsxRows($path);
  }

  private function readXlsxRows(string $path): array {
    $zip = new \ZipArchive();
    if ($zip->open($path) !== true) return [];

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) {
      $zip->close();
      return [];
    }

    $workbook = simplexml_load_string($workbookXml);
    $rels = simplexml_load_string($relsXml);
    if (!$workbook || !$rels) {
      $zip->close();
      return [];
    }

    $sheetRelId = null;
    if (isset($workbook->sheets->sheet[0])) {
      $attrs = $workbook->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
      $sheetRelId = (string)($attrs['id'] ?? '');
    }

    $sheetPath = 'xl/worksheets/sheet1.xml';
    if ($sheetRelId !== '') {
      foreach ($rels->Relationship as $rel) {
        $rAttrs = $rel->attributes();
        if ((string)($rAttrs['Id'] ?? '') === $sheetRelId) {
          $target = (string)($rAttrs['Target'] ?? '');
          $sheetPath = 'xl/' . ltrim($target, '/');
          break;
        }
      }
    }

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) {
      $zip->close();
      return [];
    }

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
      $sharedDoc = simplexml_load_string($sharedXml);
      if ($sharedDoc && isset($sharedDoc->si)) {
        foreach ($sharedDoc->si as $si) {
          $text = '';
          if (isset($si->t)) {
            $text = (string)$si->t;
          } elseif (isset($si->r)) {
            foreach ($si->r as $run) {
              $text .= (string)($run->t ?? '');
            }
          }
          $shared[] = $text;
        }
      }
    }

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
      $zip->close();
      return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
      $cells = [];
      foreach ($row->c as $c) {
        $cellRef = (string)($c['r'] ?? '');
        $colIndex = $this->xlsxColumnIndex($cellRef);
        $type = (string)($c['t'] ?? '');
        $value = '';
        if ($type === 's') {
          $idx = (int)($c->v ?? -1);
          $value = $shared[$idx] ?? '';
        } elseif ($type === 'inlineStr') {
          $value = (string)($c->is->t ?? '');
        } else {
          $value = (string)($c->v ?? '');
        }
        $cells[$colIndex] = $value;
      }
      if ($cells) {
        $maxIndex = max(array_keys($cells));
        $rowVals = array_fill(0, $maxIndex + 1, '');
        foreach ($cells as $idx => $val) $rowVals[$idx] = $val;
        $rows[] = $rowVals;
      }
    }

    $zip->close();
    return $rows;
  }

  private function xlsxColumnIndex(string $cellRef): int {
    if (!preg_match('/^([A-Z]+)\d+$/i', $cellRef, $m)) return 0;
    $letters = strtoupper($m[1]);
    $num = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
      $num = $num * 26 + (ord($letters[$i]) - 64);
    }
    return $num - 1;
  }

  public function reopen(): void {
    try {
      \require_role(['ADMIN','CEO']);
      \verify_csrf();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $lead = Lead::findWithAgent($id);
      if (!$lead) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $prevStatus = $lead['status_overall'] ?? '';
      Lead::setStatus($id, 'IN_PROGRESS');
      AuditLog::log((int)current_user()['id'], 'LEAD_REOPEN', ['lead_id'=>$id]);
      $recipients = User::userIdsByRoles(['ADMIN', 'CEO']);
      Notification::createMany(
        $recipients,
        'lead_reopen',
        'Lead reopened',
        'Lead "' . ($lead['lead_name'] ?? 'Unknown') . '" was reopened.',
        'admin/lead?id=' . $id,
        ['lead_id' => $id, 'previous_status' => $prevStatus, 'status' => 'IN_PROGRESS']
      );
      flash('success', 'Lead reopened. Agents can add more attempts.');
      redirect('admin/lead?id=' . $id);
    } catch (\Throwable $e) { $this->handleException($e); }
  }
}

