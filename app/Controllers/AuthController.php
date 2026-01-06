<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\AuditLog;

final class AuthController extends BaseController {

  public function showLogin(): void {
    try {
      if (current_user()) {
        $this->redirectByRole(current_user()['role']);
        return;
      }
      require __DIR__ . '/../Views/auth/login.php';
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function login(): void {
    try {
      \verify_csrf();

      $username = strtolower(trim((string)($_POST['username'] ?? '')));
      $password = (string)($_POST['password'] ?? '');
      $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

      $lockedUntil = LoginAttempt::isLocked($username, $ip);
      if ($lockedUntil) {
        flash('danger', 'Too many failed attempts. Locked until ' . $lockedUntil);
        redirect('login');
      }

      $user = User::findByUsername($username);
      if (!$user || !password_verify($password, $user['password_hash'])) {
        LoginAttempt::recordFailure($username, $ip);
        flash('danger', 'Invalid credentials.');
        redirect('login');
      }

      LoginAttempt::clear($username, $ip);

      session_regenerate_id(true);
      $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'photo_path' => $user['photo_path'] ?? null,
      ];
      AuditLog::log((int)$user['id'], 'LOGIN', ['username'=>$username]);

      $this->redirectByRole($user['role']);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function logout(): void {
    try {
      $u = current_user();
      if ($u) AuditLog::log((int)$u['id'], 'LOGOUT');
      $_SESSION = [];
      if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
      }
      session_destroy();
      flash('success', 'Logged out.');
      redirect('login');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  private function redirectByRole(string $role): void {
    if ($role === 'ADMIN') redirect('admin/leads');
    if ($role === 'AGENT') redirect('agent/leads');
    if ($role === 'CEO') redirect('ceo/dashboard');
    redirect('login');
  }
}
