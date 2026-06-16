<?php
// =============================================================
//  templates/layout.php
//  Main shell – include at top of every authenticated page:
//
//    $pageTitle   = 'Employees';      // required
//    $activeMenu  = 'employees';      // required
//    require ROOT_PATH . '/templates/layout.php';
// =============================================================
Auth::requireLogin();
$flash = getFlash();
$roleRaw  = (string) Auth::role();
$roleNorm = strtolower(trim($roleRaw));
$isPayrollStaff = ($roleNorm === 'payroll_staff' || $roleNorm === 'payroll staff');
$roleLabel = $roleNorm === 'admin' ? 'Admin' : ($isPayrollStaff ? 'Payroll Staff' : $roleRaw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Dashboard') ?> – <?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Tabler icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css?v=<?= urlencode(APP_VERSION) ?>">
</head>
<body>

<div class="app-wrapper">

    <!-- ======================================================
         SIDEBAR
    ====================================================== -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">P</div>
            <div class="brand-name">Panda Payroll</div>
            <div class="brand-sub">Consumer Products</div>
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section-label">Main</div>

            <a href="<?= APP_URL ?>/index.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <?php if (Auth::isAdmin()): ?>
            <div class="nav-section-label">Employees</div>

            <a href="<?= APP_URL ?>/modules/employees/index.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'employees' ? 'active' : '' ?>">
                <i class="ti ti-users"></i> All Employees
            </a>

            <a href="<?= APP_URL ?>/modules/employees/add.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'emp_add' ? 'active' : '' ?>">
                <i class="ti ti-user-plus"></i> Add Employee
            </a>
            <?php endif; ?>

            <?php if (Auth::isAdmin() || $isPayrollStaff): ?>
            <div class="nav-section-label">Payroll</div>

            <a href="<?= APP_URL ?>/modules/timecard/index.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'timecard' ? 'active' : '' ?>">
                <i class="ti ti-clock"></i> Time Cards
            </a>

            <a href="<?= APP_URL ?>/modules/payroll/summary.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'payroll' ? 'active' : '' ?>">
                <i class="ti ti-report-money"></i> Monthly Summary
            </a>

            <a href="<?= APP_URL ?>/modules/reports/payslip.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'payslip' ? 'active' : '' ?>">
                <i class="ti ti-file-invoice"></i> Pay Slips
            </a>

            <a href="<?= APP_URL ?>/modules/reports/detail_sheet.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'detail_sheet' ? 'active' : '' ?>">
                <i class="ti ti-table"></i> Detail Sheets
            </a>
            <?php endif; ?>

            <?php if (Auth::isAdmin()): ?>
            <div class="nav-section-label">Settings</div>

            <a href="<?= APP_URL ?>/modules/settings/products.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'products' ? 'active' : '' ?>">
                <i class="ti ti-package"></i> Products & Rates
            </a>

            <a href="<?= APP_URL ?>/modules/settings/users.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
                <i class="ti ti-shield-lock"></i> System Users
            </a>

            <a href="<?= APP_URL ?>/modules/settings/departments.php"
               class="sidebar-link <?= ($activeMenu ?? '') === 'departments' ? 'active' : '' ?>">
                <i class="ti ti-building"></i> Departments
            </a>
            <?php endif; ?>

        </nav>

        <div class="sidebar-footer">
            <div class="user-name"><?= sanitize(Auth::name()) ?></div>
            <div>
                <span class="user-role"><?= sanitize($roleLabel) ?></span>
            </div>
            <a href="<?= APP_URL ?>/modules/auth/logout.php"
               onclick="return confirm('Are you sure you want to log out?');"
               class="btn btn-success w-100 mt-4"
               style="text-decoration:none; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                Sign Out
            </a>
        </div>

    </aside>

    <!-- ======================================================
         MAIN CONTENT
    ====================================================== -->
    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <button class="btn p-0 me-2 d-md-none border-0"
                    onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="ti ti-menu-2 fs-5"></i>
            </button>
            <span class="topbar-title"><?= sanitize($pageTitle ?? '') ?></span>
            <div class="topbar-actions">
                <span class="text-muted fs-12"><?= date('d M Y') ?></span>
            </div>
        </div>

        <!-- Flash message -->
        <div class="px-4 pt-3">
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        </div>

        <!-- Page content injected here -->
        <div class="page-body">
