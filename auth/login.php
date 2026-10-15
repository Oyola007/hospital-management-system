<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    redirect(roleHomePath($_SESSION['role']));
}

$error =isset(($_GET['error'])) === 'unauthorized' ? 'Please log in to continue.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Contact the hospital admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                redirect(roleHomePath($user['role']));
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-icon">+</div>
            <h1>HMS</h1>
            <p>Hospital Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= clean($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label>Email</label>
            <input type="email" name="email" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="auth-footer">New patient? <a href="<?= BASE_URL ?>/auth/register.php">Create an account</a></p>
    </div>
</body>
</html>
