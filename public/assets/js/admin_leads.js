function cloneTemplateRow(){
  var tpl = document.getElementById('leadRowTemplate');
  var frag = tpl.content.cloneNode(true);
  return frag.querySelector('tr');
}
function updateRowNames(){
  var tbody = document.getElementById('leadRows');
  Array.from(tbody.querySelectorAll('tr')).forEach(function(tr, idx){
    tr.querySelectorAll('input,select').forEach(function(input){
      var base = input.getAttribute('data-base');
      input.name = 'rows[' + idx + '][' + base + ']';
    });
    var idxCell = tr.querySelector('[data-idx]');
    if(idxCell) idxCell.textContent = String(idx + 1);
  });
}
function addRow(){
  var tbody = document.getElementById('leadRows');
  var row = cloneTemplateRow();
  tbody.appendChild(row);
  updateRowNames();
}
function removeRow(btn){
  var tr = btn.closest('tr');
  if(tr) tr.remove();
  updateRowNames();
}
function applyAssignAll(){
  var assignSelect = document.querySelector('[data-assign-all]');
  if(!assignSelect) return;
  var agentId = assignSelect.value;
  var tbody = document.getElementById('leadRows');
  Array.from(tbody.querySelectorAll('select[data-base="assigned_agent_user_id"]')).forEach(function(sel){
    sel.value = agentId;
  });
}
function applyAssignSelected(){
  var assignSelect = document.querySelector('[data-assign-selected]');
  if(!assignSelect) return;
  var agentId = assignSelect.value;
  var tbody = document.getElementById('leadRows');
  Array.from(tbody.querySelectorAll('tr')).forEach(function(tr){
    var chk = tr.querySelector('[data-bulk-select-item]');
    if(chk && chk.checked){
      var sel = tr.querySelector('select[data-base="assigned_agent_user_id"]');
      if(sel) sel.value = agentId;
    }
  });
}
function selectAllLeads(checked){
  document.querySelectorAll('[data-select-item]').forEach(function(el){
    el.checked = checked;
  });
}
function selectAllBulkRows(checked){
  document.querySelectorAll('[data-bulk-select-item]').forEach(function(el){
    el.checked = checked;
  });
}

document.addEventListener('click', function(e){
  var t = e.target;
  if(t && t.matches('[data-add-row]')){ e.preventDefault(); addRow(); }
  if(t && t.matches('[data-remove-row]')){ e.preventDefault(); removeRow(t); }
  if(t && t.matches('[data-apply-assign]')){ e.preventDefault(); applyAssignAll(); }
  if(t && t.matches('[data-apply-selected]')){ e.preventDefault(); applyAssignSelected(); }
});

document.addEventListener('change', function(e){
  var t = e.target;
  if(t && t.matches('[data-select-all]')){
    selectAllLeads(t.checked);
  }
  if(t && t.matches('[data-bulk-select-all]')){
    selectAllBulkRows(t.checked);
  }
});

document.addEventListener('DOMContentLoaded', function(){ updateRowNames(); });
