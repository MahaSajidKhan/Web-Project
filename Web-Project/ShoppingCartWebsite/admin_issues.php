<?php
session_start();
require_once __DIR__ . '/db.php';

// Admin only
if(!isset($_SESSION['admin'])){
    header('Location: admin_login.php');
    exit;
}

// Ensure table exists (defensive)
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

// Handle admin actions: respond / mark resolved
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['action']) && isset($_POST['issue_id'])){
        $issue_id = intval($_POST['issue_id']);
        if($_POST['action'] === 'reply' || $_POST['action'] === 'respond'){
            $response = trim($_POST['response'] ?? '');

            // ensure notified_at column exists so we can record when user was notified
            $col = mysqli_query($conn, "SHOW COLUMNS FROM issues LIKE 'notified_at'");
            if(!$col || mysqli_num_rows($col) == 0){
                @mysqli_query($conn, "ALTER TABLE issues ADD COLUMN notified_at DATETIME NULL AFTER admin_response");
            }

            // insert admin message into conversation (do not auto-close)
            $mstmt = $conn->prepare("INSERT INTO issues_messages (issue_id, sender, message) VALUES (?, 'admin', ?)");
            if($mstmt){
                $mstmt->bind_param('is', $issue_id, $response);
                $mstmt->execute();
                $mstmt->close();
            }

            // Fetch user's email and issue subject to send notification
            $q = mysqli_query($conn, "SELECT i.subject, u.email, u.name FROM issues i LEFT JOIN users u ON u.id = i.user_id WHERE i.id = " . intval($issue_id) . " LIMIT 1");
            if($q && $row = mysqli_fetch_assoc($q)){
                $userEmail = $row['email'] ?? null;
                $userName = $row['name'] ?? '';
                $issueSubject = $row['subject'] ?? 'Your issue';
                if(!empty($userEmail)){
                    // Prepare email (simple plain-text notification)
                    $to = $userEmail;
                    $mailSub = "Response to your issue #" . intval($issue_id) . ": " . $issueSubject;
                    $body = "Hello " . ($userName ? $userName : 'Customer') . ",\n\n" .
                            "Our admin has sent a response to your issue (ID: " . intval($issue_id) . ").\n\n" .
                            "Response:\n" . $response . "\n\n" .
                            "You can view this message and the original issue on your account. If you need further assistance, reply via the Contact Admin page.\n\n" .
                            "Regards,\nSite Admin";
                    $headers = "From: no-reply@localhost\r\n" .
                               "Reply-To: no-reply@localhost\r\n" .
                               "Content-Type: text/plain; charset=UTF-8\r\n";
                    // best-effort send; on local dev mail may be disabled
                    @mail($to, $mailSub, $body, $headers);

                    // mark notified_at
                    $st2 = $conn->prepare("UPDATE issues SET notified_at = NOW() WHERE id = ?");
                    if($st2){ $st2->bind_param('i', $issue_id); $st2->execute(); $st2->close(); }
                }
            }
        } elseif($_POST['action'] === 'close'){
            $stmt = $conn->prepare("UPDATE issues SET status = 'closed' WHERE id = ?");
            if($stmt){ $stmt->bind_param('i', $issue_id); $stmt->execute(); $stmt->close(); }
        } elseif($_POST['action'] === 'reopen'){
            $stmt = $conn->prepare("UPDATE issues SET status = 'open' WHERE id = ?");
            if($stmt){ $stmt->bind_param('i', $issue_id); $stmt->execute(); $stmt->close(); }
        }
    }
}

// Fetch issues
$issues = [];
$res = mysqli_query($conn, "SELECT i.*, u.name as user_name, u.email as user_email FROM issues i LEFT JOIN users u ON u.id = i.user_id ORDER BY i.created_at DESC");
if($res){ while($r = mysqli_fetch_assoc($res)) $issues[] = $r; }

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Issues</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container admin-dashboard">
    <div class="glass-card">
        <h2>User Issues / Complaints</h2>
        <p class="muted">Review issues submitted by users. You can reply and mark as resolved.</p>

        <?php if(empty($issues)): ?>
            <p class="muted">No issues yet.</p>
        <?php else: ?>
            <div class="related-list">
                <?php foreach($issues as $it): ?>
                    <div class="related-item glass-card" style="padding:14px;">
                        <div style="flex:1">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div style="font-weight:800;">#<?php echo intval($it['id']); ?> — <?php echo htmlspecialchars($it['subject']); ?></div>
                                <div style="margin-left:auto; color:var(--muted); font-weight:700;"><?php echo htmlspecialchars($it['status']); ?></div>
                            </div>
                            <div style="margin-top:8px; color:var(--muted);">From: <?php echo htmlspecialchars($it['user_name'] ?? 'User'); ?> &lt;<?php echo htmlspecialchars($it['user_email'] ?? ''); ?>&gt;</div>
                            <?php if(!empty($it['order_id'])): ?>
                                <div style="margin-top:6px;"><a href="track_order.php?view=<?php echo intval($it['order_id']); ?>" class="btn-primary-custom">View Order #<?php echo intval($it['order_id']); ?></a></div>
                            <?php endif; ?>

                            <div style="margin-top:8px; white-space:pre-wrap; color:#eafaf1;"><?php echo nl2br(htmlspecialchars($it['message'])); ?></div>

                                            <!-- Render threaded messages -->
                                            <?php
                                                $msgs = [];
                                                $mr = mysqli_query($conn, "SELECT * FROM issues_messages WHERE issue_id = " . intval($it['id']) . " ORDER BY created_at ASC");
                                                if($mr){ while($m = mysqli_fetch_assoc($mr)) $msgs[] = $m; }
                                            ?>
                                            <?php if(!empty($msgs)): ?>
                                                <div class="chat-thread" aria-live="polite">
                                                    <?php foreach($msgs as $m): ?>
                                                        <?php if($m['sender'] === 'user'): ?>
                                                            <div class="chat-msg user"><div class="chat-bubble user"><?php echo nl2br(htmlspecialchars($m['message'])); ?><div class="chat-time"><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></div></div></div>
                                                        <?php else: ?>
                                                            <div class="chat-msg admin"><div class="chat-bubble admin"><?php echo nl2br(htmlspecialchars($m['message'])); ?><div class="chat-time"><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></div></div></div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
                                                <form method="POST" style="display:flex; gap:8px; width:100%;">
                                                    <input type="hidden" name="issue_id" value="<?php echo intval($it['id']); ?>">
                                                    <textarea name="response" placeholder="Write a response to the user" style="flex:1; padding:8px; border-radius:8px; background:rgba(255,255,255,0.02); color:#eafaf1; border:1px solid rgba(255,255,255,0.04);"></textarea>
                                                    <button class="btn-primary-custom" name="action" value="reply">Reply</button>
                                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="issue_id" value="<?php echo intval($it['id']); ?>">
                                    <?php if($it['status'] !== 'closed'): ?>
                                        <button class="btn-cancel" name="action" value="close">Close</button>
                                    <?php else: ?>
                                        <button class="btn-primary-custom" name="action" value="reopen">Reopen</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
