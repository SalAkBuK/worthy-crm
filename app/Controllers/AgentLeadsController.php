<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Models\Lead;
use App\Models\Followup;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Helpers\Logger;

final class AgentLeadsController extends BaseController {

  public function index(): void {
    try {
      \require_role(['AGENT']);
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'type' => $_GET['type'] ?? '',
        'status' => $_GET['status'] ?? '',
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc',
      ];
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 10;

      $result = Lead::searchAgent((int)current_user()['id'], $filters, $page, $perPage);

      View::render('agent/leads_index', [
        'title' => 'Assigned Leads',
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function partial(): void {
    try {
      \require_role(['AGENT']);
      header('Content-Type: text/html; charset=utf-8');
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'type' => $_GET['type'] ?? '',
        'status' => $_GET['status'] ?? '',
        'from' => \parse_date($_GET['from'] ?? null),
        'to' => \parse_date($_GET['to'] ?? null),
        'sort' => $_GET['sort'] ?? 'created_at',
        'dir' => $_GET['dir'] ?? 'desc',
      ];
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 10;

      $result = Lead::searchAgent((int)current_user()['id'], $filters, $page, $perPage);

      View::partial('agent/leads_table', [
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function addLead(): void {
    try {
      \require_role(['AGENT']);
      View::render('agent/leads_add', [
        'title' => 'Add Lead',
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function createLead(): void {
    try {
      \require_role(['AGENT']);
      \verify_csrf();
      $rows = $_POST['rows'] ?? [];
      if (!is_array($rows) || !$rows) {
        flash('danger', 'Please add at least one lead.');
        redirect('agent/leads/add');
      }
      $agentId = (int)current_user()['id'];
      foreach ($rows as $i => $row) {
        if (!is_array($row)) continue;
        $rows[$i]['assigned_agent_user_id'] = $agentId;
        $type = trim((string)($row['property_type'] ?? ''));
        if ($type === '') {
          $rows[$i]['allow_missing_type'] = true;
        }
      }
      $rowErrors = [];
      $ok = Lead::createBulk($rows, $agentId, $rowErrors);
      if (!$ok) {
        $_SESSION['_lead_agent_row_errors'] = $rowErrors;
        $_SESSION['_lead_agent_old_rows'] = $rows;
        flash('danger', 'Fix the highlighted rows and try again.');
        redirect('agent/leads/add');
      }

      AuditLog::log($agentId, 'AGENT_LEAD_CREATE', ['count' => count($rows)]);
      flash('success', 'Lead saved successfully.');
      redirect('agent/leads');
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function openLead(): void {
    try {
      \require_role(['AGENT']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $lead = Lead::findWithAgent($id);
      if (!$lead || (int)$lead['assigned_agent_user_id'] !== (int)current_user()['id']) {
        http_response_code(403); require __DIR__ . '/../Views/errors/403.php'; return;
      }
      $followups = Followup::listForLead($id);
      $nextAttempt = Followup::nextAttemptNo($id, (int)current_user()['id']);
      $lastFollowup = Followup::lastForLeadAgent($id, (int)current_user()['id']);
      $followupBlocked = false;
      $blockReason = null;
      $leadStatus = $lead['status_overall'] ?? '';
      if ($leadStatus === 'CLOSED') {
        $followupBlocked = true;
        $blockReason = 'This lead is closed and no further attempts are allowed.';
      } elseif ($lastFollowup) {
        if ($lastFollowup['call_status'] === 'RESPONDED') {
          $followupBlocked = true;
          $blockReason = 'This lead already responded; no further attempts are required.';
        } elseif ((int)$lastFollowup['attempt_no'] >= 3 && $leadStatus === 'CLOSED') {
          $followupBlocked = true;
          $blockReason = 'Maximum attempts reached (3). This lead is closed.';
        }
      }

      View::render('agent/lead_followup', [
        'title' => 'Lead Follow-up',
        'lead' => $lead,
        'followups' => $followups,
        'nextAttempt' => $nextAttempt,
        'followupBlocked' => $followupBlocked,
        'blockReason' => $blockReason,
      ]);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  public function storeFollowup(): void {
    try {
      \require_role(['AGENT']);
      \verify_csrf();
      $leadId = (int)($_POST['lead_id'] ?? 0);
      if ($leadId <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $lead = Lead::findWithAgent($leadId);
      if (!$lead || (int)$lead['assigned_agent_user_id'] !== (int)current_user()['id']) {
        http_response_code(403); require __DIR__ . '/../Views/errors/403.php'; return;
      }

      // Compute next attempt and enforce sequence
      $nextAttempt = Followup::nextAttemptNo($leadId, (int)current_user()['id']);
      $lastFollowup = Followup::lastForLeadAgent($leadId, (int)current_user()['id']);
      $leadStatus = $lead['status_overall'] ?? '';
      if ($leadStatus === 'CLOSED') {
        flash('danger', 'This lead is closed. No further attempts allowed.');
        redirect('agent/lead?id=' . $leadId);
      }
      if ($lastFollowup) {
        if ($lastFollowup['call_status'] === 'RESPONDED') {
          flash('danger', 'This lead already responded. No further attempts allowed.');
          redirect('agent/lead?id=' . $leadId);
        }
        if ((int)$lastFollowup['attempt_no'] >= 3 && $leadStatus === 'CLOSED') {
          flash('danger', 'Maximum attempts reached (3).');
          redirect('agent/lead?id=' . $leadId);
        }
      }

      $contactDt = (string)($_POST['contact_datetime'] ?? '');
      $nextFollowup = (string)($_POST['next_followup_at'] ?? '');
      $callStatus = (string)($_POST['call_status'] ?? '');
      $interestedStatus = (string)($_POST['interested_status'] ?? '');
      $intent = $_POST['intent'] ?? null;
      $buyType = $_POST['buy_property_type'] ?? null;
      $unitType = null;
      $location = null;
      $building = null;
      $sizeSqft = null;
      $beds = null;
      $budget = null;
      $downpayment = null;
      $cheques = null;
      $rentPerMonth = null;
      $rentPerYear = null;
      $notes = trim((string)($_POST['notes'] ?? ''));
      $whatsapp = isset($_POST['whatsapp_contacted']) ? 1 : 0;

      $errors = [];
      $serverOffset = (int)(-date('Z') / 60);
      $clientOffset = (int)($_POST['tz_offset'] ?? $serverOffset);
      $clientNow = (int)($_POST['client_now'] ?? 0);
      $nowTs = time();
      if ($clientNow > 0) {
        $nowTs = (int)round($clientNow / 1000);
      }
      $parseClientDateTime = function(string $value) use ($clientOffset): ?int {
        $value = trim($value);
        if ($value === '') return null;
        $sign = ($clientOffset <= 0) ? '+' : '-';
        $abs = abs($clientOffset);
        $hours = intdiv($abs, 60);
        $mins = $abs % 60;
        $tzOffset = sprintf('%s%02d:%02d', $sign, $hours, $mins);
        $tz = new \DateTimeZone($tzOffset);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value, $tz);
        if (!$dt) {
          $dt = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s', $value, $tz);
        }
        if (!$dt) return null;
        return $dt->getTimestamp();
      };
      if (!$contactDt) $errors[] = 'Contact date/time is required.';
      $contactTs = null;
      if ($contactDt) {
        $contactTs = $parseClientDateTime($contactDt);
        if ($contactTs === null) {
          Logger::info('Followup contact datetime invalid', [
            'lead_id' => $leadId,
            'agent_id' => (int)current_user()['id'],
            'contact_datetime' => $contactDt,
            'tz_offset' => $clientOffset,
            'server_offset' => $serverOffset,
            'client_now' => $clientNow,
          ]);
          $errors[] = 'Invalid contact date/time.';
        } elseif ($contactTs > $nowTs) {
          Logger::info('Followup contact datetime in future', [
            'lead_id' => $leadId,
            'agent_id' => (int)current_user()['id'],
            'contact_datetime' => $contactDt,
            'contact_ts' => $contactTs,
            'now_ts' => $nowTs,
            'tz_offset' => $clientOffset,
            'server_offset' => $serverOffset,
            'client_now' => $clientNow,
          ]);
          $errors[] = 'Contact date/time cannot be in the future.';
        }
      }
      if (!in_array($callStatus, ['NO_RESPONSE','RESPONDED','ASK_CONTACT_LATER'], true)) $errors[] = 'Call status is required.';
      if (mb_strlen($notes) < 50) $errors[] = 'Notes must be at least 50 characters.';
      if ($callStatus === 'NO_RESPONSE' && !$whatsapp) {
        if (!preg_match('/\b(whatsapp|sms|email)\b/i', $notes)) {
          $errors[] = 'For NO_RESPONSE, mark WhatsApp contacted or mention another channel (sms/email) in notes.';
        }
      }
      if ($callStatus === 'ASK_CONTACT_LATER') {
        if ($nextFollowup === '') {
          $errors[] = 'Next follow-up date/time is required when asked to contact later.';
        } else {
          $nextTs = $parseClientDateTime($nextFollowup);
          if ($nextTs === null) {
            Logger::info('Followup next datetime invalid', [
              'lead_id' => $leadId,
              'agent_id' => (int)current_user()['id'],
              'next_followup_at' => $nextFollowup,
              'tz_offset' => $clientOffset,
              'server_offset' => $serverOffset,
              'client_now' => $clientNow,
            ]);
            $errors[] = 'Invalid next follow-up date/time.';
          } else {
            if ($nextTs <= $nowTs) $errors[] = 'Next follow-up must be in the future.';
            if (isset($contactTs) && $contactTs !== false && $nextTs <= $contactTs) {
              $errors[] = 'Next follow-up must be after the contact date/time.';
            }
          }
        }
      } else {
        $nextFollowup = '';
      }

      if ($callStatus === 'RESPONDED') {
        if (!in_array($interestedStatus, ['INTERESTED','NOT_INTERESTED'], true)) $errors[] = 'Interested status is required.';
        if ($interestedStatus === 'INTERESTED') {
          if (!in_array($intent, ['RENT','BUY'], true)) $errors[] = 'Intent is required when interested.';
          if ($intent === 'BUY') {
            if (!in_array($buyType, ['READY_TO_MOVE','OFF_PLAN'], true)) $errors[] = 'Buy property type is required.';
            if ($buyType === 'READY_TO_MOVE') {
              $unitType = $_POST['unit_type_buy'] ?? ($_POST['unit_type'] ?? null);
              if (!in_array($unitType, ['VILLA','APARTMENT'], true)) $errors[] = 'Unit type is required for Ready To Move.';
              $location = trim((string)($_POST['location'] ?? '')) ?: null;
              $building = trim((string)($_POST['building'] ?? '')) ?: null;
              $sizeSqft = $_POST['size_sqft'] ?? null;
              $beds = $_POST['beds'] ?? null;
              $budget = $_POST['budget'] ?? null;
              $downpayment = $_POST['downpayment'] ?? null;
            } elseif ($buyType === 'OFF_PLAN') {
              $location = trim((string)($_POST['location_offplan'] ?? '')) ?: null;
              $sizeSqft = $_POST['size_sqft_offplan'] ?? null;
              $budget = $_POST['budget_offplan'] ?? null;
              $downpayment = $_POST['downpayment_offplan'] ?? null;
            }
          } elseif ($intent === 'RENT') {
            $buyType = null;
            $unitType = $_POST['unit_type_rent'] ?? null;
            if (!in_array($unitType, ['VILLA','APARTMENT'], true)) $errors[] = 'Unit type is required for Rent.';
            $location = trim((string)($_POST['location_rent'] ?? '')) ?: null;
            $building = trim((string)($_POST['building_rent'] ?? '')) ?: null;
            $sizeSqft = $_POST['size_sqft_rent'] ?? null;
            $beds = $_POST['beds_rent'] ?? null;
            $cheques = $_POST['cheques'] ?? null;
            $rentPerMonth = $_POST['rent_per_month'] ?? null;
            $rentPerYear = $_POST['rent_per_year_budget'] ?? null;
          }
        } else {
          $intent = null; $buyType = null;
        }
      } else {
        $interestedStatus = 'NOT_INTERESTED';
        $intent = null; $buyType = null;
      }

      if ($buyType === '') $buyType = null;

      $numFields = [
        ['Size (sqft)', $sizeSqft],
        ['Beds', $beds],
        ['Budget', $budget],
        ['Down payment', $downpayment],
        ['Cheques', $cheques],
        ['Rent per month', $rentPerMonth],
        ['Yearly budget', $rentPerYear],
      ];
      foreach ($numFields as [$label, $val]) {
        if ($val === null || $val === '') continue;
        if (!is_numeric($val) || (float)$val < 0) {
          $errors[] = $label . ' must be a number >= 0.';
        }
      }
      if ($downpayment !== null && $downpayment !== '') {
        if ((float)$downpayment > 100) {
          $errors[] = 'Down payment must be a percentage between 0 and 100.';
        }
      }

      // Upload validations
      $callShot = $_FILES['call_screenshot'] ?? null;
      if (!$callShot || ($callShot['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Call screenshot is required.';
      }
      $whatsShot = null;
      if ($whatsapp) {
        $whatsShot = $_FILES['whatsapp_screenshot'] ?? null;
        if (!$whatsShot || ($whatsShot['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          $errors[] = 'WhatsApp screenshot is required when WhatsApp contacted.';
        }
      }

      if ($errors) {
        $_SESSION['_form_errors'] = $errors;
        redirect('agent/lead?id=' . $leadId);
      }

      $callPath = $this->saveUpload($leadId, $callShot);
      $whatsPath = $whatsapp ? $this->saveUpload($leadId, $whatsShot) : null;

      $id = Followup::create([
        'lead_id' => $leadId,
        'agent_user_id' => (int)current_user()['id'],
        'attempt_no' => $nextAttempt,
        'contact_datetime' => $contactDt,
        'next_followup_at' => $nextFollowup ?: null,
        'call_status' => $callStatus,
        'interested_status' => $interestedStatus,
        'intent' => $intent,
        'buy_property_type' => $buyType,
        'if_not_interested_property_type' => null,
        'unit_type' => $unitType,
        'size_sqft' => ($sizeSqft === '' || $sizeSqft === null) ? null : (int)$sizeSqft,
        'location' => $location,
        'building' => $building,
        'beds' => ($beds === '' || $beds === null) ? null : (int)$beds,
        'budget' => ($budget === '' || $budget === null) ? null : (float)$budget,
        'downpayment' => ($downpayment === '' || $downpayment === null) ? null : (float)$downpayment,
        'cheques' => ($cheques === '' || $cheques === null) ? null : (int)$cheques,
        'rent_per_month' => ($rentPerMonth === '' || $rentPerMonth === null) ? null : (float)$rentPerMonth,
        'rent_per_year_budget' => ($rentPerYear === '' || $rentPerYear === null) ? null : (float)$rentPerYear,
        'notes' => $notes,
        'call_screenshot_path' => $callPath,
        'whatsapp_contacted' => $whatsapp,
        'whatsapp_screenshot_path' => $whatsPath,
      ]);

      $prevStatus = $lead['status_overall'] ?? 'NEW';
      \App\Models\Lead::updateStatusByLeadId($leadId);
      AuditLog::log((int)current_user()['id'], 'FOLLOWUP_CREATE', ['lead_id'=>$leadId, 'attempt_no'=>$nextAttempt, 'id'=>$id]);

      if ($nextFollowup) {
        Notification::create(
          (int)current_user()['id'],
          'followup_scheduled',
          'Follow-up scheduled',
          'Next follow-up set for ' . $nextFollowup . '.',
          'agent/lead?id=' . $leadId,
          ['lead_id' => $leadId, 'followup_id' => $id, 'next_followup_at' => $nextFollowup]
        );
      }

      $updatedLead = Lead::findWithAgent($leadId);
      $newStatus = $updatedLead['status_overall'] ?? $prevStatus;
      if ($newStatus !== $prevStatus) {
        $leadName = $lead['lead_name'] ?? 'Unknown';
        $agentName = $lead['agent_name'] ?? $lead['agent_username'] ?? (string)(current_user()['username'] ?? 'Agent');
        if ($newStatus === 'CLOSED') {
          \toast('success', 'Lead won: "' . $leadName . '" closed.');
        } else {
          $recipients = \App\Models\User::userIdsByRoles(['ADMIN']);
          Notification::createMany(
            $recipients,
            'lead_status_changed',
            'Lead status changed',
            'Lead "' . $leadName . '" moved from ' . $prevStatus . ' to ' . $newStatus . '.',
            'admin/lead?id=' . $leadId,
            ['lead_id' => $leadId, 'from' => $prevStatus, 'to' => $newStatus]
          );
        }
      }

      flash('success', 'Follow-up saved (Attempt #' . $nextAttempt . ').');
      redirect('agent/lead?id=' . $leadId);
    } catch (\Throwable $e) { $this->handleException($e); }
  }

  private function saveUpload(int $leadId, array $file): string {
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
    $dir = __DIR__ . '/../../uploads/' . $leadId;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
      throw new \RuntimeException('Upload failed.');
    }
    return 'uploads/' . $leadId . '/' . $name;
  }
}
