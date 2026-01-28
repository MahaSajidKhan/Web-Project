<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Help - Mahazon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container glass-card" style="margin-top:20px;">
    <h2>Help & Support</h2>
    <p>If you need help using the site or have questions, check the topics below or contact our support team.</p>

    <h3>Common Questions</h3>
    <ul>
        <li>How to place an order?</li>
        <li>How to track my order?</li>
        <li>How to return or exchange an item?</li>
    </ul>

    <h3>Contact</h3>
    <p>Email: support@example.com</p>
    <p>Phone: +92 300 0000000</p>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Mahazon — All Rights Reserved.
</footer>

</body>
</html>