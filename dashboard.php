<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Simple XAMPP Connection
$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];

// Get Company Info
$company_query = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query);

// Fetch Metrics Totals
$total_worked = 0;
$total_received = 0;
$total_pending = 0;

$worked_query = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM invoices");
if ($worked_row = mysqli_fetch_assoc($worked_query)) {
    $total_worked = $worked_row['total'] ? $worked_row['total'] : 0;
}

$received_query = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM invoices WHERE status='Paid'");
if ($received_row = mysqli_fetch_assoc($received_query)) {
    $total_received = $received_row['total'] ? $received_row['total'] : 0;
}

$pending_query = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM invoices WHERE status='Pending'");
if ($pending_row = mysqli_fetch_assoc($pending_query)) {
    $total_pending = $pending_row['total'] ? $pending_row['total'] : 0;
}

// Fetch Recent 5 Invoices
$recent_invoices = mysqli_query($conn, "SELECT * FROM invoices ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Invoice Billing</title>
    <!-- Linked to assets/css/dashboard.css -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

    <!-- Hidden Checkbox for Drawer Toggle -->
    <input type="checkbox" id="menu-toggle">

    <!-- Overlay Background -->
    <label for="menu-toggle" class="overlay"></label>

    <!-- Sidebar Drawer Navigation -->
    <div class="sidebar">
        <h3><?php echo htmlspecialchars($company['company_name']); ?></h3>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="customer.php">Customers</a>
        <a href="invoices.php">Monthly Invoices</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <!-- Top Header Bar -->
    <div class="navbar">
        <div class="nav-left">
            <label for="menu-toggle" class="hamburger-btn">&#9776;</label>
            <span class="brand-title">Invoice Dashboard</span>
        </div>
        <a href="invoice-create.php" class="btn-new-inv">+ New Invoice</a>
    </div>

    <!-- Main Content -->
    <div class="container">

        <!-- Financial Summary Cards -->
        <div class="stats-grid">
            <div class="card">
                <div class="card-title">Total Worked (Invoiced)</div>
                <div class="card-value">₹<?php echo number_format($total_worked, 2); ?></div>
            </div>
            <div class="card received">
                <div class="card-title">Total Received</div>
                <div class="card-value">₹<?php echo number_format($total_received, 2); ?></div>
            </div>
            <div class="card pending">
                <div class="card-title">Total Pending</div>
                <div class="card-value">₹<?php echo number_format($total_pending, 2); ?></div>
            </div>
        </div>

        <!-- Recent Invoices Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Invoices</h3>
                <a href="invoices.php" class="btn-view">View All Invoices &rarr;</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer Name</th>
                        <th>Date</th>
                        <th>Amount (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_invoices) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_invoices)): ?>
                            <tr>
                                <td>INV-<?php echo sprintf('%03d', $row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                                <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Paid'): ?>
                                        <span class="badge badge-paid">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888; padding: 20px;">No invoices generated yet. Click "+ New Invoice" to start!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>