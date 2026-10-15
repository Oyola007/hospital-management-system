<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'records';

$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patientId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT c.*, ud.full_name AS doctor_name
    FROM consultations c JOIN doctors d ON c.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE c.patient_id = ?
    ORDER BY c.consultation_date DESC
");
$stmt->execute([$patientId]);
$consultations = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT lr.*, t.test_name, ud.full_name AS doctor_name
    FROM lab_requests lr JOIN lab_tests t ON lr.test_id = t.id JOIN doctors d ON lr.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE lr.patient_id = ?
    ORDER BY lr.requested_date DESC
");
$stmt->execute([$patientId]);
$labResults = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Records | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Medical Records</h1><p>Your consultation history and lab results</p></div></div>
        <div class="pulse-divider"></div>

        <div class="card">
            <h2>Consultation History</h2>
            <?php if ($consultations): ?>
                <?php foreach ($consultations as $c): ?>
                    <div style="border-bottom:1px solid var(--border); padding:14px 0;">
                        <div style="display:flex; justify-content:space-between;">
                            <strong>Dr. <?= clean($c['doctor_name']) ?></strong>
                            <span class="mono" style="color:var(--text-muted); font-size:13px;"><?= clean(date('M j, Y', strtotime($c['consultation_date']))) ?></span>
                        </div>
                        <p style="margin:6px 0 2px;"><strong>Symptoms:</strong> <?= clean($c['symptoms'] ?: '—') ?></p>
                        <p style="margin:2px 0;"><strong>Diagnosis:</strong> <?= clean($c['diagnosis'] ?: '—') ?></p>
                        <?php if ($c['notes']): ?><p style="margin:2px 0; color:var(--text-muted);"><?= clean($c['notes']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?><div class="empty-state">No consultation records yet.</div><?php endif; ?>
        </div>

        <div class="card">
            <h2>Lab Results</h2>
            <?php if ($labResults): ?>
            <table>
                <thead><tr><th>Test</th><th>Requested By</th><th>Status</th><th>Result</th></tr></thead>
                <tbody>
                <?php foreach ($labResults as $r): ?>
                    <tr>
                        <td><?= clean($r['test_name']) ?></td>
                        <td>Dr. <?= clean($r['doctor_name']) ?></td>
                        <td><span class="badge badge-<?= $r['status']==='completed'?'success':'teal' ?>"><?= clean(str_replace('_',' ',$r['status'])) ?></span></td>
                        <td><?= clean($r['result'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No lab results yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
