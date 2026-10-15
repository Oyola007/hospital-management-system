<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    redirect(roleHomePath($_SESSION['role']));
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? null;

    if (!$full_name || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'patient')");
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO patients (user_id, dob, gender) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $dob ?: null, $gender ?: null]);

                $pdo->commit();
                $success = 'Account created successfully. You can now log in.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-icon">+</div>
            <h1>HMS</h1>
            <p>Create your patient account</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= clean($success) ?> <a href="<?= BASE_URL ?>/auth/login.php">Login now</a></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" class="auth-form">
            <label>Full Name *</label>
            <input type="text" name="full_name" required value="<?= clean($_POST['full_name'] ?? '') ?>">

            <label>Email *</label>
            <input type="email" name="email" required value="<?= clean($_POST['email'] ?? '') ?>">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= clean($_POST['phone'] ?? '') ?>">

            <div class="form-row">
                <div>
                    <label>Date of Birth</label>
                    <input type="date" name="dob">
                </div>
                <div>
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <label>Password *</label>
            <input type="password" name="password" required>

            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        <?php endif; ?>

        <p class="auth-footer">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Sign in</a></p>
    </div>
</body>
</html>
