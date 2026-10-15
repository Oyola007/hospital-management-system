<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'receptionists';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_receptionist'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $shift = trim($_POST['shift'] ?? '');

    if (!$full_name || !$email || !$password) {
        flash('error', 'Please fill all required fields.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Email already in use.');
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'receptionist')");
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $userId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO receptionists (user_id, shift) VALUES (?, ?)");
                $stmt->execute([$userId, $shift]);
                $pdo->commit();
                flash('success', 'Receptionist account created.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Failed to create receptionist.');
            }
        }
    }
    redirect('/admin/receptionists.php');
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("SELECT u.id, u.status FROM receptionists r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([(int)$_GET['toggle']]);
    if ($row = $stmt->fetch()) {
        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $row['id']]);
    }
    redirect('/admin/receptionists.php');
}

$receptionists = $pdo->query("
    SELECT r.*, u.full_name, u.email, u.phone, u.status
    FROM receptionists r JOIN users u ON r.user_id = u.id ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receptionists | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Receptionists</h1><p>Manage front-desk staff accounts</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Receptionist</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>Temporary Password *</label><input type="text" name="password" required></div>
                    <div class="form-group"><label>Shift</label>
                        <select name="shift">
                            <option value="Day">Day</option>
                            <option value="Night">Night</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_receptionist" class="btn btn-primary">Create Receptionist Account</button>
            </form>
        </div>

        <div class="card">
            <h2>All Receptionists</h2>
            <?php if ($receptionists): ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Shift</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($receptionists as $r): ?>
                    <tr>
                        <td><?= clean($r['full_name']) ?></td>
                        <td><?= clean($r['email']) ?></td>
                        <td><?= clean($r['phone']) ?></td>
                        <td><?= clean($r['shift']) ?></td>
                        <td><span class="badge badge-<?= $r['status']==='active'?'success':'muted' ?>"><?= clean($r['status']) ?></span></td>
                        <td><a href="?toggle=<?= $r['id'] ?>" class="badge badge-teal"><?= $r['status']==='active'?'Deactivate':'Activate' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No receptionists registered yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
