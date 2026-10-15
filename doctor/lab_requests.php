<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'lab';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT lr.*, t.test_name, t.price, u.full_name AS patient_name
    FROM lab_requests lr
    JOIN lab_tests t ON lr.test_id = t.id
    JOIN patients p ON lr.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE lr.doctor_id = ?
    ORDER BY lr.requested_date DESC
");
$stmt->execute([$doctorId]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lab Requests | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Lab Requests</h1><p>Tests you've ordered and their results</p></div></div>
        <div class="pulse-divider"></div>
        <div class="card">
            <?php if ($requests): ?>
            <table>
                <thead><tr><th>Patient</th><th>Test</th><th>Requested</th><th>Status</th><th>Result</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= clean($r['patient_name']) ?></td>
                        <td><?= clean($r['test_name']) ?></td>
                        <td><?= clean(date('Y-m-d', strtotime($r['requested_date']))) ?></td>
                        <td><span class="badge badge-<?= $r['status']==='completed'?'success':'teal' ?>"><?= clean(str_replace('_',' ',$r['status'])) ?></span></td>
                        <td><?= clean($r['result'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No lab requests yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
