<?php
include 'db.php';
$error = '';
$result = null;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if($order_id <= 0){
        $error = 'Please enter a valid Order ID.';
    } else {
        $sql = "SELECT o.*, IFNULL(u.email,'') AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row = $res->fetch_assoc()){
            // if user provided email, check match
            if($email !== '' && strcasecmp($email, $row['email'] ?? $row['user_email'] ?? '') !== 0){
                $error = 'Order not found for the provided email and ID.';
            } else {
                $result = $row; // includes status (if present)
            }
        } else {
            $error = 'Order not found.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Order Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container" style="margin-top:28px; max-width:720px;">
    <div class="glass-card" style="padding:18px;">
        <h2>Check Order Status</h2>
        <p class="muted">Enter your Order ID and optionally your email to see the delivery status.</p>

        <?php if($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" style="display:flex; gap:10px; flex-direction:column;">
            <label>Order ID</label>
            <input type="number" name="order_id" required>
            <label>Email (optional)</label>
            <input type="email" name="email" placeholder="Optional, helps verify ownership">
            <div style="display:flex; gap:8px; margin-top:8px;"><button class="btn-primary-custom" type="submit">Check</button><a class="btn-primary-custom" href="index.php" style="background:#777;">Home</a></div>
        </form>

        <?php if($result): ?>
            <hr style="margin:18px 0; border-color: rgba(255,255,255,0.03);">
            <table class="table" style="max-width:100%;">
                <tbody>
                    <tr><td style="padding:10px; width:160px">Order ID</td><td style="padding:10px"><?php echo intval($result['id']); ?></td></tr>
                    <tr><td style="padding:10px">Name</td><td style="padding:10px"><?php echo htmlspecialchars($result['name']); ?></td></tr>
                    <tr><td style="padding:10px">Email</td><td style="padding:10px"><?php echo htmlspecialchars($result['email']); ?></td></tr>
                    <tr><td style="padding:10px">Total</td><td style="padding:10px">PKR <?php echo number_format(floatval($result['total_price']),2); ?></td></tr>
                    <tr><td style="padding:10px">Status</td><td style="padding:10px"><?php echo isset($result['status']) ? '<strong style="text-transform:capitalize;">'.htmlspecialchars($result['status']).'</strong>' : '<span class="muted">pending</span>'; ?></td></tr>
                    <tr><td style="padding:10px">Created</td><td style="padding:10px"><?php echo !empty($result['created_at']) ? htmlspecialchars($result['created_at']) : '-'; ?></td></tr>
                    <tr><td style="padding:10px; vertical-align:top">Address</td><td style="padding:10px"><?php echo !empty($result['address']) ? nl2br(htmlspecialchars($result['address'])) : '-'; ?></td></tr>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

</body>
</html>