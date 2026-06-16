<?php
// =============================================================
//  modules/employees/view.php  –  Employee profile view
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$db  = Database::getInstance();
$id  = getInt('id');

if (!$id) redirect('/modules/employees/index.php');

$emp = $db->fetchOne(
    'SELECT e.*, d.name AS dept_name
       FROM employees e
       JOIN departments d ON d.id = e.department_id
      WHERE e.id = ?', [$id]
);
if (!$emp) { flash('danger', 'Employee not found.'); redirect('/modules/employees/index.php'); }

$pageTitle  = sanitize($emp['full_name']);
$activeMenu = 'employees';
require ROOT_PATH . '/templates/layout.php';
?>

<div class="row g-4">
    <!-- Profile card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <div class="emp-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:26px">
                    <?php if ($emp['photo']): ?>
                        <img src="<?= APP_URL ?>/uploads/employees/<?= sanitize($emp['photo']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($emp['full_name'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                <h5 class="mb-1"><?= sanitize($emp['full_name']) ?></h5>
                <p class="text-muted fs-13 mb-2"><?= sanitize($emp['designation']) ?></p>
                <span class="badge rounded-pill badge-<?= $emp['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $emp['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <?php if (Auth::isAdmin()): ?>
                    <a href="edit.php?id=<?= $emp['id'] ?>" class="btn-pp btn-pp-outline btn-pp-sm">
                        <i class="ti ti-edit"></i> Edit
                    </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/modules/timecard/index.php?emp=<?= $emp['id'] ?>"
                       class="btn-pp btn-pp-primary btn-pp-sm">
                        <i class="ti ti-clock"></i> Time Card
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="col-lg-8">
        <!-- Personal -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Personal Information</h6></div>
            <div class="card-body">
                <div class="row g-3 fs-13">
                    <div class="col-md-4"><span class="text-muted d-block">Employee Code</span><strong><?= sanitize($emp['emp_code']) ?></strong></div>
                    <div class="col-md-4"><span class="text-muted d-block">NIC</span><?= sanitize($emp['nic']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Gender</span><?= ucfirst($emp['gender']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Date of Birth</span><?= formatDate($emp['date_of_birth']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Join Date</span><?= formatDate($emp['join_date']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Department</span><?= sanitize($emp['dept_name']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Phone</span><?= sanitize($emp['phone']) ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Email</span><?= sanitize($emp['email'] ?? '–') ?></div>
                    <div class="col-12"><span class="text-muted d-block">Address</span><?= sanitize($emp['address']) ?></div>
                </div>
            </div>
        </div>
        <!-- Bank -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Bank Details</h6></div>
            <div class="card-body">
                <div class="row g-3 fs-13">
                    <div class="col-md-4"><span class="text-muted d-block">Bank</span><?= sanitize($emp['bank_name'] ?? '–') ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Branch</span><?= sanitize($emp['bank_branch'] ?? '–') ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Account No.</span><?= sanitize($emp['account_number'] ?? '–') ?></div>
                </div>
            </div>
        </div>
        <!-- Emergency -->
        <div class="card">
            <div class="card-header"><h6 class="card-title">Emergency Contact</h6></div>
            <div class="card-body">
                <div class="row g-3 fs-13">
                    <div class="col-md-4"><span class="text-muted d-block">Name</span><?= sanitize($emp['emergency_name'] ?? '–') ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Phone</span><?= sanitize($emp['emergency_phone'] ?? '–') ?></div>
                    <div class="col-md-4"><span class="text-muted d-block">Relationship</span><?= sanitize($emp['emergency_relation'] ?? '–') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
