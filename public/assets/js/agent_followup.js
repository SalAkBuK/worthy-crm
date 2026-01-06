(function(){
  function showHideConditional(){
    var callSel = document.getElementById('call_status');
    var callStatus = callSel ? callSel.value : '';
    var interestedSel = document.getElementById('interested_status');
    var interested = interestedSel ? interestedSel.value : '';
    var interestedWrap = document.getElementById('interestedStatusWrap');
    var interestedBox = document.getElementById('ifInterested');
    var nextWrap = document.getElementById('nextFollowupWrap');
    var nextInput = document.getElementById('next_followup_at');
    var intentSel = document.getElementById('intent');
    var buyTypeWrap = document.getElementById('buyPropertyTypeWrap');
    var buyTypeSel = document.getElementById('buy_property_type');
    var buyReady = document.getElementById('buyReadyFields');
    var buyOffplan = document.getElementById('buyOffplanFields');
    var rentFields = document.getElementById('rentFields');
    var unitBuy = document.getElementById('unit_type_buy');
    var unitRent = document.getElementById('unit_type_rent');
    function toggleSection(section, enabled){
      if (!section) return;
      section.classList.toggle('d-none', !enabled);
      section.querySelectorAll('input,select,textarea').forEach(function(el){
        el.disabled = !enabled;
      });
    }

    var shouldShowInterested = callStatus === 'RESPONDED';
    if (nextWrap && nextInput) {
      var showNext = callStatus === 'ASK_CONTACT_LATER';
      nextWrap.classList.toggle('d-none', !showNext);
      nextInput.required = showNext;
      nextInput.disabled = !showNext;
      if (!showNext) nextInput.value = '';
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
      if (interestedBox) interestedBox.classList.add('d-none');
      if (buyTypeWrap) { buyTypeWrap.classList.add('d-none'); if (buyTypeSel) buyTypeSel.disabled = true; }
      toggleSection(buyReady, false);
      toggleSection(buyOffplan, false);
      toggleSection(rentFields, false);
      return;
    }

    if (interestedSel) interestedSel.required = true;
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
        notes.setCustomValidity('For NO_RESPONSE, mark WhatsApp contacted or mention another channel (sms/email) in notes.');
        return;
      }
    }
    notes.setCustomValidity('');
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
      text.textContent = 'No response: try a different time, mark WhatsApp if contacted, and note any sms/email in notes.';
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
  document.addEventListener('DOMContentLoaded', function(){
    showHideConditional(); whatsappToggle(); notesCounter(); updateGuidance(); validateNoResponseChannel();
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
  });
})();
