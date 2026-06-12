<?php
// =============================================================
//  modules/settings/departments.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Departments';
$activeMenu = 'departments';
$db         = Database::getInstance();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = postStr('action');

    if ($action === 'save') {
        $did  = postInt('dept_id');
        $name = postStr('name');
        if ($name === '') { $errors[] = 'Department name is required.'; }

        if (empty($errors)) {
            $dup = $db->fetchOne(
                'SELECT id FROM departments WHERE name = ? AND id != ?', [$name, $did]
            );
            if ($dup) $errors[] = 'Department name already exists.';
        }

        if (empty($errors)) {
            if ($did > 0) {
                $db->execute('UPDATE departments SET name = ? WHERE id = ?', [$name, $did]);
                flash('success', 'Department updated.');
            } else {
                $db->insert('INSERT INTO departments (name) VALUES (?)', [$name]);
                flash('success', "Department '$name' added.");
            }
            redirect('/modules/settings/departments.php');
        }
    }

    if ($action === 'delete') {
        $did = postInt('dept_id');
        $inUse = $db->fetchOne('SELECT COUNT(*) AS c FROM employees WHERE department_id = ?', [$did])['c'];
        if ($inUse > 0) {
            flash('danger', 'Cannot delete – employees are assigned to this department.');
        } else {
            $db->execute('DELETE FROM departments WHERE id = ?', [$did]);
            flash('success', 'Department deleted.');
        }
        redirect('/modules/settings/departments.php');
    }
}

$departments = $db->fetchAll(
    'SELECT d.*, COUNT(e.id) AS emp_count
       FROM departments d
       LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
      GROUP BY d.id
      ORDER BY d.name'
);

require ROOT_PATH . '/templates/layout.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Departments</h6>
                <button class="btn-pp btn-pp-primary btn-pp-sm" onclick="openForm(0,'')">
                    <i class="ti ti-plus"></i> Add
                </button>
            </div>
            <div class="table-responsive">
                <table class="pp-table">
                    <thead>
                        <tr><th>Department Name</th><th>Active Employees</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($departments as $d): ?>
                        <tr>
                            <td class="fw-600"><?= sanitize($d['name']) ?></td>
                            <td>
                                <span class="badge rounded-pill badge-active"><?= $d['emp_count'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-pp btn-pp-outline btn-pp-sm"
                                            onclick="openForm(<?= $d['id'] ?>,'<?= addslashes($d['name']) ?>')">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <?php if ($d['emp_count'] == 0): ?>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Delete <?= addslashes($d['name']) ?>?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn-pp btn-pp-danger btn-pp-sm">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="card-title" id="formTitle">Add Department</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="dept_id" id="f_did" value="0">
                    <div class="mb-4">
                        <label class="form-label">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="f_name" class="form-control" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-pp btn-pp-primary">
                            <i class="ti ti-device-floppy"></i> Save
                        </button>
                        <button type="button" onclick="openForm(0,'')" class="btn-pp btn-pp-outline">
                            <i class="ti ti-x"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openForm(id, name) {
    document.getElementById('f_did').value = id;
    document.getElementById('f_name').value = name;
    document.getElementById('formTitle').textContent = id > 0 ? 'Edit Department' : 'Add Department';
}
</script>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
