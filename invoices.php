<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// XAMPP Connection
$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle Status Toggle (Paid <-> Pending)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $inv_id = intval($_GET['id']);
    $current_status = mysqli_real_escape_string($conn, $_GET['toggle_status']);
    $new_status = ($current_status == 'Paid') ? 'Pending' : 'Paid';

    mysqli_query($conn, "UPDATE invoices SET status='$new_status' WHERE id='$inv_id' AND user_id='$user_id'");
    $msg = "Invoice status updated to " . $new_status . "!";
}

// Handle Invoice Deletion
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM invoice_items WHERE invoice_id='$del_id'");
    mysqli_query($conn, "DELETE FROM invoices WHERE id='$del_id' AND user_id='$user_id'");
    $msg = "Invoice deleted successfully.";
}

// Fetch Company Header Name
$company_query = mysqli_query($conn, "SELECT company_name FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query);

// Fetch All Invoices
$invoices = mysqli_query($conn, "SELECT * FROM invoices WHERE user_id='$user_id' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Invoices - Billing System</title>
    <!-- Linked to assets/css/invoices.css -->
    <link rel="stylesheet" href="assets/css/invoices.css">
</head>
<body>

    <!-- Navigation Drawer -->
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="overlay"></label>

    <div class="sidebar">
        <h3><?php echo htmlspecialchars($company['company_name'] ?? 'Company'); ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="customer.php">Customers</a>
        <a href="invoices.php" class="active">Monthly Invoices</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <!-- Top Navbar -->
    <div class="navbar">
        <div class="nav-left">
            <label for="menu-toggle" class="hamburger-btn">&#9776;</label>
            <span class="brand-title">Invoice Records</span>
        </div>
        <a href="invoice-create.php" class="btn-new-inv">+ Create Invoice</a>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>All Generated Invoices</h2>
            </div>

            <?php if ($msg != ""): ?>
                <div class="alert-success"><?php echo $msg; ?></div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer Name</th>
                        <th>Invoice Date</th>
                        <th>Amount (₹)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($invoices) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($invoices)): ?>
                            <tr>
                                <td><strong>INV-<?php echo sprintf('%03d', $row['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['invoice_date'])); ?></td>
                                <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Paid'): ?>
                                        <span class="badge badge-paid">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                    <a href="invoices.php?toggle_status=<?php echo $row['status']; ?>&id=<?php echo $row['id']; ?>" class="btn-status">Toggle</a>
                                </td>
                                <td>
                                    <a href="print-invoice.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action btn-print">Print / PDF</a>
                                    <a href="invoices.php?delete_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 25px;">No invoices generated yet. Click "+ Create Invoice" above!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>