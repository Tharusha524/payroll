<?php
// =============================================================
//  modules/employees/add.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Add Employee';
$activeMenu = 'emp_add';

$db          = Database::getInstance();
$departments = $db->fetchAll('SELECT id, name FROM departments ORDER BY name');
$errors      = [];
$data        = [];  // repopulate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Collect
    $data = [
        'emp_code'          => strtoupper(trim(postStr('emp_code'))),
        'full_name'         => postStr('full_name'),
        'nic'               => postStr('nic'),
        'gender'            => postStr('gender'),
        'date_of_birth'     => postStr('date_of_birth'),
        'join_date'         => postStr('join_date'),
        'department_id'     => postInt('department_id'),
        'designation'       => postStr('designation'),
        'phone'             => postStr('phone'),
        'email'             => postStr('email'),
        'address'           => postStr('address'),
        'bank_name'         => postStr('bank_name'),
        'bank_branch'       => postStr('bank_branch'),
        'account_number'    => postStr('account_number'),
        'emergency_name'    => postStr('emergency_name'),
        'emergency_phone'   => postStr('emergency_phone'),
        'emergency_relation'=> postStr('emergency_relation'),
    ];

    // Validate
    if ($data['full_name'] === '')    $errors[] = 'Full name is required.';
    if ($data['nic'] === '')          $errors[] = 'NIC is required.';
    if ($data['gender'] === '')       $errors[] = 'Gender is required.';
    if ($data['date_of_birth'] === '') $errors[] = 'Date of birth is required.';
    if ($data['join_date'] === '')    $errors[] = 'Join date is required.';
    if ($data['department_id'] === 0) $errors[] = 'Department is required.';
    if ($data['designation'] === '')  $errors[] = 'Designation is required.';
    if ($data['phone'] === '')        $errors[] = 'Phone is required.';
    if ($data['address'] === '')      $errors[] = 'Address is required.';

    // Auto-generate code if blank
    if ($data['emp_code'] === '') {
        $data['emp_code'] = nextEmpCode();
    }

    // Check unique emp_code and NIC
    if (empty($errors)) {
        $dup = $db->fetchOne(
            'SELECT id FROM employees WHERE emp_code = ? OR nic = ?',
            [$data['emp_code'], $data['nic']]
        );
        if ($dup) $errors[] = 'Employee code or NIC already exists.';
    }

    // Photo upload
    $photoFilename = null;
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
                $errors[] = 'Failed to upload photo. Check folder permissions.';
                $photoFilename = null;
            }
        }
    }

    if (empty($errors)) {
        $db->insert(
            'INSERT INTO employees
                (emp_code, full_name, nic, gender, date_of_birth, join_date,
                 department_id, designation, phone, email, address,
                 bank_name, bank_branch, account_number,
                 emergency_name, emergency_phone, emergency_relation,
                 photo, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['emp_code'], $data['full_name'], $data['nic'],
                $data['gender'], $data['date_of_birth'], $data['join_date'],
                $data['department_id'], $data['designation'],
                $data['phone'], $data['email'], $data['address'],
                $data['bank_name'], $data['bank_branch'], $data['account_number'],
                $data['emergency_name'], $data['emergency_phone'], $data['emergency_relation'],
                $photoFilename, Auth::id(),
            ]
        );

        AuditLog::write('create', 'employees', null, 'Added ' . $data['full_name']);
        flash('success', 'Employee ' . $data['full_name'] . ' added successfully.');
        redirect('/modules/employees/index.php');
    }
}

// Pre-fill emp_code
if (empty($data['emp_code'])) {
    $data['emp_code'] = nextEmpCode();
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

    <!-- Photo + basic IDs -->
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title">Photo</h6></div>
            <div class="card-body text-center">
                <label class="photo-upload-box mx-auto mb-3" for="photo_file">
                    <img id="photo_preview" src="" alt="" style="display:none">
                    <i class="ti ti-camera" style="font-size:28px;color:#9ca3af"></i>
                    <div class="overlay"><i class="ti ti-camera"></i></div>
                </label>
                <input type="file" id="photo_file" name="photo" accept="image/*" class="d-none">
                <p class="text-muted fs-12">JPEG, PNG or WebP · Max 2 MB</p>

                <div class="mt-3">
                    <label class="form-label">Employee Code</label>
                    <input type="text" name="emp_code" class="form-control text-center fw-600"
                           value="<?= sanitize($data['emp_code'] ?? '') ?>"
                           placeholder="Auto-generated">
                </div>
            </div>
        </div>
    </div>

    <!-- Personal & job info -->
    <div class="col-lg-9">

        <!-- Personal -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Personal Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= sanitize($data['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIC <span class="text-danger">*</span></label>
                        <input type="text" name="nic" class="form-control"
                               value="<?= sanitize($data['nic'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="male"   <?= ($data['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($data['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other"  <?= ($data['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="<?= sanitize($data['date_of_birth'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Join Date <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control"
                               value="<?= sanitize($data['join_date'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= sanitize($data['phone'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= sanitize($data['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required><?= sanitize($data['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Job Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="0">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($data['department_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control"
                               value="<?= sanitize($data['designation'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title">Bank Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control"
                               value="<?= sanitize($data['bank_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <input type="text" name="bank_branch" class="form-control"
                               value="<?= sanitize($data['bank_branch'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control"
                               value="<?= sanitize($data['account_number'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency contact -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="card-title">Emergency Contact</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control"
                               value="<?= sanitize($data['emergency_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="emergency_phone" class="form-control"
                               value="<?= sanitize($data['emergency_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_relation" class="form-control"
                               value="<?= sanitize($data['emergency_relation'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-pp btn-pp-primary">
                <i class="ti ti-device-floppy"></i> Save Employee
            </button>
            <a href="index.php" class="btn-pp btn-pp-outline">
                <i class="ti ti-x"></i> Cancel
            </a>
        </div>

    </div><!-- /col-lg-9 -->
</div><!-- /row -->
</form>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
