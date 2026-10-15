<?php
require_once __DIR__ . '/../config/app.php';
requireRole('receptionist');
$activePage = 'admissions';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admit_patient'])) {
    $patient_id = (int)$_POST['patient_id'];
    $bed_id = (int)$_POST['bed_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $reason = trim($_POST['reason'] ?? '');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO admissions (patient_id, bed_id, doctor_id, admitted_by, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $bed_id, $doctor_id, $_SESSION['user_id'], $reason]);
        $stmt = $pdo->prepare("UPDATE beds SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$bed_id]);
        $pdo->commit();
        flash('success', 'Patient admitted successfully.');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to admit patient.');
    }
    redirect('/receptionist/admissions.php');
}

if (isset($_GET['discharge'])) {
    $admissionId = (int)$_GET['discharge'];
    $stmt = $pdo->prepare("SELECT bed_id FROM admissions WHERE id = ?");
    $stmt->execute([$admissionId]);
    $bedId = $stmt->fetchColumn();
    if ($bedId) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE admissions SET status = 'discharged', discharge_date = NOW() WHERE id = ?");
            $stmt->execute([$admissionId]);
            $stmt = $pdo->prepare("UPDATE beds SET status = 'available' WHERE id = ?");
            $stmt->execute([$bedId]);
            $pdo->commit();
            flash('success', 'Patient discharged. Bed released.');
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    redirect('/receptionist/admissions.php');
}

$patients = $pdo->query("SELECT p.id, u.full_name FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.full_name")->fetchAll();
$doctors = $pdo->query("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.status='active' ORDER BY u.full_name")->fetchAll();
$availableBeds = $pdo->query("SELECT b.id, b.bed_number, w.name AS ward_name FROM beds b JOIN wards w ON b.ward_id = w.id WHERE b.status = 'available' ORDER BY w.name, b.bed_number")->fetchAll();

$admissions = $pdo->query("
    SELECT ad.*, u.full_name AS patient_name, ud.full_name AS doctor_name, b.bed_number, w.name AS ward_name
    FROM admissions ad
    JOIN patients p ON ad.patient_id = p.id JOIN users u ON p.user_id = u.id
    JOIN doctors d ON ad.doctor_id = d.id JOIN users ud ON d.user_id = ud.id
    JOIN beds b ON ad.bed_id = b.id JOIN wards w ON b.ward_id = w.id
    ORDER BY ad.admit_date DESC LIMIT 30
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admissions | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Ward Admissions</h1><p>Admit and discharge patients</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Admit Patient</h2>
            <?php if (!$availableBeds): ?>
                <div class="alert alert-warning">No beds currently available.</div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Patient *</label>
                        <select name="patient_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($patients as $p): ?><option value="<?= $p['id'] ?>"><?= clean($p['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Attending Doctor *</label>
                        <select name="doctor_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($doctors as $d): ?><option value="<?= $d['id'] ?>">Dr. <?= clean($d['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bed *</label>
                        <select name="bed_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($availableBeds as $b): ?><option value="<?= $b['id'] ?>"><?= clean($b['ward_name']) ?> — Bed <?= clean($b['bed_number']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Reason for Admission</label><input type="text" name="reason"></div>
                </div>
                <button type="submit" name="admit_patient" class="btn btn-primary" <?= !$availableBeds ? 'disabled' : '' ?>>Admit Patient</button>
            </form>
        </div>

        <div class="card">
            <h2>Admissions</h2>
            <?php if ($admissions): ?>
            <table>
                <thead><tr><th>Patient</th><th>Ward / Bed</th><th>Doctor</th><th>Admitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($admissions as $a): ?>
                    <tr>
                        <td><?= clean($a['patient_name']) ?></td>
                        <td><?= clean($a['ward_name']) ?> / <?= clean($a['bed_number']) ?></td>
                        <td>Dr. <?= clean($a['doctor_name']) ?></td>
                        <td><?= clean(date('Y-m-d', strtotime($a['admit_date']))) ?></td>
                        <td><span class="badge badge-<?= $a['status']==='admitted'?'teal':'muted' ?>"><?= clean($a['status']) ?></span></td>
                        <td><?php if ($a['status'] === 'admitted'): ?><a href="?discharge=<?= $a['id'] ?>" onclick="return confirm('Discharge this patient?')" class="badge badge-danger">Discharge</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No admissions yet.</div><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
