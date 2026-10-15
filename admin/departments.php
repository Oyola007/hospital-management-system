<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'departments';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        flash('success', 'Department added.');
    }
    redirect('/admin/departments.php');
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    redirect('/admin/departments.php');
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Departments | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Departments</h1><p>Manage hospital departments</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Department</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Description</label><input type="text" name="description"></div>
                </div>
                <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
            </form>
        </div>

        <div class="card">
            <h2>All Departments</h2>
            <?php if ($departments): ?>
            <table>
                <thead><tr><th>Name</th><th>Description</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td><?= clean($d['name']) ?></td>
                        <td><?= clean($d['description']) ?></td>
                        <td><a href="?delete=<?= $d['id'] ?>" onclick="return confirm('Delete this department?')" class="badge badge-danger">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No departments yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
