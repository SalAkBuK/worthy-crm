<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$activeTab = $activeTab ?? 'due_today';
$dueSoonHours = $dueSoonHours ?? (int)(getenv('FOLLOWUP_DUE_SOON_HOURS') ?: 48);
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-1">Follow-up Inbox</h4>
          <div class="text-muted fs-12">Track upcoming follow-ups and due items.</div>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(url('agent/leads')) ?>">
          <i class="ri-list-check-2 me-1"></i>Assigned Leads
        </a>
      </div>
      <div class="card-body border-top">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="btn-group" role="group" aria-label="Follow-up tabs">
            <button class="btn btn-outline-secondary followup-tab <?= $activeTab === 'due_soon' ? 'active' : '' ?>" data-tab="due_soon" type="button">
              Due Soon <span class="badge bg-light text-dark ms-1" data-count="due_soon">0</span>
            </button>
            <button class="btn btn-outline-secondary followup-tab <?= $activeTab === 'due_today' ? 'active' : '' ?>" data-tab="due_today" type="button">
              Due Today <span class="badge bg-light text-dark ms-1" data-count="due_today">0</span>
            </button>
            <button class="btn btn-outline-secondary followup-tab <?= $activeTab === 'scheduled' ? 'active' : '' ?>" data-tab="scheduled" type="button">
              Next Follow-up <span class="badge bg-light text-dark ms-1" data-count="scheduled">0</span>
            </button>
          </div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <input class="form-control form-control-sm" type="search" placeholder="Search lead / email / phone" id="followupSearch" style="min-width:240px;">
            <button class="btn btn-sm btn-outline-primary" id="followupRefresh" type="button">
              <i class="ri-refresh-line me-1"></i>Refresh
            </button>
          </div>
        </div>
        <div class="small text-muted mt-2">
          Due soon = within next <?= e((string)$dueSoonHours) ?> hours.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle text-nowrap mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th>Lead</th>
                <th>Next Follow-up</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="followupInboxRows">
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-3">
          <div class="text-muted fs-12" id="followupMeta"></div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="followupPrev" type="button">Prev</button>
            <button class="btn btn-sm btn-outline-secondary" id="followupNext" type="button">Next</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var rowsEl = document.getElementById('followupInboxRows');
  var metaEl = document.getElementById('followupMeta');
  var searchEl = document.getElementById('followupSearch');
  var refreshBtn = document.getElementById('followupRefresh');
  var prevBtn = document.getElementById('followupPrev');
  var nextBtn = document.getElementById('followupNext');
  var tabs = Array.from(document.querySelectorAll('.followup-tab'));
  var currentTab = '<?= e($activeTab) ?>';
  var page = 1;
  var perPage = 20;
  var counts = { due_today: 0, due_soon: 0, scheduled: 0, total_inbox: 0 };

  function statusBadge(status){
    var cls = 'secondary';
    if (status === 'CLOSED') cls = 'success';
    else if (status === 'IN_PROGRESS') cls = 'warning';
    else if (status === '50/50') cls = 'info';
    else if (status === 'ON_HOLD') cls = 'primary';
    return '<span class="badge bg-' + cls + '-subtle text-' + cls + ' py-1 px-2 fs-13">' + escapeHtml(status || '-') + '</span>';
  }

  function escapeHtml(value){
    return String(value || '').replace(/[&<>"']/g, function(m){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
    });
  }

  function formatSnippet(item){
    var note = (item.next_followup_note || '').trim();
    var interest = (item.interested_in_property || '').trim();
    var snippet = note || interest;
    if (!snippet) return '-';
    if (snippet.length > 60) return snippet.slice(0, 57) + '...';
    return snippet;
  }

  function formatPhone(item){
    var phone = (item.contact_phone || '').trim();
    return phone !== '' ? phone : '-';
  }

  function renderRows(items){
    if (!items || items.length === 0){
      rowsEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No follow-ups found.</td></tr>';
      return;
    }
    rowsEl.innerHTML = items.map(function(item){
      var leadName = escapeHtml(item.lead_name || 'Unknown');
      var phone = escapeHtml(formatPhone(item));
      var snippet = escapeHtml(formatSnippet(item));
      var nextAt = escapeHtml(item.next_followup_at_display || item.next_followup_at || '-');
      var status = statusBadge(item.status_overall);
      var link = '<?= e(url('agent/lead?id=')) ?>' + encodeURIComponent(item.id);
      return '' +
        '<tr>' +
          '<td>' +
            '<div class="fw-semibold">' + leadName + '</div>' +
            '<div class="text-muted fs-12">' + phone + ' · ' + snippet + '</div>' +
          '</td>' +
          '<td class="text-muted">' + nextAt + '</td>' +
          '<td>' + status + '</td>' +
          '<td class="text-end">' +
            '<a class="btn btn-sm btn-soft-primary" href="' + link + '">Open lead</a>' +
          '</td>' +
        '</tr>';
    }).join('');
  }

  function updateCounts(){
    Object.keys(counts).forEach(function(key){
      var el = document.querySelector('[data-count="' + key + '"]');
      if (el) el.textContent = String(counts[key] || 0);
    });
    var badge = document.getElementById('followupInboxBadgeTopbar');
    if (badge){
      var total = counts.total_inbox || 0;
      badge.textContent = total;
      badge.classList.toggle('d-none', total <= 0);
    }
  }

  function updateMeta(meta){
    if (!meta) return;
    metaEl.textContent = 'Page ' + meta.page + ' of ' + meta.pages + ' · Total ' + meta.total;
    prevBtn.disabled = meta.page <= 1;
    nextBtn.disabled = meta.page >= meta.pages;
  }

  function fetchTab(){
    var q = (searchEl.value || '').trim();
    var url = '<?= e(url('agent/followups/inbox')) ?>?tab=' + encodeURIComponent(currentTab) +
      '&page=' + page + '&per_page=' + perPage + '&q=' + encodeURIComponent(q);
    fetch(url, { credentials: 'same-origin' })
      .then(function(res){ return res.json(); })
      .then(function(payload){
        counts = payload.counts || counts;
        updateCounts();
        renderRows(payload.items || []);
        updateMeta(payload.meta || { page: 1, pages: 1, total: 0 });
      })
      .catch(function(){
        rowsEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Failed to load inbox.</td></tr>';
      });
  }

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      currentTab = tab.getAttribute('data-tab');
      tabs.forEach(function(t){ t.classList.toggle('active', t === tab); });
      page = 1;
      fetchTab();
    });
  });

  refreshBtn.addEventListener('click', function(){ fetchTab(); });
  prevBtn.addEventListener('click', function(){ if (page > 1){ page -= 1; fetchTab(); } });
  nextBtn.addEventListener('click', function(){ page += 1; fetchTab(); });
  searchEl.addEventListener('keydown', function(e){
    if (e.key === 'Enter'){ e.preventDefault(); page = 1; fetchTab(); }
  });

  window.addEventListener('focus', function(){ fetchTab(); });
  fetchTab();
})();
</script>
