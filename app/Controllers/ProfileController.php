<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Models\User;
use App\Models\AuditLog;

final class ProfileController extends BaseController {

  public function show(): void {
    try {
      \require_login();
      $user = current_user();
      if (!$user) {
        redirect('login');
      }
      $full = User::findById((int)$user['id']);
      if (!$full) {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
        return;
      }
      View::render('profile', [
        'title' => 'Profile',
        'user' => $full,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function update(): void {
    try {
      \require_login();
      \verify_csrf();
      $user = current_user();
      if (!$user) {
        redirect('login');
      }
      $id = (int)$user['id'];
      $name = trim((string)($_POST['name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $phone = trim((string)($_POST['contact_phone'] ?? ''));
      $rera = trim((string)($_POST['rera_number'] ?? ''));
      if (($user['role'] ?? '') !== 'AGENT') {
        $rera = '';
      }

      $errors = [];
      if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
      }
      if ($phone !== '' && !preg_match('/^[0-9 +().-]{6,20}$/', $phone)) {
        $errors[] = 'Phone number must be 6-20 chars and digits/+()-.'; 
      }
      if ($errors) {
        $_SESSION['_profile_errors'] = $errors;
        $_SESSION['_profile_old'] = [
          'name' => $name,
          'email' => $email,
          'contact_phone' => $phone,
          'rera_number' => $rera,
        ];
        redirect('profile');
      }

      $photo = $_FILES['profile_photo'] ?? null;
      if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          $_SESSION['_profile_errors'] = ['Profile photo upload failed.'];
          $_SESSION['_profile_old'] = [
            'name' => $name,
            'email' => $email,
            'contact_phone' => $phone,
            'rera_number' => $rera,
          ];
          redirect('profile');
        }
      }

      User::updateProfile($id, $name !== '' ? $name : null, $email !== '' ? $email : null, $phone !== '' ? $phone : null, $rera !== '' ? $rera : null);
      if ($photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $photoPath = $this->saveProfilePhoto($id, $photo);
        User::updatePhotoPath($id, $photoPath);
        $_SESSION['user']['photo_path'] = $photoPath;
      }
      AuditLog::log($id, 'PROFILE_UPDATE');
      flash('success', 'Profile updated.');
      redirect('profile');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function changePassword(): void {
    try {
      \require_login();
      \verify_csrf();
      $user = current_user();
      if (!$user) {
        redirect('login');
      }
      $id = (int)$user['id'];
      $current = (string)($_POST['current_password'] ?? '');
      $new = (string)($_POST['new_password'] ?? '');
      $confirm = (string)($_POST['confirm_password'] ?? '');

      $errors = [];
      if ($current === '' || $new === '' || $confirm === '') {
        $errors[] = 'All password fields are required.';
      }
      if ($new !== '' && strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
      }
      if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
      }

      $full = User::findById($id);
      if (!$full || !password_verify($current, $full['password_hash'] ?? '')) {
        $errors[] = 'Current password is incorrect.';
      }

      if ($errors) {
        $_SESSION['_password_errors'] = $errors;
        redirect('profile');
      }

      $hash = password_hash($new, PASSWORD_DEFAULT);
      User::updatePassword($id, $hash);
      AuditLog::log($id, 'PASSWORD_CHANGE');
      flash('success', 'Password updated.');
      redirect('profile');
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  private function saveProfilePhoto(int $userId, array $file): string {
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
    $dir = __DIR__ . '/../../public/uploads/users/' . $userId;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
      throw new \RuntimeException('Upload failed.');
    }
    return 'uploads/users/' . $userId . '/' . $name;
  }
}
