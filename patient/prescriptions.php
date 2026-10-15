<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'prescriptions';

$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patientId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT pr.*, c.consultation_date, ud.full_name AS doctor_name
    FROM prescriptions pr
    JOIN consultations c ON pr.consultation_id = c.id
    JOIN doctors d ON c.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE c.patient_id = ?
    ORDER BY c.consultation_date DESC
");
$stmt->execute([$patientId]);
$prescriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Prescriptions | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Prescriptions</h1><p>Medicines prescribed to you</p></div></div>
        <div class="pulse-divider"></div>
        <div class="card">
            <?php if ($prescriptions): ?>
            <table>
                <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Doctor</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($prescriptions as $p): ?>
                    <tr>
                        <td><?= clean($p['medicine_name']) ?></td>
                        <td><?= clean($p['dosage']) ?></td>
                        <td><?= clean($p['frequency']) ?></td>
                        <td><?= clean($p['duration']) ?></td>
                        <td>Dr. <?= clean($p['doctor_name']) ?></td>
                        <td><span class="badge badge-<?= $p['status']==='dispensed'?'success':'teal' ?>"><?= clean($p['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No prescriptions yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
