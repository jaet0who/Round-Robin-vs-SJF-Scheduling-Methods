<?php
/**
 * Validator.php
 * Server-side input validation for scheduling system inputs.
 */
class Validator {

    private array $errors = [];

    /**
     * Validate all process inputs and quantum.
     *
     * @param array  $pids      Array of process IDs
     * @param array  $arrivals  Array of arrival times
     * @param array  $bursts    Array of burst times
     * @param mixed  $quantum   Quantum value (for RR)
     * @return bool  True if valid, false otherwise
     */
    public function validate(array $pids, array $arrivals, array $bursts, $quantum): bool {
        $this->errors = [];
        $n = count($pids);

        // Must have at least one process
        if ($n === 0) {
            $this->errors[] = "You must add at least one process.";
            return false;
        }

        $seenPids = [];

        for ($i = 0; $i < $n; $i++) {
            $pid     = trim($pids[$i] ?? '');
            $arrival = $arrivals[$i] ?? '';
            $burst   = $bursts[$i] ?? '';

            // --- PID ---
            if ($pid === '') {
                $this->errors[] = "Process #" . ($i + 1) . ": Process ID cannot be empty.";
            } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $pid)) {
                $this->errors[] = "Process #" . ($i + 1) . ": Process ID '$pid' contains invalid characters.";
            } elseif (in_array(strtoupper($pid), $seenPids, true)) {
                $this->errors[] = "Process #" . ($i + 1) . ": Duplicate Process ID '$pid' detected.";
            } else {
                $seenPids[] = strtoupper($pid);
            }

            // --- Arrival Time ---
            if ($arrival === '' || $arrival === null) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Arrival Time is required.";
            } elseif (!is_numeric($arrival) || (int)$arrival != $arrival) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Arrival Time must be a non-negative integer.";
            } elseif ((int)$arrival < 0) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Arrival Time cannot be negative.";
            }

            // --- Burst Time ---
            if ($burst === '' || $burst === null) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Burst Time is required.";
            } elseif (!is_numeric($burst) || (int)$burst != $burst) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Burst Time must be a positive integer.";
            } elseif ((int)$burst <= 0) {
                $this->errors[] = "Process #" . ($i + 1) . " ($pid): Burst Time must be greater than 0.";
            }
        }

        // --- Quantum ---
        if ($quantum === '' || $quantum === null) {
            $this->errors[] = "Quantum (Time Slice) is required for Round Robin.";
        } elseif (!is_numeric($quantum) || (int)$quantum != $quantum) {
            $this->errors[] = "Quantum must be a positive integer.";
        } elseif ((int)$quantum <= 0) {
            $this->errors[] = "Quantum must be greater than 0.";
        }

        return empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
