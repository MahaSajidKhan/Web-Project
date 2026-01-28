<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: user_login.php'); exit;
}
require_once 'db.php';
$userId = intval($_SESSION['user_id']);

// Fetch orders for this user
$res = mysqli_query($conn, "SELECT o.id, o.total_price, IFNULL(SUM(oi.quantity),0) AS items, o.created_at, IFNULL(o.status,'pending') AS status FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = $userId GROUP BY o.id ORDER BY o.created_at DESC");
// Handle user cancel action
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id']) && $_POST['action'] === 'cancel'){
    $oid = intval($_POST['order_id']);
    $reason = trim($_POST['reason'] ?? '');
    if($oid && $oid > 0){
        // ensure cancellation_reason column exists
        $col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
        if(!$col || mysqli_num_rows($col) == 0){
            @mysqli_query($conn, "ALTER TABLE orders ADD COLUMN cancellation_reason TEXT NULL AFTER status");
        }
        // verify ownership
        $stmt = $conn->prepare("SELECT id, status, user_id FROM orders WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if($row && intval($row['user_id']) === $userId){
            // only allow cancel if not already delivered or cancelled
            $curStatus = strtolower($row['status'] ?? 'pending');
            if($curStatus === 'delivered' || $curStatus === 'cancelled'){
                $error = 'Order cannot be cancelled (already '.$curStatus.').';
            } else {
                $newStatus = 'cancelled';
                $stmt2 = $conn->prepare("UPDATE orders SET status = ?, cancellation_reason = ? WHERE id = ?");
                $stmt2->bind_param('ssi', $newStatus, $reason, $oid);
                $stmt2->execute();
                $stmt2->close();
                header('Location: track_order.php?view=' . $oid . '&msg=cancelled'); exit;
            }
        } else {
            $error = 'Order not found or you do not have permission to cancel it.';
        }
    } else {
        $error = 'Invalid order id.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container" style="margin-top:28px;">
    <div class="glass-card" style="padding:18px;">
        <h2>My Orders</h2>
        <p class="muted">Below are your recent orders. Click View to see status and details.</p>

        <div style="overflow:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="padding:10px">#</th>
                        <th style="padding:10px">Order ID</th>
                        <th style="padding:10px">Total</th>
                        <th style="padding:10px">Items</th>
                        <th style="padding:10px">Status</th>
                        <th style="padding:10px">Created</th>
                        <th style="padding:10px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($o = mysqli_fetch_assoc($res)): ?>
                    <tr>
                        <td style="padding:10px"><?php echo $i++; ?></td>
                        <td style="padding:10px"><?php echo intval($o['id']); ?></td>
                        <td style="padding:10px">PKR <?php echo number_format($o['total_price'],2); ?></td>
                        <td style="padding:10px"><?php echo intval($o['items']); ?></td>
                        <td style="padding:10px"><?php echo '<strong style="text-transform:capitalize;">'.htmlspecialchars($o['status']).'</strong>'; ?></td>
                        <td style="padding:10px"><?php echo htmlspecialchars($o['created_at']); ?></td>
                        <td style="padding:10px">
                            <a href="track_order.php?view=<?php echo intval($o['id']); ?>" class="btn-primary-custom" style="padding:8px 10px; margin-right:8px;">View</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// View single order details (only if owned by this user)
if(isset($_GET['view'])){
    $viewId = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT o.* FROM orders o WHERE o.id = ? AND o.user_id = ? LIMIT 1");
    $stmt->bind_param('ii', $viewId, $userId);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($ord){
        $itemsRes = mysqli_query($conn, "SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = " . intval($viewId));
        echo '<div class="container" style="margin-top:18px;"><div class="glass-card" style="padding:18px;">';
        echo '<h3>Order #' . intval($ord['id']) . ' — ' . htmlspecialchars($ord['name']) . '</h3>';

        echo '<table class="table" style="max-width:800px; margin-top:8px;"><tbody>';
        echo '<tr><td style="padding:10px; width:180px">Order ID</td><td style="padding:10px">' . intval($ord['id']) . '</td></tr>';
        echo '<tr><td style="padding:10px">Name</td><td style="padding:10px">' . htmlspecialchars($ord['name']) . '</td></tr>';
        echo '<tr><td style="padding:10px">Email</td><td style="padding:10px">' . htmlspecialchars($ord['email']) . '</td></tr>';
        echo '<tr><td style="padding:10px">Phone</td><td style="padding:10px">' . (!empty($ord['phone']) ? htmlspecialchars($ord['phone']) : '-') . '</td></tr>';
        echo '<tr><td style="padding:10px">Status</td><td style="padding:10px">' . (isset($ord['status'])? '<strong style="text-transform:capitalize;">'.htmlspecialchars($ord['status']).'</strong>' : '<span class="muted">pending</span>') . '</td></tr>';
        echo '<tr><td style="padding:10px">Total</td><td style="padding:10px">PKR ' . number_format(floatval($ord['total_price']),2) . '</td></tr>';
        echo '<tr><td style="padding:10px; vertical-align:top">Address</td><td style="padding:10px">' . (!empty($ord['address']) ? nl2br(htmlspecialchars($ord['address'])) : '-') . '</td></tr>';
        echo '</tbody></table>';

        echo '<div style="margin-top:12px; overflow:auto;"><table class="table"><thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
        $j=1; $calc=0.0; while($it = mysqli_fetch_assoc($itemsRes)){
            $qty = intval($it['quantity']); $price = floatval($it['price']); $sub = $qty * $price; $calc += $sub;
            echo '<tr><td style="padding:10px">' . $j++ . '</td><td style="padding:10px">' . htmlspecialchars($it['product_name']) . '</td><td style="padding:10px">' . $qty . '</td><td style="padding:10px">PKR ' . number_format($price,2) . '</td><td style="padding:10px">PKR ' . number_format($sub,2) . '</td></tr>';
        }
        echo '</tbody></table></div>';

        // show cancellation reason or cancel form
        echo '<div style="margin-top:12px;">';
        $status = strtolower($ord['status'] ?? 'pending');
        if($status === 'cancelled'){
        } elseif($status === 'delivered'){
            echo '<div style="margin-bottom:12px;"><strong>Status:</strong> <span style="color:#39b54a;font-weight:700; text-transform:capitalize;">Delivered</span></div>';
        } else {
            echo '<div style="margin-bottom:12px;"><strong>Status:</strong> <span style="color:#f0c36f;font-weight:700; text-transform:capitalize;">' . htmlspecialchars($status) . '</span></div>';
            // cancel form
            echo '<form method="POST" style="margin-top:8px; max-width:720px;">';
            echo '<input type="hidden" name="action" value="cancel">';
            echo '<input type="hidden" name="order_id" value="' . intval($ord['id']) . '">';
            echo '<label>Cancel Reason (required)</label>';
            echo '<textarea name="reason" required style="width:100%; padding:8px; margin-top:6px;" rows="3" placeholder="Please provide a reason for canceling this order."></textarea>';
                echo '<div style="margin-top:8px; display:flex; gap:8px;"><button class="action-btn btn-deactivate" type="submit" onclick="return confirm(\'Are you sure you want to cancel this order?\')">Cancel Order</button></div>';
            echo '</form>';
        }
        echo '</div>';

        echo '<p style="margin-top:12px"><a href="track_order.php" class="btn-primary-custom">Back to My Orders</a></p>';
        echo '</div></div>';
    } else {
        echo '<div class="container" style="margin-top:18px;"><div class="glass-card" style="padding:18px;">Order not found or you do not have permission to view it.</div></div>';
    }
}
?>

</body>
</html>