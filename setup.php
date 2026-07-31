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

$error = "";
$user_id = $_SESSION['user_id'];

// If profile already exists, redirect to dashboard directly
$check_existing = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
if (mysqli_num_rows($check_existing) > 0) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['save_setup'])) {
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $gst_number   = strtoupper(trim($_POST['gst_number']));
    $pan_number   = strtoupper(trim($_POST['pan_number']));
    $address      = mysqli_real_escape_string($conn, trim($_POST['address']));

    // Server Validation for GST and PAN
    $gst_pattern = "/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/";
    $pan_pattern = "/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/";

    if (!preg_match($gst_pattern, $gst_number)) {
        $error = "Invalid GST Number format! (Example: 24AAAAA0000A1Z5)";
    } elseif (!preg_match($pan_pattern, $pan_number)) {
        $error = "Invalid PAN Number format! (Example: ABCDE1234F)";
    } else {
        // Logo Upload Handling
        $logo_name = "";
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $logo_name = time() . "_" . basename($_FILES['logo']['name']);
            
            if (!is_dir("uploads")) {
                mkdir("uploads", 0777, true);
            }
            
            move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/" . $logo_name);
        }

        $query = "INSERT INTO company (user_id, company_name, gst_number, pan_number, address, logo) 
                  VALUES ('$user_id', '$company_name', '$gst_number', '$pan_number', '$address', '$logo_name')";

        if (mysqli_query($conn, $query)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Error saving company details: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Setup - Invoice Billing</title>
    <!-- Linked to assets/css/setup.css -->
    <link rel="stylesheet" href="assets/css/setup.css">
</head>
<body>

    <div class="setup-card">
        <h2>Company Setup</h2>
        <p class="subtitle">Enter your business details to start generating invoices.</p>

        <?php if ($error != ""): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="setup.php" enctype="multipart/form-data">
            <label>Company Name:</label>
            <input type="text" name="company_name" placeholder="e.g. Chamunda Engineering Works" required>

            <label>GSTIN Number:</label>
            <input type="text" name="gst_number" placeholder="e.g. 24AAAAA0000A1Z5" maxlength="15" required>

            <label>PAN Number:</label>
            <input type="text" name="pan_number" placeholder="e.g. ABCDE1234F" maxlength="10" required>

            <label>Business Address:</label>
            <textarea name="address" rows="3" placeholder="Full workshop / office address" required></textarea>

            <label>Company Logo / Profile Image:</label>
            <input type="file" name="logo" accept="image/*">

            <button type="submit" name="save_setup" class="btn-submit">Save & Go to Dashboard</button>
        </form>
    </div>

</body>
</html>