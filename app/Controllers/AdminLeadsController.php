<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\DB;
use App\Models\Lead;
use App\Models\User;
use App\Models\Followup;
use App\Models\AuditLog;

final class AdminLeadsController extends BaseController {

  public function index(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $agents = User::allAgents();
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

      View::render('admin/leads_index', [
        'title' => 'Lead Entry & Management',
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

      $rows = $_POST['rows'] ?? [];
      if (!is_array($rows) || count($rows) === 0) {
        flash('danger', 'Please add at least one lead row.');
        redirect('admin/leads');
      }
      $rowErrors = [];
      $ok = Lead::createBulk($rows, (int)current_user()['id'], $rowErrors);

      if (!$ok) {
        $_SESSION['_lead_row_errors'] = $rowErrors;
        $_SESSION['_lead_old_rows'] = $rows;
        flash('danger', 'Fix the highlighted rows and try again.');
        redirect('admin/leads');
      }

      AuditLog::log((int)current_user()['id'], 'LEADS_BULK_CREATE', ['count'=>count($rows)]);
      flash('success', 'Leads saved successfully.');
      redirect('admin/leads');
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

  public function agents(): void {
    try {
      \require_role(['ADMIN', 'CEO']);
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'status' => $_GET['status'] ?? '',
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

      $result = User::deleteAgentIfNoActivity($id);
      if (!$result['ok']) {
        flash('danger', $result['error'] ?? 'Unable to delete agent.');
        redirect('admin/agents');
      }

      AuditLog::log((int)current_user()['id'], 'AGENT_DELETE', ['agent_id'=>$id]);
      flash('success', 'Agent deleted.');
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
      ];

      $pdo = DB::conn();
      $where = [];
      $params = [];
      if (!empty($filters['q'])) {
        $where[] = "(l.lead_name LIKE :q OR l.contact_email LIKE :q OR l.contact_phone LIKE :q)";
        $params[':q'] = '%' . $filters['q'] . '%';
      }
      if (!empty($filters['agent'])) {
        $where[] = "l.assigned_agent_user_id = :agent";
        $params[':agent'] = (int)$filters['agent'];
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
        JOIN users u ON u.id = l.assigned_agent_user_id
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

  public function reopen(): void {
    try {
      \require_role(['ADMIN','CEO']);
      \verify_csrf();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $lead = Lead::findWithAgent($id);
      if (!$lead) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      Lead::setStatus($id, 'IN_PROGRESS');
      AuditLog::log((int)current_user()['id'], 'LEAD_REOPEN', ['lead_id'=>$id]);
      flash('success', 'Lead reopened. Agents can add more attempts.');
      redirect('admin/lead?id=' . $id);
    } catch (\Throwable $e) { $this->handleException($e); }
  }
}
