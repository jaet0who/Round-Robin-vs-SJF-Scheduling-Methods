<?php
/**
 * Process.php
 * Data model representing a single CPU process.
 */
class Process {
    public string $pid;
    public int    $arrivalTime;
    public int    $burstTime;
    public int    $remaining;     // remaining burst time (mutable during simulation)
    public int    $finishTime    = 0;
    public int    $firstResponse = -1; // -1 = not yet started
    public int    $waitingTime   = 0;
    public int    $turnaroundTime= 0;
    public int    $responseTime  = 0;

    public function __construct(string $pid, int $arrivalTime, int $burstTime) {
        $this->pid         = $pid;
        $this->arrivalTime = $arrivalTime;
        $this->burstTime   = $burstTime;
        $this->remaining   = $burstTime;
    }

    /** Reset mutable fields so the same process object can be re-used for a second algorithm. */
    public function reset(): void {
        $this->remaining      = $this->burstTime;
        $this->finishTime     = 0;
        $this->firstResponse  = -1;
        $this->waitingTime    = 0;
        $this->turnaroundTime = 0;
        $this->responseTime   = 0;
    }
}
