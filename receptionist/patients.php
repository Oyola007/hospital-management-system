<?php
require_once __DIR__ . '/../config/app.php';
requireRole('receptionist');
$activePage = 'patients';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_patient'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'] ?: null;
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_phone = trim($_POST['emergency_contact_phone'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');

    if (!$full_name || !$email) {
        flash('error', 'Name and email are required.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'A patient with this email already exists.');
        } else {
            $pdo->beginTransaction();
            try {
                // Default password = phone number or 'patient123' - patient should reset later
                $tempPassword = $phone ?: 'patient123';
                $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'patient')");
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO patients (user_id, dob, gender, address, blood_group, emergency_contact_name, emergency_contact_phone, national_id, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $dob, $gender, $address, $blood_group, $emergency_name, $emergency_phone, $national_id, $_SESSION['user_id']]);

                $pdo->commit();
                flash('success', "Patient registered. Temporary login password: {$tempPassword}");
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Registration failed. Please try again.');
            }
        }
    }
    redirect('/receptionist/patients.php');
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT p.*, u.full_name, u.email, u.phone FROM patients p JOIN users u ON p.user_id = u.id";
$params = [];
if ($search) {
    $sql .= " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY p.id DESC LIMIT 30";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Patient | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Patient Registration</h1><p>Register walk-in patients at the front desk</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>New Patient</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>National ID</label><input type="text" name="national_id"></div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob"></div>
                    <div class="form-group"><label>Gender</label>
                        <select name="gender"><option value="">Select</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select>
                    </div>
                    <div class="form-group"><label>Blood Group</label><input type="text" name="blood_group" placeholder="e.g. O+"></div>
                    <div class="form-group"><label>Address</label><input type="text" name="address"></div>
                    <div class="form-group"><label>Emergency Contact Name</label><input type="text" name="emergency_contact_name"></div>
                    <div class="form-group"><label>Emergency Contact Phone</label><input type="text" name="emergency_contact_phone"></div>
                </div>
                <button type="submit" name="register_patient" class="btn btn-primary">Register Patient</button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Patients</h2>
            <form method="GET" style="display:flex; gap:10px; margin-bottom:16px;">
                <input type="text" name="q" placeholder="Search by name, email or phone" value="<?= clean($search) ?>" style="flex:1; padding:9px 12px; border:1px solid var(--border); border-radius:8px;">
                <button class="btn btn-secondary" type="submit">Search</button>
            </form>
            <?php if ($patients): ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Blood Group</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?= clean($p['full_name']) ?></td>
                        <td><?= clean($p['email']) ?></td>
                        <td><?= clean($p['phone']) ?></td>
                        <td><?= clean($p['blood_group'] ?: '—') ?></td>
                        <td><a href="<?= BASE_URL ?>/receptionist/appointments.php?patient_id=<?= $p['id'] ?>" class="badge badge-teal">Book Appointment</a></td>
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
