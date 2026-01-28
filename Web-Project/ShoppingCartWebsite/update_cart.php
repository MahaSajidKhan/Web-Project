<?php
session_start();

// Simple handler to increase/decrease quantity in the session cart
// Expects POST: id (product id), action (inc|dec)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
action:
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id || !isset($_SESSION['cart'][$id])) {
    header('Location: cart.php');
    exit;
}

if ($action === 'inc') {
    $_SESSION['cart'][$id]['quantity'] += 1;
} elseif ($action === 'dec') {
    // decrease and remove if zero or less
    $_SESSION['cart'][$id]['quantity'] -= 1;
    if ($_SESSION['cart'][$id]['quantity'] <= 0) {
        unset($_SESSION['cart'][$id]);
    }
}

// Redirect back to cart
header('Location: cart.php');
exit;
