<?php
// =============================================================
//  modules/employees/index.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle  = 'Employees';
$activeMenu = 'employees';

$db = Database::getInstance();

// Filters
$search   = getStr('search');
$deptId   = getInt('dept');
$status   = getStr('status', 'active');

$params = [];
$where  = ['1=1'];

if ($search !== '') {
    $where[]  = '(e.full_name LIKE ? OR e.emp_code LIKE ? OR e.nic LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($deptId > 0) {
    $where[]  = 'e.department_id = ?';
    $params[] = $deptId;
}
if ($status === 'active') {
    $where[] = 'e.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'e.is_active = 0';
}

$whereStr  = implode(' AND ', $where);
$employees = $db->fetchAll(
    "SELECT e.*, d.name AS dept_name
       FROM employees e
       JOIN departments d ON d.id = e.department_id
      WHERE $whereStr
      ORDER BY e.emp_code",
    $params
);

$departments = $db->fetchAll('SELECT id, name FROM departments ORDER BY name');

require ROOT_PATH . '/templates/layout.php';
?>

<!-- Filter bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Name, code, NIC…" value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="dept" class="form-select">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>>
                            <?= sanitize($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="all"      <?= $status === 'all'      ? 'selected' : '' ?>>All</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-pp btn-pp-primary">
                    <i class="ti ti-search"></i> Filter
                </button>
                <a href="?" class="btn-pp btn-pp-outline">
                    <i class="ti ti-x"></i> Clear
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="add.php" class="btn-pp btn-pp-primary ms-auto">
                    <i class="ti ti-user-plus"></i> Add
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Employee table -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title">
            <?= count($employees) ?> employee<?= count($employees) !== 1 ? 's' : '' ?> found
        </h6>
    </div>
    <div class="table-responsive">
        <table class="pp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>NIC</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
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
                            <div>
                                <div class="fw-600 fs-13"><?= sanitize($emp['full_name']) ?></div>
                                <div class="text-muted fs-12"><?= sanitize($emp['email'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code class="fs-12"><?= sanitize($emp['emp_code']) ?></code></td>
                    <td class="fs-12"><?= sanitize($emp['nic']) ?></td>
                    <td><?= sanitize($emp['dept_name']) ?></td>
                    <td><?= sanitize($emp['designation']) ?></td>
                    <td><?= sanitize($emp['phone']) ?></td>
                    <td class="text-muted fs-12"><?= formatDate($emp['join_date']) ?></td>
                    <td>
                        <span class="badge rounded-pill <?= $emp['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $emp['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="view.php?id=<?= $emp['id'] ?>"
                               class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="View Profile">
                                <i class="ti ti-eye"></i>
                            </a>
                            <?php if (Auth::isAdmin()): ?>
                            <a href="edit.php?id=<?= $emp['id'] ?>"
                               class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= APP_URL ?>/modules/timecard/index.php?emp=<?= $emp['id'] ?>"
                               class="btn-pp btn-pp-outline btn-pp-sm"
                               data-bs-toggle="tooltip" title="Time Card">
                                <i class="ti ti-clock"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No employees found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
