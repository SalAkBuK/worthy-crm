<?php
declare(strict_types=1);

namespace Tests;

use App\Helpers\DB;
use App\Models\Lead;
use PHPUnit\Framework\TestCase;

final class LeadDeletionTest extends TestCase
{
  private \PDO $pdo;

  protected function setUp(): void
  {
    $this->pdo = new \PDO('sqlite::memory:');
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->pdo->exec('PRAGMA foreign_keys = ON');
    $this->createSchema($this->pdo);
    $this->setDbConnection($this->pdo);
  }

  protected function tearDown(): void
  {
    $this->setDbConnection(null);
  }

  /**
   * @dataProvider statusProvider
   */
  public function testDeleteLeadCascadesFollowupsForAssignedLead(string $status): void
  {
    $adminId = $this->insertUser('ADMIN');
    $agentId = $this->insertUser('AGENT');
    $leadId = $this->insertLead($adminId, $agentId, $status);
    $this->insertFollowup($leadId, $agentId, 1);

    $deleted = Lead::delete($leadId);

    $this->assertSame(1, $deleted);
    $this->assertSame(0, $this->countRows('leads'));
    $this->assertSame(0, $this->countRows('lead_followups'));
  }

  public function statusProvider(): array
  {
    return [
      ['NEW'],
      ['IN_PROGRESS'],
      ['50/50'],
      ['ON_HOLD'],
      ['CLOSED'],
    ];
  }

  private function createSchema(\PDO $pdo): void
  {
    $pdo->exec("
      CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role TEXT NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1
      );
    ");
    $pdo->exec("
      CREATE TABLE leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_name TEXT NOT NULL,
        contact_email TEXT NOT NULL,
        contact_phone TEXT NOT NULL,
        interested_in_property TEXT NOT NULL,
        property_type TEXT NULL,
        assigned_agent_user_id INTEGER NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_by_user_id INTEGER NOT NULL,
        status_overall TEXT NOT NULL DEFAULT 'NEW',
        created_at TEXT NULL,
        FOREIGN KEY (assigned_agent_user_id) REFERENCES users(id) ON DELETE RESTRICT,
        FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
      );
    ");
    $pdo->exec("
      CREATE TABLE lead_followups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        agent_user_id INTEGER NOT NULL,
        attempt_no INTEGER NOT NULL,
        contact_datetime TEXT NOT NULL,
        call_status TEXT NOT NULL,
        interested_status TEXT NOT NULL,
        notes TEXT NOT NULL,
        call_screenshot_path TEXT NOT NULL,
        created_at TEXT NULL,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
      );
    ");
  }

  private function insertUser(string $role): int
  {
    $st = $this->pdo->prepare("INSERT INTO users (role, is_active) VALUES (:role, 1)");
    $st->execute([':role' => $role]);
    return (int)$this->pdo->lastInsertId();
  }

  private function insertLead(int $createdById, int $agentId, string $status): int
  {
    $st = $this->pdo->prepare(
      "INSERT INTO leads
        (lead_name, contact_email, contact_phone, interested_in_property, property_type,
         assigned_agent_user_id, is_active, created_by_user_id, status_overall, created_at)
       VALUES
        (:name, :email, :phone, :interest, :ptype, :agent, 1, :created_by, :status, :created_at)"
    );
    $st->execute([
      ':name' => 'Test Lead',
      ':email' => 'lead@example.com',
      ':phone' => '+971500000000',
      ':interest' => 'Off Plan',
      ':ptype' => 'OFF_PLAN',
      ':agent' => $agentId,
      ':created_by' => $createdById,
      ':status' => $status,
      ':created_at' => '2026-01-18 00:00:00',
    ]);
    return (int)$this->pdo->lastInsertId();
  }

  private function insertFollowup(int $leadId, int $agentId, int $attemptNo): void
  {
    $st = $this->pdo->prepare(
      "INSERT INTO lead_followups
        (lead_id, agent_user_id, attempt_no, contact_datetime, call_status, interested_status,
         notes, call_screenshot_path, created_at)
       VALUES
        (:lead_id, :agent_id, :attempt_no, :contact_dt, :call_status, :interested_status,
         :notes, :call_path, :created_at)"
    );
    $st->execute([
      ':lead_id' => $leadId,
      ':agent_id' => $agentId,
      ':attempt_no' => $attemptNo,
      ':contact_dt' => '2026-01-18 12:00:00',
      ':call_status' => 'RESPONDED',
      ':interested_status' => '50/50',
      ':notes' => 'Followup notes for test.',
      ':call_path' => 'uploads/test-call.png',
      ':created_at' => '2026-01-18 12:00:00',
    ]);
  }

  private function countRows(string $table): int
  {
    $st = $this->pdo->query("SELECT COUNT(*) FROM " . $table);
    return (int)$st->fetchColumn();
  }

  private function setDbConnection(?\PDO $pdo): void
  {
    $ref = new \ReflectionClass(DB::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $prop->setValue(null, $pdo);
  }
}
