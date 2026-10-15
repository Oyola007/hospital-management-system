<?php
require_once __DIR__ . '/../config/app.php';
requireRole('doctor');
$activePage = 'appointments';

$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetchColumn();

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS patient_name, u.id AS patient_user_id, p.id as patient_row_id, p.blood_group, p.dob, p.gender
    FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->execute([$appointmentId, $doctorId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    redirect('/doctor/appointments.php');
}

// Fetch doctor's consultation fee for auto-billing
$stmt = $pdo->prepare("SELECT consultation_fee FROM doctors WHERE id = ?");
$stmt->execute([$doctorId]);
$fee = $stmt->fetchColumn();

$labTests = $pdo->query("SELECT * FROM lab_tests ORDER BY test_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_consultation'])) {
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $med_names = $_POST['med_name'] ?? [];
    $med_dosages = $_POST['med_dosage'] ?? [];
    $med_frequencies = $_POST['med_frequency'] ?? [];
    $med_durations = $_POST['med_duration'] ?? [];
    $med_instructions = $_POST['med_instructions'] ?? [];

    $lab_test_ids = $_POST['lab_test_id'] ?? [];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO consultations (appointment_id, patient_id, doctor_id, symptoms, diagnosis, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$appointmentId, $appointment['patient_row_id'], $doctorId, $symptoms, $diagnosis, $notes]);
        $consultationId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO prescriptions (consultation_id, medicine_name, dosage, frequency, duration, instructions) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($med_names as $i => $name) {
            $name = trim($name);
            if ($name) {
                $stmt->execute([$consultationId, $name, $med_dosages[$i] ?? '', $med_frequencies[$i] ?? '', $med_durations[$i] ?? '', $med_instructions[$i] ?? '']);
            }
        }

        $stmtLab = $pdo->prepare("INSERT INTO lab_requests (patient_id, doctor_id, test_id) VALUES (?, ?, ?)");
        foreach ($lab_test_ids as $testId) {
            if ($testId) $stmtLab->execute([$appointment['patient_row_id'], $doctorId, (int)$testId]);
        }

        $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$appointmentId]);

        // Auto-generate consultation invoice
        $invoiceNo = generateInvoiceNumber();
        $stmt = $pdo->prepare("INSERT INTO invoices (patient_id, invoice_number, total_amount, status, created_by) VALUES (?, ?, ?, 'unpaid', ?)");
        $stmt->execute([$appointment['patient_row_id'], $invoiceNo, $fee, $_SESSION['user_id']]);
        $invoiceId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, amount) VALUES (?, ?, 'consultation', ?)");
        $stmt->execute([$invoiceId, 'Consultation with Dr. ' . currentUser()['full_name'], $fee]);

        $pdo->commit();
        flash('success', 'Consultation recorded and invoice generated.');
        redirect('/doctor/appointments.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to save consultation: ' . $e->getMessage());
    }
}

// Patient history
$stmt = $pdo->prepare("
    SELECT c.*, GROUP_CONCAT(pr.medicine_name SEPARATOR ', ') AS meds
    FROM consultations c
    LEFT JOIN prescriptions pr ON pr.consultation_id = c.id
    WHERE c.patient_id = ?
    GROUP BY c.id
    ORDER BY c.consultation_date DESC LIMIT 5
");
$stmt->execute([$appointment['patient_row_id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Consultation | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header">
            <div>
                <h1>Consultation: <?= clean($appointment['patient_name']) ?></h1>
                <p><?= clean($appointment['gender'] ? ucfirst($appointment['gender']) : '') ?> <?= $appointment['dob'] ? '· DOB ' . clean($appointment['dob']) : '' ?> <?= $appointment['blood_group'] ? '· Blood Group ' . clean($appointment['blood_group']) : '' ?></p>
            </div>
        </div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <?php if ($history): ?>
        <div class="card">
            <h2>Recent History</h2>
            <table>
                <thead><tr><th>Date</th><th>Diagnosis</th><th>Medicines</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr><td><?= clean(date('Y-m-d', strtotime($h['consultation_date']))) ?></td><td><?= clean($h['diagnosis'] ?: '—') ?></td><td><?= clean($h['meds'] ?: '—') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="card">
                <h2>Consultation Notes</h2>
                <div class="form-group"><label>Symptoms</label><textarea name="symptoms" rows="2"></textarea></div>
                <div class="form-group"><label>Diagnosis</label><textarea name="diagnosis" rows="2"></textarea></div>
                <div class="form-group"><label>Additional Notes</label><textarea name="notes" rows="2"></textarea></div>
            </div>

            <div class="card">
                <h2>Prescriptions</h2>
                <div id="medsWrap">
                    <div class="form-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr 2fr;">
                        <div class="form-group"><label>Medicine</label><input type="text" name="med_name[]"></div>
                        <div class="form-group"><label>Dosage</label><input type="text" name="med_dosage[]" placeholder="e.g. 500mg"></div>
                        <div class="form-group"><label>Frequency</label><input type="text" name="med_frequency[]" placeholder="e.g. 2x/day"></div>
                        <div class="form-group"><label>Duration</label><input type="text" name="med_duration[]" placeholder="e.g. 5 days"></div>
                        <div class="form-group"><label>Instructions</label><input type="text" name="med_instructions[]" placeholder="e.g. After meals"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addMedRow()">+ Add Medicine</button>
            </div>

            <div class="card">
                <h2>Lab Requests</h2>
                <div class="form-group">
                    <label>Order Tests</label>
                    <select name="lab_test_id[]" multiple size="5" style="padding:9px; border:1px solid var(--border); border-radius:8px;">
                        <?php foreach ($labTests as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= clean($t['test_name']) ?> (<?= formatMoney($t['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--text-muted);">Hold Ctrl/Cmd to select multiple tests. Leave empty if none needed.</small>
                </div>
            </div>

            <button type="submit" name="save_consultation" class="btn btn-primary">Complete Consultation &amp; Generate Invoice</button>
        </form>
    </main>
</div>
<script>
function addMedRow() {
    const wrap = document.getElementById('medsWrap');
    const row = document.createElement('div');
    row.className = 'form-grid';
    row.style.gridTemplateColumns = '2fr 1fr 1fr 1fr 2fr';
    row.innerHTML = `
        <div class="form-group"><input type="text" name="med_name[]" placeholder="Medicine"></div>
        <div class="form-group"><input type="text" name="med_dosage[]" placeholder="Dosage"></div>
        <div class="form-group"><input type="text" name="med_frequency[]" placeholder="Frequency"></div>
        <div class="form-group"><input type="text" name="med_duration[]" placeholder="Duration"></div>
        <div class="form-group"><input type="text" name="med_instructions[]" placeholder="Instructions"></div>
    `;
    wrap.appendChild(row);
}
</script>
</body>
</html>
