<?php
include "db.php";
session_start();

$message = "";

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $message = "Email already exists!";
    } else {
        $query = "INSERT INTO users (name, email, password) VALUES ('$name','$email','$password')";
        mysqli_query($conn, $query);
        $message = "Registration successful! <a href='user_login.php'>Login Now</a>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
</head>
<body>
<h2>User Signup</h2>

<p style="color:red;"><?php echo $message; ?></p>

<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="register">Register</button>
</form>

</body>
</html>
