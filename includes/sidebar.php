<?php
// Expects $activePage to be set by the including page (e.g. 'dashboard')
$role = $_SESSION['role'];
$user = currentUser();

$menus = [
    'admin' => [
        'dashboard' => ['Dashboard', '/admin/dashboard.php'],
        'doctors' => ['Doctors', '/admin/doctors.php'],
        'receptionists' => ['Receptionists', '/admin/receptionists.php'],
        'patients' => ['Patients', '/admin/patients.php'],
        'departments' => ['Departments', '/admin/departments.php'],
        'wards' => ['Wards & Beds', '/admin/wards.php'],
        'pharmacy' => ['Pharmacy', '/admin/pharmacy.php'],
        'lab' => ['Laboratory', '/admin/lab.php'],
        'billing' => ['Billing', '/admin/billing.php'],
        'reports' => ['Reports', '/admin/reports.php'],
    ],
    'doctor' => [
        'dashboard' => ['Dashboard', '/doctor/dashboard.php'],
        'appointments' => ['My Appointments', '/doctor/appointments.php'],
        'patients' => ['My Patients', '/doctor/patients.php'],
        'consultations' => ['Consultations', '/doctor/consultations.php'],
        'lab' => ['Lab Requests', '/doctor/lab_requests.php'],
        'admissions' => ['Admissions', '/doctor/admissions.php'],
    ],
    'receptionist' => [
        'dashboard' => ['Dashboard', '/receptionist/dashboard.php'],
        'patients' => ['Register Patient', '/receptionist/patients.php'],
        'appointments' => ['Appointments', '/receptionist/appointments.php'],
        'admissions' => ['Admissions', '/receptionist/admissions.php'],
        'billing' => ['Billing', '/receptionist/billing.php'],
    ],
    'patient' => [
        'dashboard' => ['Dashboard', '/patient/dashboard.php'],
        'appointments' => ['My Appointments', '/patient/appointments.php'],
        'records' => ['Medical Records', '/patient/records.php'],
        'prescriptions' => ['Prescriptions', '/patient/prescriptions.php'],
        'billing' => ['My Bills', '/patient/billing.php'],
        'profile' => ['Profile', '/patient/profile.php'],
    ],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">+</div>
        <div>
            <div class="brand-title">HMS</div>
            <div class="brand-sub"><?= ucfirst($role) ?> Portal</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menus[$role] as $key => $item): ?>
            <a href="<?= BASE_URL . $item[1] ?>" class="nav-link <?= ($activePage ?? '') === $key ? 'active' : '' ?>">
                <?= clean($item[0]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= clean($user['full_name']) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-logout">Logout</a>
    </div>
</aside>
