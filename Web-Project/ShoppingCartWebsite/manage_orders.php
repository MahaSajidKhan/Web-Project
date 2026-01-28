<?php
session_start();
if(!isset($_SESSION['admin'])){ header('Location: admin_login.php'); exit; }
require_once 'db.php';

// Handle delete action
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])){
    $action = $_POST['action'];
    $oid = intval($_POST['order_id']);
    if($action === 'delete' && $oid > 0){
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();
    } elseif(($action === 'set_status' || $action === 'mark_delivered' || $action === 'mark_pending') && $oid > 0){
        // ensure status column exists
        $col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
        if(!$col || mysqli_num_rows($col) == 0){
            @mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER total_price");
        }
        if($action === 'set_status' && isset($_POST['status'])){
            $new = mysqli_real_escape_string($conn, $_POST['status']);
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $new, $oid); $stmt->execute(); $stmt->close();
        } elseif($action === 'mark_delivered'){
            $s = 'delivered'; $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?"); $stmt->bind_param('si', $s, $oid); $stmt->execute(); $stmt->close();
        } elseif($action === 'mark_pending'){
            $s = 'pending'; $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?"); $stmt->bind_param('si', $s, $oid); $stmt->execute(); $stmt->close();
        }
    }
    header('Location: manage_orders.php'); exit;
}

// Fetch orders with aggregate item counts
// ensure status column exists for select (non-destructive check)
$col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
$hasStatus = ($col && mysqli_num_rows($col) > 0);

// check for payment_method column
$colPay = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_method'");
$hasPayment = ($colPay && mysqli_num_rows($colPay) > 0);

// check for cancellation_reason column so admin can view user cancellation reasons
$colCancel = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
$hasCancellation = ($colCancel && mysqli_num_rows($colCancel) > 0);

$selectCols = "o.id, o.name, o.email, o.total_price, o.created_at, IFNULL(SUM(oi.quantity),0) AS items";
if($hasPayment) $selectCols .= ", o.payment_method";
if($hasStatus) $selectCols .= ", o.status";
if($hasCancellation) $selectCols .= ", o.cancellation_reason";

$q = "SELECT " . $selectCols . "\n      FROM orders o\n      LEFT JOIN order_items oi ON oi.order_id = o.id\n      GROUP BY o.id";

// choose ordering depending on existence of created_at
$createdCol = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
$orderBy = ($createdCol && mysqli_num_rows($createdCol) > 0) ? 'o.created_at DESC' : 'o.id DESC';
$q .= "\n      ORDER BY " . $orderBy;
$res = mysqli_query($conn, $q);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container" style="margin-top:28px;">
    <div class="glass-card" style="padding:18px;">
        <h2>Orders</h2>
        <p class="muted">List of orders. Click View to inspect items or Delete to remove an order.</p>

        <div style="overflow:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="padding:10px">#</th>
                        <th style="padding:10px">Customer</th>
                        <th style="padding:10px">Email</th>
                            <th style="padding:10px">Total</th>
                            <th style="padding:10px">Items</th>
                            <th style="padding:10px">Payment</th>
                            <th style="padding:10px">Status</th>
                            <th style="padding:10px">Created</th>
                        <th style="padding:10px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($o = mysqli_fetch_assoc($res)): ?>
                    <tr>
                        <td style="padding:10px"><?php echo $i++; ?></td>
                        <td style="padding:10px"><?php echo htmlspecialchars($o['name']); ?></td>
                        <td style="padding:10px"><?php echo htmlspecialchars($o['email']); ?></td>
                        <td style="padding:10px">PKR <?php echo number_format($o['total_price'],2); ?></td>
                        <td style="padding:10px"><?php echo intval($o['items']); ?></td>
                        <td style="padding:10px"><?php echo ($hasPayment && isset($o['payment_method'])) ? htmlspecialchars((strtolower($o['payment_method']) === 'cod') ? 'Cash on Delivery' : 'Card') : '<span class="muted">-</span>'; ?></td>
                        <td style="padding:10px"><?php echo (isset($o['status'])? '<strong style="text-transform:capitalize;">'.htmlspecialchars($o['status']).'</strong>' : '<span class="muted">pending</span>'); ?></td>
                        <td style="padding:10px"><?php echo htmlspecialchars($o['created_at']); ?></td>
                        <td style="padding:10px; white-space:nowrap;">
                            <div style="display:inline-flex; gap:8px; align-items:center; flex-wrap:nowrap;">
                                <a href="manage_orders.php?view=<?php echo intval($o['id']); ?>" class="btn-primary-custom" style="padding:8px 10px;">View</a>
                                <?php $st = isset($o['status']) ? strtolower($o['status']) : 'pending'; ?>
                                <?php if($st !== 'cancelled'): ?>
                                    <?php if($st !== 'delivered'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="order_id" value="<?php echo intval($o['id']); ?>">
                                            <input type="hidden" name="action" value="mark_delivered">
                                            <button class="action-btn btn-activate" type="submit">Mark Delivered</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="order_id" value="<?php echo intval($o['id']); ?>">
                                            <input type="hidden" name="action" value="mark_pending">
                                            <button class="action-btn btn-deactivate" type="submit">Mark Pending</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="order_id" value="<?php echo intval($o['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="action-btn btn-delete" type="submit" onclick="return confirm('Permanently delete this order?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// If viewing a single order, show details below
if(isset($_GET['view'])){
    $viewId = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT o.*, u.email AS user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1");
    $stmt->bind_param('i', $viewId);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

        if($ord){
            // ensure we have a payment_method field available
            $ord_payment = isset($ord['payment_method']) ? $ord['payment_method'] : null;
        $itemsRes = mysqli_query($conn, "SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = " . intval($viewId));
        echo '<div class="container" style="margin-top:18px;"><div class="glass-card" style="padding:18px;">';
        echo '<h3>Order #' . intval($ord['id']) . ' — ' . htmlspecialchars($ord['name']) . '</h3>';

        // Order meta table
        echo '<div style="margin-top:8px; overflow:auto;">';
        echo '<table class="table" style="max-width:800px;">';
        echo '<thead><tr><th style="width:200px">Field</th><th>Value</th></tr></thead><tbody>';
        echo '<tr><td style="padding:10px">User ID</td><td style="padding:10px">' . (!empty($ord['user_id']) ? intval($ord['user_id']) : '-') . '</td></tr>';
        echo '<tr><td style="padding:10px">Email</td><td style="padding:10px">' . htmlspecialchars($ord['email'] ?? $ord['user_email'] ?? '-') . '</td></tr>';
        echo '<tr><td style="padding:10px">Phone</td><td style="padding:10px">' . (!empty($ord['phone']) ? htmlspecialchars($ord['phone']) : '-') . '</td></tr>';
        echo '<tr><td style="padding:10px">Created</td><td style="padding:10px">' . (!empty($ord['created_at']) ? htmlspecialchars($ord['created_at']) : '-') . '</td></tr>';
        $payLabel = '-';
        if(!empty($ord_payment)){
            $payLabel = (strtolower($ord_payment) === 'cod') ? 'Cash on Delivery' : 'Card';
        }
        echo '<tr><td style="padding:10px">Payment</td><td style="padding:10px">' . htmlspecialchars($payLabel) . '</td></tr>';
        echo '<tr><td style="padding:10px; vertical-align:top">Address</td><td style="padding:10px">' . (!empty($ord['address']) ? nl2br(htmlspecialchars($ord['address'])) : '-') . '</td></tr>';
            // Show cancellation reason if available
            if(!empty($ord['cancellation_reason'])){
                echo '<tr><td style="padding:10px">Cancellation reason</td><td style="padding:10px; color:var(--muted);">' . nl2br(htmlspecialchars($ord['cancellation_reason'])) . '</td></tr>';
            }
        echo '</tbody></table></div>';

        // Items table with subtotals
        echo '<div style="margin-top:16px; overflow:auto;"><table class="table"><thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>';
        $j = 1; $calcTotal = 0.0;
        while($it = mysqli_fetch_assoc($itemsRes)){
            $qty = intval($it['quantity']);
            $price = floatval($it['price']);
            $subtotal = $qty * $price;
            $calcTotal += $subtotal;
            echo '<tr>';
            echo '<td style="padding:10px">' . $j++ . '</td>';
            echo '<td style="padding:10px">' . htmlspecialchars($it['product_name'] ?? 'Product #' . intval($it['product_id'])) . '</td>';
            echo '<td style="padding:10px">' . $qty . '</td>';
            echo '<td style="padding:10px">PKR ' . number_format($price,2) . '</td>';
            echo '<td style="padding:10px">PKR ' . number_format($subtotal,2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';

        // Totals table
        echo '<div style="margin-top:12px; max-width:420px;">';
        echo '<table class="table"><tbody>';
        echo '<tr><td style="padding:10px">Calculated total</td><td style="padding:10px">PKR ' . number_format($calcTotal,2) . '</td></tr>';
        echo '<tr><td style="padding:10px">Order total</td><td style="padding:10px">PKR ' . number_format(floatval($ord['total_price']),2) . '</td></tr>';
        echo '</tbody></table>';
        echo '</div>';

        echo '<p style="margin-top:12px;"><a href="manage_orders.php" class="btn-primary-custom">Back to Orders</a></p>';
        echo '</div></div>';
    } else {
        echo '<div class="container" style="margin-top:18px;"><div class="glass-card" style="padding:18px;">Order not found.</div></div>';
    }
}
?>

</body>
</html>