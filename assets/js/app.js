/**
 * app.js
 * Handles: dynamic process rows, client-side validation, scenario presets, quantum controls.
 */

/* ── Process colour palette (shared with gantt.js) ── */
const PROCESS_COLORS = [
  '#6c63ff','#00c9a7','#f7b731','#ff4d6d','#3b82f6',
  '#a855f7','#ec4899','#10b981','#f97316','#06b6d4',
  '#84cc16','#8b5cf6','#14b8a6','#f43f5e','#0ea5e9',
];
window.PROCESS_COLORS = PROCESS_COLORS;

const pidColorMap = {};
let colorIdx = 0;
function colorForPid(pid) {
  if (!pidColorMap[pid]) {
    pidColorMap[pid] = PROCESS_COLORS[colorIdx % PROCESS_COLORS.length];
    colorIdx++;
  }
  return pidColorMap[pid];
}
window.colorForPid = colorForPid;

let rowCounter = 0;

/* ── Update process counter badge ── */
function updateCounter() {
  const count = document.querySelectorAll('.process-row').length;
  const el = document.getElementById('proc-count');
  if (el) el.textContent = count;
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Add a new card-style process row ── */
function addRow(pid = '', arrival = '', burst = '') {
  rowCounter++;
  const wrap = document.getElementById('process-table-body');
  const div = document.createElement('div');
  div.className = 'pi-row process-row';
  div.dataset.row = rowCounter;

  // Pick a colour for the dot
  const colors = window.PROCESS_COLORS;
  const dotColor = colors[(rowCounter - 1) % colors.length];

  div.innerHTML = `
    <div class="pi-row-num">
      <span class="pi-dot" style="background:${dotColor};box-shadow:0 0 8px ${dotColor}66"></span>
    </div>
    <div class="pi-row-field">
      <input type="text" class="pi-input pid-input" name="pid[]"
             placeholder="P${rowCounter}" value="${escHtml(pid)}"
             maxlength="10" required>
    </div>
    <div class="pi-row-field">
      <div class="pi-input-wrap">
        <span class="pi-input-icon">🕐</span>
        <input type="number" class="pi-input" name="arrival[]"
               placeholder="0" min="0" value="${escHtml(String(arrival))}" required>
      </div>
    </div>
    <div class="pi-row-field">
      <div class="pi-input-wrap">
        <span class="pi-input-icon">⚡</span>
        <input type="number" class="pi-input" name="burst[]"
               placeholder="1" min="1" value="${escHtml(String(burst))}" required>
      </div>
    </div>
    <div class="pi-row-del">
      <button type="button" class="pi-del-btn" onclick="removeRow(this)" title="Remove">✕</button>
    </div>`;

  wrap.appendChild(div);
  div.querySelector('.pid-input').focus();
  updateCounter();
}

function removeRow(btn) {
  const row = btn.closest('.pi-row');
  row.style.opacity = '0';
  row.style.transform = 'translateX(24px) scale(0.97)';
  row.style.transition = 'all .22s ease';
  setTimeout(() => { row.remove(); updateCounter(); }, 220);
}

function clearAllRows() {
  const wrap = document.getElementById('process-table-body');
  wrap.innerHTML = '';
  rowCounter = 0;
  updateCounter();
}

/* ── Quantum controls ── */
function updateQuantumBadge() {
  const q   = parseInt(document.getElementById('quantum-input')?.value, 10);
  const badge = document.getElementById('quantum-badge');
  const desc  = document.getElementById('quantum-desc');
  if (badge) badge.textContent = isNaN(q) || q <= 0 ? 'Q = ?' : `Q = ${q}`;
  if (desc) {
    if (isNaN(q) || q <= 0) { desc.textContent = '⚠ Enter a valid quantum'; return; }
    if (q <= 2)  desc.textContent = '⚡ Very high fairness, high overhead';
    else if (q <= 5)  desc.textContent = '✅ Balanced — recommended';
    else if (q <= 10) desc.textContent = '⚖ Moderate, fewer switches';
    else              desc.textContent = '🔁 Large — approaches FCFS';
  }
  // Sync slider
  const slider = document.getElementById('quantum-slider');
  if (slider && !isNaN(q)) slider.value = Math.min(q, 20);
}

function adjustQuantum(delta) {
  const qi = document.getElementById('quantum-input');
  if (!qi) return;
  const cur = parseInt(qi.value, 10) || 0;
  qi.value = Math.max(1, cur + delta);
  updateQuantumBadge();
}

/* ── Client-side validation ── */
function validateForm(e) {
  const errors = [];
  const rows = document.querySelectorAll('.process-row');

  if (rows.length === 0) errors.push('Add at least one process before simulating.');

  const pids = new Set();
  rows.forEach((row, i) => {
    const pid     = row.querySelector('input[name="pid[]"]').value.trim();
    const arrival = row.querySelector('input[name="arrival[]"]').value.trim();
    const burst   = row.querySelector('input[name="burst[]"]').value.trim();

    if (!pid) errors.push(`Row ${i+1}: Process ID is required.`);
    else if (pids.has(pid.toUpperCase())) errors.push(`Row ${i+1}: Duplicate Process ID "${pid}".`);
    else pids.add(pid.toUpperCase());

    if (arrival === '') errors.push(`Row ${i+1} (${pid||'?'}): Arrival Time is required.`);
    else if (parseInt(arrival,10) < 0) errors.push(`Row ${i+1} (${pid||'?'}): Arrival Time cannot be negative.`);

    if (burst === '') errors.push(`Row ${i+1} (${pid||'?'}): Burst Time is required.`);
    else if (parseInt(burst,10) <= 0) errors.push(`Row ${i+1} (${pid||'?'}): Burst Time must be > 0.`);
  });

  const q = document.getElementById('quantum-input')?.value.trim();
  if (!q) errors.push('Quantum is required.');
  else if (parseInt(q,10) <= 0) errors.push('Quantum must be > 0.');

  const errBox = document.getElementById('client-errors');
  if (errors.length > 0) {
    e.preventDefault();
    errBox.innerHTML = `<div class="alert-glass"><strong>⚠ Please fix the following errors:</strong><ul>${errors.map(er=>`<li>${escHtml(er)}</li>`).join('')}</ul></div>`;
    errBox.scrollIntoView({ behavior:'smooth', block:'center' });
    return false;
  }
  errBox.innerHTML = '';
  return true;
}

/* ── Scenario presets ── */
const SCENARIOS = {
  normal: {
    quantum: 3,
    processes: [
      { pid:'P1', arrival:0, burst:8 },
      { pid:'P2', arrival:1, burst:4 },
      { pid:'P3', arrival:2, burst:9 },
      { pid:'P4', arrival:3, burst:5 },
    ]
  },
  fairness: {
    quantum: 4,
    processes: [
      { pid:'P1', arrival:0, burst:20 },
      { pid:'P2', arrival:1, burst:3  },
      { pid:'P3', arrival:2, burst:3  },
      { pid:'P4', arrival:3, burst:3  },
      { pid:'P5', arrival:4, burst:2  },
    ]
  },
};

function loadScenario(key) {
  const sc = SCENARIOS[key];
  if (!sc) return;
  document.querySelectorAll('.scenario-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`.scenario-btn[data-scenario="${key}"]`)?.classList.add('active');
  clearAllRows();
  sc.processes.forEach(p => addRow(p.pid, p.arrival, p.burst));
  const qi = document.getElementById('quantum-input');
  if (qi) { qi.value = sc.quantum; updateQuantumBadge(); }
}

/* ── DOM Ready ── */
document.addEventListener('DOMContentLoaded', () => {
  // Default rows
  ['P1','P2','P3','P4'].forEach((pid, i) => addRow(pid, i, ''));

  // Quantum input ↔ badge ↔ slider
  const qi     = document.getElementById('quantum-input');
  const slider = document.getElementById('quantum-slider');
  if (qi)     qi.addEventListener('input', updateQuantumBadge);
  if (slider) slider.addEventListener('input', () => {
    if (qi) qi.value = slider.value;
    updateQuantumBadge();
  });
  updateQuantumBadge();

  // Form validation
  document.getElementById('scheduler-form')?.addEventListener('submit', validateForm);

  // Scenario buttons
  document.querySelectorAll('.scenario-btn').forEach(btn => {
    btn.addEventListener('click', () => loadScenario(btn.dataset.scenario));
  });

  // Add row
  document.getElementById('add-row-btn')?.addEventListener('click', () => addRow());
});
