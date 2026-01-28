<?php
include "db.php";
session_start();

$message = "";

if(isset($_POST['register'])){
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if($name === "" || $email === "" || $password === ""){
        $message = "All fields are required!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "Please enter a valid email address.";
    } else {
        // check if email already exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        if($stmt){
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0){
                $message = 'An account with this email already exists.';
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);
                // insert new user with hashed password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                if($ins){
                    mysqli_stmt_bind_param($ins, 'sss', $name, $email, $hashed);
                    $ok = mysqli_stmt_execute($ins);
                    mysqli_stmt_close($ins);
                    if($ok){
                        header("Location: user_login.php");
                        exit;
                    } else {
                        $message = 'Registration failed. Please try again later.';
                    }
                } else {
                    $message = 'Registration temporarily unavailable.';
                }
            }
        } else {
            $message = 'Registration temporarily unavailable.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-box glass-card">
    <h2>User Registration</h2>

    <!-- error -->
    <?php if($message != ""): ?>
        <p style="color:#ff9b9b;"><?= $message ?></p>
    <?php endif; ?>

    <form action="" method="POST">

        <label>Name</label>
        <input type="text" name="name" placeholder="Enter full name">

        <label>Email</label>
        <input type="email" name="email" placeholder="Enter email">

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password">

        <button class="btn-primary-custom" type="submit" name="register">Register</button>

    </form>

    <p>Already have an account? <a href="user_login.php">Login here</a></p>
</div>

</body>
</html>
