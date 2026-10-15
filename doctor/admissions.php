<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'admissions';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT ad.*, u.full_name AS patient_name, b.bed_number, w.name AS ward_name
    FROM admissions ad
    JOIN patients p ON ad.patient_id = p.id JOIN users u ON p.user_id = u.id
    JOIN beds b ON ad.bed_id = b.id JOIN wards w ON b.ward_id = w.id
    WHERE ad.doctor_id = ?
    ORDER BY ad.admit_date DESC
");
$stmt->execute([$doctorId]);
$admissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Admitted Patients | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Admitted Patients</h1><p>Ward rounds overview</p></div></div>
        <div class="pulse-divider"></div>
        <div class="card">
            <?php if ($admissions): ?>
            <table>
                <thead><tr><th>Patient</th><th>Ward / Bed</th><th>Reason</th><th>Admitted</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($admissions as $a): ?>
                    <tr>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td><?= clean($a['ward_name']) ?> / <?= clean($a['bed_number']) ?></td>
                        <td><?= clean($a['reason'] ?: '—') ?></td>
                        <td><?= clean(date('Y-m-d', strtotime($a['admit_date']))) ?></td>
                        <td><span class="badge badge-<?= $a['status']==='admitted'?'teal':'muted' ?>"><?= clean($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No admitted patients under your care.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
