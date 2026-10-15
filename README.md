# HMS — Hospital Management System

A full-stack hospital management system built with PHP + MySQL, following the same
stack and conventions as your HoodGym project (PDO, session-based auth, role-based
portals, XAMPP-friendly structure).

## Roles

- **Admin** — manage doctors, receptionists, departments, wards/beds, pharmacy inventory,
  lab tests, billing overview, and reports.
- **Doctor** — view appointments, run consultations (diagnosis, prescriptions, lab
  requests), see patient history, track admitted patients.
- **Receptionist** — register walk-in patients, book/manage appointments, admit/discharge
  patients to wards, create invoices and record payments.
- **Patient** — self-register, book appointments, view medical records, prescriptions,
  and bills, manage their own profile.

## Setup (XAMPP, same as HoodGym)

1. Copy the `hms` folder into your `htdocs` directory, e.g. `C:\xampp\htdocs\hms`.
2. Start Apache and MySQL in the XAMPP control panel.
3. Open **phpMyAdmin** → Import → select `database/hms_schema.sql` → Go.
   This creates the `hms_db` database, all tables, seed departments/wards, and a
   default admin account.
4. Open `config/db.php` and confirm your MySQL credentials (defaults: `root` / no
   password, matching a standard XAMPP install).
5. Visit `http://localhost/hms/` in your browser.

### Default login

```
Email:    admin@hms.local
Password: Admin@123
```

**Change this password immediately** — there's no reset-my-own-password page for
admin yet (patients can change theirs under Profile). You can update it directly in
phpMyAdmin using PHP's `password_hash()`, or add an admin profile page later.

## How the core patient flow works

1. **Receptionist** registers a walk-in patient (or the patient self-registers via
   `/auth/register.php`).
2. **Receptionist** or the **patient** books an appointment with a doctor.
3. **Doctor** opens the appointment and runs the consultation: records symptoms/
   diagnosis, adds prescriptions, and orders lab tests if needed. This automatically:
   - marks the appointment `completed`
   - generates an invoice for the consultation fee
4. **Admin** (acting as lab tech) enters lab results against pending requests, and
   sees pending prescriptions to dispense from Pharmacy.
5. **Receptionist** records payments against invoices (cash, M-Pesa, card, insurance).
6. **Patient** sees their appointments, records, prescriptions, and bills update in
   real time.

## What's built vs. what's next

**Built and working end-to-end:** auth (login/register/logout), all 4 dashboards,
doctor/department/receptionist/patient management, appointment booking, the full
consultation → prescription → lab-request → auto-invoice workflow, ward/bed setup
and admission/discharge, pharmacy inventory + restock, lab test catalog + result
entry, invoicing + payments, and basic reports.

**Natural next additions** (say the word and I'll build these in):
- Pharmacy: a dedicated "dispense" action that decrements stock and marks a
  prescription `dispensed` (currently admin can see pending prescriptions but
  dispensing isn't wired to inventory deduction yet).
- A dedicated receptionist/lab-tech role split (right now admin doubles as lab tech).
- Doctor/admin ability to edit their own profile + change password (patients already
  have this).
- Email/SMS notifications for appointment confirmations.
- PDF invoice/prescription printing.
- Search/filter and pagination on the larger tables (patients, invoices) as data grows.

## Folder structure

```
hms/
├── admin/            # admin portal pages
├── doctor/           # doctor portal pages
├── receptionist/      # receptionist portal pages
├── patient/          # patient portal pages
├── auth/             # login, register, logout
├── config/           # db.php, app.php (bootstrap)
├── includes/         # functions.php, sidebar.php
├── assets/css/       # style.css (design system)
├── database/         # hms_schema.sql
└── index.php         # role-based redirect
```
