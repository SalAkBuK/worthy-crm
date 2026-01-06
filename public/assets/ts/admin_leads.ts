type LeadRow = {
  lead_name: string;
  contact_email: string;
  interested_in_property: string;
  property_type: 'OFF_PLAN'|'READY_TO_MOVE';
  assigned_agent_user_id: string;
};

function $(sel: string, root: Document|HTMLElement = document): HTMLElement {
  const el = root.querySelector(sel);
  if(!el) throw new Error('Missing element: ' + sel);
  return el as HTMLElement;
}

function cloneTemplateRow(): HTMLTableRowElement {
  const tpl = document.getElementById('leadRowTemplate') as HTMLTemplateElement;
  const frag = tpl.content.cloneNode(true) as DocumentFragment;
  return frag.querySelector('tr') as HTMLTableRowElement;
}

function updateRowNames() {
  const tbody = document.getElementById('leadRows') as HTMLTableSectionElement;
  Array.from(tbody.querySelectorAll('tr')).forEach((tr, idx) => {
    tr.querySelectorAll<HTMLInputElement|HTMLSelectElement>('input,select').forEach((input) => {
      const base = input.getAttribute('data-base')!;
      input.name = `rows[${idx}][${base}]`;
    });
    const idxCell = tr.querySelector('[data-idx]') as HTMLElement;
    if(idxCell) idxCell.textContent = String(idx + 1);
  });
}

function addRow() {
  const tbody = document.getElementById('leadRows') as HTMLTableSectionElement;
  const row = cloneTemplateRow();
  tbody.appendChild(row);
  updateRowNames();
}

function removeRow(btn: HTMLElement) {
  const tr = btn.closest('tr');
  if(tr) tr.remove();
  updateRowNames();
}

document.addEventListener('click', (e) => {
  const t = e.target as HTMLElement;
  if(t && t.matches('[data-add-row]')){
    e.preventDefault(); addRow();
  }
  if(t && t.matches('[data-remove-row]')){
    e.preventDefault(); removeRow(t);
  }
  if(t && t.matches('[data-apply-assign]')){
    e.preventDefault();
    const assignSelect = document.querySelector('[data-assign-all]') as HTMLSelectElement | null;
    if (!assignSelect) return;
    const agentId = assignSelect.value;
    const tbody = document.getElementById('leadRows') as HTMLTableSectionElement;
    Array.from(tbody.querySelectorAll<HTMLSelectElement>('select[data-base="assigned_agent_user_id"]'))
      .forEach((sel) => { sel.value = agentId; });
  }
  if(t && t.matches('[data-apply-selected]')){
    e.preventDefault();
    const assignSelect = document.querySelector('[data-assign-selected]') as HTMLSelectElement | null;
    if (!assignSelect) return;
    const agentId = assignSelect.value;
    const tbody = document.getElementById('leadRows') as HTMLTableSectionElement;
    Array.from(tbody.querySelectorAll<HTMLTableRowElement>('tr')).forEach((tr) => {
      const chk = tr.querySelector<HTMLInputElement>('[data-bulk-select-item]');
      if (chk && chk.checked) {
        const sel = tr.querySelector<HTMLSelectElement>('select[data-base="assigned_agent_user_id"]');
        if (sel) sel.value = agentId;
      }
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  updateRowNames();
});

document.addEventListener('change', (e) => {
  const t = e.target as HTMLElement;
  if (t && t.matches('[data-select-all]')) {
    const checked = (t as HTMLInputElement).checked;
    document.querySelectorAll<HTMLInputElement>('[data-select-item]').forEach((el) => {
      el.checked = checked;
    });
  }
  if (t && t.matches('[data-bulk-select-all]')) {
    const checked = (t as HTMLInputElement).checked;
    document.querySelectorAll<HTMLInputElement>('[data-bulk-select-item]').forEach((el) => {
      el.checked = checked;
    });
  }
});
