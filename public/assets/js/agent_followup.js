(function(){
  function getDisplayInput(input){
    if (input && input._flatpickr && input._flatpickr.altInput) return input._flatpickr.altInput;
    return input;
  }
  function ensureFeedback(input){
    if (!input) return null;
    if (input._inlineFeedback) return input._inlineFeedback;
    var display = getDisplayInput(input);
    if (!display || !display.parentNode) return null;
    var fb = document.createElement('div');
    fb.className = 'invalid-feedback';
    fb.dataset.inline = '1';
    display.parentNode.insertBefore(fb, display.nextSibling);
    input._inlineFeedback = fb;
    return fb;
  }
  function markInvalid(input, message){
    if (!input) return;
    var display = getDisplayInput(input);
    input.classList.add('is-invalid');
    if (display && display !== input) display.classList.add('is-invalid');
    var fb = ensureFeedback(input);
    if (fb) fb.textContent = message;
  }
  function clearInvalid(input){
    if (!input) return;
    var display = getDisplayInput(input);
    input.classList.remove('is-invalid');
    if (display && display !== input) display.classList.remove('is-invalid');
    if (input._inlineFeedback) input._inlineFeedback.textContent = '';
  }
  function parseLocalDateTime(value){
    if (!value) return null;
    value = String(value).trim();
    var m = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (m) {
      var year = Number(m[1]);
      var month = Number(m[2]) - 1;
      var day = Number(m[3]);
      var hour = Number(m[4]);
      var min = Number(m[5]);
      var sec = Number(m[6] || 0);
      var dt = new Date(year, month, day, hour, min, sec);
      if (dt.getFullYear() !== year || dt.getMonth() !== month || dt.getDate() !== day) return null;
      return dt;
    }

    var m2 = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})(?:\s*([AaPp][Mm]))?$/);
    if (m2) {
      var a = Number(m2[1]);
      var b = Number(m2[2]);
      var year2 = Number(m2[3]);
      var hour2 = Number(m2[4]);
      var min2 = Number(m2[5]);
      var ampm = (m2[6] || '').toUpperCase();
      var month2 = a;
      var day2 = b;
      if (a > 12 && b <= 12) {
        day2 = a;
        month2 = b;
      } else if (b > 12 && a <= 12) {
        month2 = a;
        day2 = b;
      }
      if (ampm === 'PM' && hour2 < 12) hour2 += 12;
      if (ampm === 'AM' && hour2 === 12) hour2 = 0;
      var dt2 = new Date(year2, month2 - 1, day2, hour2, min2, 0);
      if (dt2.getFullYear() !== year2 || dt2.getMonth() !== (month2 - 1) || dt2.getDate() !== day2) return null;
      return dt2;
    }

    var fallback = new Date(value);
    if (!isNaN(fallback.getTime())) {
      return fallback;
    }
    return null;
  }
  function showHideConditional(){
    var callSel = document.getElementById('call_status');
    var callStatus = callSel ? callSel.value : '';
    var interestedSel = document.getElementById('interested_status');
    var interested = interestedSel ? interestedSel.value : '';
    var interestedWrap = document.getElementById('interestedStatusWrap');
    var interestedBox = document.getElementById('ifInterested');
    var nextWrap = document.getElementById('nextFollowupWrap');
    var nextInput = document.getElementById('next_followup_at');
    var futureWrap = document.getElementById('futureInterestWrap');
    var launchInput = document.getElementById('launch_at');
    var intentSel = document.getElementById('intent');
    var buyTypeWrap = document.getElementById('buyPropertyTypeWrap');
    var buyTypeSel = document.getElementById('buy_property_type');
    var buyReady = document.getElementById('buyReadyFields');
    var buyOffplan = document.getElementById('buyOffplanFields');
    var rentFields = document.getElementById('rentFields');
    var unitBuy = document.getElementById('unit_type_buy');
    var unitRent = document.getElementById('unit_type_rent');
    var nextHint = document.getElementById('nextFollowupHint');
    function syncPickerState(input, enabled){
      if (!input) return;
      input.disabled = !enabled;
      if (input._flatpickr && input._flatpickr.altInput) {
        input._flatpickr.altInput.disabled = !enabled;
      }
    }
    function toggleSection(section, enabled){
      if (!section) return;
      section.classList.toggle('d-none', !enabled);
      section.querySelectorAll('input,select,textarea').forEach(function(el){
        el.disabled = !enabled;
      });
    }

    var shouldShowInterested = callStatus === 'RESPONDED';
    if (nextWrap && nextInput) {
      var showNext = callStatus === 'ASK_CONTACT_LATER' || callStatus === 'NO_RESPONSE' ||
        (callStatus === 'RESPONDED' && (interested === '50/50' || interested === 'FUTURE_INTEREST'));
      var requiresNext = callStatus === 'ASK_CONTACT_LATER' ||
        (callStatus === 'RESPONDED' && (interested === '50/50' || interested === 'FUTURE_INTEREST'));
      nextWrap.classList.toggle('d-none', !showNext);
      nextInput.required = requiresNext;
      syncPickerState(nextInput, showNext);
      if (!showNext) {
        if (nextInput._flatpickr) nextInput._flatpickr.clear();
        else nextInput.value = '';
      }
    }
    if (interestedWrap) {
      interestedWrap.classList.toggle('d-none', !shouldShowInterested);
    }
    if (!shouldShowInterested) {
      if (interestedSel) { interestedSel.value = ''; interestedSel.required = false; }
      if (intentSel) { intentSel.value = ''; intentSel.required = false; }
      if (buyTypeSel) { buyTypeSel.value = ''; buyTypeSel.required = false; }
      if (unitBuy) unitBuy.value = '';
      if (unitRent) unitRent.value = '';
      if (launchInput) { launchInput.value = ''; launchInput.required = false; }
      if (futureWrap) futureWrap.classList.add('d-none');
      if (interestedBox) interestedBox.classList.add('d-none');
      if (buyTypeWrap) { buyTypeWrap.classList.add('d-none'); if (buyTypeSel) buyTypeSel.disabled = true; }
      toggleSection(buyReady, false);
      toggleSection(buyOffplan, false);
      toggleSection(rentFields, false);
      return;
    }

    if (interestedSel) interestedSel.required = true;
    if (nextHint) {
      nextHint.classList.toggle('d-none', interested !== '50/50');
    }
    if (futureWrap && launchInput) {
      var showFuture = interested === 'FUTURE_INTEREST';
      futureWrap.classList.toggle('d-none', !showFuture);
      launchInput.required = showFuture;
      syncPickerState(launchInput, showFuture);
      if (!showFuture) {
        if (launchInput._flatpickr) launchInput._flatpickr.clear();
        else launchInput.value = '';
      }
      var useLaunch = document.getElementById('use_launch_as_followup');
      if (!showFuture && useLaunch) useLaunch.checked = false;
      if (nextInput) nextInput.readOnly = false;
    }
    if (interested === 'INTERESTED') {
      if (interestedBox) interestedBox.classList.remove('d-none');
      if (intentSel) intentSel.required = true;
    } else {
      if (interestedBox) interestedBox.classList.add('d-none');
      if (intentSel) { intentSel.value = ''; intentSel.required = false; }
      if (buyTypeSel) { buyTypeSel.value = ''; buyTypeSel.required = false; }
      if (unitBuy) unitBuy.value = '';
      if (unitRent) unitRent.value = '';
      if (buyTypeWrap) { buyTypeWrap.classList.add('d-none'); buyTypeSel.disabled = true; }
      toggleSection(buyReady, false);
      toggleSection(buyOffplan, false);
      toggleSection(rentFields, false);
      return;
    }

    var intent = intentSel ? intentSel.value : '';
    if (intent === 'BUY') {
      if (buyTypeWrap) { buyTypeWrap.classList.remove('d-none'); buyTypeSel.disabled = false; }
      if (buyTypeSel) buyTypeSel.required = true;
      toggleSection(rentFields, false);
      if (unitRent) unitRent.value = '';
      if (unitRent) unitRent.required = false;
      var buyType = buyTypeSel ? buyTypeSel.value : '';
      if (buyType === 'READY_TO_MOVE') {
        toggleSection(buyReady, true);
        toggleSection(buyOffplan, false);
        if (unitBuy) unitBuy.required = true;
      } else if (buyType === 'OFF_PLAN') {
        toggleSection(buyReady, false);
        toggleSection(buyOffplan, true);
        if (unitBuy) { unitBuy.value = ''; unitBuy.required = false; }
      } else {
        toggleSection(buyReady, false);
        toggleSection(buyOffplan, false);
        if (unitBuy) { unitBuy.value = ''; unitBuy.required = false; }
      }
    } else if (intent === 'RENT') {
      if (buyTypeWrap) { buyTypeWrap.classList.add('d-none'); buyTypeSel.disabled = true; }
      if (buyTypeSel) { buyTypeSel.value = ''; buyTypeSel.required = false; }
      toggleSection(buyReady, false);
      toggleSection(buyOffplan, false);
      if (unitBuy) { unitBuy.value = ''; unitBuy.required = false; }
      toggleSection(rentFields, true);
      if (unitRent) unitRent.required = true;
    } else {
      if (buyTypeWrap) { buyTypeWrap.classList.add('d-none'); buyTypeSel.disabled = true; }
      if (buyTypeSel) { buyTypeSel.value = ''; buyTypeSel.required = false; }
      toggleSection(buyReady, false);
      toggleSection(buyOffplan, false);
      toggleSection(rentFields, false);
      if (unitBuy) { unitBuy.value = ''; unitBuy.required = false; }
      if (unitRent) { unitRent.value = ''; unitRent.required = false; }
    }
  }
  function whatsappToggle(){
    var cb = document.getElementById('whatsapp_contacted');
    var box = document.getElementById('whatsappBox');
    if(cb && cb.checked) box.classList.remove('d-none');
    else box.classList.add('d-none');
  }
  function notesCounter(){
    var ta = document.getElementById('notes');
    var counter = document.getElementById('notesCount');
    if(!ta || !counter) return;
    counter.textContent = String(ta.value.length);
    counter.classList.toggle('text-danger', ta.value.length < 50);
  }
  function validateNoResponseChannel(){
    var callSel = document.getElementById('call_status');
    var notes = document.getElementById('notes');
    var whatsapp = document.getElementById('whatsapp_contacted');
    if (!notes) return;
    var status = callSel ? callSel.value : '';
    var hasWhatsapp = whatsapp ? whatsapp.checked : false;
    if (status === 'NO_RESPONSE' && !hasWhatsapp) {
      if (!/\b(whatsapp|sms|email)\b/i.test(notes.value)) {
        markInvalid(notes, 'For NO_RESPONSE, mark WhatsApp contacted or mention another channel (sms/email) in notes.');
        return;
      }
    }
    clearInvalid(notes);
  }
  function validateFollowupForm(form){
    var ok = true;
    if (!form) return ok;
    form.querySelectorAll('input,select,textarea').forEach(function(el){
      clearInvalid(el);
    });

    var callSel = document.getElementById('call_status');
    var interestedSel = document.getElementById('interested_status');
    var contactInput = document.getElementsByName('contact_datetime')[0];
    var nextInput = document.getElementById('next_followup_at');
    var launchInput = document.getElementById('launch_at');
    var useLaunch = document.getElementById('use_launch_as_followup');
    var intentSel = document.getElementById('intent');
    var buyTypeSel = document.getElementById('buy_property_type');
    var unitBuy = document.getElementById('unit_type_buy');
    var unitRent = document.getElementById('unit_type_rent');
    var notes = document.getElementById('notes');
    var whatsapp = document.getElementById('whatsapp_contacted');
    var callShot = form.querySelector('input[name="call_screenshot"]');
    var whatsappShot = form.querySelector('input[name="whatsapp_screenshot"]');

    if (!contactInput || contactInput.disabled) return ok;

    var callStatus = callSel ? callSel.value : '';
    var interested = interestedSel ? interestedSel.value : '';

    if (callSel && callStatus === '') {
      markInvalid(callSel, 'Call status is required.');
      ok = false;
    }

    if (!contactInput.value) {
      markInvalid(contactInput, 'Contact date/time is required.');
      ok = false;
    } else {
      var contactDt = parseLocalDateTime(contactInput.value);
      if (!contactDt) {
        markInvalid(contactInput, 'Invalid contact date/time.');
        ok = false;
      } else if (contactDt.getTime() > Date.now()) {
        markInvalid(contactInput, 'Contact date/time cannot be in the future.');
        ok = false;
      }
    }

    if (callStatus === 'RESPONDED' && interestedSel) {
      if (interested === '') {
        markInvalid(interestedSel, 'Interested status is required.');
        ok = false;
      }
    }

    var requiresNext = callStatus === 'ASK_CONTACT_LATER' ||
      (callStatus === 'RESPONDED' && (interested === '50/50' || interested === 'FUTURE_INTEREST'));
    var optionalNext = callStatus === 'NO_RESPONSE';
    if ((requiresNext || (optionalNext && nextInput && nextInput.value)) && nextInput) {
      var nextValue = nextInput.value;
      if (!nextValue && useLaunch && useLaunch.checked && launchInput && launchInput.value) {
        nextValue = launchInput.value;
      }
      if (!nextValue) {
        markInvalid(nextInput, 'Next follow-up date/time is required.');
        ok = false;
      } else {
        var nextDt = parseLocalDateTime(nextValue);
        if (!nextDt) {
          markInvalid(nextInput, 'Invalid next follow-up date/time.');
          ok = false;
        } else {
          if (nextDt.getTime() <= Date.now()) {
            markInvalid(nextInput, 'Next follow-up must be in the future.');
            ok = false;
          }
          var contactDt2 = contactInput.value ? parseLocalDateTime(contactInput.value) : null;
          if (contactDt2 && nextDt.getTime() <= contactDt2.getTime()) {
            markInvalid(nextInput, 'Next follow-up must be after the contact time.');
            ok = false;
          }
        }
      }
    }

    if (callStatus === 'RESPONDED' && interested === 'FUTURE_INTEREST' && launchInput) {
      if (!launchInput.value) {
        markInvalid(launchInput, 'Project launch date/time is required.');
        ok = false;
      } else {
        var launchDt = parseLocalDateTime(launchInput.value);
        if (!launchDt) {
          markInvalid(launchInput, 'Invalid project launch date/time.');
          ok = false;
        } else {
          if (launchDt.getTime() <= Date.now()) {
            markInvalid(launchInput, 'Project launch date/time must be in the future.');
            ok = false;
          }
          var contactDt3 = contactInput.value ? parseLocalDateTime(contactInput.value) : null;
          if (contactDt3 && launchDt.getTime() <= contactDt3.getTime()) {
            markInvalid(launchInput, 'Project launch date/time must be after the contact time.');
            ok = false;
          }
        }
      }
    }

    if (callStatus === 'RESPONDED' && interested === 'INTERESTED') {
      var intent = intentSel ? intentSel.value : '';
      if (intentSel && intent === '') {
        markInvalid(intentSel, 'Intent is required.');
        ok = false;
      }
      if (intent === 'BUY') {
        var buyType = buyTypeSel ? buyTypeSel.value : '';
        if (buyTypeSel && buyType === '') {
          markInvalid(buyTypeSel, 'Buy property type is required.');
          ok = false;
        }
        if (buyType === 'READY_TO_MOVE' && unitBuy && unitBuy.value === '') {
          markInvalid(unitBuy, 'Unit type is required.');
          ok = false;
        }
      } else if (intent === 'RENT') {
        if (unitRent && unitRent.value === '') {
          markInvalid(unitRent, 'Unit type is required.');
          ok = false;
        }
      }
    }

    if (notes) {
      if (!notes.value || notes.value.trim().length < 50) {
        markInvalid(notes, 'Notes must be at least 50 characters.');
        ok = false;
      }
    }

    if (callStatus === 'NO_RESPONSE' && whatsapp && !whatsapp.checked && notes) {
      if (!/\\b(whatsapp|sms|email)\\b/i.test(notes.value)) {
        markInvalid(notes, 'Mention WhatsApp, SMS, or Email in notes or tick WhatsApp.');
        ok = false;
      }
    }

    if (callShot && (!callShot.files || callShot.files.length === 0)) {
      markInvalid(callShot, 'Call screenshot is required.');
      ok = false;
    }

    if (whatsapp && whatsapp.checked && whatsappShot && (!whatsappShot.files || whatsappShot.files.length === 0)) {
      markInvalid(whatsappShot, 'WhatsApp screenshot is required.');
      ok = false;
    }

    if (!ok) {
      var firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid && typeof firstInvalid.focus === 'function') {
        firstInvalid.focus();
      }
    }

    return ok;
  }
  function updateGuidance(){
    var sel = document.getElementById('call_status');
    var box = document.getElementById('followupGuidance');
    var text = document.getElementById('guidanceText');
    if(!box || !text) return;
    var val = sel ? sel.value : '';
    box.classList.remove('alert-info', 'alert-warning', 'alert-success');
    if(val === 'NO_RESPONSE'){
      box.classList.add('alert-warning');
      text.textContent = 'No response: try a different time, optionally set a next follow-up, mark WhatsApp if contacted, and note any sms/email in notes.';
    } else if(val === 'ASK_CONTACT_LATER'){
      box.classList.add('alert-info');
      text.textContent = 'Asked to contact later: set the next follow-up date/time and note the preference.';
    } else if(val === 'RESPONDED'){
      box.classList.add('alert-success');
      text.textContent = 'Response received: capture interest, select intent/unit type if relevant, and add clear notes.';
    } else {
      box.classList.add('alert-info');
      text.textContent = 'Select call status to see recommended action.';
    }
  }
  function initDateTimePickers(){
    if (!window.flatpickr) return;
    document.querySelectorAll('.flatpickr-datetime').forEach(function(input){
      window.flatpickr(input, {
        enableTime: true,
        dateFormat: "Y-m-d\\TH:i",
        altInput: true,
        altFormat: "M d, Y h:i K",
        time_24hr: false,
        allowInput: true
      });
    });
  }
  function initUploadProgress(form){
    var overlay = document.getElementById('uploadOverlay');
    var bar = document.getElementById('uploadProgressBar');
    var status = document.getElementById('uploadStatus');
    if (!form || !overlay || !bar || !status) return;

    function setProgress(pct){
      var value = Math.max(0, Math.min(100, pct));
      bar.style.width = value + '%';
      bar.setAttribute('aria-valuenow', String(value));
    }
    function setStatus(text){
      status.textContent = text;
    }
    function setDisabled(disabled){
      form.querySelectorAll('input,select,textarea,button').forEach(function(el){
        el.disabled = disabled;
      });
    }
    function showOverlay(){
      overlay.classList.remove('d-none');
    }
    function hideOverlay(){
      overlay.classList.add('d-none');
    }

    form.addEventListener('submit', function(e){
      if (form.dataset.uploading === '1') {
        e.preventDefault();
        return;
      }

      if (!validateFollowupForm(form)) {
        e.preventDefault();
        return;
      }

      if (!window.FormData || !window.XMLHttpRequest) {
        showOverlay();
        setStatus('Uploading...');
        return;
      }

      e.preventDefault();
      form.dataset.uploading = '1';
      var data = new FormData(form);

      showOverlay();
      setProgress(0);
      setStatus('Uploading...');
      setDisabled(true);

      var xhr = new XMLHttpRequest();
      xhr.open((form.method || 'POST').toUpperCase(), form.action, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.onprogress = function(evt){
        if (evt.lengthComputable) {
          var pct = Math.min(95, Math.round((evt.loaded / evt.total) * 100));
          setProgress(pct);
          setStatus('Uploading ' + pct + '%');
        } else {
          setStatus('Uploading...');
        }
      };
      xhr.upload.onload = function(){
        setProgress(100);
        setStatus('Processing image...');
      };
      xhr.onload = function(){
        if (xhr.status >= 200 && xhr.status < 400) {
          setProgress(100);
          setStatus('Finishing...');
          var redirectUrl = xhr.responseURL || form.action;
          window.location = redirectUrl;
          return;
        }
        setStatus('Upload failed. Please try again.');
        setDisabled(false);
        form.dataset.uploading = '0';
        setTimeout(hideOverlay, 1200);
      };
      xhr.onerror = function(){
        setStatus('Upload failed. Please try again.');
        setDisabled(false);
        form.dataset.uploading = '0';
        setTimeout(hideOverlay, 1200);
      };

      xhr.send(data);
    });
  }
  function onReady(fn){
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
      return;
    }
    fn();
  }
  onReady(function(){
    showHideConditional(); whatsappToggle(); notesCounter(); updateGuidance(); validateNoResponseChannel(); initDateTimePickers();
    var tz = document.getElementById('tz_offset');
    var now = document.getElementById('client_now');
    if (tz) tz.value = String(new Date().getTimezoneOffset());
    if (now) now.value = String(Date.now());
    var form = document.querySelector('form[action*="agent/followup"]');
    if (form) {
      form.addEventListener('submit', function(){
        if (tz) tz.value = String(new Date().getTimezoneOffset());
        if (now) now.value = String(Date.now());
      });
    }
    var isel = document.getElementById('interested_status'); if(isel) isel.addEventListener('change', showHideConditional);
    var csel = document.getElementById('call_status');
    if(csel){
      csel.addEventListener('change', function(){
        showHideConditional();
        updateGuidance();
        validateNoResponseChannel();
      });
    }
    var intent = document.getElementById('intent'); if(intent) intent.addEventListener('change', showHideConditional);
    var buyType = document.getElementById('buy_property_type'); if(buyType) buyType.addEventListener('change', showHideConditional);
    var wcb = document.getElementById('whatsapp_contacted'); if(wcb) wcb.addEventListener('change', function(){ whatsappToggle(); validateNoResponseChannel(); });
    var notes = document.getElementById('notes'); if(notes) notes.addEventListener('input', function(){ notesCounter(); validateNoResponseChannel(); });
    var launch = document.getElementById('launch_at');
    var useLaunch = document.getElementById('use_launch_as_followup');
    function syncLaunchToFollowup(){
      var nextInput = document.getElementById('next_followup_at');
      if (!nextInput || !launch || !useLaunch) return;
      if (useLaunch.checked) {
        if (launch.value) nextInput.value = launch.value;
        nextInput.readOnly = true;
      } else {
        nextInput.readOnly = false;
      }
    }
    if (launch) launch.addEventListener('change', syncLaunchToFollowup);
    if (useLaunch) useLaunch.addEventListener('change', syncLaunchToFollowup);

    initUploadProgress(form);
  });
})();
