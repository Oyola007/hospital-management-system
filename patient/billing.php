<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'billing';

$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patientId = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->execute([$patientId]);
$invoices = $stmt->fetchAll();

$itemsStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bills | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Bills</h1><p>Invoices and payment history</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($invoices): ?>
            <?php foreach ($invoices as $inv): ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h2 style="margin:0;"><?= clean($inv['invoice_number']) ?></h2>
                    <span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='partial'?'warning':'danger') ?>"><?= clean($inv['status']) ?></span>
                </div>
                <?php $itemsStmt->execute([$inv['id']]); $items = $itemsStmt->fetchAll(); ?>
                <table>
                    <thead><tr><th>Description</th><th>Category</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr><td><?= clean($it['description']) ?></td><td><span class="badge badge-muted"><?= clean($it['category']) ?></span></td><td><?= formatMoney($it['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display:flex; justify-content:flex-end; gap:24px; margin-top:12px; font-size:14px;">
                    <div><strong>Total:</strong> <?= formatMoney($inv['total_amount']) ?></div>
                    <div><strong>Paid:</strong> <?= formatMoney($inv['amount_paid']) ?></div>
                    <div><strong>Balance:</strong> <?= formatMoney($inv['total_amount'] - $inv['amount_paid']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card"><div class="empty-state">No bills yet.</div></div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
