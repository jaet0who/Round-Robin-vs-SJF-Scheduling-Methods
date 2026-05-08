<?php
/**
 * RoundRobin.php
 * Implements the Round Robin CPU Scheduling Algorithm.
 *
 * Algorithm:
 *  - Maintains a FIFO ready queue (implemented as a PHP array used as a queue).
 *  - At each time step, checks for newly arrived processes and enqueues them.
 *  - Runs the front-of-queue process for min(remaining, quantum) time units.
 *  - If unfinished, re-enqueues at the back.
 *  - Also records the queue snapshot at each time unit for visualization.
 */
class RoundRobin {

    /**
     * Run the Round Robin algorithm.
     *
     * @param array $processes  Array of Process objects (will be deep-cloned inside)
     * @param int   $quantum    Time quantum
     * @return array [
     *     'timeline'       => array of ['pid', 'start', 'end'],
     *     'processes'      => array of completed Process objects with metrics filled,
     *     'queue_snapshots'=> array of ['time' => int, 'queue' => [pids]]
     * ]
     */
    public function run(array $processes, int $quantum): array {
        // Deep-clone processes so originals are untouched
        $procs = [];
        foreach ($processes as $p) {
            $clone = clone $p;
            $clone->reset();
            $procs[$clone->pid] = $clone;
        }

        // Sort by arrival time (stable sort using array index as tiebreaker)
        $sorted = array_values($procs);
        usort($sorted, fn($a, $b) => $a->arrivalTime <=> $b->arrivalTime ?: strcmp($a->pid, $b->pid));

        $timeline        = [];   // Gantt segments
        $queueSnapshots  = [];   // Ready queue state at each dispatch
        $readyQueue      = [];   // FIFO queue of PIDs
        $time            = 0;
        $arrivedIdx      = 0;    // Pointer into $sorted for arrival check
        $n               = count($sorted);
        $completed       = 0;

        // Enqueue all processes that arrived at time 0
        while ($arrivedIdx < $n && $sorted[$arrivedIdx]->arrivalTime <= $time) {
            $readyQueue[] = $sorted[$arrivedIdx]->pid;
            $arrivedIdx++;
        }

        while ($completed < $n) {
            if (empty($readyQueue)) {
                // CPU idle — advance to next arrival
                $nextArrival = $sorted[$arrivedIdx]->arrivalTime;
                // Enqueue all processes arriving at $nextArrival
                while ($arrivedIdx < $n && $sorted[$arrivedIdx]->arrivalTime <= $nextArrival) {
                    $readyQueue[] = $sorted[$arrivedIdx]->pid;
                    $arrivedIdx++;
                }
                $time = $nextArrival;
                continue;
            }

            // Dequeue front
            $pid     = array_shift($readyQueue);
            $process = $procs[$pid];

            // Record first response
            if ($process->firstResponse === -1) {
                $process->firstResponse = $time;
            }

            // Execute for min(remaining, quantum)
            $execTime = min($process->remaining, $quantum);
            $start    = $time;
            $end      = $time + $execTime;

            // Record queue snapshot at dispatch moment
            $queueSnapshots[] = [
                'time'    => $start,
                'running' => $pid,
                'queue'   => array_values($readyQueue),  // queue AFTER dequeuing current
            ];

            $timeline[] = ['pid' => $pid, 'start' => $start, 'end' => $end];

            $process->remaining -= $execTime;
            $time = $end;

            // Enqueue newly arrived processes during this burst (before re-queueing current)
            while ($arrivedIdx < $n && $sorted[$arrivedIdx]->arrivalTime <= $time) {
                $readyQueue[] = $sorted[$arrivedIdx]->pid;
                $arrivedIdx++;
            }

            if ($process->remaining > 0) {
                // Not finished — re-enqueue at back
                $readyQueue[] = $pid;
            } else {
                // Finished
                $process->finishTime    = $time;
                $process->turnaroundTime= $time - $process->arrivalTime;
                $process->waitingTime   = $process->turnaroundTime - $process->burstTime;
                $process->responseTime  = $process->firstResponse - $process->arrivalTime;
                $completed++;
            }
        }

        return [
            'timeline'        => $timeline,
            'processes'       => array_values($procs),
            'queue_snapshots' => $queueSnapshots,
        ];
    }
}
