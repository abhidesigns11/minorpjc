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
$error = "";
$success = "";

// Get Company Info for Header
$company_query = mysqli_query($conn, "SELECT company_name FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query);

// Handle Customer Form Submission
if (isset($_POST['add_customer'])) {
    $name       = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $mobile     = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $gst_number = strtoupper(trim($_POST['gst_number']));
    $pan_number = strtoupper(trim($_POST['pan_number']));
    $state      = mysqli_real_escape_string($conn, trim($_POST['state']));
    $state_code = mysqli_real_escape_string($conn, trim($_POST['state_code']));
    $address    = mysqli_real_escape_string($conn, trim($_POST['address']));

    // Validation Patterns
    $gst_pattern    = "/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/";
    $pan_pattern    = "/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/";
    $mobile_pattern = "/^[6-9][0-9]{9}$/";

    if (!preg_match($mobile_pattern, $mobile)) {
        $error = "Invalid Mobile Number! Enter a valid 10-digit Indian mobile number.";
    } elseif ($gst_number != "" && !preg_match($gst_pattern, $gst_number)) {
        $error = "Invalid GST Number format! (Example: 24AAAAA0000A1Z5)";
    } elseif ($pan_number != "" && !preg_match($pan_pattern, $pan_number)) {
        $error = "Invalid PAN Number format! (Example: ABCDE1234F)";
    } else {
        $insert = "INSERT INTO customers (user_id, name, email, mobile, gst_number, pan_number, state, state_code, address) 
                   VALUES ('$user_id', '$name', '$email', '$mobile', '$gst_number', '$pan_number', '$state', '$state_code', '$address')";

        if (mysqli_query($conn, $insert)) {
            $success = "Customer added successfully!";
        } else {
            $error = "Error adding customer: " . mysqli_error($conn);
        }
    }
}

// Fetch Existing Customers
$customers = mysqli_query($conn, "SELECT * FROM customers WHERE user_id='$user_id' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Invoice Billing</title>
    <!-- Linked to assets/css/customer.css -->
    <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body>

    <!-- Drawer Checkbox -->
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="overlay"></label>

    <!-- Sidebar Drawer -->
    <div class="sidebar">
        <h3><?php echo htmlspecialchars($company['company_name'] ?? 'Company'); ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="customer.php" class="active">Customers</a>
        <a href="invoices.php">Monthly Invoices</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <!-- Header -->
    <div class="navbar">
        <div class="nav-left">
            <label for="menu-toggle" class="hamburger-btn">&#9776;</label>
            <span class="brand-title">Customer Management</span>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">

        <div class="customer-grid">
            
            <!-- Add Customer Form -->
            <div class="card">
                <h3>Add New Customer</h3>

                <?php if ($error != ""): ?>
                    <div class="msg error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success != ""): ?>
                    <div class="msg success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="customer.php">
                    <label>Customer / Firm Name:</label>
                    <input type="text" name="name" placeholder="e.g. Acme Enterprises" required>

                    <label>Mobile Number:</label>
                    <input type="text" name="mobile" placeholder="10-digit number" maxlength="10" required>

                    <label>Email Address:</label>
                    <input type="email" name="email" placeholder="customer@example.com">

                    <div class="form-row">
                        <div class="field-group">
                            <label>GSTIN Number:</label>
                            <input type="text" name="gst_number" placeholder="24AAAAA0000A1Z5" maxlength="15">
                        </div>
                        <div class="field-group">
                            <label>PAN Number:</label>
                            <input type="text" name="pan_number" placeholder="ABCDE1234F" maxlength="10">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field-group" style="flex: 2;">
                            <label>State:</label>
                            <select name="state" required>
                                <option value="Gujarat">Gujarat</option>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="Rajasthan">Rajasthan</option>
                                <option value="Delhi">Delhi</option>
                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="field-group" style="flex: 1;">
                            <label>State Code:</label>
                            <input type="text" name="state_code" placeholder="e.g. 24" maxlength="2" required>
                        </div>
                    </div>

                    <label>Address:</label>
                    <textarea name="address" rows="3" placeholder="Full address" required></textarea>

                    <button type="submit" name="add_customer" class="btn-submit">Save Customer</button>
                </form>
            </div>

            <!-- Customer List Table -->
            <div class="card">
                <h3>Saved Customers List</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>GSTIN</th>
                            <th>State (Code)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($customers) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($customers)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                    <td><?php echo $row['gst_number'] ? htmlspecialchars($row['gst_number']) : '<span style="color:#aaa;">N/A</span>'; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['state']); ?> 
                                        <span class="badge-code"><?php echo htmlspecialchars($row['state_code']); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #888; padding: 20px;">No customers added yet. Fill the form to create your first customer!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

</body>
</html>