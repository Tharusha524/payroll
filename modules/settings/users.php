<?php
// =============================================================
//  modules/settings/users.php  –  System user management
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'System Users';
$activeMenu = 'users';
$db         = Database::getInstance();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = postStr('action');

    if ($action === 'add_user') {
        $username  = postStr('username');
        $fullName  = postStr('full_name');
        $role      = postStr('role');
        $password  = postStr('password');
        $password2 = postStr('password2');

        if ($username === '')  $errors[] = 'Username is required.';
        if ($fullName === '')  $errors[] = 'Full name is required.';
        if ($password === '')  $errors[] = 'Password is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $dup = $db->fetchOne('SELECT id FROM users WHERE username = ?', [$username]);
            if ($dup) { $errors[] = 'Username already exists.'; }
        }

        if (empty($errors)) {
            // Store legacy MD5 password column as requested
            $hash = md5($password);
            $db->insert(
                'INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)',
                [$username, $hash, $fullName, $role]
            );
            AuditLog::write('create', 'users', null, "Added user: $username");
            flash('success', "User '$username' created.");
            redirect('/modules/settings/users.php');
        }
    }

    if ($action === 'toggle_user') {
        $uid = postInt('user_id');
        if ($uid === Auth::id()) {
            flash('danger', 'You cannot deactivate your own account.');
        } else {
            $db->execute('UPDATE users SET is_active = NOT is_active WHERE id = ?', [$uid]);
            AuditLog::write('update', 'users', $uid, 'Toggled user status');
            flash('success', 'User status updated.');
        }
        redirect('/modules/settings/users.php');
    }

    if ($action === 'change_password') {
        $uid  = postInt('user_id');
        $pwd  = postStr('new_password');
        $pwd2 = postStr('new_password2');
        if (strlen($pwd) < 8)   $errors[] = 'Password must be at least 8 characters.';
        if ($pwd !== $pwd2)     $errors[] = 'Passwords do not match.';
        if (empty($errors)) {
            $hash = md5($pwd);
            $db->execute('UPDATE users SET password = ? WHERE id = ?', [$hash, $uid]);
            AuditLog::write('update', 'users', $uid, 'Password changed');
            flash('success', 'Password updated.');
            redirect('/modules/settings/users.php');
        }
    }
}

$users = $db->fetchAll('SELECT * FROM users ORDER BY id');
require ROOT_PATH . '/templates/layout.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Users list -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="card-title">All System Users</h6></div>
            <div class="table-responsive">
                <table class="pp-table">
                    <thead>
                        <tr><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><code class="fs-12"><?= sanitize($u['username']) ?></code></td>
                            <td><?= sanitize($u['full_name']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $u['role']==='admin' ? 'badge-admin' : 'badge-staff' ?>">
                                    <?= $u['role'] === 'admin' ? 'Admin' : 'Payroll Staff' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="POST" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-pp btn-pp-outline btn-pp-sm"
                                                <?= $u['id'] === Auth::id() ? 'disabled' : '' ?>
                                                data-bs-toggle="tooltip"
                                                title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="ti ti-<?= $u['is_active'] ? 'lock' : 'lock-open' ?>"></i>
                                        </button>
                                    </form>
                                    <button class="btn-pp btn-pp-outline btn-pp-sm"
                                            onclick="openPwdForm(<?= $u['id'] ?>, '<?= addslashes($u['username']) ?>')"
                                            data-bs-toggle="tooltip" title="Change password">
                                        <i class="ti ti-key"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add user form -->
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Add New User</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_user">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="payroll_staff">Payroll Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password2" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-pp btn-pp-primary w-100">
                        <i class="ti ti-user-plus"></i> Create User
                    </button>
                </form>
            </div>
        </div>

        <!-- Change password -->
        <div class="card" id="pwdCard" style="display:none">
            <div class="card-header"><h6 class="card-title" id="pwdTitle">Change Password</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="pwd_uid" value="">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="new_password2" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-pp btn-pp-primary">
                        <i class="ti ti-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openPwdForm(uid, username) {
    document.getElementById('pwd_uid').value   = uid;
    document.getElementById('pwdTitle').textContent = 'Change password – ' + username;
    document.getElementById('pwdCard').style.display = 'block';
    document.getElementById('pwdCard').scrollIntoView({behavior:'smooth'});
}
</script>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
