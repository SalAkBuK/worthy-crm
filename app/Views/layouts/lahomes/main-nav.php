<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
$user = current_user();
$role = $user['role'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';
?>
<div class="main-nav">
  <div class="logo-box">
    <a href="<?= e(url('')) ?>" class="logo-dark">
      <img src="<?= e(url('assets/lahomes/images/favicon.ico')) ?>" class="logo-sm" alt="logo sm">
      <img src="<?= e(url('assets/lahomes/images/logo-light.png')) ?>" class="logo-lg" alt="logo">
    </a>
    <a href="<?= e(url('')) ?>" class="logo-light">
      <img src="<?= e(url('assets/lahomes/images/favicon.ico')) ?>" class="logo-sm" alt="logo sm">
      <img src="<?= e(url('assets/lahomes/images/logo-light.png')) ?>" class="logo-lg" alt="logo">
    </a>
  </div>

  <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
    <i class="ri-menu-2-line fs-24 button-sm-hover-icon"></i>
  </button>

  <div class="scrollbar" data-simplebar>
    <ul class="navbar-nav" id="navbar-nav">
      <li class="menu-title">Menu</li>

      <?php if ($role === 'CEO'): ?>
        <li class="nav-item">
          <a class="nav-link <?= str_contains($uri, '/ceo/dashboard') ? 'active' : '' ?>" href="<?= e(url('ceo/dashboard')) ?>">
            <span class="nav-icon"><i class="ri-dashboard-2-line"></i></span>
            <span class="nav-text">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_contains($uri, '/ceo/summary') ? 'active' : '' ?>" href="<?= e(url('ceo/summary')) ?>">
            <span class="nav-icon"><i class="ri-bar-chart-2-line"></i></span>
            <span class="nav-text">Summary</span>
          </a>
        </li>
      <?php elseif ($role === 'ADMIN'): ?>
        <li class="nav-item">
          <a class="nav-link <?= str_contains($uri, '/admin/leads') ? 'active' : '' ?>" href="<?= e(url('admin/leads')) ?>">
            <span class="nav-icon"><i class="ri-community-line"></i></span>
            <span class="nav-text">Leads</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= str_contains($uri, '/admin/agents') ? 'active' : '' ?>" href="<?= e(url('admin/agents')) ?>">
            <span class="nav-icon"><i class="ri-team-line"></i></span>
            <span class="nav-text">Agent Lists</span>
          </a>
        </li>
      <?php elseif ($role === 'AGENT'): ?>
        <li class="nav-item">
          <a class="nav-link <?= str_contains($uri, '/agent/leads') ? 'active' : '' ?>" href="<?= e(url('agent/leads')) ?>">
            <span class="nav-icon"><i class="ri-group-line"></i></span>
            <span class="nav-text">Assigned Leads</span>
          </a>
        </li>
      <?php endif; ?>

      <li class="nav-item">
        <a class="nav-link" href="<?= e(url('logout')) ?>">
          <span class="nav-icon"><i class="ri-logout-box-line"></i></span>
          <span class="nav-text">Logout</span>
        </a>
      </li>
    </ul>
  </div>
</div>
