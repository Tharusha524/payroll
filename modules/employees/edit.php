<?php
// =============================================================
//  modules/employees/edit.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireRole('admin');

$db  = Database::getInstance();
$id  = getInt('id');
if (!$id) redirect('/modules/employees/index.php');

$emp = $db->fetchOne('SELECT * FROM employees WHERE id = ?', [$id]);
if (!$emp) { flash('danger', 'Employee not found.'); redirect('/modules/employees/index.php'); }

$pageTitle   = 'Edit – ' . $emp['full_name'];
$activeMenu  = 'employees';
$departments = $db->fetchAll('SELECT id, name FROM departments ORDER BY name');
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'full_name'          => postStr('full_name'),
        'nic'                => postStr('nic'),
        'gender'             => postStr('gender'),
        'date_of_birth'      => postStr('date_of_birth'),
        'join_date'          => postStr('join_date'),
        'department_id'      => postInt('department_id'),
        'designation'        => postStr('designation'),
        'phone'              => postStr('phone'),
        'email'              => postStr('email'),
        'address'            => postStr('address'),
        'bank_name'          => postStr('bank_name'),
        'bank_branch'        => postStr('bank_branch'),
        'account_number'     => postStr('account_number'),
        'emergency_name'     => postStr('emergency_name'),
        'emergency_phone'    => postStr('emergency_phone'),
        'emergency_relation' => postStr('emergency_relation'),
        'is_active'          => postInt('is_active', 1),
    ];

    if ($data['full_name'] === '')     $errors[] = 'Full name is required.';
    if ($data['nic'] === '')           $errors[] = 'NIC is required.';
    if ($data['department_id'] === 0)  $errors[] = 'Department is required.';
    if ($data['designation'] === '')   $errors[] = 'Designation is required.';
    if ($data['phone'] === '')         $errors[] = 'Phone is required.';

    // Check NIC uniqueness (excluding self)
    if (empty($errors)) {
        $dup = $db->fetchOne('SELECT id FROM employees WHERE nic = ? AND id != ?', [$data['nic'], $id]);
        if ($dup) $errors[] = 'NIC already used by another employee.';
    }

    // Photo upload
    $photoFilename = $emp['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $file = $_FILES['photo'];
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Photo must be JPEG, PNG, or WebP.';
        } elseif ($file['size'] > MAX_PHOTO_SIZE) {
            $errors[] = 'Photo must be under 2 MB.';
        } else {
            $ext           = pathinfo($file['name'], PATHINFO_EXTENSION);
            $photoFilename = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
            $destPath      = EMPLOYEE_PHOTO_PATH . '/' . $photoFilename;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $errors[] = 'Photo upload failed.';
                $photoFilename = $emp['photo'];
            } elseif ($emp['photo'] && file_exists(EMPLOYEE_PHOTO_PATH . '/' . $emp['photo'])) {
                unlink(EMPLOYEE_PHOTO_PATH . '/' . $emp['photo']);
            }
        }
    }

    if (empty($errors)) {
        $db->execute(
            'UPDATE employees SET
                full_name=?, nic=?, gender=?, date_of_birth=?, join_date=?,
                department_id=?, designation=?, phone=?, email=?, address=?,
                bank_name=?, bank_branch=?, account_number=?,
                emergency_name=?, emergency_phone=?, emergency_relation=?,
                photo=?, is_active=?
             WHERE id=?',
            [
                $data['full_name'], $data['nic'], $data['gender'],
                $data['date_of_birth'], $data['join_date'],
                $data['department_id'], $data['designation'],
                $data['phone'], $data['email'], $data['address'],
                $data['bank_name'], $data['bank_branch'], $data['account_number'],
                $data['emergency_name'], $data['emergency_phone'], $data['emergency_relation'],
                $photoFilename, $data['is_active'], $id,
            ]
        );
        AuditLog::write('update', 'employees', $id, 'Updated ' . $data['full_name']);
        flash('success', 'Employee updated successfully.');
        redirect('/modules/employees/view.php?id=' . $id);
    }

    // Repopulate $emp with POST data on error
    $emp = array_merge($emp, $data);
}

require ROOT_PATH . '/templates/layout.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<?= csrfField() ?>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title">Photo</h6></div>
            <div class="card-body text-center">
                <label class="photo-upload-box mx-auto mb-3" for="photo_file">
                    <?php if ($emp['photo']): ?>
                        <img id="photo_preview"
                             src="<?= APP_URL ?>/uploads/employees/<?= sanitize($emp['photo']) ?>"
                             alt="" style="display:block">
                    <?php else: ?>
                        <img id="photo_preview" src="" alt="" style="display:none">
                        <i class="ti ti-camera" style="font-size:28px;color:#9ca3af"></i>
                    <?php endif; ?>
                    <div class="overlay"><i class="ti ti-camera"></i></div>
                </label>
                <input type="file" id="photo_file" name="photo" accept="image/*" class="d-none">
                <p class="text-muted fs-12">Click to change photo<br>JPEG, PNG or WebP · Max 2 MB</p>

                <div class="mt-3">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= $emp['is_active'] ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !$emp['is_active'] ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="mt-2 text-muted fs-12">
                    Code: <strong><?= sanitize($emp['emp_code']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Personal Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= sanitize($emp['full_name']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIC <span class="text-danger">*</span></label>
                        <input type="text" name="nic" class="form-control"
                               value="<?= sanitize($emp['nic']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="male"   <?= $emp['gender']==='male'   ? 'selected':'' ?>>Male</option>
                            <option value="female" <?= $emp['gender']==='female' ? 'selected':'' ?>>Female</option>
                            <option value="other"  <?= $emp['gender']==='other'  ? 'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="<?= sanitize($emp['date_of_birth']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Join Date</label>
                        <input type="date" name="join_date" class="form-control"
                               value="<?= sanitize($emp['join_date']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= sanitize($emp['phone']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= sanitize($emp['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= sanitize($emp['address']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Job Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= $emp['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control"
                               value="<?= sanitize($emp['designation']) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Bank Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control"
                               value="<?= sanitize($emp['bank_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <input type="text" name="bank_branch" class="form-control"
                               value="<?= sanitize($emp['bank_branch'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control"
                               value="<?= sanitize($emp['account_number'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h6 class="card-title">Emergency Contact</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control"
                               value="<?= sanitize($emp['emergency_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="emergency_phone" class="form-control"
                               value="<?= sanitize($emp['emergency_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_relation" class="form-control"
                               value="<?= sanitize($emp['emergency_relation'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-pp btn-pp-primary">
                <i class="ti ti-device-floppy"></i> Save Changes
            </button>
            <a href="view.php?id=<?= $id ?>" class="btn-pp btn-pp-outline">
                <i class="ti ti-x"></i> Cancel
            </a>
        </div>
    </div>
</div>
</form>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
