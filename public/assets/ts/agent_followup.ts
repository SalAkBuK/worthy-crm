function showHideConditional() {
  const interested = (document.getElementById('interested_status') as HTMLSelectElement)?.value;
  const interestedBox = document.getElementById('ifInterested') as HTMLElement;
  const notInterestedBox = document.getElementById('ifNotInterested') as HTMLElement;

  if(interested === 'INTERESTED'){
    interestedBox.classList.remove('d-none');
    notInterestedBox.classList.add('d-none');
  } else if(interested === 'NOT_INTERESTED'){
    interestedBox.classList.add('d-none');
    notInterestedBox.classList.remove('d-none');
  } else {
    interestedBox.classList.add('d-none');
    notInterestedBox.classList.add('d-none');
  }
}

function whatsappToggle() {
  const cb = document.getElementById('whatsapp_contacted') as HTMLInputElement;
  const box = document.getElementById('whatsappBox') as HTMLElement;
  if(cb?.checked) box.classList.remove('d-none');
  else box.classList.add('d-none');
}

function notesCounter() {
  const ta = document.getElementById('notes') as HTMLTextAreaElement;
  const counter = document.getElementById('notesCount') as HTMLElement;
  if(!ta || !counter) return;
  counter.textContent = String(ta.value.length);
  counter.classList.toggle('text-danger', ta.value.length < 50);
}

document.addEventListener('DOMContentLoaded', () => {
  showHideConditional();
  whatsappToggle();
  notesCounter();
  document.getElementById('interested_status')?.addEventListener('change', showHideConditional);
  document.getElementById('whatsapp_contacted')?.addEventListener('change', whatsappToggle);
  document.getElementById('notes')?.addEventListener('input', notesCounter);
});
