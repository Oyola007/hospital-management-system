<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'profile';

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.email, u.phone FROM patients p JOIN users u ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_phone = trim($_POST['emergency_contact_phone'] ?? '');

    $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $_SESSION['user_id']]);
    $pdo->prepare("UPDATE patients SET address = ?, blood_group = ?, emergency_contact_name = ?, emergency_contact_phone = ? WHERE user_id = ?")
        ->execute([$address, $blood_group, $emergency_name, $emergency_phone, $_SESSION['user_id']]);

    flash('success', 'Profile updated.');
    redirect('/patient/profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        flash('error', 'Current password is incorrect.');
    } elseif ($new !== $confirm) {
        flash('error', 'New passwords do not match.');
    } elseif (strlen($new) < 6) {
        flash('error', 'New password must be at least 6 characters.');
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $_SESSION['user_id']]);
        flash('success', 'Password changed successfully.');
    }
    redirect('/patient/profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Profile</h1><p>Manage your personal details</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Personal Details</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?= clean($patient['full_name']) ?>" disabled></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?= clean($patient['email']) ?>" disabled></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= clean($patient['phone']) ?>"></div>
                    <div class="form-group"><label>Blood Group</label><input type="text" name="blood_group" value="<?= clean($patient['blood_group']) ?>"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Address</label><input type="text" name="address" value="<?= clean($patient['address']) ?>"></div>
                    <div class="form-group"><label>Emergency Contact Name</label><input type="text" name="emergency_contact_name" value="<?= clean($patient['emergency_contact_name']) ?>"></div>
                    <div class="form-group"><label>Emergency Contact Phone</label><input type="text" name="emergency_contact_phone" value="<?= clean($patient['emergency_contact_phone']) ?>"></div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

        <div class="card">
            <h2>Change Password</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
                    <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
