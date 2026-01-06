<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class Followup {
  public static function listForLead(int $leadId): array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM lead_followups WHERE lead_id=:id ORDER BY attempt_no ASC");
    $st->execute([':id'=>$leadId]);
    return $st->fetchAll();
  }

  public static function nextAttemptNo(int $leadId, int $agentId): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT COALESCE(MAX(attempt_no),0) FROM lead_followups WHERE lead_id=:l AND agent_user_id=:a");
    $st->execute([':l'=>$leadId, ':a'=>$agentId]);
    return ((int)$st->fetchColumn()) + 1;
  }

  public static function lastForLeadAgent(int $leadId, int $agentId): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM lead_followups WHERE lead_id=:l AND agent_user_id=:a ORDER BY attempt_no DESC LIMIT 1");
    $st->execute([':l'=>$leadId, ':a'=>$agentId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("INSERT INTO lead_followups
      (lead_id, agent_user_id, attempt_no, contact_datetime, next_followup_at, call_status, interested_status, intent,
       buy_property_type, if_not_interested_property_type, unit_type, size_sqft, location, building, beds, budget,
       downpayment, cheques, rent_per_month, rent_per_year_budget, notes, call_screenshot_path, whatsapp_contacted, whatsapp_screenshot_path, created_at)
      VALUES
      (:lead,:agent,:attempt,:dt,:next,:call,:interested,:intent,:buytype,:nptype,:unit,:size,:location,:building,:beds,:budget,
       :down,:cheques,:rentpm,:rentpy,:notes,:callshot,:w,:wshot,NOW())");
    $st->execute([
      ':lead'=>$data['lead_id'],
      ':agent'=>$data['agent_user_id'],
      ':attempt'=>$data['attempt_no'],
      ':dt'=>$data['contact_datetime'],
      ':next'=>$data['next_followup_at'],
      ':call'=>$data['call_status'],
      ':interested'=>$data['interested_status'],
      ':intent'=>$data['intent'],
      ':buytype'=>$data['buy_property_type'],
      ':nptype'=>$data['if_not_interested_property_type'],
      ':unit'=>$data['unit_type'],
      ':size'=>$data['size_sqft'],
      ':location'=>$data['location'],
      ':building'=>$data['building'],
      ':beds'=>$data['beds'],
      ':budget'=>$data['budget'],
      ':down'=>$data['downpayment'],
      ':cheques'=>$data['cheques'],
      ':rentpm'=>$data['rent_per_month'],
      ':rentpy'=>$data['rent_per_year_budget'],
      ':notes'=>$data['notes'],
      ':callshot'=>$data['call_screenshot_path'],
      ':w'=>$data['whatsapp_contacted'],
      ':wshot'=>$data['whatsapp_screenshot_path'],
    ]);
    return (int)$pdo->lastInsertId();
  }
}
