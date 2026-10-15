<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'lab';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_test'])) {
    $name = trim($_POST['test_name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO lab_tests (test_name, price) VALUES (?, ?)");
        $stmt->execute([$name, $price]);
        flash('success', 'Lab test added.');
    }
    redirect('/admin/lab.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_result'])) {
    $requestId = (int)$_POST['request_id'];
    $result = trim($_POST['result'] ?? '');
    if ($result) {
        $stmt = $pdo->prepare("UPDATE lab_requests SET result = ?, status = 'completed', result_date = NOW() WHERE id = ?");
        $stmt->execute([$result, $requestId]);
        flash('success', 'Result submitted.');
    }
    redirect('/admin/lab.php');
}

$tests = $pdo->query("SELECT * FROM lab_tests ORDER BY test_name")->fetchAll();
$requests = $pdo->query("
    SELECT lr.*, t.test_name, u.full_name AS patient_name, ud.full_name AS doctor_name
    FROM lab_requests lr
    JOIN lab_tests t ON lr.test_id = t.id
    JOIN patients p ON lr.patient_id = p.id JOIN users u ON p.user_id = u.id
    JOIN doctors d ON lr.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE lr.status != 'completed'
    ORDER BY lr.requested_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Laboratory | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Laboratory</h1><p>Manage tests and pending results</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Lab Test Type</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Test Name *</label><input type="text" name="test_name" required></div>
                    <div class="form-group"><label>Price (KSh)</label><input type="number" step="0.01" name="price" value="0"></div>
                </div>
                <button type="submit" name="add_test" class="btn btn-primary">Add Test</button>
            </form>
        </div>

        <div class="card">
            <h2>Available Tests</h2>
            <?php if ($tests): ?>
            <table><thead><tr><th>Test</th><th>Price</th></tr></thead><tbody>
            <?php foreach ($tests as $t): ?>
                <tr><td><?= clean($t['test_name']) ?></td><td><?= formatMoney($t['price']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state">No tests configured yet.</div><?php endif; ?>
        </div>

        <div class="card">
            <h2>Pending Requests</h2>
            <?php if ($requests): ?>
            <table>
                <thead><tr><th>Patient</th><th>Test</th><th>Requested By</th><th>Status</th><th>Enter Result</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= clean($r['patient_name']) ?></td>
                        <td><?= clean($r['test_name']) ?></td>
                        <td>Dr. <?= clean($r['doctor_name']) ?></td>
                        <td><span class="badge badge-teal"><?= clean(str_replace('_',' ',$r['status'])) ?></span></td>
                        <td>
                            <form method="POST" style="display:flex; gap:6px;">
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <input type="text" name="result" placeholder="Result summary" required style="flex:1; padding:6px 8px; border:1px solid var(--border); border-radius:6px;">
                                <button type="submit" name="submit_result" class="btn btn-sm btn-primary" style="margin:0;">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No pending lab requests.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
