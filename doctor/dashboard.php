<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'dashboard';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$stats = [
    'today' => (function() use ($pdo, $doctorId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
        $s->execute([$doctorId]); return $s->fetchColumn();
    })(),
    'pending' => (function() use ($pdo, $doctorId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status IN ('pending','confirmed')");
        $s->execute([$doctorId]); return $s->fetchColumn();
    })(),
    'patients' => (function() use ($pdo, $doctorId) {
        $s = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
        $s->execute([$doctorId]); return $s->fetchColumn();
    })(),
];

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS patient_name
    FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time
");
$stmt->execute([$doctorId]);
$todayAppts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Welcome, Dr. <?= clean(explode(' ', currentUser()['full_name'])[0]) ?></h1><p>Your schedule and patients</p></div></div>
        <div class="pulse-divider"></div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Today's Appointments</div><div class="stat-value"><?= $stats['today'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Pending / Confirmed</div><div class="stat-value"><?= $stats['pending'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Patients Seen</div><div class="stat-value"><?= $stats['patients'] ?></div></div>
        </div>

        <div class="card">
            <h2>Today's Appointments</h2>
            <?php if ($todayAppts): ?>
            <table>
                <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($todayAppts as $a): ?>
                    <tr>
                        <td class="mono"><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td><?= clean($a['reason'] ?: '—') ?></td>
                        <td><span class="badge badge-<?= $a['status']==='completed'?'success':($a['status']==='cancelled'?'danger':'teal') ?>"><?= clean($a['status']) ?></span></td>
                        <td><?php if ($a['status'] !== 'completed' && $a['status'] !== 'cancelled'): ?>
                            <a href="<?= BASE_URL ?>/doctor/consultation.php?appointment_id=<?= $a['id'] ?>" class="badge badge-teal">Start Consultation</a>
                        <?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No appointments today.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
