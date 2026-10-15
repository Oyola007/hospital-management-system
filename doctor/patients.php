<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'patients';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT DISTINCT p.id, u.full_name, u.phone, u.email, p.blood_group, p.gender
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$doctorId]);
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Patients | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Patients</h1><p>Patients you have seen</p></div></div>
        <div class="pulse-divider"></div>
        <div class="card">
            <?php if ($patients): ?>
            <table>
                <thead><tr><th>Name</th><th>Gender</th><th>Blood Group</th><th>Phone</th><th>Email</th></tr></thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?= clean($p['full_name']) ?></td>
                        <td><?= clean(ucfirst($p['gender'] ?? '—')) ?></td>
                        <td><?= clean($p['blood_group'] ?: '—') ?></td>
                        <td><?= clean($p['phone']) ?></td>
                        <td><?= clean($p['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No patients seen yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
