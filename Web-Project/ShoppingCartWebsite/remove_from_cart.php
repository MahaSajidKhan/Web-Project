<?php
session_start();

$id = $_GET['id'];

unset($_SESSION['cart'][$id]);

echo "<script>
        alert('Item removed from cart');
        window.location.href='cart.php';
      </script>";
?>
