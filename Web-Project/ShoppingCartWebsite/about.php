<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>About Us - Mahazon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container glass-card" style="margin-top:20px;">
    <h2>About Us</h2>
    <p>Welcome to Mahazon. We build user-friendly shopping experiences.</p>

    <h3>Our Mission</h3>
    <p>To provide an intuitive, fast, and secure shopping platform for our customers.</p>

    <h3>Our Team</h3>
    <p>Small team of passionate developers and designers.</p>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Mahazon — All Rights Reserved.
</footer>

</body>
</html>