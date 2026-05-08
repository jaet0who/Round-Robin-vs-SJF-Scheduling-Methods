<?php
/**
 * simulate.php
 * Entry point for POST form submission.
 * Validates input, runs both schedulers, stores results in session, redirects.
 */
session_start();

require_once __DIR__ . '/src/model/Process.php';
require_once __DIR__ . '/src/util/Validator.php';
require_once __DIR__ . '/src/scheduler/RoundRobin.php';
require_once __DIR__ . '/src/scheduler/SJFPreemptive.php';
require_once __DIR__ . '/src/metrics/MetricsCalculator.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// --- Collect raw inputs ---
$pids     = $_POST['pid']     ?? [];
$arrivals = $_POST['arrival'] ?? [];
$bursts   = $_POST['burst']   ?? [];
$quantum  = trim($_POST['quantum'] ?? '');

// --- Validate ---
$validator = new Validator();
if (!$validator->validate($pids, $arrivals, $bursts, $quantum)) {
    $_SESSION['errors']     = $validator->getErrors();
    $_SESSION['form_data']  = $_POST;  // preserve form for repopulation
    header('Location: index.php');
    exit;
}

// Clear any previous errors
unset($_SESSION['errors'], $_SESSION['form_data']);

// --- Build Process objects ---
$processes = [];
$n = count($pids);
for ($i = 0; $i < $n; $i++) {
    $processes[] = new Process(
        trim($pids[$i]),
        (int)$arrivals[$i],
        (int)$bursts[$i]
    );
}
$quantum = (int)$quantum;

// --- Run Round Robin ---
$rr       = new RoundRobin();
$rrResult = $rr->run($processes, $quantum);

// --- Run SJF Preemptive ---
$sjf       = new SJFPreemptive();
$sjfResult = $sjf->run($processes);

// --- Compute Metrics ---
$calc        = new MetricsCalculator();
$rrMetrics   = $calc->compute($rrResult['processes']);
$sjfMetrics  = $calc->compute($sjfResult['processes']);

// --- Store in session ---
$_SESSION['results'] = [
    'quantum'     => $quantum,
    'process_input' => array_map(fn($p) => [
        'pid' => $p->pid, 'at' => $p->arrivalTime, 'bt' => $p->burstTime
    ], $processes),
    'rr' => [
        'timeline'       => $rrResult['timeline'],
        'queue_snapshots'=> $rrResult['queue_snapshots'],
        'metrics'        => $rrMetrics,
    ],
    'sjf' => [
        'timeline' => $sjfResult['timeline'],
        'metrics'  => $sjfMetrics,
    ],
];

header('Location: results.php');
exit;
