<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'patients';

$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT p.*, u.full_name, u.email, u.phone, u.status
    FROM patients p JOIN users u ON p.user_id = u.id
";
$params = [];
if ($search) {
    $sql .= " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY u.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patients | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Patients</h1><p>All registered patients</p></div></div>
        <div class="pulse-divider"></div>

        <div class="card">
            <form method="GET" style="display:flex; gap:10px; margin-bottom:16px;">
                <input type="text" name="q" placeholder="Search by name, email or phone" value="<?= clean($search) ?>" style="flex:1; padding:9px 12px; border:1px solid var(--border); border-radius:8px;">
                <button class="btn btn-secondary" type="submit">Search</button>
            </form>

            <?php if ($patients): ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Blood Group</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?= clean($p['full_name']) ?></td>
                        <td><?= clean($p['email']) ?></td>
                        <td><?= clean($p['phone']) ?></td>
                        <td><?= clean(ucfirst($p['gender'] ?? '—')) ?></td>
                        <td><?= clean($p['blood_group'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= $p['status']==='active'?'success':'muted' ?>"><?= clean($p['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No patients found.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
