<?php
session_start();
require_once __DIR__ . '/db.php';

// Only logged-in users
if(!isset($_SESSION['user_id'])){
    header('Location: user_login.php');
    exit;
}
$userId = intval($_SESSION['user_id']);

// Ensure issues table exists
$create = "CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    admin_response TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $create);

// Ensure messages table exists for threaded conversation
$createMsg = "CREATE TABLE IF NOT EXISTS issues_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    sender ENUM('user','admin') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createMsg);

// Check whether this user has any orders
$hasOrder = false;
$q = mysqli_query($conn, "SELECT id FROM orders WHERE user_id = $userId LIMIT 1");
if($q && mysqli_num_rows($q) > 0) $hasOrder = true;

$errors = [];
$success = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!$hasOrder){
        $errors[] = 'You must have placed an order before contacting admin.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if($subject === '' || $message === ''){
            $errors[] = 'Subject and message are required.';
        }

        // require related order when user has orders
        if($hasOrder && !$order_id){
            $errors[] = 'Please select a related order (required).';
        } else {
            if($order_id){
                $stmt = $conn->prepare("INSERT INTO issues (user_id, order_id, subject, message) VALUES (?, ?, ?, ?)");
                if(!$stmt){
                    $errors[] = 'Database error: ' . mysqli_error($conn);
                } else {
                    $stmt->bind_param('iiss', $userId, $order_id, $subject, $message);
                    if($stmt->execute()){
                        $issue_id = $stmt->insert_id;
                        // insert initial message into messages table
                        $mstmt = $conn->prepare("INSERT INTO issues_messages (issue_id, sender, message) VALUES (?, 'user', ?)");
                        if($mstmt){ $mstmt->bind_param('is', $issue_id, $message); $mstmt->execute(); $mstmt->close(); }
                        $success = 'Your message has been sent to the admin. We will review it shortly.';
                        header('Location: my_issues.php'); exit;
                    } else {
                        $errors[] = 'Failed to submit: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO issues (user_id, subject, message) VALUES (?, ?, ?)");
                if(!$stmt){
                    $errors[] = 'Database error: ' . mysqli_error($conn);
                } else {
                    $stmt->bind_param('iss', $userId, $subject, $message);
                    if($stmt->execute()){
                        $issue_id = $stmt->insert_id;
                        $mstmt = $conn->prepare("INSERT INTO issues_messages (issue_id, sender, message) VALUES (?, 'user', ?)");
                        if($mstmt){ $mstmt->bind_param('is', $issue_id, $message); $mstmt->execute(); $mstmt->close(); }
                        $success = 'Your message has been sent to the admin. We will review it shortly.';
                        header('Location: my_issues.php'); exit;
                    } else {
                        $errors[] = 'Failed to submit: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Fetch user's recent orders for optional linking
$orders = [];
$res = mysqli_query($conn, "SELECT id, created_at, total_price FROM orders WHERE user_id = $userId ORDER BY created_at DESC LIMIT 20");
if($res){
    while($r = mysqli_fetch_assoc($res)) $orders[] = $r;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
  <div class="glass-card" style="max-width:820px;margin:28px auto;padding:18px;">
    <h2>Contact Admin</h2>
    <p class="muted">Only users who have placed an order can submit complaints. Choose the related order if applicable.</p>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if($errors): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <?php if(!$hasOrder): ?>
        <div class="alert alert-error">You haven't placed any orders yet. Place an order before contacting the admin.</div>
    <?php else: ?>
        <form method="POST" class="admin-form">
            <div class="field">
                <label>Subject</label>
                <input type="text" name="subject" required maxlength="255">
            </div>

            <div class="field">
                <label>Related Order (required)</label>
                <select name="order_id" required>
                    <?php foreach($orders as $o): ?>
                        <option value="<?php echo intval($o['id']); ?>">Order #<?php echo intval($o['id']); ?> — <?php echo date('Y-m-d', strtotime($o['created_at'])); ?> — RS <?php echo number_format($o['total_price'],2); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Message</label>
                <textarea name="message" rows="6" required></textarea>
            </div>

            <div class="form-actions">
                <button class="btn-primary-custom" type="submit">Send to Admin</button>
                <a class="btn-cancel" href="track_order.php">Back to My Orders</a>
            </div>
        </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
