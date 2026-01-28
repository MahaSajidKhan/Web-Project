<?php
session_start();
include "db.php";

// If admin is already logged in, redirect to dashboard
if(isset($_SESSION['admin'])){
    header("Location: admin_dashboard.php");
    exit;
}

// When form is submitted
if(isset($_POST['email']) && isset($_POST['password'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // sanitize input
    $email_s = mysqli_real_escape_string($conn, $email);
    $password_s = mysqli_real_escape_string($conn, $password);

    // Determine where admin accounts are stored
    $has_admin_table = false;
    $has_is_admin_col = false;

    $res1 = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
    if($res1 && mysqli_num_rows($res1) > 0) $has_admin_table = true;

    $res2 = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_admin'");
    if($res2 && mysqli_num_rows($res2) > 0) $has_is_admin_col = true;

    $row = null;
    if($has_admin_table){
        $res = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email_s' LIMIT 1");
        if($res && mysqli_num_rows($res) > 0) $row = mysqli_fetch_assoc($res);
    } elseif($has_is_admin_col){
        $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email_s' AND is_admin=1 LIMIT 1");
        if($res && mysqli_num_rows($res) > 0) $row = mysqli_fetch_assoc($res);
    } else {
        $error = "Admin table not found and users table has no 'is_admin' column.\nPlease create an admin user or add the 'is_admin' column. Example SQL shown below.";
        $setup_sql = "-- Create a simple admin table\nCREATE TABLE admin (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), email VARCHAR(255) UNIQUE, password VARCHAR(255));\n-- OR add a flag to users table\nALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;\n-- Then mark a user as admin:\nUPDATE users SET is_admin=1 WHERE email='your-admin-email@example.com';";
    }

    if($row){
        $stored = $row['password'] ?? '';
        $auth_ok = false;

        // if stored password looks like a PHP password_hash() (bcrypt/argon2), use password_verify
        if (strlen($stored) > 0 && (strpos($stored,'$2y$') === 0 || strpos($stored,'$2a$') === 0 || strpos($stored,'$2b$') === 0 || strpos($stored,'$argon2') === 0)){
            if (password_verify($password, $stored)) $auth_ok = true;
        } else {
            // fallback: plain-text comparison (existing installations)
            if ($password === $stored) $auth_ok = true;
        }

        if($auth_ok){
            // store admin identifier in session
            $_SESSION['admin'] = $row['email'] ?? $email;
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "No admin account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-fullscreen">
    <div class="auth-panel">
        <div class="intro">
            <h2>Admin Access</h2>
            <p style="opacity:0.9; line-height:1.5">Sign in with your administrator account to manage products, categories and orders. If you don't have an admin account yet, use the Create Admin link to provision one (remember to remove it after use).</p>
            <?php if(isset($setup_sql)){ ?>
                    <div style="margin-top:12px; font-size:13px; color:var(--muted)">Quick setup SQL available below the form if needed.</div>
            <?php } ?>
        </div>

            <!-- Removed magical lamp UI -->

            <div class="form-wrap glass-card">
            <div class="form-inner">
                <h3 style="margin-top:0; margin-bottom:8px">Sign In</h3>
                <?php if(isset($error)){ ?>
                        <div style="color:#ff9b9b; margin-bottom:10px"><?php echo nl2br(htmlspecialchars($error)); ?></div>
                <?php } ?>

                <?php if(isset($setup_sql)){ ?>
                        <div style="margin:10px 0;">
                                <pre class="setup-sql" style="white-space:pre-wrap; background:rgba(0,0,0,0.12); padding:10px; border-radius:8px; color:var(--muted)"><?php echo htmlspecialchars($setup_sql); ?></pre>
                        </div>
                <?php } ?>

                <form method="POST" id="adminLoginForm">
                        <div class="field"><input type="email" name="email" placeholder="Email address" required></div>
                        <div class="field"><input type="password" name="password" placeholder="Password" required></div>
                        <div class="field"><button class="btn-primary-custom" type="submit" style="width:100%">Login</button></div>
                        <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-top:6px">
                            <span></span>
                            <a href="user_login.php" style="color:var(--muted); font-size:13px">Back to user login</a>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Magical lamp interaction removed -->

</body>
</html>
