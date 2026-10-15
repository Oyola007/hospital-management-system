<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'wards';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ward'])) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['ward_type'] ?? 'general';
    $bedCount = (int)($_POST['bed_count'] ?? 0);

    if ($name && $bedCount > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO wards (name, ward_type, total_beds) VALUES (?, ?, ?)");
            $stmt->execute([$name, $type, $bedCount]);
            $wardId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO beds (ward_id, bed_number) VALUES (?, ?)");
            for ($i = 1; $i <= $bedCount; $i++) {
                $stmt->execute([$wardId, 'B' . str_pad($i, 2, '0', STR_PAD_LEFT)]);
            }
            $pdo->commit();
            flash('success', "Ward '{$name}' created with {$bedCount} beds.");
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Failed to create ward.');
        }
    } else {
        flash('error', 'Please provide a ward name and bed count.');
    }
    redirect('/admin/wards.php');
}

$wards = $pdo->query("
    SELECT w.*,
        SUM(CASE WHEN b.status = 'occupied' THEN 1 ELSE 0 END) AS occupied,
        SUM(CASE WHEN b.status = 'available' THEN 1 ELSE 0 END) AS available
    FROM wards w LEFT JOIN beds b ON b.ward_id = w.id
    GROUP BY w.id ORDER BY w.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wards &amp; Beds | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Wards &amp; Beds</h1><p>Manage inpatient capacity</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Ward</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Ward Name *</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Type</label>
                        <select name="ward_type">
                            <option value="general">General</option>
                            <option value="private">Private</option>
                            <option value="icu">ICU</option>
                            <option value="maternity">Maternity</option>
                            <option value="pediatric">Pediatric</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Number of Beds *</label><input type="number" name="bed_count" min="1" required></div>
                </div>
                <button type="submit" name="add_ward" class="btn btn-primary">Create Ward</button>
            </form>
        </div>

        <div class="card">
            <h2>All Wards</h2>
            <?php if ($wards): ?>
            <table>
                <thead><tr><th>Ward</th><th>Type</th><th>Total Beds</th><th>Occupied</th><th>Available</th></tr></thead>
                <tbody>
                <?php foreach ($wards as $w): ?>
                    <tr>
                        <td><?= clean($w['name']) ?></td>
                        <td><span class="badge badge-teal"><?= clean(ucfirst($w['ward_type'])) ?></span></td>
                        <td><?= $w['total_beds'] ?></td>
                        <td><?= $w['occupied'] ?></td>
                        <td><?= $w['available'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No wards set up yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
