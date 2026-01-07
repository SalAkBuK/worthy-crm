<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$user = current_user();
$flashes = get_flashes();
$toasts = get_toasts();
$role = $user['role'] ?? '';
$notifTypes = notification_types_for_role($role);
$title = $title ?? 'Dashboard';
$page_title = $page_title ?? $title;
$page_subtitle = $page_subtitle ?? $title;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-topbar-color="light" data-menu-color="dark" data-menu-size="default">
<head>
  <?php require __DIR__ . '/lahomes/head.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="wrapper">
    <?php require __DIR__ . '/lahomes/topbar.php'; ?>
    <?php require __DIR__ . '/lahomes/main-nav.php'; ?>

    <div class="page-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="page-title-box">
              <h4 class="mb-0 fw-semibold"><?= e($page_subtitle) ?></h4>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= e(url('')) ?>"><?= e($page_title) ?></a></li>
                <li class="breadcrumb-item active"><?= e($page_subtitle) ?></li>
              </ol>
            </div>
          </div>
        </div>

        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>

        <?= $content ?? '' ?>
      </div>
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12 text-center">
              <script>document.write(new Date().getFullYear())</script> &copy; Worthy CRM. Crafted by
              <iconify-icon icon="solar:hearts-bold-duotone" class="fs-18 align-middle text-danger"></iconify-icon>
              <a href="#" class="fw-bold footer-text">CodeFier</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <?php require __DIR__ . '/lahomes/vendor-scripts.php'; ?>
  <script>
    (function () {
      var items = <?= json_encode($toasts, JSON_UNESCAPED_SLASHES) ?>;
      if (!items || !items.length || !window.Toastify) {
        return;
      }
      var classFor = function (type) {
        switch (type) {
          case 'success': return 'bg-success';
          case 'warning': return 'bg-warning';
          case 'danger': return 'bg-danger';
          case 'info': return 'bg-info';
          default: return 'bg-primary';
        }
      };
      items.forEach(function (item) {
        if (!item || !item.message) {
          return;
        }
        Toastify({
          text: item.message,
          duration: 4000,
          gravity: 'top',
          position: 'right',
          className: classFor(item.type || ''),
          close: true
        }).showToast();
      });
    })();
  </script>
  <script>
    (function () {
      if (!window.EventSource) {
        return;
      }
      var badgeTopbar = document.getElementById('notificationsBadgeTopbar');
      var badgeMenu = document.getElementById('notificationsBadgeMenu');
      if (!badgeTopbar && !badgeMenu) {
        return;
      }
      var streamUrl = "<?= e(url('notifications/stream')) ?>";
      var lastId = <?= (int)\App\Models\Notification::latestIdForUser((int)($user['id'] ?? 0), $notifTypes) ?>;
      var source = new EventSource(streamUrl + '?last_id=' + encodeURIComponent(lastId));
      var lastToastId = lastId;
      var soundEnabled = <?= ($role ?? '') === 'AGENT' ? 'true' : 'false' ?>;
      var audioUnlocked = false;
      var audioCtx = null;
      function unlockAudio() {
        audioUnlocked = true;
      }
      if (soundEnabled) {
        window.addEventListener('click', unlockAudio, { once: true, passive: true });
        window.addEventListener('keydown', unlockAudio, { once: true, passive: true });
      }
      function playNotificationSound() {
        if (!soundEnabled || !audioUnlocked) {
          return;
        }
        try {
          var Ctx = window.AudioContext || window.webkitAudioContext;
          if (!Ctx) {
            return;
          }
          if (!audioCtx) {
            audioCtx = new Ctx();
          }
          if (audioCtx.state === 'suspended') {
            audioCtx.resume();
          }
          var osc = audioCtx.createOscillator();
          var gain = audioCtx.createGain();
          osc.type = 'sine';
          osc.frequency.value = 880;
          gain.gain.value = 0.06;
          osc.connect(gain);
          gain.connect(audioCtx.destination);
          osc.start();
          gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.2);
          osc.stop(audioCtx.currentTime + 0.2);
        } catch (e) {
          // Ignore audio errors; notification still shows.
        }
      }
      function showToast(latest) {
        if (!latest || !window.Toastify) {
          return;
        }
        var text = latest.title || 'New notification';
        if (latest.body) {
          text += ' - ' + latest.body;
        }
        Toastify({
          text: text,
          duration: 4000,
          gravity: 'top',
          position: 'right',
          className: 'primary',
          close: true
        }).showToast();
      }
      function refreshAgentLeads(latest) {
        if (!latest || latest.type !== 'lead_assigned') {
          return;
        }
        var table = document.getElementById('agentLeadsTable');
        if (!table) {
          return;
        }
        var refreshUrl = table.getAttribute('data-refresh-url');
        if (!refreshUrl) {
          return;
        }
        var url = new URL(refreshUrl, window.location.origin);
        url.search = window.location.search || '';
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (res) { return res.text(); })
          .then(function (html) { table.innerHTML = html; })
          .catch(function () { /* ignore */ });
      }
      source.addEventListener('notification', function (event) {
        try {
          var payload = JSON.parse(event.data || '{}');
          var unread = payload.unread || 0;
          var show = unread > 0;
          if (badgeTopbar) {
            badgeTopbar.textContent = String(unread);
            badgeTopbar.classList.toggle('d-none', !show);
          }
          if (badgeMenu) {
            badgeMenu.textContent = String(unread);
            badgeMenu.classList.toggle('d-none', !show);
          }
          if (payload.latest && payload.latest.id) {
            lastId = payload.latest.id;
            if (payload.latest.id > lastToastId) {
              showToast(payload.latest);
              refreshAgentLeads(payload.latest);
              playNotificationSound();
              lastToastId = payload.latest.id;
            }
          }
        } catch (err) {
          // Ignore parse errors for safety.
        }
      });
      source.onerror = function () {
        if (source) {
          source.close();
        }
        setTimeout(function () {
          source = new EventSource(streamUrl + '?last_id=' + encodeURIComponent(lastId));
        }, 5000);
      };
    })();
  </script>
</body>
</html>
