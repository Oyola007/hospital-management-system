<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'pharmacy';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $name = trim($_POST['medicine_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? 'tablets');
    $qty = (int)($_POST['stock_qty'] ?? 0);
    $reorder = (int)($_POST['reorder_level'] ?? 10);
    $price = (float)($_POST['unit_price'] ?? 0);
    $expiry = $_POST['expiry_date'] ?: null;

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO pharmacy_inventory (medicine_name, category, unit, stock_qty, reorder_level, unit_price, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $unit, $qty, $reorder, $price, $expiry]);
        flash('success', 'Medicine added to inventory.');
    }
    redirect('/admin/pharmacy.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $id = (int)$_POST['item_id'];
    $addQty = (int)$_POST['add_qty'];
    if ($addQty > 0) {
        $stmt = $pdo->prepare("UPDATE pharmacy_inventory SET stock_qty = stock_qty + ? WHERE id = ?");
        $stmt->execute([$addQty, $id]);
        flash('success', 'Stock updated.');
    }
    redirect('/admin/pharmacy.php');
}

$inventory = $pdo->query("SELECT * FROM pharmacy_inventory ORDER BY medicine_name")->fetchAll();

// Pending prescriptions awaiting dispensing
$pendingRx = $pdo->query("
    SELECT pr.*, u.full_name AS patient_name, c.consultation_date
    FROM prescriptions pr
    JOIN consultations c ON pr.consultation_id = c.id
    JOIN patients p ON c.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE pr.status = 'pending'
    ORDER BY c.consultation_date DESC LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pharmacy | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Pharmacy</h1><p>Inventory and dispensing</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Medicine to Inventory</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Medicine Name *</label><input type="text" name="medicine_name" required></div>
                    <div class="form-group"><label>Category</label><input type="text" name="category" placeholder="e.g. Analgesic"></div>
                    <div class="form-group"><label>Unit</label><input type="text" name="unit" value="tablets"></div>
                    <div class="form-group"><label>Stock Quantity</label><input type="number" name="stock_qty" min="0" value="0"></div>
                    <div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" min="0" value="10"></div>
                    <div class="form-group"><label>Unit Price (KSh)</label><input type="number" step="0.01" name="unit_price" min="0" value="0"></div>
                    <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date"></div>
                </div>
                <button type="submit" name="add_medicine" class="btn btn-primary">Add Medicine</button>
            </form>
        </div>

        <div class="card">
            <h2>Inventory</h2>
            <?php if ($inventory): ?>
            <table>
                <thead><tr><th>Medicine</th><th>Category</th><th>Stock</th><th>Unit Price</th><th>Expiry</th><th>Restock</th></tr></thead>
                <tbody>
                <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td><?= clean($item['medicine_name']) ?></td>
                        <td><?= clean($item['category'] ?: '—') ?></td>
                        <td>
                            <?= $item['stock_qty'] ?> <?= clean($item['unit']) ?>
                            <?php if ($item['stock_qty'] <= $item['reorder_level']): ?><span class="badge badge-danger">Low</span><?php endif; ?>
                        </td>
                        <td><?= formatMoney($item['unit_price']) ?></td>
                        <td><?= clean($item['expiry_date'] ?: '—') ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:6px;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="number" name="add_qty" min="1" placeholder="+ qty" style="width:80px; padding:6px 8px; border:1px solid var(--border); border-radius:6px;">
                                <button type="submit" name="restock" class="btn btn-sm btn-secondary" style="margin:0;">Add</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No inventory items yet.</div><?php endif; ?>
        </div>

        <div class="card">
            <h2>Pending Prescriptions (Awaiting Dispense)</h2>
            <?php if ($pendingRx): ?>
            <table>
                <thead><tr><th>Patient</th><th>Medicine</th><th>Dosage</th><th>Prescribed</th></tr></thead>
                <tbody>
                <?php foreach ($pendingRx as $rx): ?>
                    <tr>
                        <td><?= clean($rx['patient_name']) ?></td>
                        <td><?= clean($rx['medicine_name']) ?></td>
                        <td><?= clean($rx['dosage']) ?> · <?= clean($rx['frequency']) ?> · <?= clean($rx['duration']) ?></td>
                        <td><?= clean(date('Y-m-d', strtotime($rx['consultation_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No pending prescriptions.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
