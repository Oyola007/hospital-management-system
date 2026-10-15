<?php
require_once __DIR__ . '/config/app.php';

if (isLoggedIn()) {
    redirect(roleHomePath($_SESSION['role']));
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name LIMIT 6")->fetchAll();

$doctors = $pdo->query("
    SELECT u.full_name, d.specialization, dep.name AS dept
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN departments dep ON d.department_id = dep.id
    WHERE u.status = 'active'
    ORDER BY u.full_name LIMIT 4
")->fetchAll();

$stats = [
    'doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
    'patients' => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'beds' => $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn(),
];

$deptIcons = ['General Medicine' => '⚕', 'Pediatrics' => '🧸', 'Surgery' => '🔬', 'Maternity' => '👶', 'Laboratory' => '🧪'];

// Contact / location details — edit these to match your facility
$HOSPITAL_ADDRESS = 'Kenyatta Avenue, Nakuru, Kenya';
$HOSPITAL_PHONE = '+254 700 000 000';
$HOSPITAL_EMAIL = 'info@hms.local';
$HOSPITAL_HOURS = 'Outpatient: Mon–Sat, 8:00 AM – 6:00 PM · Emergency: 24/7';
$HOSPITAL_MAP_QUERY = urlencode($HOSPITAL_ADDRESS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="landing-body">

<header class="landing-nav">
    <div class="landing-nav-inner">
        <a href="<?= BASE_URL ?>/" class="landing-brand">
            <div class="brand-icon">+</div>
            <span>HMS</span>
        </a>
        <nav class="landing-nav-links">
            <a href="#departments">Departments</a>
            <a href="#doctors">Doctors</a>
            <a href="#contact">Contact</a>
        </nav>
        <div class="landing-nav-actions">
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary btn-sm">Sign In</a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm" style="margin-top:0;">Register</a>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Quality care, close to home</span>
            <h1>Healthcare that puts <em>you</em> first.</h1>
            <p>Book appointments, track your medical records, and manage your care — all in one place. Our doctors and staff are here for you around the clock.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary" style="margin-top:0;">Book an Appointment</a>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary">Patient Login</a>
            </div>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><div class="hero-stat-value"><?= $stats['doctors'] ?>+</div><div class="hero-stat-label">Doctors</div></div>
            <div class="hero-stat"><div class="hero-stat-value"><?= $stats['patients'] ?>+</div><div class="hero-stat-label">Patients Served</div></div>
            <div class="hero-stat"><div class="hero-stat-value"><?= $stats['departments'] ?></div><div class="hero-stat-label">Departments</div></div>
            <div class="hero-stat"><div class="hero-stat-value"><?= $stats['beds'] ?></div><div class="hero-stat-label">Inpatient Beds</div></div>
        </div>
    </div>
</section>

<section class="landing-section" id="departments">
    <div class="landing-section-inner">
        <span class="eyebrow">What we offer</span>
        <h2>Our Departments</h2>
        <?php if ($departments): ?>
        <div class="dept-grid">
            <?php foreach ($departments as $d): ?>
                <div class="dept-card">
                    <div class="dept-icon"><?= $deptIcons[$d['name']] ?? '✚' ?></div>
                    <h3><?= clean($d['name']) ?></h3>
                    <p><?= clean($d['description'] ?: 'Dedicated specialists ready to help.') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state">Departments will appear here once configured.</div>
        <?php endif; ?>
    </div>
</section>

<section class="landing-section landing-section-alt" id="doctors">
    <div class="landing-section-inner">
        <span class="eyebrow">Meet the team</span>
        <h2>Our Doctors</h2>
        <?php if ($doctors): ?>
        <div class="doctor-grid">
            <?php foreach ($doctors as $doc): ?>
                <div class="doctor-card">
                    <div class="doctor-avatar"><?= strtoupper(substr($doc['full_name'], 0, 1)) ?></div>
                    <h3>Dr. <?= clean($doc['full_name']) ?></h3>
                    <p><?= clean($doc['specialization'] ?: ($doc['dept'] ?: 'General Practice')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state">Our medical staff directory will appear here soon.</div>
        <?php endif; ?>
    </div>
</section>

<section class="landing-section" id="contact">
    <div class="landing-section-inner">
        <span class="eyebrow">Get in touch</span>
        <h2>Visit or Contact Us</h2>
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div>
                        <div class="contact-label">Address</div>
                        <div class="contact-value"><?= clean($HOSPITAL_ADDRESS) ?></div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div>
                        <div class="contact-label">Phone</div>
                        <div class="contact-value"><a href="tel:<?= clean(str_replace(' ', '', $HOSPITAL_PHONE)) ?>"><?= clean($HOSPITAL_PHONE) ?></a></div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">✉️</div>
                    <div>
                        <div class="contact-label">Email</div>
                        <div class="contact-value"><a href="mailto:<?= clean($HOSPITAL_EMAIL) ?>"><?= clean($HOSPITAL_EMAIL) ?></a></div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">🕒</div>
                    <div>
                        <div class="contact-label">Hours</div>
                        <div class="contact-value"><?= clean($HOSPITAL_HOURS) ?></div>
                    </div>
                </div>
                <div class="contact-emergency">
                    For medical emergencies, please call us directly or go to the nearest emergency room. This system is not monitored 24/7.
                </div>
            </div>
            <div class="contact-map">
                <iframe
                    src="https://www.openstreetmap.org/export/embed.html?bbox=36.7,-1.35,37.1,-0.15&layer=mapnik&marker=-0.283,36.066"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Hospital location map">
                </iframe>
            </div>
        </div>
    </div>
</section>

<section class="landing-section landing-section-alt" id="about">
    <div class="landing-section-inner cta-inner">
        <h2>Ready to get started?</h2>
        <p>Create your free patient account and book your first appointment in minutes.</p>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary" style="margin-top:20px;">Create Account</a>
    </div>
</section>

<section class="landing-section landing-section-alt" id="contact">
    <div class="landing-section-inner">
        <span class="eyebrow">Get in touch</span>
        <h2>Visit or Reach Us</h2>
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div>
                        <h4>Address</h4>
                        <p>123 Hospital Road, Nairobi, Kenya<br><span class="contact-hint">Update this in index.php</span></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div>
                        <h4>Phone</h4>
                        <p>+254 700 000 000<br><span class="contact-hint">Front desk &amp; general inquiries</span></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">✉️</div>
                    <div>
                        <h4>Email</h4>
                        <p>info@hms.local</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">🕒</div>
                    <div>
                        <h4>Opening Hours</h4>
                        <p>Outpatient: Mon–Sat, 8:00 AM – 6:00 PM<br>Emergency: 24/7</p>
                    </div>
                </div>
            </div>
            <div class="contact-map">
                <iframe
                    src="https://www.openstreetmap.org/export/embed.html?bbox=36.78%2C-1.32%2C36.86%2C-1.26&layer=mapnik&marker=-1.2921%2C36.8219"
                    style="border:0; width:100%; height:100%;"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Hospital location map">
                </iframe>
            </div>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div class="landing-section-inner footer-inner">
        <div>
            <div class="landing-brand" style="margin-bottom:6px;">
                <div class="brand-icon">+</div>
                <span>HMS</span>
            </div>
            <p style="margin:0; font-size:13px; color:var(--text-muted);"><?= clean($HOSPITAL_ADDRESS) ?></p>
        </div>
        <div class="footer-contact">
            <a href="tel:<?= clean(str_replace(' ', '', $HOSPITAL_PHONE)) ?>"><?= clean($HOSPITAL_PHONE) ?></a>
            <a href="mailto:<?= clean($HOSPITAL_EMAIL) ?>"><?= clean($HOSPITAL_EMAIL) ?></a>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> HMS — Hospital Management System.</p>
    </div>
</footer>

</body>
</html>
