<?php
// =============================================================
//  modules/settings/products.php
//  Manage products & production rates (admin only)
// =============================================================
require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Products & Rates';
$activeMenu = 'products';
$db         = Database::getInstance();
$errors     = [];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = postStr('action');

    if ($action === 'update_product') {
        $pid            = postInt('product_id');
        $name           = postStr('name');
        $targetWd       = postInt('target_weekday');
        $targetSat      = postInt('target_saturday');
        $rateAbove      = postFloat('rate_above');
        $rateBelow      = postFloat('rate_below');
        $sortOrder      = postInt('sort_order');
        $isActive       = postInt('is_active', 1);

        if ($name === '')       $errors[] = 'Product name is required.';
        if ($targetWd <= 0)    $errors[] = 'Weekday target must be > 0.';
        if ($targetSat <= 0)   $errors[] = 'Saturday target must be > 0.';
        if ($rateAbove <= 0)   $errors[] = 'Above-target rate must be > 0.';
        if ($rateBelow <= 0)   $errors[] = 'Below-target rate must be > 0.';

        if (empty($errors)) {
            if ($pid > 0) {
                $db->execute(
                    'UPDATE products SET name=?, target_weekday=?, target_saturday=?,
                            rate_above=?, rate_below=?, sort_order=?, is_active=?
                      WHERE id=?',
                    [$name, $targetWd, $targetSat, $rateAbove, $rateBelow, $sortOrder, $isActive, $pid]
                );
                AuditLog::write('update', 'products', $pid, "Updated product: $name");
                flash('success', "Product '$name' updated.");
            } else {
                $newId = $db->insert(
                    'INSERT INTO products (name, target_weekday, target_saturday, rate_above, rate_below, sort_order)
                     VALUES (?,?,?,?,?,?)',
                    [$name, $targetWd, $targetSat, $rateAbove, $rateBelow, $sortOrder]
                );
                AuditLog::write('create', 'products', $newId, "Added product: $name");
                flash('success', "Product '$name' added.");
            }
            redirect('/modules/settings/products.php');
        }
    }

    if ($action === 'delete_product') {
        $pid = postInt('product_id');
        if ($pid > 0) {
            $prod = $db->fetchOne('SELECT name FROM products WHERE id=?', [$pid]);
            $db->execute('DELETE FROM products WHERE id=?', [$pid]);
            AuditLog::write('delete', 'products', $pid, 'Deleted product: ' . ($prod['name'] ?? $pid));
            flash('success', "Product '" . ($prod['name'] ?? '') . "' deleted.");
        }
        redirect('/modules/settings/products.php');
    }
}

$products = $db->fetchAll('SELECT * FROM products ORDER BY sort_order, id');
require ROOT_PATH . '/templates/layout.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Product list -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Products & Production Rates</h6>
                <button class="btn-pp btn-pp-primary btn-pp-sm"
                        onclick="openForm(0,'','65','33','40','15','0')">
                    <i class="ti ti-plus"></i> Add Product
                </button>
            </div>
            <div class="table-responsive">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Product</th>
                            <th>Target Wkday</th><th>Target Sat</th>
                            <th>Rate Above</th><th>Rate Below</th>
                            <th>Status</th><th>Edit</th><th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="text-muted fs-12"><?= $p['sort_order'] ?></td>
                            <td class="fw-600"><?= sanitize($p['name']) ?></td>
                            <td><?= $p['target_weekday'] ?> units</td>
                            <td><?= $p['target_saturday'] ?> units</td>
                            <td class="text-green fw-600"><?= formatRs($p['rate_above']) ?></td>
                            <td><?= formatRs($p['rate_below']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $p['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-pp btn-pp-outline btn-pp-sm"
                                        onclick="openForm(<?= $p['id'] ?>,'<?= addslashes($p['name']) ?>',
                                            '<?= $p['target_weekday'] ?>','<?= $p['target_saturday'] ?>',
                                            '<?= $p['rate_above'] ?>','<?= $p['rate_below'] ?>',
                                            '<?= $p['sort_order'] ?>','<?= $p['is_active'] ?>')">
                                    <i class="ti ti-edit"></i>
                                </button>
                            </td>
                            <td>
                                <button class="btn-pp btn-pp-sm" style="background:#ef4444;color:#fff;border:none;"
                                        onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <div class="col-lg-5">
        <div class="card" id="productFormCard">
            <div class="card-header">
                <h6 class="card-title" id="formTitle">Add / Edit Product</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="productForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_product">
                    <input type="hidden" name="product_id" id="f_id" value="0">

                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="f_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Target Mon–Fri</label>
                            <div class="input-group">
                                <input type="number" name="target_weekday" id="f_twd" class="form-control" min="1" required>
                                <span class="input-group-text fs-12">units</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Target Saturday</label>
                            <div class="input-group">
                                <input type="number" name="target_saturday" id="f_tsat" class="form-control" min="1" required>
                                <span class="input-group-text fs-12">units</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Rate — Above target</label>
                            <div class="input-group">
                                <span class="input-group-text fs-12">Rs.</span>
                                <input type="number" name="rate_above" id="f_above" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Rate — Below target</label>
                            <div class="input-group">
                                <span class="input-group-text fs-12">Rs.</span>
                                <input type="number" name="rate_below" id="f_below" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="f_sort" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="f_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-pp btn-pp-primary">
                            <i class="ti ti-device-floppy"></i> Save
                        </button>
                        <button type="button" onclick="resetForm()" class="btn-pp btn-pp-outline">
                            <i class="ti ti-x"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden delete form -->
<form method="POST" id="deleteForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_product">
    <input type="hidden" name="product_id" id="delete_product_id" value="">
</form>

<script>
function openForm(id, name, twd, tsat, above, below, sort, active=1) {
    document.getElementById('f_id').value     = id;
    document.getElementById('f_name').value   = name;
    document.getElementById('f_twd').value    = twd;
    document.getElementById('f_tsat').value   = tsat;
    document.getElementById('f_above').value  = above;
    document.getElementById('f_below').value  = below;
    document.getElementById('f_sort').value   = sort;
    document.getElementById('f_active').value = active;
    document.getElementById('formTitle').textContent = id > 0 ? 'Edit Product' : 'Add Product';
    document.getElementById('productFormCard').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
    openForm(0,'','65','33','40','15','0');
}
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?\nThis cannot be undone.')) {
        document.getElementById('delete_product_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require ROOT_PATH . '/templates/layout_footer.php'; ?>
