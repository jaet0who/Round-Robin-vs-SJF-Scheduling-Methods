<?php
session_start();
if (empty($_SESSION['results'])) {
    header('Location: index.php');
    exit;
}
$r       = $_SESSION['results'];
$quantum = $r['quantum'];
$input   = $r['process_input'];
$rrT     = $r['rr']['timeline'];
$rrQ     = $r['rr']['queue_snapshots'];
$rrM     = $r['rr']['metrics'];
$sjfT    = $r['sjf']['timeline'];
$sjfM    = $r['sjf']['metrics'];

// Helper: render a results table
function metricsTable(array $metrics, string $accentVar): string {
    $rows = '';
    foreach ($metrics['per_process'] as $p) {
        $rows .= "<tr>
            <td><span class='pid-badge' style='background:{$accentVar}33;border:1px solid {$accentVar}66;color:{$accentVar}'>{$p['pid']}</span></td>
            <td>{$p['at']}</td><td>{$p['bt']}</td><td>{$p['ft']}</td>
            <td>{$p['tat']}</td><td>{$p['wt']}</td><td>{$p['rt']}</td>
        </tr>";
    }
    return "
    <div class='table-responsive'>
    <table class='results-table table-dark-custom'>
      <thead><tr>
        <th>PID</th><th>AT</th><th>BT</th><th>FT</th>
        <th>TAT</th><th>WT</th><th>RT</th>
      </tr></thead>
      <tbody>{$rows}</tbody>
      <tfoot><tr>
        <td colspan='4' style='text-align:right;color:var(--text-secondary);font-size:.78rem'>Averages →</td>
        <td>{$metrics['avg_tat']}</td>
        <td>{$metrics['avg_wt']}</td>
        <td>{$metrics['avg_rt']}</td>
      </tr></tfoot>
    </table>
    </div>
    <div class='d-flex gap-3 mt-3 flex-wrap'>
      <span style='font-size:.82rem;color:var(--text-secondary)'>
        🖥 CPU Utilization: <strong style='color:var(--accent-warn)'>{$metrics['cpu_utilization']}%</strong>
      </span>
      <span style='font-size:.82rem;color:var(--text-secondary)'>
        ⚡ Throughput: <strong style='color:var(--accent-warn)'>{$metrics['throughput']} proc/unit</strong>
      </span>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Results — CPU Scheduling Comparison</title>
  <meta name="description" content="CPU scheduling simulation results: Gantt charts, metrics tables, and algorithm comparison for Round Robin vs SJF Preemptive.">
  <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark sticky-top">
  <div class="container">
    <span class="navbar-brand fw-800 fs-5">⚙ CPU<span>Sched</span></span>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn-outline-light-custom py-1 px-3 text-decoration-none" style="font-size:.82rem">← New Simulation</a>
      <a href="export.php" class="btn-sjf py-1 px-3 text-decoration-none" style="font-size:.82rem">⬇ Export CSV</a>
    </div>
  </div>
</nav>

<div class="container py-4">

  <!-- HERO -->
  <div class="hero" style="padding:2rem 0 1.5rem">
    <h1>Simulation Results</h1>
    <p>Round Robin (Q=<?= $quantum ?>) vs SJF Preemptive — <?= count($input) ?> processes</p>
  </div>

  <!-- INPUT SUMMARY -->
  <div class="glass-card mb-section">
    <div class="section-title">📋 Input Summary</div>
    <div class="d-flex gap-3 flex-wrap mb-3">
      <span class="quantum-badge">Quantum Q = <?= $quantum ?></span>
      <span style="color:var(--text-secondary);font-size:.85rem">Processes: <?= count($input) ?></span>
    </div>
    <div class="table-responsive">
      <table class="results-table">
        <thead><tr>
          <th>Process ID</th><th>Arrival Time</th><th>Burst Time</th>
        </tr></thead>
        <tbody>
          <?php foreach ($input as $p): ?>
          <tr>
            <td><span class="pid-badge mono" style="background:rgba(255,255,255,.08);color:var(--text-primary)"><?= htmlspecialchars($p['pid']) ?></span></td>
            <td><?= $p['at'] ?></td>
            <td><?= $p['bt'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TABS: RR | SJF | Comparison -->
  <div class="glass-card mb-section">
    <div class="custom-tabs" id="main-tabs">
      <button class="custom-tab active" data-tab="tab-rr">🔵 Round Robin</button>
      <button class="custom-tab" data-tab="tab-sjf">🟢 SJF Preemptive</button>
      <button class="custom-tab" data-tab="tab-compare">📊 Comparison</button>
      <button class="custom-tab" data-tab="tab-conclusion">🏆 Conclusion</button>
    </div>

    <!-- ══════════ TAB: ROUND ROBIN ══════════ -->
    <div class="tab-pane active" id="tab-rr">

      <div class="section-title text-rr">Round Robin — Gantt Chart <small style="font-weight:400;color:var(--text-muted);font-size:.78rem">(Q = <?= $quantum ?>)</small></div>
      <div id="rr-gantt-container" style="min-height:80px"></div>

      <div class="divider"></div>

      <!-- RR Ready Queue -->
      <div class="section-title text-rr">Ready Queue Snapshots <small style="font-weight:400;color:var(--text-muted);font-size:.78rem">(at each dispatch)</small></div>
      <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:.75rem">Select a time point to see which processes were in the ready queue.</p>
      <div id="rr-queue-container"></div>

      <div class="divider"></div>

      <!-- RR Metrics Table -->
      <div class="section-title text-rr">Round Robin — Metrics Table</div>
      <p style="color:var(--text-muted);font-size:.8rem;margin-bottom:.75rem">AT=Arrival, BT=Burst, FT=Finish, TAT=Turnaround, WT=Waiting, RT=Response</p>
      <?= metricsTable($rrM, '#6c63ff') ?>
    </div>

    <!-- ══════════ TAB: SJF ══════════ -->
    <div class="tab-pane" id="tab-sjf">

      <div class="section-title sjf-title text-sjf">SJF Preemptive (SRTF) — Gantt Chart</div>
      <div id="sjf-gantt-container" style="min-height:80px"></div>

      <div class="divider"></div>

      <!-- SJF Metrics Table -->
      <div class="section-title sjf-title text-sjf">SJF Preemptive — Metrics Table</div>
      <p style="color:var(--text-muted);font-size:.8rem;margin-bottom:.75rem">AT=Arrival, BT=Burst, FT=Finish, TAT=Turnaround, WT=Waiting, RT=Response</p>
      <?= metricsTable($sjfM, '#00c9a7') ?>

      <div class="glass-card mt-3" style="background:rgba(0,201,167,.06);border-color:rgba(0,201,167,.2)">
        <p style="color:var(--text-secondary);font-size:.85rem;margin:0">
          <strong style="color:#5eead4">ℹ SJF Preemptive (SRTF):</strong>
          At every time unit the process with the <em>shortest remaining burst time</em> is selected.
          If a new process arrives with a shorter remaining burst, it preempts the current process.
          Ties are broken by <strong>earliest arrival time</strong>, then by <strong>Process ID</strong>.
        </p>
      </div>
    </div>

    <!-- ══════════ TAB: COMPARISON ══════════ -->
    <div class="tab-pane" id="tab-compare">
      <div class="section-title">📊 Side-by-Side Comparison</div>

      <!-- Average metrics summary row -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div style="background:rgba(108,99,255,.08);border:1px solid rgba(108,99,255,.2);border-radius:10px;padding:1.25rem">
            <div style="color:#a5b4fc;font-weight:700;margin-bottom:.75rem">🔵 Round Robin (Q=<?= $quantum ?>)</div>
            <div class="d-flex gap-3 flex-wrap">
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg WT</div><div class="mono fw-700"><?= $rrM['avg_wt'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg TAT</div><div class="mono fw-700"><?= $rrM['avg_tat'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg RT</div><div class="mono fw-700"><?= $rrM['avg_rt'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">CPU Util.</div><div class="mono fw-700"><?= $rrM['cpu_utilization'] ?>%</div></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div style="background:rgba(0,201,167,.07);border:1px solid rgba(0,201,167,.2);border-radius:10px;padding:1.25rem">
            <div style="color:#5eead4;font-weight:700;margin-bottom:.75rem">🟢 SJF Preemptive</div>
            <div class="d-flex gap-3 flex-wrap">
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg WT</div><div class="mono fw-700"><?= $sjfM['avg_wt'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg TAT</div><div class="mono fw-700"><?= $sjfM['avg_tat'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">Avg RT</div><div class="mono fw-700"><?= $sjfM['avg_rt'] ?></div></div>
              <div><div style="font-size:.72rem;color:var(--text-muted)">CPU Util.</div><div class="mono fw-700"><?= $sjfM['cpu_utilization'] ?>%</div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Animated comparison cards (built by comparison.js) -->
      <div class="comparison-grid">
        <div class="comparison-card" id="cmp-avg-wt"></div>
        <div class="comparison-card" id="cmp-avg-tat"></div>
        <div class="comparison-card" id="cmp-avg-rt"></div>
        <div class="comparison-card" id="cmp-cpu"></div>
        <div class="comparison-card" id="cmp-tp"></div>
        <div class="comparison-card" id="cmp-fairness"></div>
      </div>

      <!-- Analysis text table -->
      <div class="divider"></div>
      <div class="section-title">🔬 Algorithm Characteristics</div>
      <div class="table-responsive">
        <table class="results-table">
          <thead><tr>
            <th style="text-align:left">Characteristic</th>
            <th>Round Robin</th>
            <th>SJF Preemptive</th>
          </tr></thead>
          <tbody>
            <tr><td style="text-align:left;color:var(--text-secondary)">Scheduling Type</td><td>Preemptive (time-slice)</td><td>Preemptive (burst-based)</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Starvation Risk</td><td style="color:#5eead4">None</td><td style="color:#f7b731">High (long jobs)</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Optimal Avg WT</td><td style="color:#f7b731">No</td><td style="color:#5eead4">Yes (provably)</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Fairness</td><td style="color:#5eead4">High (guaranteed)</td><td style="color:#f7b731">Low (favors short)</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Context Switches</td><td style="color:#f7b731">High (Q=<?= $quantum ?>)</td><td style="color:#5eead4">Lower</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Best Use Case</td><td>Interactive / Time-sharing</td><td>Batch / Throughput</td></tr>
            <tr><td style="text-align:left;color:var(--text-secondary)">Quantum Impact</td><td>Q=<?= $quantum ?> — <?= $quantum<=2?'very high overhead':($quantum>=10?'near-FCFS':'balanced') ?></td><td>N/A</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══════════ TAB: CONCLUSION ══════════ -->
    <div class="tab-pane" id="tab-conclusion">
      <div class="conclusion-card">
        <h4>🏆 Final Analysis &amp; Recommendation</h4>
        <div id="conclusion-text">
          <p style="color:var(--text-muted)">Loading analysis…</p>
        </div>
      </div>
    </div>

  </div><!-- glass-card tabs -->

  <!-- Back + Export -->
  <div class="d-flex gap-3 mb-section flex-wrap">
    <a href="index.php" class="btn-rr text-decoration-none px-4 py-2">← New Simulation</a>
    <a href="export.php" class="btn-sjf text-decoration-none px-4 py-2">⬇ Export CSV</a>
  </div>

</div><!-- container -->

<footer class="text-center py-4" style="border-top:1px solid var(--border);color:var(--text-muted);font-size:.8rem">
  CPU Scheduling Comparison System &nbsp;|&nbsp; Round Robin vs SJF Preemptive &nbsp;|&nbsp; Pure PHP + Bootstrap
</footer>

<!-- Inject PHP data into JS -->
<script>
window.RR_TIMELINE        = <?= json_encode($rrT) ?>;
window.SJF_TIMELINE       = <?= json_encode($sjfT) ?>;
window.RR_QUEUE_SNAPSHOTS = <?= json_encode($rrQ) ?>;
window.METRICS_RR         = <?= json_encode(array_merge($rrM, ['per_process' => $rrM['per_process']])) ?>;
window.METRICS_SJF        = <?= json_encode(array_merge($sjfM, ['per_process' => $sjfM['per_process']])) ?>;
window.QUANTUM            = <?= (int)$quantum ?>;
</script>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/gantt.js"></script>
<script src="assets/js/comparison.js"></script>
<script>
// Tab switching
document.querySelectorAll('.custom-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.custom-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.tab).classList.add('active');
    // Re-render Gantt on tab switch (container may have had 0 width initially)
    if (tab.dataset.tab === 'tab-rr' && window.RR_TIMELINE) {
      setTimeout(() => renderGantt('rr-gantt-container', window.RR_TIMELINE, '#6c63ff'), 50);
    }
    if (tab.dataset.tab === 'tab-sjf' && window.SJF_TIMELINE) {
      setTimeout(() => renderGantt('sjf-gantt-container', window.SJF_TIMELINE, '#00c9a7'), 50);
    }
  });
});
</script>
</body>
</html>
