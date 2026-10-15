<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'reports';

$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(paid_date, '%Y-%m') AS month, SUM(amount) AS total
    FROM payments
    GROUP BY month ORDER BY month DESC LIMIT 6
")->fetchAll();

$topDoctors = $pdo->query("
    SELECT u.full_name, COUNT(a.id) AS appointment_count
    FROM doctors d JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON a.doctor_id = d.id
    GROUP BY d.id ORDER BY appointment_count DESC LIMIT 5
")->fetchAll();

$departmentLoad = $pdo->query("
    SELECT dep.name, COUNT(a.id) AS total
    FROM departments dep
    LEFT JOIN doctors d ON d.department_id = dep.id
    LEFT JOIN appointments a ON a.doctor_id = d.id
    GROUP BY dep.id ORDER BY total DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Reports</h1><p>Operational insights</p></div></div>
        <div class="pulse-divider"></div>

        <div class="card">
            <h2>Monthly Revenue</h2>
            <?php if ($monthlyRevenue): ?>
            <table><thead><tr><th>Month</th><th>Revenue</th></tr></thead><tbody>
            <?php foreach ($monthlyRevenue as $r): ?>
                <tr><td><?= clean($r['month']) ?></td><td><?= formatMoney($r['total']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state">No payment data yet.</div><?php endif; ?>
        </div>

        <div class="card">
            <h2>Busiest Doctors</h2>
            <?php if ($topDoctors): ?>
            <table><thead><tr><th>Doctor</th><th>Appointments</th></tr></thead><tbody>
            <?php foreach ($topDoctors as $d): ?>
                <tr><td>Dr. <?= clean($d['full_name']) ?></td><td><?= $d['appointment_count'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state">No data yet.</div><?php endif; ?>
        </div>

        <div class="card">
            <h2>Department Load</h2>
            <?php if ($departmentLoad): ?>
            <table><thead><tr><th>Department</th><th>Appointments</th></tr></thead><tbody>
            <?php foreach ($departmentLoad as $d): ?>
                <tr><td><?= clean($d['name']) ?></td><td><?= $d['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state">No data yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
