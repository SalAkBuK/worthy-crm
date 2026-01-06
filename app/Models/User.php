<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class User {
  public static function findByUsername(string $username): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
    $st->execute([':u' => $username]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function findById(int $id): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT u.*, e.employee_name
      FROM users u
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      WHERE u.id = :id
      LIMIT 1");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function allAgents(): array {
    $pdo = DB::conn();
    $st = $pdo->query("SELECT u.*, e.employee_name
      FROM users u
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      WHERE u.role='AGENT'
      ORDER BY COALESCE(e.employee_name, u.username) ASC");
    return $st->fetchAll();
  }

  public static function agentsWithStats(array $filters): array {
    $pdo = DB::conn();
    $where = "u.role='AGENT'";
    $params = [];
    $query = trim((string)($filters['q'] ?? ''));
    if ($query !== '') {
      $where .= " AND (u.username LIKE :q OR u.agent_name LIKE :q OR e.employee_name LIKE :q OR u.email LIKE :q OR u.contact_phone LIKE :q)";
      $params[':q'] = '%' . $query . '%';
    }
    $status = (string)($filters['status'] ?? '');
    if ($status === 'ACTIVE') {
      $where .= " AND u.is_active=1";
    } elseif ($status === 'INACTIVE') {
      $where .= " AND u.is_active=0";
    }
    $scope = (string)($filters['scope'] ?? '');
    if (in_array($scope, ['OFF_PLAN','SECONDARY','BOTH'], true)) {
      $where .= " AND u.properties_scope=:scope";
      $params[':scope'] = $scope;
    }
    $leads = (string)($filters['leads'] ?? '');
    if ($leads === '0') {
      $where .= " AND (SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=u.id)=0";
    } elseif ($leads === '1-5') {
      $where .= " AND (SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=u.id) BETWEEN 1 AND 5";
    } elseif ($leads === '6+') {
      $where .= " AND (SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=u.id) >= 6";
    }
    $sql = "SELECT u.*, e.employee_name,
      (SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=u.id) as leads_count,
      (SELECT COUNT(*) FROM lead_followups f WHERE f.agent_user_id=u.id) as followups_count,
      (SELECT MAX(f.contact_datetime) FROM lead_followups f WHERE f.agent_user_id=u.id) as last_contact
      FROM users u
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      WHERE $where
      ORDER BY COALESCE(e.employee_name, u.username) ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
  }

  public static function agentWithStats(int $id): ?array {
    $pdo = DB::conn();
    $sql = "SELECT u.*, e.employee_name,
      (SELECT COUNT(*) FROM leads l WHERE l.assigned_agent_user_id=u.id) as leads_count,
      (SELECT COUNT(*) FROM lead_followups f WHERE f.agent_user_id=u.id) as followups_count,
      (SELECT MAX(f.contact_datetime) FROM lead_followups f WHERE f.agent_user_id=u.id) as last_contact
      FROM users u
      LEFT JOIN employees e ON e.employee_code = u.employee_code
      WHERE u.id=:id AND u.role='AGENT'
      LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':id'=>$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function createAgent(array $data): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("INSERT INTO users
      (username, password_hash, role, employee_code, created_at, email, contact_phone, agent_name, rera_number, properties_scope, photo_path)
      VALUES (:u,:p,'AGENT',:e,NOW(),:email,:phone,:name,:rera,:scope,:photo)");
    $st->execute([
      ':u' => $data['username'],
      ':p' => $data['password_hash'],
      ':e' => $data['employee_code'],
      ':email' => $data['email'],
      ':phone' => $data['contact_phone'],
      ':name' => $data['agent_name'],
      ':rera' => $data['rera_number'],
      ':scope' => $data['properties_scope'],
      ':photo' => $data['photo_path'],
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function updateAgentPhoto(int $id, string $photoPath): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET photo_path=:p WHERE id=:id AND role='AGENT'");
    $st->execute([':p'=>$photoPath, ':id'=>$id]);
  }

  public static function updatePhotoPath(int $id, string $photoPath): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET photo_path=:p WHERE id=:id");
    $st->execute([':p'=>$photoPath, ':id'=>$id]);
  }

  public static function updateAgentContact(int $id, ?string $email, ?string $phone): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET email=:e, contact_phone=:p WHERE id=:id AND role='AGENT'");
    $st->execute([
      ':e' => $email,
      ':p' => $phone,
      ':id' => $id,
    ]);
  }

  public static function setAgentsActive(array $ids, bool $active): int {
    $pdo = DB::conn();
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (!$ids) return 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("UPDATE users SET is_active=? WHERE role='AGENT' AND id IN ($placeholders)");
    $params = array_merge([$active ? 1 : 0], $ids);
    $st->execute($params);
    return $st->rowCount();
  }

  public static function resetAgentPassword(int $id, string $passwordHash): bool {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET password_hash=:p WHERE id=:id AND role='AGENT'");
    $st->execute([':p'=>$passwordHash, ':id'=>$id]);
    return $st->rowCount() > 0;
  }

  public static function updateProfile(int $id, ?string $name, ?string $email, ?string $phone, ?string $rera): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET agent_name=:n, email=:e, contact_phone=:p, rera_number=:r WHERE id=:id");
    $st->execute([
      ':n' => $name !== '' ? $name : null,
      ':e' => $email !== '' ? $email : null,
      ':p' => $phone !== '' ? $phone : null,
      ':r' => $rera !== '' ? $rera : null,
      ':id' => $id,
    ]);
  }

  public static function updatePassword(int $id, string $passwordHash): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE users SET password_hash=:p WHERE id=:id");
    $st->execute([':p' => $passwordHash, ':id' => $id]);
  }

  public static function deleteAgentIfNoActivity(int $id): array {
    $pdo = DB::conn();
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=:id AND role='AGENT'");
    $chk->execute([':id'=>$id]);
    if ((int)$chk->fetchColumn() === 0) {
      return ['ok'=>false, 'error'=>'Agent not found.'];
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_agent_user_id=:id");
    $st->execute([':id'=>$id]);
    $leads = (int)$st->fetchColumn();
    if ($leads > 0) {
      return ['ok'=>false, 'error'=>'Cannot delete agent with assigned leads.'];
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM lead_followups WHERE agent_user_id=:id");
    $st->execute([':id'=>$id]);
    $followups = (int)$st->fetchColumn();
    if ($followups > 0) {
      return ['ok'=>false, 'error'=>'Cannot delete agent with follow-up history.'];
    }

    $del = $pdo->prepare("DELETE FROM users WHERE id=:id AND role='AGENT'");
    $del->execute([':id'=>$id]);
    return ['ok'=>true];
  }

  public static function agentDisplayName(array $user): string {
    return $user['agent_name'] ?: ($user['employee_name'] ?: ucfirst($user['username']));
  }

}
