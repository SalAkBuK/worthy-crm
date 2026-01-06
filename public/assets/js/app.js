(function(){
  // Confirm modal helper
  window.confirmAction = function(message, onOk){
    var modalEl = document.getElementById('confirmModal');
    if(!modalEl) return;
    document.getElementById('confirmModalBody').textContent = message || 'Are you sure?';
    var okBtn = document.getElementById('confirmModalOk');
    var handler = function(){
      try{ onOk && onOk(); } finally {
        okBtn.removeEventListener('click', handler);
        var m = bootstrap.Modal.getInstance(modalEl);
        if(m) m.hide();
      }
    };
    okBtn.addEventListener('click', handler);
    new bootstrap.Modal(modalEl).show();
  };

  // Loading state on forms
  document.addEventListener('submit', function(e){
    var form = e.target;
    if(!(form instanceof HTMLFormElement)) return;
    var btn = form.querySelector('button[type="submit"]');
    if(btn){
      btn.dataset._origText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
      setTimeout(function(){
        // if navigation doesn't happen, re-enable after a while
        btn.disabled = false;
        btn.innerHTML = btn.dataset._origText || 'Submit';
      }, 8000);
    }
  }, true);
})();
