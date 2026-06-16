<?php
// =============================================================
//  modules/payroll/summary.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle  = 'Monthly Payroll Summary';
$activeMenu = 'payroll';

$db    = Database::getInstance();
$year  = getInt('year',  (int) date('Y'));
$month = getInt('month', (int) date('m'));

// Recompute all if requested (admin)
if (Auth::isAdmin() && isset($_GET['recompute'])) {
    verifyCsrf();
    $engine    = new PayrollEngine();
    $employees = $db->fetchAll('SELECT id FROM employees WHERE is_active = 1');
    foreach ($employees as $e) {
        $engine->computeAndSaveSummary((int)$e['id'], $year, $month);
    }
    flash('success', 'Payroll recomputed for all employees.');
    redirect("/modules/payroll/summary.php?year=$year&month=$month");
}

// Load summaries joined with employee info
$rows = $db->fetchAll(
    'SELECT e.id, e.emp_code, e.full_name, e.photo, d.name AS dept_name,
            COALESCE(ps.days_worked,0)      AS days_worked,
            COALESCE(ps.days_leave,0)       AS days_leave,
            COALESCE(ps.total_production,0) AS total_production,
            COALESCE(ps.total_ot,0)         AS total_ot,
            COALESCE(ps.total_day_duty,0)   AS total_day_duty,
            COALESCE(ps.total_travelling,0) AS total_travelling,
            COALESCE(ps.total_other,0)      AS total_other,
            COALESCE(ps.gross_pay,0)        AS gross_pay,
            ps.is_locked
       FROM employees e
       JOIN departments d ON d.id = e.department_id
       LEFT JOIN payroll_summaries ps
              ON ps.employee_id = e.id
             AND ps.payroll_year  = ?
             AND ps.payroll_month = ?
      WHERE e.is_active = 1
      ORDER BY e.emp_code',
    [$year, $month]
);

// Totals
$totals = ['days_worked'=>0,'total_production'=>0,'total_ot'=>0,
           'total_day_duty'=>0,'total_travelling'=>0,'total_other'=>0,'gross_pay'=>0];
foreach ($rows as $r) {
    foreach (array_keys($totals) as $k) {
        $totals[$k] += (float) $r[$k];
    }
}

require ROOT_PATH . '/templates/layout.php';
?>

<!-- Month picker -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                            <?= monthName($m) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-pp btn-pp-primary">
                    <i class="ti ti-arrow-right"></i> Load
                </button>
                <?php if (Auth::isAdmin()): ?>
                <a href="?year=<?= $year ?>&month=<?= $month ?>&recompute=1&csrf_token=<?= csrfToken() ?>"
                   class="btn-pp btn-pp-outline"
                   onclick="return confirm('Recompute payroll for all employees for <?= monthName($month).' '.$year ?>?')">
                    <i class="ti ti-refresh"></i> Recompute All
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Metric row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-label">Total Payroll</div>
            <div class="metric-value green" style="font-size:22px"><?= formatRs($totals['gross_pay']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-label">Total Production Pay</div>
            <div class="metric-value" style="font-size:20px"><?= formatRs($totals['total_production']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-label">Total Allowances</div>
            <div class="metric-value" style="font-size:20px">
                <?= formatRs($totals['total_ot'] + $totals['total_day_duty'] + $totals['total_travelling'] + $totals['total_other']) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-label">Working Days (total)</div>
            <div class="metric-value"><?= $totals['days_worked'] ?></div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title"><?= monthName($month) ?> <?= $year ?> – All Employees</h6>
        <a href="<?= APP_URL ?>/modules/reports/detail_sheet.php?all=1&year=<?= $year ?>&month=<?= $month ?>"
           target="_blank" class="btn-pp btn-pp-outline btn-pp-sm">
            <i class="ti ti-download"></i> Export All Detail Sheets
        </a>
    </div>
    <div class="table-responsive">
        <table class="pp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Dept</th>
                    <th>Days</th>
                    <th>Leaves</th>
                    <th>Prod. Pay</th>
                    <th>OT</th>
                    <th>Day Duty</th>
                    <th>Travel</th>
                    <th>Other</th>
                    <th>Gross Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="emp-avatar">
                                <?php if ($r['photo']): ?>
                                    <img src="<?= APP_URL ?>/uploads/employees/<?= sanitize($r['photo']) ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(substr($r['full_name'], 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-600 fs-13"><?= sanitize($r['full_name']) ?></div>
                                <div class="text-muted fs-12"><?= sanitize($r['emp_code']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="fs-12"><?= sanitize($r['dept_name']) ?></td>
                    <td><?= $r['days_worked'] ?></td>
                    <td><?= $r['days_leave'] ?></td>
                    <td><?= formatRs($r['total_production']) ?></td>
                    <td><?= $r['total_ot'] > 0 ? formatRs($r['total_ot']) : '-' ?></td>
                    <td><?= $r['total_day_duty'] > 0 ? formatRs($r['total_day_duty']) : '-' ?></td>
                    <td><?= $r['total_travelling'] > 0 ? formatRs($r['total_travelling']) : '-' ?></td>
                    <td><?= $r['total_other'] > 0 ? formatRs($r['total_other']) : '-' ?></td>
                    <td class="fw-600 text-green"><?= formatRs($r['gross_pay']) ?></td>
                    <td>
                        <span class="badge rounded-pill <?= $r['is_locked'] ? 'badge-inactive' : 'badge-active' ?>">
                            <?= $r['is_locked'] ? 'Locked' : 'Open' ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= APP_URL ?>/modules/reports/payslip.php?emp=<?= $r['id'] ?>&year=<?= $year ?>&month=<?= $month ?>"
                               target="_blank" class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="Pay Slip">
                                <i class="ti ti-file-invoice"></i>
                            </a>
                            <a href="<?= APP_URL ?>/modules/reports/detail_sheet.php?emp=<?= $r['id'] ?>&year=<?= $year ?>&month=<?= $month ?>"
                               target="_blank" class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="Detail Sheet">
                                <i class="ti ti-table"></i>
                            </a>
                            <a href="<?= APP_URL ?>/modules/timecard/index.php?emp=<?= $r['id'] ?>&year=<?= $year ?>&month=<?= $month ?>"
                               class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="Edit Time Card">
                                <i class="ti ti-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="fw-600">Totals</td>
                    <td class="fw-600"><?= $totals['days_worked'] ?></td>
                    <td>–</td>
                    <td class="fw-600"><?= formatRs($totals['total_production']) ?></td>
                    <td class="fw-600"><?= formatRs($totals['total_ot']) ?></td>
                    <td class="fw-600"><?= formatRs($totals['total_day_duty']) ?></td>
                    <td class="fw-600"><?= formatRs($totals['total_travelling']) ?></td>
                    <td class="fw-600"><?= formatRs($totals['total_other']) ?></td>
                    <td class="fw-600 text-green" style="font-size:15px"><?= formatRs($totals['gross_pay']) ?></td>
                    <td colspan="2">–</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
