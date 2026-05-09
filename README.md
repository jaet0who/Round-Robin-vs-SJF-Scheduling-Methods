# CPU Scheduling Comparison System
### Round Robin vs SJF Preemptive

A full-stack web application comparing **Round Robin (RR)** and **Shortest Job First Preemptive (SRTF)** CPU scheduling algorithms — built with pure **PHP + HTML + CSS + JavaScript + Bootstrap**.

---

## Features

| Feature | Details |
|---------|---------|
| **Algorithms** | Round Robin (RR) + SJF Preemptive (SRTF) |
| **Input** | Dynamic process table (add/remove rows), Quantum field |
| **Validation** | Client-side (JS) + Server-side (PHP) — duplicate PIDs, negatives, zero burst |
| **Gantt Charts** | Pure CSS/JS — no chart libraries |
| **Metrics** | WT, TAT, RT per process + averages + CPU utilization + throughput |
| **Comparison** | Animated metric bars, fairness analysis (std-dev), winner badges |
| **Conclusion** | Auto-generated recommendation with reasoning |
| **Export** | CSV download — pure PHP, no libraries |
| **Scenarios** | Normal, Fairness, Invalid Input presets |
| **Stack** | PHP (procedural), Bootstrap 5, Vanilla JS, CSS animations |

---

## 🔗 Live Demo
 [Click Here to Try the System](https://cpuscheduling.infinityfreeapp.com/results.php)

---

## 📄 Documentation
 [Click Here for Full Documentation](https://1drv.ms/w/c/ac08f163835388f3/IQDFZOf3bimlS4S61oGGVnlbAR_6gJ8Q6dC7E_Y82PRSsXk?e=pkZdd1)

---

## Project Structure

```
Round Robin vs SJF Comparison Project2/
├── index.php                   # Main input form page
├── simulate.php                # POST handler — runs both algorithms
├── results.php                 # Results: Gantt charts, tables, comparison
├── export.php                  # CSV export
├── README.md
├── .gitignore
│
├── src/
│   ├── model/
│   │   └── Process.php         # Process data class
│   ├── scheduler/
│   │   ├── RoundRobin.php      # RR algorithm (FIFO queue)
│   │   └── SJFPreemptive.php   # SRTF algorithm (tick-by-tick)
│   ├── metrics/
│   │   └── MetricsCalculator.php  # WT, TAT, RT, CPU util, throughput
│   └── util/
│       └── Validator.php       # Server-side input validation
│
├── assets/
│   ├── css/
│   │   └── style.css           # Dark-mode premium theme + Gantt CSS
│   └── js/
│       ├── app.js              # Dynamic rows, client validation, presets
│       ├── gantt.js            # Pure JS Gantt chart renderer
│       └── comparison.js       # Metric bar animations + recommendation
│
├── screenshots/                # (Add UI screenshots here)
└── test-cases/
    ├── normal_case.json        # 4 processes, mixed workload
    ├── fairness_case.json      # 1 long + 4 short processes
    └── invalid_case.json       # Validation error scenarios
```

---

## How to Run (XAMPP / localhost)

### Using XAMPP
1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache**
2. Copy the entire project folder to:
   ```
   C:\xampp\htdocs\cpu-scheduling\
   ```
3. Open your browser and visit:
   ```
   http://localhost/cpu-scheduling/
   ```

### Using PHP Built-in Server
1. Open a terminal in the project directory
2. Run:
   ```bash
   php -S localhost:8080
   ```
3. Visit `http://localhost:8080`

> **Note:** No database setup required. Results are stored in PHP sessions.

---

## Algorithm Explanations

### Round Robin (RR)
Round Robin is a **preemptive, time-sharing** algorithm.

**How it works:**
1. Processes are arranged in a **FIFO ready queue**
2. Each process runs for at most **Q (quantum)** time units
3. If a process hasn't finished within Q units, it is preempted and re-added to the back of the queue
4. New arrivals are added to the queue at their arrival time
5. The cycle continues until all processes complete

**Implementation details:**
- Queue implemented as a PHP array used as a FIFO queue (`array_shift` / `array_push`)
- Handles idle CPU: if queue is empty, jumps to next arrival
- New arrivals enqueued after current burst (before re-queuing unfinished process)
- Tracks `firstResponse` for RT calculation

**Quantum impact:**
- Small Q → More context switches → More overhead, but fairer
- Large Q → Fewer switches → Approaches FCFS behavior
- Q = 1 → Pure round-robin one unit at a time

---

### SJF Preemptive (SRTF — Shortest Remaining Time First)
SRTF is an **optimal** algorithm for minimizing average waiting time.

**How it works:**
1. At **every time unit**, check all arrived processes
2. Select the process with the **shortest remaining burst time**
3. If a new process arrives with a shorter remaining time than the current process, **preempt** the current process
4. Ties broken by: earliest arrival time → lexicographically smallest PID
5. Continue until all processes finish

**Implementation details:**
- Simulates tick-by-tick (O(n × total_burst) time complexity)
- Consecutive same-process ticks compressed into single Gantt segments
- Handles idle CPU gaps between arrival times
- Provably optimal for average WT among all preemptive algorithms

**Limitation:** Long processes may **starve** if short processes keep arriving.

---

## Metrics Explained

| Metric | Formula | Description |
|--------|---------|-------------|
| **Finish Time (FT)** | Direct | When the process completes execution |
| **Turnaround Time (TAT)** | FT − Arrival Time | Total time from arrival to completion |
| **Waiting Time (WT)** | TAT − Burst Time | Time spent waiting in ready queue |
| **Response Time (RT)** | First CPU Time − Arrival Time | Time from arrival to first CPU allocation |
| **CPU Utilization** | Total Burst / Makespan × 100% | Percentage of time CPU was busy |
| **Throughput** | N / Makespan | Processes completed per time unit |

---

## Test Cases

### Test Case 1 — Normal Case
| Process | AT | BT |
|---------|----|----|
| P1 | 0 | 8 |
| P2 | 1 | 4 |
| P3 | 2 | 9 |
| P4 | 3 | 5 |

**Quantum = 3**

Expected behavior: SJF achieves lower average WT/TAT. RR distributes time fairly.

---

### Test Case 2 — Fairness / Long-Job Sensitivity
| Process | AT | BT |
|---------|----|----|
| P1 | 0 | 20 |
| P2 | 1 | 3  |
| P3 | 2 | 3  |
| P4 | 3 | 3  |
| P5 | 4 | 2  |

**Quantum = 4**

**Key insight:** SJF starves P1 (the long job) — P2-P5 always preempt it. RR gives P1 a slot every 4 units = **fairer**. This demonstrates the classic fairness vs efficiency trade-off.

---

### Test Case 3 — Invalid Input Case
Inputs that trigger validation errors:
- Duplicate PID → "Duplicate Process ID detected"
- Negative Arrival Time → "Arrival Time cannot be negative"
- Zero Burst Time → "Burst Time must be greater than 0"
- Negative Quantum → "Quantum must be greater than 0"
- Empty Process ID → "Process ID cannot be empty"

Both **JavaScript** (client-side) and **PHP** (server-side) validate independently.

---

## Assumptions & Limitations

1. **Integer inputs only** — Arrival and burst times must be non-negative integers
2. **No database** — Results stored in PHP sessions (cleared on browser close)
3. **Max processes** — Recommended ≤ 10 for clear Gantt visualization
4. **SJF time complexity** — O(n × total_burst): may be slow for very large burst times (> 10,000 units)
5. **No aging** — SJF Preemptive does not implement aging, so starvation is possible
6. **Single CPU** — Only one-processor scheduling modeled
7. **No I/O bursts** — Pure CPU burst scheduling only

---

## Technology Stack

- **Backend:** PHP 7.4+ (procedural, no frameworks)
- **Frontend:** HTML5, Bootstrap 5.3 (CDN), Vanilla JavaScript (ES6+)
- **Styling:** Custom CSS (dark mode, glassmorphism, CSS animations)
- **Charts:** Pure HTML/CSS/JS — zero chart libraries
- **Server:** XAMPP (Apache) or PHP built-in server
- **Database:** None required (PHP sessions)

---

## Author Notes

This system was built as a comprehensive demonstration of CPU scheduling algorithm comparison. Every line of scheduling logic is hand-coded — no third-party algorithm libraries are used. The RR and SJF implementations follow standard OS textbook definitions (Silberschatz, "Operating System Concepts").

---

## 👥 Team Members
- Yasmin Abdelhalim Ibrahim  
- Nour Karamallah Mahmoud Galal  
- Jana Alaaeldin Ahmed  
- Norhan Sabry Ramadan  
- Manar Makawy Gab Allah  
- Marwan Mohsen Sayed  
- Amr Mohamed Ayman  
