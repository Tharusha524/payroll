<?php
// =============================================================
//  modules/auth/login.php
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (Auth::isLoggedIn()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = postStr('username');
    $password = postStr('password');

    if ($username === '' || $password === '') 
    {
        $error = 'Please enter your username and password test.';
    } 
    elseif 
    (!Auth::login($username, $password)) 
    {
        //redirect('/index.php');
        //echo "<script>alert('Hai..');</script>";
        $error = 'Invalid username or password. test1';
    } 
    else 
    {
        redirect('/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="login-page">

<div class="login-card">

    <div class="text-center mb-4">
        <div style="width:52px;height:52px;background:#1D9E75;border-radius:12px;
                    display:flex;align-items:center;justify-content:center;
                    font-size:26px;font-weight:700;color:#fff;margin:0 auto 14px;">P</div>
        <h4 style="font-weight:700;font-size:20px;margin-bottom:4px;">Panda Payroll</h4>
        <p class="text-muted" style="font-size:13px;">Consumer Products – Staff Portal</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size:13px;"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-user"></i></span>
                <input type="text" name="username" class="form-control"
                       value="<?= sanitize(postStr('username')) ?>"
                       autofocus required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn-pp btn-pp-primary w-100 justify-content-center py-2">
            <i class="ti ti-login"></i> Sign In
        </button>
    </form>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:11px;">
        <?= APP_NAME ?> v<?= APP_VERSION ?>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
