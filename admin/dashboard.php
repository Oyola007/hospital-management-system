<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'dashboard';

$stats = [
    'patients' => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
    'today_appts' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn(),
    'admitted' => $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'admitted'")->fetchColumn(),
    'unpaid_invoices' => $pdo->query("SELECT COUNT(*) FROM invoices WHERE status != 'paid'")->fetchColumn(),
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM pharmacy_inventory WHERE stock_qty <= reorder_level")->fetchColumn(),
];

$recentAppointments = $pdo->query("
    SELECT a.*, up.full_name AS patient_name, ud.full_name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id JOIN users up ON p.user_id = up.id
    JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    ORDER BY a.created_at DESC LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Hospital-wide overview and controls</p>
            </div>
        </div>
        <div class="pulse-divider"></div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Total Patients</div><div class="stat-value"><?= $stats['patients'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Doctors</div><div class="stat-value"><?= $stats['doctors'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Today's Appointments</div><div class="stat-value"><?= $stats['today_appts'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Currently Admitted</div><div class="stat-value"><?= $stats['admitted'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Unpaid Invoices</div><div class="stat-value"><?= $stats['unpaid_invoices'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Low Stock Items</div><div class="stat-value"><?= $stats['low_stock'] ?></div></div>
        </div>

        <div class="card">
            <h2>Recent Appointments</h2>
            <?php if ($recentAppointments): ?>
            <table>
                <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentAppointments as $a): ?>
                    <tr>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td>Dr. <?= clean($a['doctor_name']) ?></td>
                        <td><?= clean($a['appointment_date']) ?></td>
                        <td><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td><span class="badge badge-<?= $a['status']==='completed'?'success':($a['status']==='cancelled'?'danger':'teal') ?>"><?= clean($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state"><div class="display">—</div>No appointments booked yet.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
