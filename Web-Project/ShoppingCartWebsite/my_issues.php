<?php
session_start();
require_once __DIR__ . '/db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: user_login.php');
    exit;
}
$userId = intval($_SESSION['user_id']);

// Ensure issues table exists (defensive)
$create = "CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    admin_response TEXT NULL,
    notified_at DATETIME NULL,
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

// Handle user replies to an existing issue (only when not closed)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_issue_id'])){
    $reply_issue_id = intval($_POST['reply_issue_id']);
    $reply_msg = trim($_POST['reply_message'] ?? '');
    // check issue belongs to user and is not closed
    $c = mysqli_query($conn, "SELECT status FROM issues WHERE id = $reply_issue_id AND user_id = $userId LIMIT 1");
    if($c && $row = mysqli_fetch_assoc($c)){
        if($row['status'] !== 'closed' && $reply_msg !== ''){
            $mstmt = $conn->prepare("INSERT INTO issues_messages (issue_id, sender, message) VALUES (?, 'user', ?)");
            if($mstmt){ $mstmt->bind_param('is', $reply_issue_id, $reply_msg); $mstmt->execute(); $mstmt->close(); }
        }
    }
    header('Location: my_issues.php'); exit;
}

// Fetch user's issues
$issues = [];
$res = mysqli_query($conn, "SELECT i.*, o.total_price, o.created_at as order_date FROM issues i LEFT JOIN orders o ON o.id = i.order_id WHERE i.user_id = $userId ORDER BY i.created_at DESC");
if($res){ while($r = mysqli_fetch_assoc($res)) $issues[] = $r; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Issues</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
  <div class="glass-card" style="max-width:900px;margin:28px auto;padding:18px;">
    <h2>My Issues</h2>
    <p class="muted">Below are the issues you submitted and any responses from admin.</p>

    <?php if(empty($issues)): ?>
        <div class="alert alert-error">You have not submitted any issues yet. <a href="contact_user.php">Contact Admin</a> to create one.</div>
    <?php else: ?>
        <div class="related-list">
            <?php foreach($issues as $it): ?>
                <div class="related-item glass-card" style="padding:14px;">
                    <div style="flex:1">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <div style="font-weight:800;">#<?php echo intval($it['id']); ?> — <?php echo htmlspecialchars($it['subject']); ?></div>
                            <div style="margin-left:auto; color:var(--muted); font-weight:700;"><?php echo htmlspecialchars($it['status']); ?></div>
                        </div>
                        <div style="margin-top:6px; color:var(--muted); font-size:13px;">
                            Submitted: <?php echo date('Y-m-d H:i', strtotime($it['created_at'])); ?>
                            <?php if(!empty($it['order_id'])): ?>
                                &nbsp;|&nbsp; Related Order: #<?php echo intval($it['order_id']); ?> (<?php echo $it['order_date'] ? date('Y-m-d', strtotime($it['order_date'])) : 'n/a'; ?>)
                            <?php endif; ?>
                        </div>

                        <div style="margin-top:8px; white-space:pre-wrap; color:#eafaf1;"><?php echo nl2br(htmlspecialchars($it['message'])); ?></div>

                        <?php
                            // load threaded messages
                            $msgs = [];
                            $mr = mysqli_query($conn, "SELECT * FROM issues_messages WHERE issue_id = " . intval($it['id']) . " ORDER BY created_at ASC");
                            if($mr){ while($m = mysqli_fetch_assoc($mr)) $msgs[] = $m; }
                        ?>
                        <?php if(!empty($msgs)): ?>
                            <div class="chat-thread" aria-live="polite">
                                <?php foreach($msgs as $m): ?>
                                    <?php if($m['sender'] === 'user'): ?>
                                        <div class="chat-msg user"><div class="chat-bubble user">You: <?php echo nl2br(htmlspecialchars($m['message'])); ?><div class="chat-time"><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></div></div></div>
                                    <?php else: ?>
                                        <div class="chat-msg admin"><div class="chat-bubble admin">Admin: <?php echo nl2br(htmlspecialchars($m['message'])); ?><div class="chat-time"><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></div></div></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($it['status'] !== 'closed'): ?>
                            <form method="POST" style="margin-top:10px; display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="reply_issue_id" value="<?php echo intval($it['id']); ?>">
                                <textarea name="reply_message" placeholder="Reply to admin" style="flex:1; padding:8px; border-radius:8px; background:rgba(255,255,255,0.02); color:#eafaf1; border:1px solid rgba(255,255,255,0.04);"></textarea>
                                <button class="btn-primary-custom" type="submit">Send</button>
                            </form>
                        <?php else: ?>
                            <div style="margin-top:10px; color:var(--muted);">This conversation is closed by the admin.</div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
