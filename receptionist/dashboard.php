<?php
require_once __DIR__ . '/../config/app.php';
requireRole('receptionist');
$activePage = 'dashboard';

$stats = [
    'today_appts' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn(),
    'pending_appts' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn(),
    'admitted' => $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'admitted'")->fetchColumn(),
    'unpaid' => $pdo->query("SELECT COUNT(*) FROM invoices WHERE status != 'paid'")->fetchColumn(),
];

$todayAppts = $pdo->query("
    SELECT a.*, up.full_name AS patient_name, ud.full_name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id JOIN users up ON p.user_id = up.id
    JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receptionist Dashboard | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Front Desk</h1><p>Today's overview</p></div></div>
        <div class="pulse-divider"></div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Today's Appointments</div><div class="stat-value"><?= $stats['today_appts'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Pending Confirmation</div><div class="stat-value"><?= $stats['pending_appts'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Currently Admitted</div><div class="stat-value"><?= $stats['admitted'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Unpaid Invoices</div><div class="stat-value"><?= $stats['unpaid'] ?></div></div>
        </div>

        <div class="card">
            <h2>Today's Schedule</h2>
            <?php if ($todayAppts): ?>
            <table>
                <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($todayAppts as $a): ?>
                    <tr>
                        <td class="mono"><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td>Dr. <?= clean($a['doctor_name']) ?></td>
                        <td><span class="badge badge-<?= $a['status']==='completed'?'success':($a['status']==='cancelled'?'danger':'teal') ?>"><?= clean($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No appointments scheduled today.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
