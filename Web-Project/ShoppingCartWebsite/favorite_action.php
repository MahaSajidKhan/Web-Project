<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    // require login
    header('Location: user_login.php'); exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])){
    header('Location: index.php'); exit;
}

$product_id = intval($_POST['product_id']);
$user_id = intval($_SESSION['user_id']);

if($product_id <= 0){
    header('Location: index.php'); exit;
}

// ensure favorites table exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS favorites (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_user_product (user_id, product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// check if exists
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($row){
    // remove favorite
    $del = $conn->prepare("DELETE FROM favorites WHERE id = ?");
    $del->bind_param('i', $row['id']);
    $del->execute();
    $del->close();
} else {
    // add favorite
    $ins = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
    $ins->bind_param('ii', $user_id, $product_id);
    $ins->execute();
    $ins->close();
}

// redirect back to referrer or product page
$back = $_SERVER['HTTP_REFERER'] ?? ('product.php?id=' . $product_id);
header('Location: ' . $back);
exit;

?>
