<?php
// templates/403.php – shown when a user lacks permission
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#f9fafb">
    <div class="text-center">
        <div style="font-size:64px;font-weight:700;color:#1D9E75">403</div>
        <h4 class="mb-2">Access Denied</h4>
        <p class="text-muted">You do not have permission to view this page.</p>
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">Go Back</a>
    </div>
</body>
</html>
