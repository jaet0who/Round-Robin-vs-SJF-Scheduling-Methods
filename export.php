<?php
/**
 * export.php
 * Exports current session results as a CSV file.
 * No external libraries used.
 */
session_start();

if (empty($_SESSION['results'])) {
    header('Location: index.php');
    exit;
}

$results = $_SESSION['results'];
$quantum = $results['quantum'];

// Build CSV content
$lines = [];

$lines[] = ['CPU SCHEDULING COMPARISON REPORT'];
$lines[] = ['Generated:', date('Y-m-d H:i:s')];
$lines[] = ['Quantum (RR):', $quantum];
$lines[] = [];

// --- Input Table ---
$lines[] = ['=== INPUT PROCESSES ==='];
$lines[] = ['Process ID', 'Arrival Time', 'Burst Time'];
foreach ($results['process_input'] as $p) {
    $lines[] = [$p['pid'], $p['at'], $p['bt']];
}
$lines[] = [];

// --- RR Results ---
$lines[] = ['=== ROUND ROBIN RESULTS (Quantum = ' . $quantum . ') ==='];
$lines[] = ['Process ID', 'Arrival Time', 'Burst Time', 'Finish Time', 'Turnaround Time', 'Waiting Time', 'Response Time'];
foreach ($results['rr']['metrics']['per_process'] as $row) {
    $lines[] = [$row['pid'], $row['at'], $row['bt'], $row['ft'], $row['tat'], $row['wt'], $row['rt']];
}
$m = $results['rr']['metrics'];
$lines[] = ['AVERAGES', '', '', '', $m['avg_tat'], $m['avg_wt'], $m['avg_rt']];
$lines[] = ['CPU Utilization', $m['cpu_utilization'] . '%'];
$lines[] = ['Throughput', $m['throughput'] . ' proc/unit'];
$lines[] = [];

// --- SJF Results ---
$lines[] = ['=== SJF PREEMPTIVE RESULTS ==='];
$lines[] = ['Process ID', 'Arrival Time', 'Burst Time', 'Finish Time', 'Turnaround Time', 'Waiting Time', 'Response Time'];
foreach ($results['sjf']['metrics']['per_process'] as $row) {
    $lines[] = [$row['pid'], $row['at'], $row['bt'], $row['ft'], $row['tat'], $row['wt'], $row['rt']];
}
$m2 = $results['sjf']['metrics'];
$lines[] = ['AVERAGES', '', '', '', $m2['avg_tat'], $m2['avg_wt'], $m2['avg_rt']];
$lines[] = ['CPU Utilization', $m2['cpu_utilization'] . '%'];
$lines[] = ['Throughput', $m2['throughput'] . ' proc/unit'];
$lines[] = [];

// --- RR Gantt Timeline ---
$lines[] = ['=== ROUND ROBIN GANTT CHART ==='];
$lines[] = ['Process', 'Start', 'End', 'Duration'];
foreach ($results['rr']['timeline'] as $seg) {
    $lines[] = [$seg['pid'], $seg['start'], $seg['end'], $seg['end'] - $seg['start']];
}
$lines[] = [];

// --- SJF Gantt Timeline ---
$lines[] = ['=== SJF PREEMPTIVE GANTT CHART ==='];
$lines[] = ['Process', 'Start', 'End', 'Duration'];
foreach ($results['sjf']['timeline'] as $seg) {
    $lines[] = [$seg['pid'], $seg['start'], $seg['end'], $seg['end'] - $seg['start']];
}

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="cpu_scheduling_results_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
foreach ($lines as $line) {
    fputcsv($output, is_array($line) ? $line : [$line]);
}
fclose($output);
exit;
