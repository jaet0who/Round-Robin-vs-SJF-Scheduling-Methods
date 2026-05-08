/**
 * gantt.js
 * Pure CSS/JS Gantt chart renderer.
 */

function renderGantt(containerId, timeline, accentColor) {
  const container = document.getElementById(containerId);
  if (!container || !timeline || timeline.length === 0) return;

  const totalTime = Math.max(...timeline.map(s => s.end));
  const maxWidth  = container.clientWidth || 800;
  const minPx = 28;
  const autoPx = Math.max(minPx, Math.floor((maxWidth - 40) / totalTime));
  const PX = Math.min(autoPx, 64);

  // Fill gaps (idle CPU)
  const filled = [];
  for (let i = 0; i < timeline.length; i++) {
    if (i === 0 && timeline[i].start > 0) {
      filled.push({ pid: '__idle__', start: 0, end: timeline[i].start });
    }
    filled.push(timeline[i]);
    if (i < timeline.length - 1 && timeline[i].end < timeline[i+1].start) {
      filled.push({ pid: '__idle__', start: timeline[i].end, end: timeline[i+1].start });
    }
  }

  const blocksDiv = document.createElement('div');
  blocksDiv.className = 'gantt-blocks';

  filled.forEach((seg, idx) => {
    const dur   = seg.end - seg.start;
    const block = document.createElement('div');
    block.className = 'gantt-block';
    block.style.width = (dur * PX) + 'px';
    block.style.animationDelay = (idx * 0.04) + 's';

    if (seg.pid === '__idle__') {
      block.classList.add('idle');
      block.textContent = 'idle';
    } else {
      const color = window.colorForPid ? window.colorForPid(seg.pid) : accentColor;
      block.style.background = color;
      block.textContent = seg.pid;
      block.title = `${seg.pid}: [${seg.start}→${seg.end}] (${dur}u)`;
    }
    blocksDiv.appendChild(block);
  });

  // Time axis
  const axisDiv = document.createElement('div');
  axisDiv.className = 'gantt-axis';
  axisDiv.style.width = (totalTime * PX) + 'px';

  const ticks = new Set([0]);
  filled.forEach(s => { ticks.add(s.start); ticks.add(s.end); });
  ticks.forEach(t => {
    const tick = document.createElement('div');
    tick.className = 'gantt-tick';
    tick.textContent = t;
    tick.style.left = (t * PX) + 'px';
    axisDiv.appendChild(tick);
  });

  container.innerHTML = '';
  const wrapper = document.createElement('div');
  wrapper.className = 'gantt-wrapper';
  const chart = document.createElement('div');
  chart.className = 'gantt-chart';
  chart.appendChild(blocksDiv);
  chart.appendChild(axisDiv);
  wrapper.appendChild(chart);
  container.appendChild(wrapper);
}

function renderQueueSnapshots(containerId, snapshots) {
  const container = document.getElementById(containerId);
  if (!container || !snapshots || snapshots.length === 0) {
    if (container) container.innerHTML = '<p style="color:var(--text-muted);font-size:.85rem;">No queue data.</p>';
    return;
  }

  const selectEl = document.createElement('select');
  selectEl.className = 'form-select form-select-sm mb-3';
  selectEl.style.cssText = 'max-width:320px;background:rgba(255,255,255,0.05);color:var(--text-primary);border:1px solid var(--border)';

  const displayDiv = document.createElement('div');
  displayDiv.className = 'queue-display';

  function renderSnapshot(idx) {
    const snap = snapshots[idx];
    displayDiv.innerHTML = '';
    const runPill = document.createElement('span');
    runPill.className = 'queue-pill queue-running';
    runPill.textContent = '▶ ' + snap.running;
    if (window.colorForPid) runPill.style.background = window.colorForPid(snap.running);
    displayDiv.appendChild(runPill);

    if (snap.queue.length > 0) {
      const arrow = document.createElement('span');
      arrow.className = 'queue-arrow';
      arrow.textContent = '←';
      displayDiv.appendChild(arrow);
      snap.queue.forEach(pid => {
        const pill = document.createElement('span');
        pill.className = 'queue-pill';
        const c = window.colorForPid ? window.colorForPid(pid) : '#6c63ff';
        pill.style.cssText = `background:${c}33;border-color:${c}88;color:${c}`;
        pill.textContent = pid;
        displayDiv.appendChild(pill);
      });
    } else {
      const empty = document.createElement('span');
      empty.style.cssText = 'color:var(--text-muted);font-size:.8rem';
      empty.textContent = '(queue empty)';
      displayDiv.appendChild(empty);
    }
  }

  snapshots.forEach((snap, i) => {
    const opt = document.createElement('option');
    opt.value = i;
    opt.textContent = `t=${snap.time} — Running: ${snap.running} | Queue: [${snap.queue.join(', ')||'empty'}]`;
    selectEl.appendChild(opt);
  });

  selectEl.addEventListener('change', () => renderSnapshot(parseInt(selectEl.value,10)));
  renderSnapshot(0);
  container.innerHTML = '';
  container.appendChild(selectEl);
  container.appendChild(displayDiv);
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.RR_TIMELINE)        renderGantt('rr-gantt-container', window.RR_TIMELINE, '#6c63ff');
  if (window.SJF_TIMELINE)       renderGantt('sjf-gantt-container', window.SJF_TIMELINE, '#00c9a7');
  if (window.RR_QUEUE_SNAPSHOTS) renderQueueSnapshots('rr-queue-container', window.RR_QUEUE_SNAPSHOTS);
});
