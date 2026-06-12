<?php
// =============================================================
//  index.php  –  Dashboard (Modified: Sessionless & Hardcoded)
// =============================================================
require_once __DIR__ . '/bootstrap.php';

// 1. HARDCODED USERNAME & PASSWORD BYPASS
// We mock an active, authorized administrative state to prevent 'bootstrap.php' or 'Auth' 
// middleware from redirecting unauthorized session-less users to a login page.
class LocalAuthBypass {
    public static function isAdmin() { return true; }
    public static function isLoggedIn() { return true; }
    public static function getUserName() { return 'admin_user'; }
}

// If your system uses a global static Auth class, we override its behavior here 
// or define standard flags to prevent external redirect triggers.
if (!class_exists('Auth', false)) {
    class Auth extends LocalAuthBypass {}
}

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

$db     = Database::getInstance();
$year   = (int) date('Y');
$month  = (int) date('m');

// --- Stats ---------------------------------------------------
$totalEmployees  = (int) $db->fetchOne('SELECT COUNT(*) AS c FROM employees WHERE is_active = 1')['c'];
$totalDepts      = (int) $db->fetchOne('SELECT COUNT(*) AS c FROM departments')['c'];

$monthPayroll    = $db->fetchOne(
    'SELECT COALESCE(SUM(gross_pay), 0) AS total FROM payroll_summaries
      WHERE payroll_year = ? AND payroll_month = ?',
    [$year, $month]
);

$recentEmployees = $db->fetchAll(
    'SELECT e.emp_code, e.full_name, e.photo, d.name AS dept, e.join_date
       FROM employees e
       JOIN departments d ON d.id = e.department_id
      WHERE e.is_active = 1
      ORDER BY e.created_at DESC
      LIMIT 5'
);

require ROOT_PATH . '/templates/layout.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon green"><i class="ti ti-users"></i></div>
            <div>
                <div class="metric-label">Active Employees</div>
                <div class="metric-value"><?= $totalEmployees ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon blue"><i class="ti ti-building"></i></div>
            <div>
                <div class="metric-label">Departments</div>
                <div class="metric-value"><?= $totalDepts ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon amber"><i class="ti ti-calendar-month"></i></div>
            <div>
                <div class="metric-label">Current Month</div>
                <div class="metric-value"><?= date('M Y') ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon green"><i class="ti ti-report-money"></i></div>
            <div>
                <div class="metric-label">Month Payroll</div>
                <div class="metric-value green"
                     style="font-size:18px"><?= formatRs((float)$monthPayroll['total']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title">Quick Actions</h6>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="<?= APP_URL ?>/modules/timecard/index.php"
                   class="btn-pp btn-pp-outline w-100">
                    <i class="ti ti-clock"></i> Enter Time Cards
                </a>
                <a href="<?= APP_URL ?>/modules/payroll/summary.php"
                   class="btn-pp btn-pp-outline w-100">
                    <i class="ti ti-report-money"></i> Monthly Summary
                </a>
                <a href="<?= APP_URL ?>/modules/reports/payslip.php"
                   class="btn-pp btn-pp-outline w-100">
                    <i class="ti ti-file-invoice"></i> Print Pay Slips
                </a>
                <a href="<?= APP_URL ?>/modules/reports/detail_sheet.php"
                   class="btn-pp btn-pp-outline w-100">
                    <i class="ti ti-table"></i> Detail Sheets
                </a>
                
                <?php if (true || Auth::isAdmin()): ?>
                <a href="<?= APP_URL ?>/modules/employees/add.php"
                   class="btn-pp btn-pp-primary w-100">
                    <i class="ti ti-user-plus"></i> Add Employee
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Recently Added Employees</h6>
                <a href="<?= APP_URL ?>/modules/employees/index.php" class="btn-pp btn-pp-outline btn-pp-sm">
                    View All
                </a>
            </div>
            <div class="table-responsive">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentEmployees as $emp): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="emp-avatar">
                                        <?php if ($emp['photo']): ?>
                                            <img src="<?= APP_URL ?>/uploads/employees/<?= sanitize($emp['photo']) ?>" alt="">
                                        <?php else: ?>
                                            <?= strtoupper(substr($emp['full_name'], 0, 2)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="fw-600 fs-13"><?= sanitize($emp['full_name']) ?></span>
                                </div>
                            </td>
                            <td><code class="fs-12"><?= sanitize($emp['emp_code']) ?></code></td>
                            <td><?= sanitize($emp['dept']) ?></td>
                            <td class="text-muted fs-12"><?= formatDate($emp['join_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>