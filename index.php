<?php
session_start();
$errors   = $_SESSION['errors']   ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

// Restore previous input values if any
$oldPids     = $formData['pid']     ?? [];
$oldArrivals = $formData['arrival'] ?? [];
$oldBursts   = $formData['burst']   ?? [];
$oldQuantum  = $formData['quantum'] ?? 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CPU Scheduling Comparison — RR vs SJF</title>
  <meta name="description" content="Interactive CPU Scheduling Comparison System. Compare Round Robin and Preemptive SJF algorithms with Gantt charts and performance metrics.">
  <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════ NAVBAR ═══════════════════════════════ -->
<nav class="navbar navbar-dark sticky-top">
  <div class="container">
    <span class="navbar-brand fw-800 fs-5">
      ⚙ CPU<span>Sched</span>
    </span>
    <div class="d-flex align-items-center gap-2">
      <span class="nav-badge" style="background:rgba(108,99,255,.25);color:#a5b4fc;border:1px solid rgba(108,99,255,.35)">Round Robin</span>
      <span class="nav-badge" style="background:rgba(0,201,167,.15);color:#5eead4;border:1px solid rgba(0,201,167,.3)">SJF Preemptive</span>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════ HERO ════════════════════════════════ -->
<div class="container">
  <div class="hero">
    <h1>CPU Scheduling Comparison</h1>
    <p>Compare <strong style="color:#a5b4fc">Round Robin</strong> &amp; <strong style="color:#5eead4">Preemptive SJF</strong> on the same workload — Gantt charts, metrics &amp; analysis</p>
  </div>

  <!-- Steps bar -->
  <div class="steps-bar mb-4">
    <div class="step-item active">
      <div class="step-num">1</div>
      <span>Input Processes</span>
    </div>
    <div class="step-divider"></div>
    <div class="step-item">
      <div class="step-num">2</div>
      <span>Run Simulation</span>
    </div>
    <div class="step-divider"></div>
    <div class="step-item">
      <div class="step-num">3</div>
      <span>View Results</span>
    </div>
    <div class="step-divider"></div>
    <div class="step-item">
      <div class="step-num">4</div>
      <span>Analysis</span>
    </div>
  </div>

  <!-- ═══════════ VALIDATION ERRORS ═══════════ -->
  <?php if (!empty($errors)): ?>
  <div id="server-errors" class="alert-glass mb-4">
    <strong>⚠ Please fix the following errors:</strong>
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div id="client-errors" class="mb-3"></div>

  <!-- ═══════════════════════════════ MAIN FORM ═══════════════════════════════ -->
  <form id="scheduler-form" action="simulate.php" method="POST" novalidate>

    <!-- ─── Scenario Presets ─── -->
    <div class="glass-card mb-section">
      <div class="section-title">🎯 Quick Test Scenarios</div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="scenario-btn" data-scenario="normal" id="btn-normal">
          📊 Normal Case (4 processes)
        </button>
        <button type="button" class="scenario-btn" data-scenario="fairness" id="btn-fairness">
          ⚖ Fairness Case (1 long + 4 short)
        </button>
        <button type="button" class="scenario-btn" id="btn-invalid"
          onclick="loadInvalidScenario()">
          ❌ Invalid Input Case
        </button>
      </div>
      <p class="mt-2" style="color:var(--text-muted);font-size:.8rem">
        💡 Select a preset to auto-fill the form, or enter your own processes below.
      </p>
    </div>

    <!-- ─── Process Input (Redesigned) ─── -->
    <div class="glass-card mb-section process-input-card">

      <!-- Header row -->
      <div class="pi-header">
        <div class="pi-header-left">
          <div class="pi-icon">⚡</div>
          <div>
            <div class="section-title mb-0">Process Input <span class="badge-pill">Dynamic</span></div>
            <div class="pi-subtitle">Define each process with its arrival and CPU burst time</div>
          </div>
        </div>
        <div class="pi-counter" id="pi-counter">
          <span id="proc-count">0</span>
          <span class="pi-counter-label">processes</span>
        </div>
      </div>

      <!-- Column labels -->
      <div class="pi-col-labels">
        <div class="pi-col-label" style="width:44px">#</div>
        <div class="pi-col-label flex-1">Process ID</div>
        <div class="pi-col-label flex-1">Arrival Time <span class="pi-hint">(≥ 0)</span></div>
        <div class="pi-col-label flex-1">Burst Time <span class="pi-hint">(> 0)</span></div>
        <div style="width:40px"></div>
      </div>

      <!-- Process rows container -->
      <div id="process-table-body" class="pi-rows-wrap">
        <?php
        if (!empty($oldPids)):
          for ($i = 0; $i < count($oldPids); $i++):
        ?>
        <div class="pi-row process-row">
          <div class="pi-row-num"><span class="pi-dot"></span></div>
          <div class="pi-row-field">
            <input type="text" class="pi-input pid-input" name="pid[]"
                   value="<?= htmlspecialchars($oldPids[$i]) ?>" maxlength="10"
                   placeholder="e.g. P<?= $i+1 ?>" required>
          </div>
          <div class="pi-row-field">
            <div class="pi-input-wrap">
              <span class="pi-input-icon">🕐</span>
              <input type="number" class="pi-input" name="arrival[]"
                     value="<?= htmlspecialchars($oldArrivals[$i]) ?>" min="0"
                     placeholder="0" required>
            </div>
          </div>
          <div class="pi-row-field">
            <div class="pi-input-wrap">
              <span class="pi-input-icon">⚙</span>
              <input type="number" class="pi-input" name="burst[]"
                     value="<?= htmlspecialchars($oldBursts[$i]) ?>" min="1"
                     placeholder="1" required>
            </div>
          </div>
          <div class="pi-row-del">
            <button type="button" class="pi-del-btn" onclick="removeRow(this)" title="Remove">✕</button>
          </div>
        </div>
        <?php endfor; endif; ?>
      </div>

      <!-- Footer actions -->
      <div class="pi-footer">
        <button type="button" class="pi-add-btn" id="add-row-btn">
          <span class="pi-add-icon">＋</span> Add Process
        </button>
        <button type="button" class="pi-clear-btn"
          onclick="clearAllRows()">
          <span>🗑</span> Clear All
        </button>
        <span class="pi-limit-note">Max recommended: 10 processes</span>
      </div>
    </div>

    <!-- ─── Quantum Input (Redesigned) ─── -->
    <div class="glass-card mb-section quantum-card">
      <div class="quantum-card-inner">

        <div class="quantum-left">
          <div class="quantum-icon-wrap">⏱</div>
          <div>
            <div class="section-title mb-1">Time Quantum <span class="badge-pill">RR Only</span></div>
            <div class="pi-subtitle">Controls the time slice for Round Robin scheduling</div>
          </div>
        </div>

        <div class="quantum-controls">
          <div class="quantum-input-group">
            <label class="quantum-label" for="quantum-input">Q Value</label>
            <div class="quantum-input-row">
              <button type="button" class="q-adj-btn" onclick="adjustQuantum(-1)">−</button>
              <input type="number" class="pi-input quantum-main-input" id="quantum-input"
                     name="quantum" min="1" max="99" placeholder="3"
                     value="<?= htmlspecialchars((string)$oldQuantum) ?>" required>
              <button type="button" class="q-adj-btn" onclick="adjustQuantum(1)">＋</button>
            </div>
          </div>

          <div class="quantum-slider-wrap">
            <input type="range" id="quantum-slider" min="1" max="20" value="<?= (int)$oldQuantum ?>" class="quantum-slider">
            <div class="quantum-slider-labels">
              <span>1</span><span>5</span><span>10</span><span>15</span><span>20</span>
            </div>
          </div>
        </div>

        <div class="quantum-visual">
          <div class="qv-badge" id="quantum-badge">Q = <?= (int)$oldQuantum ?></div>
          <div class="qv-desc" id="quantum-desc"><?php
            $q = (int)$oldQuantum;
            echo $q <= 2 ? '⚡ Very high fairness, high overhead'
               : ($q <= 5 ? '✅ Balanced — recommended'
               : ($q <= 10 ? '⚖ Moderate, fewer switches'
               : '🔁 Large — approaches FCFS'));
          ?></div>
        </div>

      </div>
    </div>

    <!-- ─── Submit ─── -->
    <div class="d-flex gap-3 mb-section flex-wrap">
      <button type="submit" class="btn-rr px-4 py-2 fs-6" id="simulate-btn">
        ▶ Run Simulation
      </button>
      <a href="index.php" class="btn-outline-light-custom py-2">↺ Reset</a>
    </div>

  </form><!-- end form -->

</div><!-- container -->

<!-- Footer -->
<footer class="text-center py-4 mt-5" style="border-top:1px solid var(--border);color:var(--text-muted);font-size:.8rem">
  CPU Scheduling Comparison System &nbsp;|&nbsp; Round Robin vs SJF Preemptive &nbsp;|&nbsp; Pure PHP + Bootstrap
</footer>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
// Invalid scenario loader (demo only — shows validation errors)
function loadInvalidScenario() {
  document.getElementById('process-table-body').innerHTML = '';
  rowCounter = 0;
  addRow('P1', 0, 5);
  addRow('P1', -2, 3);  // duplicate PID + negative arrival
  addRow('P3', 1, 0);   // zero burst
  document.getElementById('quantum-input').value = -1;
  updateQuantumBadge();
  document.querySelectorAll('.scenario-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('btn-invalid').classList.add('active');
}
</script>
</body>
</html>
