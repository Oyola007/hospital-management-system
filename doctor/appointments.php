<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'appointments';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$filter = $_GET['status'] ?? 'upcoming';
$sql = "
    SELECT a.*, u.full_name AS patient_name, u.phone AS patient_phone
    FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ?
";
if ($filter === 'upcoming') $sql .= " AND a.status IN ('pending','confirmed')";
elseif ($filter === 'completed') $sql .= " AND a.status = 'completed'";
elseif ($filter === 'cancelled') $sql .= " AND a.status = 'cancelled'";
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Appointments</h1><p>All appointments assigned to you</p></div></div>
        <div class="pulse-divider"></div>

        <div class="card">
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <a href="?status=upcoming" class="btn btn-sm <?= $filter==='upcoming'?'btn-primary':'btn-secondary' ?>">Upcoming</a>
                <a href="?status=completed" class="btn btn-sm <?= $filter==='completed'?'btn-primary':'btn-secondary' ?>">Completed</a>
                <a href="?status=cancelled" class="btn btn-sm <?= $filter==='cancelled'?'btn-primary':'btn-secondary' ?>">Cancelled</a>
                <a href="?status=all" class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-secondary' ?>">All</a>
            </div>
            <?php if ($appointments): ?>
            <table>
                <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Phone</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= clean($a['appointment_date']) ?></td>
                        <td class="mono"><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td><?= clean($a['patient_phone']) ?></td>
                        <td><?= clean($a['reason'] ?: '—') ?></td>
                        <td><span class="badge badge-<?= $a['status']==='completed'?'success':($a['status']==='cancelled'?'danger':'teal') ?>"><?= clean($a['status']) ?></span></td>
                        <td><?php if (!in_array($a['status'], ['completed','cancelled'])): ?>
                            <a href="<?= BASE_URL ?>/doctor/consultation.php?appointment_id=<?= $a['id'] ?>" class="badge badge-teal">Consult</a>
                        <?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No appointments in this view.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
