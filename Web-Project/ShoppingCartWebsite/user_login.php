<?php
include "db.php";
session_start();

$error = "";

if(isset($_POST['login'])){
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // basic input check
    if($email === '' || $password === ''){
        $error = 'Please enter email and password.';
    } else {
        // sanitize
        $email_s = mysqli_real_escape_string($conn, $email);

        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email_s' LIMIT 1");
        if($query && mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);
            // check for is_active column if present
            $is_active = 1;
            if(array_key_exists('is_active', $row)){
                $is_active = intval($row['is_active']);
            } else {
                // defensive: check DB schema
                $col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_active'");
                if($col && mysqli_num_rows($col) > 0){
                    // re-fetch single value
                    $row2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM users WHERE id = " . intval($row['id']) . " LIMIT 1"));
                    $is_active = intval($row2['is_active'] ?? 1);
                }
            }

            if(!$is_active){
                $error = 'Your account has been deactivated. Contact the site administrator.';
            } else {
                $stored = $row['password'] ?? '';
            $auth_ok = false;

            // if stored looks like a PHP hash, use password_verify, otherwise fallback to plain-text compare
            if(strlen($stored) > 0 && (strpos($stored,'$2y$') === 0 || strpos($stored,'$2a$') === 0 || strpos($stored,'$2b$') === 0 || strpos($stored,'$argon2') === 0)){
                if(password_verify($password, $stored)) $auth_ok = true;
            } else {
                if($password === $stored) $auth_ok = true;
            }

                if($auth_ok){
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Incorrect password!";
                }
            }
        } else {
            $error = "No user found!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-box glass-card">
    <h2>User Login</h2>

    <?php if($error != "") echo "<p style='color:#ff7b7b;'>$error</p>"; ?>

    <form method="POST">
        <div class="field"><input type="email" name="email" placeholder="Enter Email" required></div>
        <div class="field"><input type="password" name="password" placeholder="Enter Password" required></div>
        <button class="btn-primary-custom" type="submit" name="login">Login</button>
    </form>

    <p style="margin-top:12px;">Don't have an account? <a href="register.php">Register Now</a></p>
</div>

</body>
</html>
