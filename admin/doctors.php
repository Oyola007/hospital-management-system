<?php
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$activePage = 'doctors';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $department_id = $_POST['department_id'] ?: null;
    $specialization = trim($_POST['specialization'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $fee = $_POST['consultation_fee'] ?: 0;

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
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'doctor')");
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialization, license_no, consultation_fee) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $department_id, $specialization, $license_no, $fee]);

                $pdo->commit();
                flash('success', 'Doctor account created.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Failed to create doctor.');
            }
        }
    }
    redirect('/admin/doctors.php');
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("SELECT u.id, u.status FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->execute([(int)$_GET['toggle']]);
    if ($row = $stmt->fetch()) {
        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $row['id']]);
    }
    redirect('/admin/doctors.php');
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$doctors = $pdo->query("
    SELECT d.*, u.full_name, u.email, u.phone, u.status, dep.name AS department_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN departments dep ON d.department_id = dep.id
    ORDER BY u.full_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctors | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Doctors</h1><p>Manage medical staff accounts</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Add Doctor</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>Temporary Password *</label><input type="text" name="password" required></div>
                    <div class="form-group"><label>Department</label>
                        <select name="department_id">
                            <option value="">-- Select --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= clean($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Specialization</label><input type="text" name="specialization"></div>
                    <div class="form-group"><label>License No.</label><input type="text" name="license_no"></div>
                    <div class="form-group"><label>Consultation Fee (KSh)</label><input type="number" step="0.01" name="consultation_fee" value="500"></div>
                </div>
                <button type="submit" name="add_doctor" class="btn btn-primary">Create Doctor Account</button>
            </form>
        </div>

        <div class="card">
            <h2>All Doctors</h2>
            <?php if ($doctors): ?>
            <table>
                <thead><tr><th>Name</th><th>Department</th><th>Specialization</th><th>Email</th><th>Fee</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($doctors as $d): ?>
                    <tr>
                        <td>Dr. <?= clean($d['full_name']) ?></td>
                        <td><?= clean($d['department_name'] ?? '—') ?></td>
                        <td><?= clean($d['specialization']) ?></td>
                        <td><?= clean($d['email']) ?></td>
                        <td><?= formatMoney($d['consultation_fee']) ?></td>
                        <td><span class="badge badge-<?= $d['status']==='active'?'success':'muted' ?>"><?= clean($d['status']) ?></span></td>
                        <td><a href="?toggle=<?= $d['id'] ?>" class="badge badge-teal"><?= $d['status']==='active'?'Deactivate':'Activate' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No doctors registered yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
