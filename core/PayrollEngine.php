<?php
// =============================================================
//  core/PayrollEngine.php
//  All payroll calculation logic lives here.
//  Changing rates / targets only requires editing the DB.
//  No business logic in controllers or views.
// =============================================================

class PayrollEngine
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    //  Calculate production pay for one day
    //
    //  @param int    $productId
    //  @param int    $quantity
    //  @param string $workDate   'Y-m-d'
    //  @return float
    // ----------------------------------------------------------
    public function calcProductionPay(int $productId, int $quantity, string $workDate, string $status = 'work'): float
    {
        if ($quantity <= 0) return 0.0;

        $dow = (int) date('w', strtotime($workDate)); // 0=Sun … 6=Sat

        // Sundays: only payable if Holiday
        if ($dow === DAY_SUNDAY && $status !== 'holiday') return 0.0;

        $product = $this->db->fetchOne(
            'SELECT target_weekday, target_saturday, rate_above, rate_below
               FROM products WHERE id = ?',
            [$productId]
        );
        if (!$product) return 0.0;

        $target = ($dow === DAY_SATURDAY)
            ? (int) $product['target_saturday']
            : (int) $product['target_weekday'];

        // Holiday: always use rate_above for all units
        if ($status === 'holiday') {
            return round($quantity * (float) $product['rate_above'], 2);
        }

        $aboveTarget = max(0, $quantity - $target);
        $belowTarget = min($quantity, $target);

        return round(
            ($aboveTarget * (float) $product['rate_above']) +
            ($belowTarget * (float) $product['rate_below']),
            2
        );
    }

    // ----------------------------------------------------------
    //  Calculate total pay for a single timecard row
    //
    //  @param array $timecard    row from timecards + timecard_products
    //  @return array             ['production', 'ot', 'day_duty',
    //                             'travelling', 'other', 'gross']
    // ----------------------------------------------------------
    public function calcTimecardTotals(array $timecard): array
    {
        $totals = [
            'production' => 0.0,
            'ot'         => (float) $timecard['ot_amount'],
            'day_duty'   => (float) $timecard['day_duty_amount'],
            'travelling' => (float) $timecard['travelling'],
            'other'      => (float) $timecard['other_amount'],
        ];

        // Production pay only on workable days
        if (in_array($timecard['status'], ['work', 'holiday'], true)) {
            $products = $this->db->fetchAll(
                'SELECT tp.product_id, tp.quantity
                   FROM timecard_products tp
                  WHERE tp.timecard_id = ?',
                [$timecard['id']]
            );

            $workDate = (string) $timecard['work_date'];
            $status   = (string) $timecard['status'];
            $dow      = (int) date('w', strtotime($workDate));

            if ($dow !== DAY_SUNDAY || $status === 'holiday') {
                $nonZero = [];
                $totalUnits = 0;
                $topProductId = null;
                $topQty = -1;

                foreach ($products as $p) {
                    $pid = (int) $p['product_id'];
                    $qty = (int) $p['quantity'];
                    if ($qty <= 0) continue;
                    $nonZero[$pid] = $qty;
                    $totalUnits += $qty;
                    if ($qty > $topQty) {
                        $topQty = $qty;
                        $topProductId = $pid;
                    }
                }

                if ($totalUnits > 0) {
                    if ($status === 'holiday') {
                        foreach ($nonZero as $pid => $qty) {
                            $totals['production'] += $this->calcProductionPay($pid, $qty, $workDate, 'holiday');
                        }
                    } else {
                        if (count($nonZero) >= 2 && $topProductId !== null) {
                            $top = $this->db->fetchOne(
                                'SELECT target_weekday, target_saturday, rate_above, rate_below
                                   FROM products WHERE id = ?',
                                [$topProductId]
                            );
                            if ($top) {
                                $target = ($dow === DAY_SATURDAY)
                                    ? (int) $top['target_saturday']
                                    : (int) $top['target_weekday'];

                                $below = min($totalUnits, $target);
                                $above = max(0, $totalUnits - $target);

                                $totals['production'] = round(
                                    ($below * (float) $top['rate_below']) +
                                    ($above * (float) $top['rate_above']),
                                    2
                                );
                            }
                        } else {
                            foreach ($nonZero as $pid => $qty) {
                                $totals['production'] += $this->calcProductionPay($pid, $qty, $workDate, 'work');
                            }
                        }
                    }
                }
            }
        }

        $totals['gross'] = round(
            $totals['production'] + $totals['ot'] +
            $totals['day_duty']  + $totals['travelling'] + $totals['other'],
            2
        );

        return $totals;
    }

    // ----------------------------------------------------------
    //  Build full monthly detail for one employee
    //  (used by detail sheet & payslip)
    //
    //  @return array  ['days' => [...], 'summary' => [...]]
    // ----------------------------------------------------------
    public function buildMonthlyDetail(int $employeeId, int $year, int $month): array
    {
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $products    = $this->db->fetchAll(
            'SELECT id, name, sort_order,
                    target_weekday, target_saturday, rate_above, rate_below
               FROM products WHERE is_active = 1 ORDER BY sort_order'
        );
        $productById = [];
        foreach ($products as $p) {
            $productById[(int) $p['id']] = $p;
        }

        // Fetch all timecards for the month in one query
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $timecards = $this->db->fetchAll(
            'SELECT t.*, 
                    TIME_FORMAT(t.shift_start, "%H.%i") AS shift_start_fmt,
                    TIME_FORMAT(t.shift_end,   "%H.%i") AS shift_end_fmt
               FROM timecards t
              WHERE t.employee_id = ?
                AND t.work_date BETWEEN ? AND ?
              ORDER BY t.work_date',
            [$employeeId, $firstDay, $lastDay]
        );

        // Index timecards by date for fast lookup
        $tcByDate = [];
        foreach ($timecards as $tc) {
            $tcByDate[$tc['work_date']] = $tc;
        }

        // Fetch all product quantities for this employee/month in one query
        $productQtys = $this->db->fetchAll(
            'SELECT tp.timecard_id, tp.product_id, tp.quantity
               FROM timecard_products tp
               JOIN timecards t ON t.id = tp.timecard_id
              WHERE t.employee_id = ?
                AND t.work_date BETWEEN ? AND ?',
            [$employeeId, $firstDay, $lastDay]
        );

        // Index: [timecard_id][product_id] = quantity
        $qtyIndex = [];
        foreach ($productQtys as $pq) {
            $qtyIndex[$pq['timecard_id']][$pq['product_id']] = (int) $pq['quantity'];
        }

        // Build one row per calendar day
        $days      = [];
        $summary   = [
            'days_worked'      => 0,
            'days_leave'       => 0,
            'total_production' => 0.0,
            'total_ot'         => 0.0,
            'total_day_duty'   => 0.0,
            'total_travelling' => 0.0,
            'total_other'      => 0.0,
            'gross_pay'        => 0.0,
            'product_totals'   => array_fill_keys(array_column($products, 'id'), 0),
        ];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow     = (int) date('w', strtotime($dateStr));
            $dayName = date('D', strtotime($dateStr));

            $tc = $tcByDate[$dateStr] ?? null;

            $row = [
                'date'       => $dateStr,
                'day'        => $d,
                'day_name'   => $dayName,
                'dow'        => $dow,
                'is_sunday'  => $dow === DAY_SUNDAY,
                'is_saturday'=> $dow === DAY_SATURDAY,
                'status'     => $tc['status'] ?? ($dow === DAY_SUNDAY ? 'holiday' : 'work'),
                'shift'      => $tc ? trim(($tc['shift_start_fmt'] ?? '') . '-' . ($tc['shift_end_fmt'] ?? ''), '-') : '',
                'quantities' => [],
                'production' => 0.0,
                'ot'         => $tc ? (float)$tc['ot_amount']       : 0.0,
                'day_duty'   => $tc ? (float)$tc['day_duty_amount']  : 0.0,
                'travelling' => $tc ? (float)$tc['travelling']       : 0.0,
                'other'      => $tc ? (float)$tc['other_amount']     : 0.0,
                'gross'      => 0.0,
                'notes'      => $tc['notes'] ?? '',
            ];

            $isWorkable = in_array($row['status'], ['work', 'holiday'], true);

            // Product quantities & production pay
            $nonZeroQtys = [];
            $totalUnits  = 0;
            $topProductId = null;
            $topQty = -1;
            foreach ($products as $p) {
                $qty = 0;
                if ($tc && isset($qtyIndex[$tc['id']][$p['id']])) {
                    $qty = $qtyIndex[$tc['id']][$p['id']];
                }
                $row['quantities'][$p['id']] = $qty;
                $summary['product_totals'][$p['id']] += $qty;

                if ($qty > 0) {
                    $nonZeroQtys[(int) $p['id']] = $qty;
                    $totalUnits += $qty;
                    if ($qty > $topQty) {
                        $topQty = $qty;
                        $topProductId = (int) $p['id'];
                    }
                }
            }

            // Production pay
            if ($isWorkable && $tc && ($dow !== DAY_SUNDAY || $row['status'] === 'holiday') && $totalUnits > 0) {
                if ($row['status'] === 'holiday') {
                    foreach ($nonZeroQtys as $pid => $qty) {
                        $p = $productById[$pid] ?? null;
                        if (!$p) continue;
                        $row['production'] += $qty * (float) $p['rate_above'];
                    }
                } elseif ($row['status'] === 'work') {
                    if (count($nonZeroQtys) >= 2 && $topProductId !== null) {
                        $top = $productById[$topProductId] ?? null;
                        if ($top) {
                            $target = ($dow === DAY_SATURDAY)
                                ? (int) $top['target_saturday']
                                : (int) $top['target_weekday'];

                            $below = min($totalUnits, $target);
                            $above = max(0, $totalUnits - $target);

                            $row['production'] =
                                ($below * (float) $top['rate_below']) +
                                ($above * (float) $top['rate_above']);
                        }
                    } else {
                        foreach ($nonZeroQtys as $pid => $qty) {
                            $row['production'] += $this->calcProductionPay($pid, $qty, $dateStr, 'work');
                        }
                    }
                }
            }
            $row['production'] = round($row['production'], 2);
            $row['gross']      = round(
                $row['production'] + $row['ot'] + $row['day_duty'] + $row['travelling'] + $row['other'],
                2
            );

            // Accumulate summary
            if ($row['status'] === 'work') $summary['days_worked']++;
            if ($row['status'] === 'leave') $summary['days_leave']++;
            $summary['total_production'] += $row['production'];
            $summary['total_ot']         += $row['ot'];
            $summary['total_day_duty']   += $row['day_duty'];
            $summary['total_travelling'] += $row['travelling'];
            $summary['total_other']      += $row['other'];
            $summary['gross_pay']        += $row['gross'];

            $days[] = $row;
        }

        $summary['gross_pay'] = round($summary['gross_pay'], 2);

        return [
            'days'     => $days,
            'summary'  => $summary,
            'products' => $products,
        ];
    }

    // ----------------------------------------------------------
    //  Compute & upsert payroll_summary for one employee/month
    // ----------------------------------------------------------
    public function computeAndSaveSummary(int $employeeId, int $year, int $month): array
    {
        $detail  = $this->buildMonthlyDetail($employeeId, $year, $month);
        $summary = $detail['summary'];

        $this->db->query(
            'INSERT INTO payroll_summaries
                (employee_id, payroll_year, payroll_month,
                 days_worked, days_leave,
                 total_production, total_ot, total_day_duty,
                 total_travelling, total_other, gross_pay)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                days_worked       = VALUES(days_worked),
                days_leave        = VALUES(days_leave),
                total_production  = VALUES(total_production),
                total_ot          = VALUES(total_ot),
                total_day_duty    = VALUES(total_day_duty),
                total_travelling  = VALUES(total_travelling),
                total_other       = VALUES(total_other),
                gross_pay         = VALUES(gross_pay),
                updated_at        = CURRENT_TIMESTAMP',
            [
                $employeeId, $year, $month,
                $summary['days_worked'],
                $summary['days_leave'],
                $summary['total_production'],
                $summary['total_ot'],
                $summary['total_day_duty'],
                $summary['total_travelling'],
                $summary['total_other'],
                $summary['gross_pay'],
            ]
        );

        return $summary;
    }
}
