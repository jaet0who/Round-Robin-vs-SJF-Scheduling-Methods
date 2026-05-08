<?php
/**
 * SJFPreemptive.php
 * Implements Shortest Job First — Preemptive (SRTF) CPU Scheduling Algorithm.
 *
 * Algorithm:
 *  - At EVERY time unit, select the arrived process with the shortest remaining burst.
 *  - Tie-break: earliest arrival time, then lexicographically smallest PID.
 *  - Preempt the running process if a shorter job arrives.
 *  - Compress consecutive same-process ticks into Gantt segments.
 */
class SJFPreemptive {

    /**
     * Run the SJF Preemptive (SRTF) algorithm.
     *
     * @param array $processes  Array of Process objects (deep-cloned inside)
     * @return array [
     *     'timeline'  => array of ['pid', 'start', 'end'],
     *     'processes' => array of completed Process objects with metrics,
     * ]
     */
    public function run(array $processes): array {
        // Deep-clone
        $procs = [];
        foreach ($processes as $p) {
            $clone = clone $p;
            $clone->reset();
            $procs[$clone->pid] = $clone;
        }

        $all = array_values($procs);
        $n   = count($all);

        // Find the max time bound (worst case: all burst times summed + max arrival)
        $totalBurst  = array_sum(array_map(fn($p) => $p->burstTime, $all));
        $maxArrival  = max(array_map(fn($p) => $p->arrivalTime, $all));
        $timeLimit   = $totalBurst + $maxArrival + 1;

        $timeline  = [];
        $completed = 0;
        $time      = 0;
        $prevPid   = null;
        $segStart  = 0;

        while ($completed < $n && $time <= $timeLimit) {
            // Gather all processes that have arrived and are not yet finished
            $candidates = array_filter($all, fn($p) =>
                $p->arrivalTime <= $time && $p->remaining > 0
            );

            if (empty($candidates)) {
                // CPU idle — jump to next arrival
                if ($prevPid !== null) {
                    $timeline[] = ['pid' => $prevPid, 'start' => $segStart, 'end' => $time];
                    $prevPid = null;
                }
                $nextArrivals = array_filter($all, fn($p) => $p->arrivalTime > $time && $p->remaining > 0);
                if (empty($nextArrivals)) break;
                $time = min(array_map(fn($p) => $p->arrivalTime, $nextArrivals));
                $segStart = $time;
                continue;
            }

            // Select process with shortest remaining burst
            // Tie-break: earliest arrival, then pid lexicographic
            usort($candidates, function ($a, $b) {
                if ($a->remaining !== $b->remaining) return $a->remaining <=> $b->remaining;
                if ($a->arrivalTime !== $b->arrivalTime) return $a->arrivalTime <=> $b->arrivalTime;
                return strcmp($a->pid, $b->pid);
            });

            $selected = reset($candidates);
            $pid      = $selected->pid;

            // Record first response
            if ($procs[$pid]->firstResponse === -1) {
                $procs[$pid]->firstResponse = $time;
            }

            // Detect preemption / context switch for Gantt segment building
            if ($pid !== $prevPid) {
                if ($prevPid !== null) {
                    $timeline[] = ['pid' => $prevPid, 'start' => $segStart, 'end' => $time];
                }
                $segStart = $time;
                $prevPid  = $pid;
            }

            // Execute one time unit
            $procs[$pid]->remaining--;
            $time++;

            if ($procs[$pid]->remaining === 0) {
                // Process completed
                $procs[$pid]->finishTime     = $time;
                $procs[$pid]->turnaroundTime = $time - $procs[$pid]->arrivalTime;
                $procs[$pid]->waitingTime    = $procs[$pid]->turnaroundTime - $procs[$pid]->burstTime;
                $procs[$pid]->responseTime   = $procs[$pid]->firstResponse - $procs[$pid]->arrivalTime;
                $completed++;

                // Close Gantt segment
                $timeline[] = ['pid' => $pid, 'start' => $segStart, 'end' => $time];
                $prevPid  = null;
                $segStart = $time;
            }
        }

        // Close any trailing segment
        if ($prevPid !== null) {
            $timeline[] = ['pid' => $prevPid, 'start' => $segStart, 'end' => $time];
        }

        return [
            'timeline'  => $timeline,
            'processes' => array_values($procs),
        ];
    }
}
