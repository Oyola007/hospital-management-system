<?php
require_once __DIR__ . '/../config/app.php';
requireRole('receptionist');
$activePage = 'billing';

// Create a new invoice with line items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $patient_id = (int)$_POST['patient_id'];
    $descriptions = $_POST['item_description'] ?? [];
    $categories = $_POST['item_category'] ?? [];
    $amounts = $_POST['item_amount'] ?? [];

    $total = 0;
    $items = [];
    foreach ($descriptions as $i => $desc) {
        $desc = trim($desc);
        $amt = (float)($amounts[$i] ?? 0);
        if ($desc && $amt > 0) {
            $items[] = [$desc, $categories[$i] ?? 'other', $amt];
            $total += $amt;
        }
    }

    if ($patient_id && $items) {
        $pdo->beginTransaction();
        try {
            $invoiceNo = generateInvoiceNumber();
            $stmt = $pdo->prepare("INSERT INTO invoices (patient_id, invoice_number, total_amount, status, created_by) VALUES (?, ?, ?, 'unpaid', ?)");
            $stmt->execute([$patient_id, $invoiceNo, $total, $_SESSION['user_id']]);
            $invoiceId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, category, amount) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([$invoiceId, $item[0], $item[1], $item[2]]);
            }
            $pdo->commit();
            flash('success', "Invoice {$invoiceNo} created.");
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Failed to create invoice.');
        }
    } else {
        flash('error', 'Select a patient and add at least one line item.');
    }
    redirect('/receptionist/billing.php');
}

// Record a payment against an invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $invoice_id = (int)$_POST['invoice_id'];
    $amount = (float)$_POST['amount'];
    $method = $_POST['method'];
    $reference = trim($_POST['reference_no'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if ($invoice && $amount > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, amount, method, reference_no, received_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$invoice_id, $amount, $method, $reference, $_SESSION['user_id']]);

            $newPaid = $invoice['amount_paid'] + $amount;
            $status = $newPaid >= $invoice['total_amount'] ? 'paid' : 'partial';
            $stmt = $pdo->prepare("UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ?");
            $stmt->execute([$newPaid, $status, $invoice_id]);

            $pdo->commit();
            flash('success', 'Payment recorded.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Failed to record payment.');
        }
    }
    redirect('/receptionist/billing.php');
}

$patients = $pdo->query("SELECT p.id, u.full_name FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.full_name")->fetchAll();
$invoices = $pdo->query("
    SELECT i.*, u.full_name AS patient_name
    FROM invoices i JOIN patients p ON i.patient_id = p.id JOIN users u ON p.user_id = u.id
    ORDER BY i.created_at DESC LIMIT 30
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Billing | <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main">
        <div class="page-header"><div><h1>Billing</h1><p>Create invoices and record payments</p></div></div>
        <div class="pulse-divider"></div>

        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= clean($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= clean($msg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Create Invoice</h2>
            <form method="POST" id="invoiceForm">
                <div class="form-group">
                    <label>Patient *</label>
                    <select name="patient_id" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= clean($p['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="itemsWrap">
                    <div class="form-grid" style="grid-template-columns: 2fr 1fr 1fr;">
                        <div class="form-group"><label>Description</label><input type="text" name="item_description[]" placeholder="e.g. Consultation fee"></div>
                        <div class="form-group"><label>Category</label>
                            <select name="item_category[]">
                                <option value="consultation">Consultation</option>
                                <option value="lab">Lab</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="admission">Admission</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Amount (KSh)</label><input type="number" step="0.01" name="item_amount[]"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addItemRow()">+ Add Line Item</button>
                <div class="form-actions">
                    <button type="submit" name="create_invoice" class="btn btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Invoices</h2>
            <?php if ($invoices): ?>
            <table>
                <thead><tr><th>Invoice #</th><th>Patient</th><th>Total</th><th>Paid</th><th>Status</th><th>Record Payment</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td class="mono"><?= clean($inv['invoice_number']) ?></td>
                        <td><?= clean($inv['patient_name']) ?></td>
                        <td><?= formatMoney($inv['total_amount']) ?></td>
                        <td><?= formatMoney($inv['amount_paid']) ?></td>
                        <td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='partial'?'warning':'danger') ?>"><?= clean($inv['status']) ?></span></td>
                        <td>
                            <?php if ($inv['status'] !== 'paid'): ?>
                            <form method="POST" style="display:flex; gap:6px;">
                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                <input type="number" step="0.01" name="amount" placeholder="Amount" required style="width:90px; padding:6px 8px; border:1px solid var(--border); border-radius:6px;">
                                <select name="method" style="padding:6px; border:1px solid var(--border); border-radius:6px;">
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="card">Card</option>
                                    <option value="insurance">Insurance</option>
                                </select>
                                <button type="submit" name="record_payment" class="btn btn-sm btn-primary" style="margin:0;">Pay</button>
                            </form>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state">No invoices yet.</div><?php endif; ?>
        </div>
    </main>
</div>
<script>
function addItemRow() {
    const wrap = document.getElementById('itemsWrap');
    const row = document.createElement('div');
    row.className = 'form-grid';
    row.style.gridTemplateColumns = '2fr 1fr 1fr';
    row.innerHTML = `
        <div class="form-group"><input type="text" name="item_description[]" placeholder="Description"></div>
        <div class="form-group"><select name="item_category[]">
            <option value="consultation">Consultation</option>
            <option value="lab">Lab</option>
            <option value="pharmacy">Pharmacy</option>
            <option value="admission">Admission</option>
            <option value="other">Other</option>
        </select></div>
        <div class="form-group"><input type="number" step="0.01" name="item_amount[]" placeholder="Amount"></div>
    `;
    wrap.appendChild(row);
}
</script>
</body>
</html>
