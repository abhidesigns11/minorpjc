<?php
session_start();

// Simple XAMPP Connection
$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$message = "";
$mode = isset($_GET['mode']) && $_GET['mode'] == 'login' ? 'login' : 'signup';

// Google Login Simulation
if (isset($_GET['google_login'])) {
    $google_email = "user.google@gmail.com";
    
    $check_google = mysqli_query($conn, "SELECT * FROM users WHERE email='$google_email'");
    if (mysqli_num_rows($check_google) > 0) {
        $row = mysqli_fetch_assoc($check_google);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['email'] = $row['email'];
        
        $company_check = mysqli_query($conn, "SELECT * FROM company WHERE user_id='" . $row['id'] . "'");
        if (mysqli_num_rows($company_check) > 0) {
            header("Location: dashboard.php");
        } else {
            header("Location: setup.php");
        }
        exit();
    } else {
        mysqli_query($conn, "INSERT INTO users (email, password) VALUES ('$google_email', 'google_auth')");
        $new_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $new_id;
        $_SESSION['email'] = $google_email;
        header("Location: setup.php");
        exit();
    }
}

// Form Submission
if (isset($_POST['submit_auth'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $action_mode = $_POST['action_mode'];

    if ($action_mode == 'login') {
        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check_user) > 0) {
            $row = mysqli_fetch_assoc($check_user);
            if ($row['password'] == $password) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];

                $company_check = mysqli_query($conn, "SELECT * FROM company WHERE user_id='" . $row['id'] . "'");
                if (mysqli_num_rows($company_check) > 0) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: setup.php");
                }
                exit();
            } else {
                $message = "Incorrect password!";
            }
        } else {
            $message = "No account found with this email. Please Sign Up!";
        }
    } else {
        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check_user) > 0) {
            $message = "Account already exists! Please Login instead.";
        } else {
            $create_query = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
            if (mysqli_query($conn, $create_query)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $new_id;
                $_SESSION['email'] = $email;
                header("Location: setup.php");
                exit();
            } else {
                $message = "Error creating account!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice System - Auth</title>
    <!-- Linked to assets/css/index.css -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <div class="auth-card">
        <h2>Invoice Billing</h2>
        <p class="subtitle"><?php echo $mode == 'login' ? 'Sign in to continue' : 'Create your account'; ?></p>

        <?php if ($message != ""): ?>
            <div class="error-msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Google Direct Login -->
        <a href="index.php?google_login=1" class="google-btn">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            Continue with Google
        </a>

        <div class="divider">OR</div>

        <!-- Form -->
        <form method="POST" action="index.php">
            <input type="hidden" name="action_mode" value="<?php echo $mode; ?>">

            <label>Email Address:</label>
            <input type="email" name="email" placeholder="name@company.com" required>

            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit" name="submit_auth" class="btn-submit">
                <?php echo $mode == 'login' ? 'Login' : 'Create Account'; ?>
            </button>
        </form>

        <div class="toggle-text">
            <?php if ($mode == 'login'): ?>
                Don't have an account? <a href="index.php?mode=signup">Sign Up</a>
            <?php else: ?>
                Already have an account? <a href="index.php?mode=login">Log In</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>