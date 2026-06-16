<?php
// =============================================================
//  modules/reports/detail_sheet.php
//  Full daily detail sheet (1–31 days) – printable
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle  = 'Detail Sheet';
$activeMenu = 'detail_sheet';

$db     = Database::getInstance();
$engine = new PayrollEngine();

$empId = getInt('emp');
$year  = getInt('year',  (int) date('Y'));
$month = getInt('month', (int) date('m'));

$employees = $db->fetchAll(
    'SELECT id, emp_code, full_name FROM employees WHERE is_active = 1 ORDER BY emp_code'
);

if ($empId === 0 && !empty($employees)) {
    $empId = (int) $employees[0]['id'];
}

$emp = $empId
    ? $db->fetchOne(
        'SELECT e.*, d.name AS dept_name
           FROM employees e
           JOIN departments d ON d.id = e.department_id
          WHERE e.id = ?', [$empId])
    : null;

$detail   = ($emp) ? $engine->buildMonthlyDetail($empId, $year, $month) : null;
$products = $detail['products'] ?? [];
$s        = $detail['summary'] ?? null;

require ROOT_PATH . '/templates/layout.php';
?>

<!-- Controls -->
<div class="d-flex gap-2 mb-4 no-print flex-wrap align-items-end">
    <div class="field-group">
        <label class="form-label">Employee</label>
        <select class="form-select" style="width:220px"
                onchange="location.href='?emp='+this.value+'&year=<?= $year ?>&month=<?= $month ?>'">
            <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $e['id'] == $empId ? 'selected' : '' ?>>
                    <?= sanitize($e['emp_code']) ?> – <?= sanitize($e['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field-group">
        <label class="form-label">Year</label>
        <select class="form-select" style="width:90px"
                onchange="location.href='?emp=<?= $empId ?>&year='+this.value+'&month=<?= $month ?>'">
            <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="field-group">
        <label class="form-label">Month</label>
        <select class="form-select" style="width:120px"
                onchange="location.href='?emp=<?= $empId ?>&year=<?= $year ?>&month='+this.value">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= monthName($m) ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php if ($emp && $detail): ?>
    <button onclick="window.print()" class="btn-pp btn-pp-primary">
        <i class="ti ti-printer"></i> Print Detail Sheet
    </button>
    <?php endif; ?>
</div>

<?php if ($emp && $detail): ?>

<div class="detail-sheet-wrap" style="max-width:1100px">

    <!-- Sheet header -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div style="font-size:18px;font-weight:700;color:var(--pp-green-dark)">
                Panda Consumer Products
            </div>
            <div class="text-muted fs-12">Monthly Salary / Time Card Detail</div>
        </div>
        <div class="text-end fs-12">
            <div class="fw-600"><?= monthName($month) ?> <?= $year ?></div>
            <div class="text-muted">Generated: <?= date('d M Y H:i') ?></div>
        </div>
    </div>

    <!-- Employee info strip -->
    <div style="background:#f0fdf8;border:1px solid #9FE1CB;border-radius:8px;
                padding:12px 16px;font-size:13px;margin-bottom:16px">
        <div class="row">
            <div class="col-md-4">
                <span class="text-muted">Name:</span>
                <strong class="ms-1"><?= sanitize($emp['full_name']) ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted">Code:</span>
                <strong class="ms-1"><?= sanitize($emp['emp_code']) ?></strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted">Department:</span>
                <strong class="ms-1"><?= sanitize($emp['dept_name']) ?></strong>
            </div>
            <div class="col-md-2">
                <span class="text-muted">NIC:</span>
                <strong class="ms-1"><?= sanitize($emp['nic']) ?></strong>
            </div>
        </div>
    </div>

    <!-- Detail table -->
    <div class="table-responsive">
        <table class="pp-table" style="font-size:12px">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Status</th>
                    <th>Shift</th>
                    <?php foreach ($products as $p): ?>
                        <th><?= sanitize($p['name']) ?></th>
                    <?php endforeach; ?>
                    <th>Prod. Pay</th>
                    <th class="col-ot">OT</th>
                    <th>Day Duty</th>
                    <th class="col-travel">Travel</th>
                    <th class="col-other">Other</th>
                    <th>Total</th>
                    <th class="col-notes">Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detail['days'] as $row): ?>
                <?php
                $rowStyle = '';
                if ($row['is_sunday'])  $rowStyle = 'background:#fef9f0;color:#9ca3af';
                elseif ($row['is_saturday']) $rowStyle = 'background:#f0fdf8';
                elseif ($row['status'] === 'leave') $rowStyle = 'background:#fff7ed;color:#92400e';
                elseif ($row['status'] === 'off')   $rowStyle = 'background:#f9fafb;color:#d1d5db';
                ?>
                <tr style="<?= $rowStyle ?>">
                    <td style="white-space:nowrap"><?= $row['date'] ?></td>
                    <td><?= $row['day_name'] ?></td>
                    <td>
                        <span class="badge rounded-pill badge-<?= $row['status'] ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap"><?= sanitize($row['shift']) ?: '-' ?></td>
                    <?php foreach ($products as $p): ?>
                        <td class="text-center">
                            <?= ($row['quantities'][$p['id']] ?? 0) > 0 ? $row['quantities'][$p['id']] : '-' ?>
                        </td>
                    <?php endforeach; ?>
                    <td style="color:#0F6E56;font-weight:600">
                        <?= $row['production'] > 0 ? formatRs($row['production']) : '-' ?>
                    </td>
                    <td class="col-ot"><?= $row['ot']         > 0 ? formatRs($row['ot'])         : '-' ?></td>
                    <td><?= $row['day_duty']   > 0 ? formatRs($row['day_duty'])   : '-' ?></td>
                    <td class="col-travel"><?= $row['travelling'] > 0 ? formatRs($row['travelling']) : '-' ?></td>
                    <td class="col-other"><?= $row['other']      > 0 ? formatRs($row['other'])      : '-' ?></td>
                    <td style="font-weight:600">
                        <?= $row['gross'] > 0 ? formatRs($row['gross']) : '-' ?>
                    </td>
                    <td class="text-muted col-notes"><?= sanitize($row['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f0fdf8;font-weight:700">
                    <td colspan="4">Grand Total – <?= $s['days_worked'] ?> day(s) worked, <?= $s['days_leave'] ?> leave(s)</td>
                    <?php foreach ($products as $p): ?>
                        <td class="text-center"><?= $s['product_totals'][$p['id']] ?></td>
                    <?php endforeach; ?>
                    <td style="color:#0F6E56"><?= formatRs($s['total_production']) ?></td>
                    <td class="col-ot"><?= formatRs($s['total_ot']) ?></td>
                    <td><?= formatRs($s['total_day_duty']) ?></td>
                    <td class="col-travel"><?= formatRs($s['total_travelling']) ?></td>
                    <td class="col-other"><?= formatRs($s['total_other']) ?></td>
                    <td style="color:#0F6E56;font-size:14px"><?= formatRs($s['gross_pay']) ?></td>
                    <td class="col-notes"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary box -->
    <div class="row g-3 mt-3">
        <div class="col-md-5">
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-size:13px">
                <div class="fw-600 mb-2" style="font-size:12px;text-transform:uppercase;
                     letter-spacing:.05em;color:#6b7280">Earnings Summary</div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Production Allowance</span>
                    <span class="fw-600"><?= formatRs($s['total_production']) ?></span>
                </div>
                <?php if ($s['total_ot'] > 0): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Overtime</span><span><?= formatRs($s['total_ot']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($s['total_day_duty'] > 0): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Day Duty</span><span><?= formatRs($s['total_day_duty']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($s['total_travelling'] > 0): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Travelling</span><span><?= formatRs($s['total_travelling']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($s['total_other'] > 0): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>Other</span><span><?= formatRs($s['total_other']) ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between pt-2 mt-1"
                     style="border-top:2px solid #1D9E75;font-size:15px;font-weight:700;color:#0F6E56">
                    <span>GROSS PAY</span>
                    <span><?= formatRs($s['gross_pay']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-size:13px">
                <div class="fw-600 mb-2" style="font-size:12px;text-transform:uppercase;
                     letter-spacing:.05em;color:#6b7280">Attendance Summary</div>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div style="font-size:24px;font-weight:700;color:#1D9E75"><?= $s['days_worked'] ?></div>
                        <div class="text-muted" style="font-size:11px">Days Worked</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:24px;font-weight:700;color:#d97706"><?= $s['days_leave'] ?></div>
                        <div class="text-muted" style="font-size:11px">Days Leave</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:24px;font-weight:700;color:#6b7280">
                            <?= daysInMonth($year, $month) - $s['days_worked'] - $s['days_leave'] ?>
                        </div>
                        <div class="text-muted" style="font-size:11px">Off / Holiday</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Signature row -->
    <div class="mt-5 d-flex justify-content-between"
         style="font-size:12px;color:#9ca3af">
        <div>
            <div style="border-top:1px solid #374151;width:160px;padding-top:4px;
                        color:#374151;margin-top:36px">Employee Signature</div>
        </div>
        <div>
            <div style="border-top:1px solid #374151;width:160px;padding-top:4px;
                        color:#374151;margin-top:36px;text-align:center">Prepared By</div>
        </div>
        <div>
            <div style="border-top:1px solid #374151;width:160px;padding-top:4px;
                        color:#374151;margin-top:36px;text-align:right">Authorised By</div>
        </div>
    </div>

</div>
<?php endif; ?>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
