<?php
// =============================================================
//  modules/timecard/index.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle  = 'Time Cards';
$activeMenu = 'timecard';

$db       = Database::getInstance();
$engine   = new PayrollEngine();
$products = $db->fetchAll('SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order');

// Selected employee & month
$selectedEmpId = getInt('emp');
$selectedYear  = getInt('year', (int) date('Y'));
$selectedMonth = getInt('month', (int) date('m'));

$employees = $db->fetchAll(
    'SELECT id, emp_code, full_name FROM employees WHERE is_active = 1 ORDER BY emp_code'
);

if ($selectedEmpId === 0 && !empty($employees)) {
    $selectedEmpId = (int) $employees[0]['id'];
}

// ---- Handle form POST (save timecard) ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && postStr('action') === 'save_timecard') {
    verifyCsrf();

    $empId = postInt('emp_id');
    $year  = postInt('year');
    $month = postInt('month');
    $days  = (array) ($_POST['days'] ?? []);

    // Check payroll is not locked
    $locked = $db->fetchOne(
        'SELECT is_locked FROM payroll_summaries
          WHERE employee_id = ? AND payroll_year = ? AND payroll_month = ?',
        [$empId, $year, $month]
    );
    if ($locked && $locked['is_locked']) {
        flash('danger', 'Payroll for this month is locked. Cannot edit.');
        redirect("/modules/timecard/index.php?emp=$empId&year=$year&month=$month");
    }

    $db->beginTransaction();
    try {
        foreach ($days as $dayNum => $dayData) {
            $dayNum   = (int) $dayNum;
            $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $dayNum);
            $status   = $dayData['status'] ?? 'off';
            $dow      = (int) date('w', strtotime($dateStr));
            if ($dow === 0) {
                $status = 'holiday';
            }
            $isWorkable = in_array($status, ['work', 'holiday'], true);

            // Parse shift times
            $shiftStart = null;
            $shiftEnd   = null;
            if (!empty($dayData['shift_start']) && $isWorkable) {
                $shiftStart = $dayData['shift_start'];
            }
            if (!empty($dayData['shift_end']) && $isWorkable) {
                $shiftEnd = $dayData['shift_end'];
            }

            $otAmt     = $status !== 'off' ? (float)($dayData['ot']       ?? 0) : 0;
            $ddAmt     = $status !== 'off' ? (float)($dayData['day_duty'] ?? 0) : 0;
            $travelAmt = $status !== 'off' ? (float)($dayData['travel']   ?? 0) : 0;
            $otherAmt  = $status !== 'off' ? (float)($dayData['other']    ?? 0) : 0;
            $notes     = trim($dayData['notes'] ?? '');

            // Upsert timecard row
            $existing = $db->fetchOne(
                'SELECT id FROM timecards WHERE employee_id = ? AND work_date = ?',
                [$empId, $dateStr]
            );

            if ($existing) {
                $tcId = (int) $existing['id'];
                $db->execute(
                    'UPDATE timecards SET status=?, shift_start=?, shift_end=?,
                            ot_amount=?, day_duty_amount=?, travelling=?, other_amount=?,
                            notes=?, updated_by=?
                      WHERE id=?',
                    [$status, $shiftStart, $shiftEnd, $otAmt, $ddAmt, $travelAmt, $otherAmt,
                     $notes, Auth::id(), $tcId]
                );
            } else {
                $tcId = $db->insert(
                    'INSERT INTO timecards
                        (employee_id, work_date, status, shift_start, shift_end,
                         ot_amount, day_duty_amount, travelling, other_amount,
                         notes, created_by, updated_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$empId, $dateStr, $status, $shiftStart, $shiftEnd,
                     $otAmt, $ddAmt, $travelAmt, $otherAmt,
                     $notes, Auth::id(), Auth::id()]
                );
            }

            // Product quantities
            if ($isWorkable) {
                foreach ($products as $p) {
                    $qty = (int)($dayData['qty'][$p['id']] ?? 0);
                    $db->execute(
                        'INSERT INTO timecard_products (timecard_id, product_id, quantity)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)',
                        [$tcId, $p['id'], $qty]
                    );
                }
            } else {
                // Clear quantities on non-work days
                $db->execute(
                    'DELETE tp FROM timecard_products tp
                      JOIN timecards t ON t.id = tp.timecard_id
                     WHERE t.employee_id = ? AND t.work_date = ?',
                    [$empId, $dateStr]
                );
            }
        }

        // Recompute summary
        $engine->computeAndSaveSummary($empId, $year, $month);
        $db->commit();
        AuditLog::write('update', 'timecards', $empId, "Saved timecard $year-$month");
        flash('success', 'Time card saved successfully.');
    } catch (Throwable $e) {
        $db->rollback();
        error_log($e->getMessage());
        flash('danger', 'An error occurred while saving. Please try again.');
    }

    redirect("/modules/timecard/index.php?emp=$empId&year=$year&month=$month");
}

// ---- Build month detail for display ------------------------
$detail   = ($selectedEmpId > 0)
    ? $engine->buildMonthlyDetail($selectedEmpId, $selectedYear, $selectedMonth)
    : null;

$selectedEmp = $selectedEmpId
    ? $db->fetchOne('SELECT * FROM employees WHERE id = ?', [$selectedEmpId])
    : null;

$isLocked = false;
if ($selectedEmpId) {
    $lock = $db->fetchOne(
        'SELECT is_locked FROM payroll_summaries
          WHERE employee_id = ? AND payroll_year = ? AND payroll_month = ?',
        [$selectedEmpId, $selectedYear, $selectedMonth]
    );
    $isLocked = $lock && $lock['is_locked'];
}

// Pass products to JS
$productsJson = json_encode($products);

require ROOT_PATH . '/templates/layout.php';
?>

<script>window.PRODUCTS = <?= $productsJson ?>;</script>

<!-- Selector bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Employee</label>
                <select name="emp" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"
                            <?= $e['id'] == $selectedEmpId ? 'selected' : '' ?>>
                            <?= sanitize($e['emp_code']) ?> – <?= sanitize($e['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>>
                            <?= monthName($m) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-pp btn-pp-primary">
                    <i class="ti ti-arrow-right"></i> Load
                </button>
                <?php if ($detail): ?>
                <a href="<?= APP_URL ?>/modules/reports/detail_sheet.php?emp=<?= $selectedEmpId ?>&year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>"
                   target="_blank" class="btn-pp btn-pp-outline">
                    <i class="ti ti-table"></i> Detail Sheet
                </a>
                <a href="<?= APP_URL ?>/modules/reports/payslip.php?emp=<?= $selectedEmpId ?>&year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>"
                   target="_blank" class="btn-pp btn-pp-outline">
                    <i class="ti ti-file-invoice"></i> Pay Slip
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($isLocked): ?>
    <div class="alert alert-warning">
        <i class="ti ti-lock"></i>
        This payroll period is <strong>locked</strong>. Time card editing is disabled.
    </div>
<?php endif; ?>

<?php if ($detail && $selectedEmp): ?>

<form method="POST" id="tcForm">
<?= csrfField() ?>
<input type="hidden" name="action"    value="save_timecard">
<input type="hidden" name="emp_id"   value="<?= $selectedEmpId ?>">
<input type="hidden" name="year"     value="<?= $selectedYear ?>">
<input type="hidden" name="month"    value="<?= $selectedMonth ?>">

<div class="card">
    <div class="card-header">
        <div>
            <h6 class="card-title mb-0">
                <?= sanitize($selectedEmp['full_name']) ?> –
                <?= monthName($selectedMonth) ?> <?= $selectedYear ?>
            </h6>
            <span class="text-muted fs-12"><?= sanitize($selectedEmp['emp_code']) ?></span>
        </div>
        <?php if (!$isLocked): ?>
        <button type="submit" class="btn-pp btn-pp-primary">
            <i class="ti ti-device-floppy"></i> Save Time Card
        </button>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="pp-table timecard-table">
            <thead>
                <tr>
                    <th style="width:80px">Date</th>
                    <th style="width:40px">Day</th>
                    <th style="width:90px">Status</th>
                    <th style="width:130px">Shift</th>
                    <?php foreach ($detail['products'] as $p): ?>
                        <th style="width:70px"><?= sanitize($p['name']) ?></th>
                    <?php endforeach; ?>
                    <th style="width:100px">Prod. Pay</th>
                    <th style="width:80px">OT</th>
                    <th style="width:80px">Day Duty</th>
                    <th style="width:80px">Travel</th>
                    <th style="width:80px">Other</th>
                    <th style="width:110px">Gross</th>
                    <th style="width:120px">Notes</th>
                </tr>
            </thead>
            <tbody id="tcBody">
            <?php foreach ($detail['days'] as $row): ?>
                <?php
                    $rowClass = '';
                    if ($row['is_sunday'])   $rowClass = 'row-sunday';
                    elseif ($row['is_saturday']) $rowClass = 'row-saturday';
                    elseif ($row['status'] === 'leave') $rowClass = 'row-leave';
                    elseif ($row['status'] === 'off')   $rowClass = 'row-off';
                    $d = $row['day'];
                    $disabled = $isLocked ? 'disabled' : '';
                    $isWorkable = in_array($row['status'], ['work', 'holiday'], true);
                ?>
                <tr class="<?= $rowClass ?>" data-date="<?= $row['date'] ?>">
                    <td class="fw-600 fs-12"><?= $row['date'] ?></td>
                    <td class="fs-12"><?= $row['day_name'] ?></td>
                    <td>
                        <?php if ($row['is_sunday']): ?>
                            <input type="hidden" name="days[<?= $d ?>][status]" value="holiday">
                            <select class="tc-status" disabled>
                                <option value="holiday" selected>Holiday</option>
                            </select>
                        <?php else: ?>
                            <select name="days[<?= $d ?>][status]"
                                    class="tc-status" <?= $disabled ?>>
                                <option value="work"    <?= $row['status'] === 'work'    ? 'selected' : '' ?>>Work</option>
                                <option value="leave"   <?= $row['status'] === 'leave'   ? 'selected' : '' ?>>Leave</option>
                                <option value="off"     <?= $row['status'] === 'off'     ? 'selected' : '' ?>>Off</option>
                                <option value="holiday" <?= $row['status'] === 'holiday' ? 'selected' : '' ?>>Holiday</option>
                            </select>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $shiftParts = $row['shift'] ? explode('-', $row['shift']) : [];
                            $shiftStartVal = trim($shiftParts[0] ?? '');
                            $shiftEndVal   = trim($shiftParts[1] ?? '');
                            $preset = '';
                            if ($shiftStartVal === '08.00' && $shiftEndVal === '14.00') {
                                $preset = 's1';
                            } elseif ($shiftStartVal === '14.00' && $shiftEndVal === '22.00') {
                                $preset = 's2';
                            }
                        ?>

                        <div class="d-flex flex-column gap-1">
                            <select class="tc-shift-preset" <?= !$isWorkable ? 'disabled' : '' ?> <?= $disabled ?>>
                                <option value="" <?= $preset === '' ? 'selected' : '' ?>>Custom</option>
                                <option value="s1" <?= $preset === 's1' ? 'selected' : '' ?>>08:00 - 14:00</option>
                                <option value="s2" <?= $preset === 's2' ? 'selected' : '' ?>>14:00 - 22:00</option>
                            </select>
                            <div class="d-flex gap-1">
                                <input type="text" name="days[<?= $d ?>][shift_start]"
                                       class="tc-shift shift-input" placeholder="08.00"
                                       value="<?= sanitize($shiftStartVal) ?>"
                                       <?= !$isWorkable ? 'disabled' : '' ?>
                                       <?= $disabled ?>>
                                <input type="text" name="days[<?= $d ?>][shift_end]"
                                       class="tc-shift shift-input" placeholder="14.00"
                                       value="<?= sanitize($shiftEndVal) ?>"
                                       <?= !$isWorkable ? 'disabled' : '' ?>
                                       <?= $disabled ?>>
                            </div>
                        </div>
                    </td>

                    <?php foreach ($detail['products'] as $p): ?>
                    <td>
                        <input type="number" min="0"
                               name="days[<?= $d ?>][qty][<?= $p['id'] ?>]"
                               class="tc-qty qty-input"
                               data-product-id="<?= $p['id'] ?>"
                               value="<?= $row['quantities'][$p['id']] ?? 0 ?>"
                               <?= !$isWorkable ? 'disabled' : '' ?>
                               <?= $disabled ?>>
                    </td>
                    <?php endforeach; ?>

                    <td class="tc-prodpay col-prodpay">
                        <?= $row['production'] > 0 ? formatRs($row['production']) : '-' ?>
                    </td>

                    <td><input type="number" min="0" step="0.01"
                               name="days[<?= $d ?>][ot]" class="tc-ot money-input"
                               value="<?= $row['ot'] > 0 ? $row['ot'] : '' ?>"
                               placeholder="0" <?= $disabled ?>></td>

                    <td><input type="number" min="0" step="0.01"
                               name="days[<?= $d ?>][day_duty]" class="tc-dayduty money-input"
                               value="<?= $row['day_duty'] > 0 ? $row['day_duty'] : '' ?>"
                               placeholder="0" <?= $disabled ?>></td>

                    <td><input type="number" min="0" step="0.01"
                               name="days[<?= $d ?>][travel]" class="tc-travel money-input"
                               value="<?= $row['travelling'] > 0 ? $row['travelling'] : '' ?>"
                               placeholder="0" <?= $disabled ?>></td>

                    <td><input type="number" min="0" step="0.01"
                               name="days[<?= $d ?>][other]" class="tc-other money-input"
                               value="<?= $row['other'] > 0 ? $row['other'] : '' ?>"
                               placeholder="0" <?= $disabled ?>></td>

                    <td class="tc-total col-total">
                        <?= $row['gross'] > 0 ? formatRs($row['gross']) : '-' ?>
                    </td>

                    <td><input type="text" name="days[<?= $d ?>][notes]"
                               value="<?= sanitize($row['notes']) ?>"
                               placeholder="Notes…" style="width:110px"
                               <?= $disabled ?>></td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="4" class="fw-600">Grand Total</td>
                    <?php foreach ($detail['products'] as $p): ?>
                        <td class="fw-600"><?= $detail['summary']['product_totals'][$p['id']] ?></td>
                    <?php endforeach; ?>
                    <td id="foot-prod"    class="col-prodpay"><?= formatRs($detail['summary']['total_production']) ?></td>
                    <td id="foot-ot"     ><?= formatRs($detail['summary']['total_ot']) ?></td>
                    <td id="foot-dayduty"><?= formatRs($detail['summary']['total_day_duty']) ?></td>
                    <td id="foot-travel" ><?= formatRs($detail['summary']['total_travelling']) ?></td>
                    <td id="foot-other"  ><?= formatRs($detail['summary']['total_other']) ?></td>
                    <td id="foot-gross" class="col-total text-green"><?= formatRs($detail['summary']['gross_pay']) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if (!$isLocked): ?>
<div class="mt-3 text-end">
    <button type="submit" form="tcForm" class="btn-pp btn-pp-primary">
        <i class="ti ti-device-floppy"></i> Save Time Card
    </button>
</div>
<?php endif; ?>
</form>

<?php endif; ?>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
