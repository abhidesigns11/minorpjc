<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Fetch User & Company Data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

$company_query = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $gst_number   = mysqli_real_escape_string($conn, $_POST['gst_number']);
    $pan_number   = mysqli_real_escape_string($conn, $_POST['pan_number']);
    $phone        = mysqli_real_escape_string($conn, $_POST['phone']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $address      = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Bank Details
    $bank_name   = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account_no  = mysqli_real_escape_string($conn, $_POST['account_no']);
    $ifsc_code   = mysqli_real_escape_string($conn, $_POST['ifsc_code']);
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name']);

    // Handle Logo Upload
    $logo_filename = $company['logo'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['logo']['tmp_name'];
        $file_name = $_FILES['logo']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = array('jpg', 'jpeg', 'png', 'webp');

        if (in_array($file_ext, $allowed_exts)) {
            // Create uploads directory if missing
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            $new_logo_name = "company_logo_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_target = "uploads/" . $new_logo_name;

            if (move_uploaded_file($file_tmp, $upload_target)) {
                // Remove old logo if exists
                if (!empty($logo_filename) && file_exists("uploads/" . $logo_filename)) {
                    unlink("uploads/" . $logo_filename);
                }
                $logo_filename = $new_logo_name;
            } else {
                $error = "Failed to upload company logo.";
            }
        } else {
            $error = "Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.";
        }
    }

    if (empty($error)) {
        if ($company) {
            // Update Existing Company Record
            $update_sql = "UPDATE company SET 
                company_name = '$company_name',
                gst_number   = '$gst_number',
                pan_number   = '$pan_number',
                phone        = '$phone',
                email        = '$email',
                address      = '$address',
                bank_name    = '$bank_name',
                account_no   = '$account_no',
                ifsc_code    = '$ifsc_code',
                branch_name  = '$branch_name',
                logo         = '$logo_filename'
                WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $message = "Company Profile & Logo updated successfully!";
            } else {
                $error = "Error updating company record: " . mysqli_error($conn);
            }
        } else {
            // Insert New Company Record
            $insert_sql = "INSERT INTO company 
                (user_id, company_name, gst_number, pan_number, phone, email, address, bank_name, account_no, ifsc_code, branch_name, logo) 
                VALUES 
                ('$user_id', '$company_name', '$gst_number', '$pan_number', '$phone', '$email', '$address', '$bank_name', '$account_no', '$ifsc_code', '$branch_name', '$logo_filename')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $message = "Company Profile created successfully!";
            } else {
                $error = "Error inserting company record: " . mysqli_error($conn);
            }
        }

        // Refresh Company Data
        $company_query = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
        $company = mysqli_fetch_assoc($company_query);
    }
}

// Logo Path Setup
$logo_path = "";
if (!empty($company['logo']) && file_exists("uploads/" . $company['logo'])) {
    $logo_path = "uploads/" . $company['logo'];
} else {
    $logo_path = "assets/images/logo.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Company Profile & Logo</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; color: #15355f; padding: 30px 15px; }
        .container { max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 30px; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eef2f5; padding-bottom: 15px; margin-bottom: 25px; }
        .header-bar h2 { color: #15355f; font-size: 22px; }
        
        .nav-links a { text-decoration: none; padding: 8px 16px; border-radius: 5px; font-weight: 600; font-size: 14px; margin-left: 8px; display: inline-block; }
        .btn-dash { background-color: #15355f; color: #fff; }
        .btn-logout { background-color: #dc3545; color: #fff; }

        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; }

        .logo-preview-box { display: flex; align-items: center; gap: 20px; background: #fafbfc; border: 1px dashed #cca14c; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .company-logo-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #cca14c; background: #fff; }
        .logo-upload-info h4 { font-size: 15px; margin-bottom: 5px; color: #15355f; }
        .logo-upload-info p { font-size: 12px; color: #6c757d; margin-bottom: 8px; }

        .section-title { font-size: 16px; font-weight: 700; color: #cca14c; border-bottom: 1px solid #cca14c; padding-bottom: 5px; margin: 25px 0 15px 0; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .full-width { grid-column: span 2; }

        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #15355f; }
        .form-group input, .form-group textarea { padding: 10px; border: 1px solid #cccccc; border-radius: 5px; font-size: 14px; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: #15355f; }
        .form-group textarea { resize: vertical; height: 70px; }

        .btn-submit { background-color: #28a745; color: #ffffff; padding: 12px 25px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 25px; transition: background 0.2s ease; }
        .btn-submit:hover { background-color: #218838; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <h2>Company Profile & Settings</h2>
        <div class="nav-links">
            <a href="invoices.php" class="btn-dash">Back to Invoices</a>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data">
        
        <!-- Logo Upload Section -->
        <div class="logo-preview-box">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Company Logo" class="company-logo-img">
            <div class="logo-upload-info">
                <h4>Company Logo / Brand Profile</h4>
                <p>This logo will display at the top of your printed tax invoices (A4 format).</p>
                <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg, image/webp">
            </div>
        </div>

        <!-- General Details -->
        <div class="section-title">General Details</div>
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Company Name</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($company['company_name'] ?? 'ANUYOG ENGINEERING'); ?>" required>
            </div>
            <div class="form-group">
                <label>GSTIN Number</label>
                <input type="text" name="gst_number" value="<?php echo htmlspecialchars($company['gst_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>PAN Number</label>
                <input type="text" name="pan_number" value="<?php echo htmlspecialchars($company['pan_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Phone / Mobile</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
            </div>
            <div class="form-group full-width">
                <label>Address</label>
                <textarea name="address"><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Bank Details -->
        <div class="section-title">Bank Details (Printed on Invoice)</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Bank Name</label>
                <input type="text" name="bank_name" value="<?php echo htmlspecialchars($company['bank_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Account Number</label>
                <input type="text" name="account_no" value="<?php echo htmlspecialchars($company['account_no'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>IFSC Code</label>
                <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($company['ifsc_code'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Branch Name</label>
                <input type="text" name="branch_name" value="<?php echo htmlspecialchars($company['branch_name'] ?? ''); ?>">
            </div>
        </div>

        <button type="submit" class="btn-submit">Save & Update Profile</button>
    </form>
</div>

</body>
</html>