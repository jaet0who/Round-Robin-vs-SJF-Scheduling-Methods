<?php
/**
 * MetricsCalculator.php
 * Computes aggregate metrics (averages) from a set of completed Process objects.
 */
class MetricsCalculator {

    /**
     * Compute per-process and average metrics.
     *
     * @param  array $processes  Array of Process objects (already have WT/TAT/RT filled)
     * @return array [
     *     'per_process' => [ ['pid', 'at', 'bt', 'ft', 'tat', 'wt', 'rt'], ... ],
     *     'avg_wt'      => float,
     *     'avg_tat'     => float,
     *     'avg_rt'      => float,
     *     'cpu_utilization' => float,
     *     'throughput'      => float,
     * ]
     */
    public function compute(array $processes): array {
        $n         = count($processes);
        $sumWT     = 0;
        $sumTAT    = 0;
        $sumRT     = 0;
        $perProcess= [];

        foreach ($processes as $p) {
            $sumWT  += $p->waitingTime;
            $sumTAT += $p->turnaroundTime;
            $sumRT  += $p->responseTime;

            $perProcess[] = [
                'pid' => $p->pid,
                'at'  => $p->arrivalTime,
                'bt'  => $p->burstTime,
                'ft'  => $p->finishTime,
                'tat' => $p->turnaroundTime,
                'wt'  => $p->waitingTime,
                'rt'  => $p->responseTime,
            ];
        }

        // Sort per_process by PID for consistent display
        usort($perProcess, fn($a, $b) => strcmp($a['pid'], $b['pid']));

        // CPU utilization: total burst / makespan
        $makespan   = max(array_map(fn($p) => $p->finishTime, $processes))
                    - min(array_map(fn($p) => $p->arrivalTime, $processes));
        $totalBurst = array_sum(array_map(fn($p) => $p->burstTime, $processes));
        $cpuUtil    = $makespan > 0 ? round($totalBurst / $makespan * 100, 2) : 100.0;

        // Throughput: processes completed per unit time
        $throughput = $makespan > 0 ? round($n / $makespan, 4) : 0;

        return [
            'per_process'     => $perProcess,
            'avg_wt'          => round($sumWT  / $n, 2),
            'avg_tat'         => round($sumTAT / $n, 2),
            'avg_rt'          => round($sumRT  / $n, 2),
            'cpu_utilization' => $cpuUtil,
            'throughput'      => $throughput,
        ];
    }
}
