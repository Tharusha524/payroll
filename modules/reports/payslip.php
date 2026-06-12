<?php
// =============================================================
//  modules/reports/payslip.php
//  Printable pay slip for one employee / month
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle  = 'Pay Slip';
$activeMenu = 'payslip';

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

$detail = ($emp)
    ? $engine->buildMonthlyDetail($empId, $year, $month)
    : null;

$s = $detail['summary'] ?? null;

require ROOT_PATH . '/templates/layout.php';
?>

<!-- Controls (hidden on print) -->
<div class="d-flex gap-2 mb-4 no-print flex-wrap align-items-end">
    <div class="field-group">
        <label class="form-label">Employee</label>
        <select class="form-select" id="empSel" style="width:220px"
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
        <select class="form-select" id="yrSel" style="width:90px"
                onchange="location.href='?emp=<?= $empId ?>&year='+this.value+'&month=<?= $month ?>'">
            <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="field-group">
        <label class="form-label">Month</label>
        <select class="form-select" id="monSel" style="width:120px"
                onchange="location.href='?emp=<?= $empId ?>&year=<?= $year ?>&month='+this.value">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= monthName($m) ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php if ($emp && $s): ?>
    <button onclick="window.print()" class="btn-pp btn-pp-primary">
        <i class="ti ti-printer"></i> Print Pay Slip
    </button>
    <?php endif; ?>
</div>

<?php if ($emp && $s): ?>
<div class="payslip-print">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="payslip-company-name">Panda Consumer Products</div>
            <div class="text-muted fs-12">Payroll Division</div>
        </div>
        <div class="text-end">
            <div class="fw-600" style="font-size:15px">PAY SLIP</div>
            <div class="text-muted fs-12"><?= monthName($month) ?> <?= $year ?></div>
        </div>
    </div>

    <hr class="payslip-divider">

    <!-- Employee info -->
    <div class="row mb-4">
        <div class="col-6">
            <table style="font-size:13px;width:100%">
                <tr><td class="text-muted pe-3" style="width:130px">Employee Name</td>
                    <td class="fw-600"><?= sanitize($emp['full_name']) ?></td></tr>
                <tr><td class="text-muted">Employee Code</td>
                    <td><?= sanitize($emp['emp_code']) ?></td></tr>
                <tr><td class="text-muted">Department</td>
                    <td><?= sanitize($emp['dept_name']) ?></td></tr>
                <tr><td class="text-muted">Designation</td>
                    <td><?= sanitize($emp['designation']) ?></td></tr>
            </table>
        </div>
        <div class="col-6">
            <table style="font-size:13px;width:100%">
                <tr><td class="text-muted pe-3" style="width:130px">Pay Period</td>
                    <td class="fw-600"><?= monthName($month) ?> <?= $year ?></td></tr>
                <tr><td class="text-muted">Days Worked</td>
                    <td><?= $s['days_worked'] ?></td></tr>
                <tr><td class="text-muted">Days Leave</td>
                    <td><?= $s['days_leave'] ?></td></tr>
                <tr><td class="text-muted">NIC</td>
                    <td><?= sanitize($emp['nic']) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Earnings -->
    <div style="font-size:13px">
        <div class="fw-600 mb-2" style="font-size:12px;text-transform:uppercase;
             letter-spacing:.05em;color:#6b7280;border-bottom:1px solid #e5e7eb;padding-bottom:6px">
            Earnings
        </div>

        <!-- Production breakdown -->
        <?php
        $productTotals = $s['product_totals'] ?? [];
        foreach ($detail['products'] as $p):
            $qty = $productTotals[$p['id']] ?? 0;
            if ($qty <= 0) continue;
        ?>
        <div class="payslip-row">
            <span><?= sanitize($p['name']) ?> (<?= $qty ?> units)</span>
            <span class="amount">—</span>
        </div>
        <?php endforeach; ?>

        <div class="payslip-row" style="font-weight:600">
            <span>Production Allowance</span>
            <span class="amount"><?= formatRs($s['total_production']) ?></span>
        </div>

        <?php if ($s['total_ot'] > 0): ?>
        <div class="payslip-row">
            <span>Overtime Allowance</span>
            <span class="amount"><?= formatRs($s['total_ot']) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($s['total_day_duty'] > 0): ?>
        <div class="payslip-row">
            <span>Day Duty Allowance</span>
            <span class="amount"><?= formatRs($s['total_day_duty']) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($s['total_travelling'] > 0): ?>
        <div class="payslip-row">
            <span>Travelling Allowance</span>
            <span class="amount"><?= formatRs($s['total_travelling']) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($s['total_other'] > 0): ?>
        <div class="payslip-row">
            <span>Other Allowances</span>
            <span class="amount"><?= formatRs($s['total_other']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Total -->
    <div class="payslip-total-row">
        <span>GROSS PAY</span>
        <span class="amount"><?= formatRs($s['gross_pay']) ?></span>
    </div>

    <!-- Bank info -->
    <?php if ($emp['bank_name'] || $emp['account_number']): ?>
    <div class="mt-4 pt-3" style="border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280">
        <div class="fw-600 mb-1" style="color:#374151">Bank Transfer Details</div>
        <div><?= sanitize($emp['bank_name'] ?? '') ?>
             <?= $emp['bank_branch'] ? '– ' . sanitize($emp['bank_branch']) : '' ?>
        </div>
        <div>A/C: <?= sanitize($emp['account_number'] ?? '') ?></div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="mt-5 pt-4" style="border-top:1px dashed #d1d5db;
         display:flex;justify-content:space-between;font-size:12px;color:#9ca3af">
        <div>
            <div style="border-top:1px solid #374151;padding-top:4px;width:160px;color:#374151;
                        margin-top:36px">Employee Signature</div>
        </div>
        <div class="text-end">
            <div>Generated: <?= date('d M Y, H:i') ?></div>
            <div>Panda Consumer Products – Confidential</div>
        </div>
        <div>
            <div style="border-top:1px solid #374151;padding-top:4px;width:160px;color:#374151;
                        margin-top:36px;text-align:center">Authorised Signature</div>
        </div>
    </div>
</div><!-- /payslip-print -->
<?php endif; ?>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
