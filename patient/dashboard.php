<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'dashboard';

$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patientId = $stmt->fetchColumn();

$stats = [
    'upcoming' => (function() use ($pdo, $patientId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status IN ('pending','confirmed')");
        $s->execute([$patientId]); return $s->fetchColumn();
    })(),
    'unpaid' => (function() use ($pdo, $patientId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE patient_id = ? AND status != 'paid'");
        $s->execute([$patientId]); return $s->fetchColumn();
    })(),
    'prescriptions' => (function() use ($pdo, $patientId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM prescriptions pr JOIN consultations c ON pr.consultation_id = c.id WHERE c.patient_id = ? AND pr.status = 'pending'");
        $s->execute([$patientId]); return $s->fetchColumn();
    })(),
];

$stmt = $pdo->prepare("
    SELECT a.*, ud.full_name AS doctor_name
    FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE a.patient_id = ? AND a.status IN ('pending','confirmed')
    ORDER BY a.appointment_date, a.appointment_time LIMIT 5
");
$stmt->execute([$patientId]);
$upcoming = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Welcome, <?= clean(explode(' ', currentUser()['full_name'])[0]) ?></h1><p>Your health at a glance</p></div></div>
        <div class="pulse-divider"></div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Upcoming Appointments</div><div class="stat-value"><?= $stats['upcoming'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Unpaid Bills</div><div class="stat-value"><?= $stats['unpaid'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Active Prescriptions</div><div class="stat-value"><?= $stats['prescriptions'] ?></div></div>
        </div>

        <div class="card">
            <h2>Upcoming Appointments</h2>
            <?php if ($upcoming): ?>
            <table>
                <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $a): ?>
                    <tr>
                        <td><?= clean($a['appointment_date']) ?></td>
                        <td class="mono"><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td>Dr. <?= clean($a['doctor_name']) ?></td>
                        <td><span class="badge badge-teal"><?= clean($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state">No upcoming appointments. <a href="<?= BASE_URL ?>/patient/appointments.php">Book one now</a>.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
