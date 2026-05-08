/**
 * comparison.js
 * Reads window.METRICS_RR and window.METRICS_SJF (injected by PHP).
 * Builds animated bar comparisons, winner badges, and the final recommendation text.
 */

document.addEventListener('DOMContentLoaded', () => {
  const rr  = window.METRICS_RR;
  const sjf = window.METRICS_SJF;
  if (!rr || !sjf) return;

  /* ── Helper: determine winner ── */
  function winner(rrVal, sjfVal, lowerIsBetter = true) {
    const diff = lowerIsBetter ? rrVal - sjfVal : sjfVal - rrVal;
    if (Math.abs(diff) < 0.01) return 'tie';
    return diff > 0 ? 'sjf' : 'rr';
  }

  /* ── Render a metric comparison card ── */
  function renderCard(cardId, metric, rrVal, sjfVal, lowerIsBetter, unit) {
    const card = document.getElementById(cardId);
    if (!card) return;
    const w = winner(rrVal, sjfVal, lowerIsBetter);
    const maxVal = Math.max(rrVal, sjfVal, 0.01);
    const rrPct  = Math.round((rrVal / maxVal) * 100);
    const sjfPct = Math.round((sjfVal/ maxVal) * 100);
    const badgeClass = w === 'rr' ? 'winner-rr' : w === 'sjf' ? 'winner-sjf' : 'winner-tie';
    const badgeText  = w === 'rr' ? '✓ RR Wins' : w === 'sjf' ? '✓ SJF Wins' : '⇔ Tie';

    card.innerHTML = `
      <div class="metric-label">${metric}</div>
      <span class="winner-badge ${badgeClass}">${badgeText}</span>
      <div class="metric-bar-wrap mt-2">
        <span class="metric-bar-label" style="color:var(--accent-rr)">RR</span>
        <div class="metric-bar-bg">
          <div class="metric-bar-fill fill-rr" style="width:0%" data-target="${rrPct}%"></div>
        </div>
        <span class="metric-val">${rrVal}${unit}</span>
      </div>
      <div class="metric-bar-wrap">
        <span class="metric-bar-label" style="color:var(--accent-sjf)">SJF</span>
        <div class="metric-bar-bg">
          <div class="metric-bar-fill fill-sjf" style="width:0%" data-target="${sjfPct}%"></div>
        </div>
        <span class="metric-val">${sjfVal}${unit}</span>
      </div>`;

    // Animate bars after small delay
    setTimeout(() => {
      card.querySelectorAll('.metric-bar-fill').forEach(bar => {
        bar.style.width = bar.dataset.target;
      });
    }, 150);
  }

  /* ── Render all comparison cards ── */
  renderCard('cmp-avg-wt',  'Avg Waiting Time',     rr.avg_wt,  sjf.avg_wt,  true,  '');
  renderCard('cmp-avg-tat', 'Avg Turnaround Time',  rr.avg_tat, sjf.avg_tat, true,  '');
  renderCard('cmp-avg-rt',  'Avg Response Time',    rr.avg_rt,  sjf.avg_rt,  true,  '');
  renderCard('cmp-cpu',     'CPU Utilization',      rr.cpu_utilization, sjf.cpu_utilization, false, '%');
  renderCard('cmp-tp',      'Throughput',           rr.throughput, sjf.throughput, false, '');

  /* ── Fairness: lower std-dev of WT = more fair ── */
  const fairnessEl = document.getElementById('cmp-fairness');
  if (fairnessEl) {
    const rrWTs  = rr.per_process.map(p => p.wt);
    const sjfWTs = sjf.per_process.map(p => p.wt);
    const stdDev = arr => {
      const mean = arr.reduce((a,b)=>a+b,0)/arr.length;
      return Math.sqrt(arr.reduce((s,v)=>s+Math.pow(v-mean,2),0)/arr.length).toFixed(2);
    };
    const rrSD = stdDev(rrWTs), sjfSD = stdDev(sjfWTs);
    const fw = parseFloat(rrSD) <= parseFloat(sjfSD) ? 'rr' : 'sjf';
    const badgeClass = fw === 'rr' ? 'winner-rr' : 'winner-sjf';
    fairnessEl.innerHTML = `
      <div class="metric-label">Fairness (WT Std-Dev ↓ = Fairer)</div>
      <span class="winner-badge ${badgeClass}">${fw==='rr'?'✓ RR Fairer':'✓ SJF Fairer'}</span>
      <div class="metric-bar-wrap mt-2">
        <span class="metric-bar-label" style="color:var(--accent-rr)">RR</span>
        <div class="metric-bar-bg">
          <div class="metric-bar-fill fill-rr" style="width:${Math.round(rrSD/Math.max(rrSD,sjfSD,0.01)*100)}%"></div>
        </div>
        <span class="metric-val">σ=${rrSD}</span>
      </div>
      <div class="metric-bar-wrap">
        <span class="metric-bar-label" style="color:var(--accent-sjf)">SJF</span>
        <div class="metric-bar-bg">
          <div class="metric-bar-fill fill-sjf" style="width:${Math.round(sjfSD/Math.max(rrSD,sjfSD,0.01)*100)}%"></div>
        </div>
        <span class="metric-val">σ=${sjfSD}</span>
      </div>`;
  }

  /* ── Final Recommendation ── */
  const recEl = document.getElementById('conclusion-text');
  if (!recEl) return;

  const wtW   = winner(rr.avg_wt,  sjf.avg_wt,  true);
  const tatW  = winner(rr.avg_tat, sjf.avg_tat, true);
  const rtW   = winner(rr.avg_rt,  sjf.avg_rt,  true);

  const scores = { rr: 0, sjf: 0 };
  [wtW, tatW, rtW].forEach(w => { if (w !== 'tie') scores[w]++; });

  let rec, recClass, recLabel;
  if (scores.rr > scores.sjf) {
    rec = 'rr'; recClass = 'rec-rr'; recLabel = '🏆 Recommendation: Round Robin (RR)';
  } else if (scores.sjf > scores.rr) {
    rec = 'sjf'; recClass = 'rec-sjf'; recLabel = '🏆 Recommendation: SJF Preemptive';
  } else {
    rec = 'tie'; recClass = 'rec-tie'; recLabel = '⇔ Both Algorithms Comparable';
  }

  const quantum = window.QUANTUM || '?';

  recEl.innerHTML = `
    <span class="recommendation-badge ${recClass}">${recLabel}</span>
    <p><strong>Round Robin (Q=${quantum}):</strong> Provides <em>fair CPU time distribution</em> across all processes.
    Every process gets guaranteed CPU time every Q=${quantum} units, preventing starvation.
    This makes RR ideal for <strong>interactive/time-sharing systems</strong> where responsiveness matters.
    Average WT: <strong>${rr.avg_wt}</strong> | Avg TAT: <strong>${rr.avg_tat}</strong> | Avg RT: <strong>${rr.avg_rt}</strong>.</p>
    <br>
    <p><strong>SJF Preemptive (SRTF):</strong> Always selects the process with the <em>shortest remaining burst</em>,
    minimizing average waiting time mathematically (it is provably optimal for avg WT).
    However, long processes may face <strong>starvation</strong> if short jobs keep arriving.
    Average WT: <strong>${sjf.avg_wt}</strong> | Avg TAT: <strong>${sjf.avg_tat}</strong> | Avg RT: <strong>${sjf.avg_rt}</strong>.</p>
    <br>
    <p><strong>Quantum Impact:</strong> With Q=${quantum}, Round Robin causes
    ${quantum <= 2 ? 'high context-switching overhead due to very short quantum' :
      quantum >= 10 ? 'behavior close to FCFS due to large quantum' :
      'moderate context-switching with reasonable responsiveness'}.
    Smaller Q → more fairness, more overhead. Larger Q → less overhead, less fairness.</p>
    <br>
    <p><strong>Long-Job Behavior:</strong> SJF discriminates against long jobs (potential starvation).
    RR treats all processes equally regardless of burst length — a key fairness advantage.</p>
    <br>
    <p><strong>Conclusion:</strong> ${
      rec === 'rr'  ? `For this workload, <strong>Round Robin</strong> achieves better or comparable metrics while guaranteeing fairness. Recommended for general-purpose and interactive environments.` :
      rec === 'sjf' ? `For this workload, <strong>SJF Preemptive</strong> achieves lower waiting and turnaround times. Recommended for batch-processing environments where throughput is the primary goal.` :
      `Both algorithms perform comparably on this workload. Choose <strong>RR</strong> for fairness and interactivity; choose <strong>SJF</strong> for optimal average waiting time in batch systems.`
    }</p>`;
});
