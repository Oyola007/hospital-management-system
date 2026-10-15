<?php
require_once __DIR__ . '/../config/app.php';
requireRole('patient');
$activePage = 'appointments';

$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patientId = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $reason = trim($_POST['reason'] ?? '');

    if ($doctor_id && $date && $time) {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by) VALUES (?, ?, ?, ?, ?, 'pending', 'patient')");
        $stmt->execute([$patientId, $doctor_id, $date, $time, $reason]);
        flash('success', 'Appointment request submitted. You will be notified once confirmed.');
    } else {
        flash('error', 'Please complete all required fields.');
    }
    redirect('/patient/appointments.php');
}

if (isset($_GET['cancel'])) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ?");
    $stmt->execute([(int)$_GET['cancel'], $patientId]);
    redirect('/patient/appointments.php');
}

$doctors = $pdo->query("
    SELECT d.id, u.full_name, dep.name AS dept, d.specialization, d.consultation_fee
    FROM doctors d JOIN users u ON d.user_id = u.id LEFT JOIN departments dep ON d.department_id = dep.id
    WHERE u.status = 'active' ORDER BY u.full_name
")->fetchAll();

$stmt = $pdo->prepare("
    SELECT a.*, ud.full_name AS doctor_name
    FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>My Appointments</h1><p>Book a new appointment or track existing ones</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Book Appointment</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Doctor *</label>
                        <select name="doctor_id" required>
                            <option value="">-- Select Doctor --</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= $d['id'] ?>">Dr. <?= clean($d['full_name']) ?> <?= $d['dept'] ? '— ' . clean($d['dept']) : '' ?> (<?= formatMoney($d['consultation_fee']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Preferred Date *</label><input type="date" name="appointment_date" required min="<?= date('Y-m-d') ?>"></div>
                    <div class="form-group"><label>Preferred Time *</label><input type="time" name="appointment_time" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Reason for Visit</label><input type="text" name="reason" placeholder="Briefly describe your concern"></div>
                </div>
                <button type="submit" name="book_appointment" class="btn btn-primary">Request Appointment</button>
            </form>
        </div>

        <div class="card">
            <h2>My Appointment History</h2>
            <?php if ($appointments): ?>
            <table>
                <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= clean($a['appointment_date']) ?></td>
                        <td class="mono"><?= clean(substr($a['appointment_time'],0,5)) ?></td>
                        <td>Dr. <?= clean($a['doctor_name']) ?></td>
                        <td><span class="badge badge-<?= $a['status']==='completed'?'success':($a['status']==='cancelled'?'danger':'teal') ?>"><?= clean($a['status']) ?></span></td>
                        <td><?php if (in_array($a['status'], ['pending','confirmed'])): ?><a href="?cancel=<?= $a['id'] ?>" onclick="return confirm('Cancel this appointment?')" class="badge badge-danger">Cancel</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">You haven't booked any appointments yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
