<?php
session_start();
if(!isset($_SESSION['admin'])){
    header('Location: admin_login.php'); exit;
}
require_once 'db.php';

// Ensure users.is_active column exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_active'");
if(!$colCheck || mysqli_num_rows($colCheck) == 0){
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER password");
}

// Handle POST actions: deactivate, activate, delete
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])){
    $action = $_POST['action'];
    $uid = intval($_POST['user_id']);
    if($uid > 0){
        if($action === 'deactivate'){
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param('i', $uid); $stmt->execute(); $stmt->close();
        } elseif($action === 'activate'){
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param('i', $uid); $stmt->execute(); $stmt->close();
        } elseif($action === 'delete'){
            // delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $uid); $stmt->execute(); $stmt->close();
        }
    }
    header('Location: manage_users.php'); exit;
}

// Handle optional search query (by email)
$qFilter = '';
if(isset($_GET['q']) && strlen(trim($_GET['q'])) > 0){
    $qRaw = trim($_GET['q']);
    $qFilter = mysqli_real_escape_string($conn, $qRaw);
}

// Fetch users list, handle missing created_at column gracefully
$hasCreated = false;
$col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'created_at'");
if($col && mysqli_num_rows($col) > 0) $hasCreated = true;

// Build base SELECT with optional WHERE for email search
$selectBase = "SELECT id, name, email, IFNULL(is_active,1) AS is_active" . ($hasCreated ? ", created_at" : "") . " FROM users";
if($qFilter !== ''){
    $selectBase .= " WHERE email LIKE '%" . $qFilter . "%'";
}
$selectBase .= ($hasCreated) ? " ORDER BY created_at DESC" : " ORDER BY id DESC";
$res = mysqli_query($conn, $selectBase);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container" style="margin-top:28px;">
    <div class="glass-card" style="padding:18px;">
        <h2>Users</h2>
        <p class="muted">Below is the list of registered users. Use the actions to deactivate/reactivate or delete users.</p>

        <div style="display:flex; gap:12px; align-items:center; margin:12px 0;">
            <form method="GET" style="display:flex; gap:8px; width:100%; max-width:520px;">
                <input type="search" name="q" placeholder="Search by email..." value="<?php echo isset($qRaw) ? htmlspecialchars($qRaw) : ''; ?>" style="flex:1 1 auto; padding:8px 10px; border-radius:8px; border:1px solid rgba(255,255,255,0.04); background:transparent; color:#eaf6f0;">
                <button class="btn-primary-custom" type="submit" style="padding:8px 12px;">Search</button>
                <a href="manage_users.php" class="btn-secondary" style="padding:8px 12px;">Clear</a>
            </form>
        </div>

        <div style="overflow:auto;">
        <table class="table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid rgba(255,255,255,0.04);">
                        <th style="padding:10px">#</th>
                        <th style="padding:10px">Name</th>
                        <th style="padding:10px">Email</th>
                        <th style="padding:10px">Status</th>
                        <th style="padding:10px">Actions</th>
                    </tr>
            </thead>
            <tbody>
                <?php $i=1; while($u = mysqli_fetch_assoc($res)): ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.02);">
                        <td style="padding:10px; vertical-align:middle"><?php echo $i++; ?></td>
                        <td style="padding:10px; vertical-align:middle"><?php echo htmlspecialchars($u['name']); ?></td>
                        <td style="padding:10px; vertical-align:middle"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="padding:10px; vertical-align:middle"><?php echo ($u['is_active'] ? '<span style="color:var(--pinkish-purple); font-weight:700">Active</span>' : '<span style="color:#ff9b9b; font-weight:700">Deactivated</span>'); ?></td>
                        <td style="padding:10px; vertical-align:middle">
                            <form method="POST" style="display:inline-block; margin-right:6px;">
                                <input type="hidden" name="user_id" value="<?php echo intval($u['id']); ?>">
                                <?php if($u['is_active']): ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <button class="action-btn btn-deactivate" type="submit" onclick="return confirm('Deactivate this user? They will not be able to login.')">Deactivate</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button class="action-btn btn-activate" type="submit">Activate</button>
                                <?php endif; ?>
                            </form>

                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo intval($u['id']); ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="action-btn btn-delete" type="submit" onclick="return confirm('Permanently delete this user? This cannot be undone.')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</body>
</html>
