<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'billing';

$invoices = $pdo->query("
    SELECT i.*, u.full_name AS patient_name
    FROM invoices i JOIN patients p ON i.patient_id = p.id JOIN users u ON p.user_id = u.id
    ORDER BY i.created_at DESC
")->fetchAll();

$totals = $pdo->query("
    SELECT
        SUM(total_amount) AS total_billed,
        SUM(amount_paid) AS total_collected,
        SUM(total_amount - amount_paid) AS total_outstanding
    FROM invoices
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing Overview | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Billing Overview</h1><p>Hospital-wide financial summary</p></div></div>
        <div class="pulse-divider"></div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Total Billed</div><div class="stat-value"><?= formatMoney($totals['total_billed'] ?? 0) ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Collected</div><div class="stat-value"><?= formatMoney($totals['total_collected'] ?? 0) ?></div></div>
            <div class="stat-card"><div class="stat-label">Outstanding</div><div class="stat-value"><?= formatMoney($totals['total_outstanding'] ?? 0) ?></div></div>
        </div>

        <div class="card">
            <h2>All Invoices</h2>
            <?php if ($invoices): ?>
            <table>
                <thead><tr><th>Invoice #</th><th>Patient</th><th>Total</th><th>Paid</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td class="mono"><?= clean($inv['invoice_number']) ?></td>
                        <td><?= clean($inv['patient_name']) ?></td>
                        <td><?= formatMoney($inv['total_amount']) ?></td>
                        <td><?= formatMoney($inv['amount_paid']) ?></td>
                        <td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='partial'?'warning':'danger') ?>"><?= clean($inv['status']) ?></span></td>
                        <td><?= clean(date('Y-m-d', strtotime($inv['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No invoices yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
