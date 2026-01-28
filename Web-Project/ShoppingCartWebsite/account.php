<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// If user not logged in, redirect to login
if (empty($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

$uid = intval($_SESSION['user_id']);
// fetch user record up-front so we can verify password if needed
$userQ = mysqli_prepare($conn, "SELECT id, name, email, password FROM users WHERE id = ? LIMIT 1");
$user = null;
if($userQ){
    mysqli_stmt_bind_param($userQ, 'i', $uid);
    mysqli_stmt_execute($userQ);
    $res = mysqli_stmt_get_result($userQ);
    $user = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($userQ);
}

// Prepare random-word challenge stored in session (three simple words)
if (empty($_SESSION['delete_challenge'])) {
    $words = ['orchid','tiger','maple','cobalt','saffron','nebula','ember','quartz','sage','lunar','harbor','pioneer','citrus','voyage','willow','marble','zenith','aurora','cascade','breeze'];
    shuffle($words);
    $_SESSION['delete_challenge'] = implode(' ', array_slice($words, 0, 3));
}

$error = '';
// Handle account deletion request (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $token = $_POST['csrf'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_delete'] ?? '', $token)) {
        $error = 'Invalid request token. Please try again.';
    } else {
        // Require explicit consent to remove orders: deletion is only allowed when user checks the box
        $deleteData = isset($_POST['delete_data']) && $_POST['delete_data'] == '1';
        if (!$deleteData) {
            $error = 'You must check "Also delete my orders and order items" to permanently delete your account.';
        } else {
            $method = $_POST['method'] ?? 'challenge';
            if ($method === 'password') {
                // Password confirmation flow
                $pw = $_POST['confirm_password'] ?? '';
                $stored = $user['password'] ?? '';
                $auth_ok = false;
                if (strlen($stored) > 0 && (strpos($stored,'$2y$') === 0 || strpos($stored,'$2a$') === 0 || strpos($stored,'$2b$') === 0 || strpos($stored,'$argon2') === 0)) {
                    if (password_verify($pw, $stored)) $auth_ok = true;
                } else {
                    if ($pw === $stored) $auth_ok = true;
                }

                if (!$auth_ok) {
                    $error = 'Password confirmation failed. Please enter your password correctly.';
                } else {
                    // delete orders and user inside a transaction
                    mysqli_begin_transaction($conn);
                    $errLocal = '';
                    $delOrders = mysqli_prepare($conn, "DELETE FROM orders WHERE user_id = ?");
                    if ($delOrders) {
                        mysqli_stmt_bind_param($delOrders, 'i', $uid);
                        mysqli_stmt_execute($delOrders);
                        mysqli_stmt_close($delOrders);
                    } else {
                        $errLocal = 'Failed to prepare orders deletion.';
                    }

                    if ($errLocal === '') {
                        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? LIMIT 1");
                        if ($del) {
                            mysqli_stmt_bind_param($del, 'i', $uid);
                            mysqli_stmt_execute($del);
                            $affected = mysqli_stmt_affected_rows($del);
                            mysqli_stmt_close($del);
                            if ($affected > 0) {
                                mysqli_commit($conn);
                                unset($_SESSION['delete_challenge']);
                                session_unset(); session_destroy();
                                header('Location: index.php?account_deleted=1'); exit;
                            } else {
                                mysqli_rollback($conn);
                                $error = 'Account not found or could not be deleted.';
                            }
                        } else {
                            mysqli_rollback($conn);
                            $error = 'Unable to process deletion at this time.';
                        }
                    } else {
                        mysqli_rollback($conn);
                        $error = $errLocal;
                    }
                }
            } else {
                // Challenge flow
                $confirm = trim($_POST['confirm_delete'] ?? '');
                $typed = trim($_POST['confirm_words'] ?? '');
                $challenge = $_SESSION['delete_challenge'] ?? '';
                if ($confirm !== 'DELETE') {
                    $error = 'Please type DELETE (in uppercase) in the confirmation field.';
                } elseif ($typed !== $challenge) {
                    $error = 'The words you typed did not match the challenge. Please try again.';
                } else {
                    mysqli_begin_transaction($conn);
                    $errLocal = '';
                    $delOrders = mysqli_prepare($conn, "DELETE FROM orders WHERE user_id = ?");
                    if ($delOrders) {
                        mysqli_stmt_bind_param($delOrders, 'i', $uid);
                        mysqli_stmt_execute($delOrders);
                        mysqli_stmt_close($delOrders);
                    } else {
                        $errLocal = 'Failed to prepare orders deletion.';
                    }

                    if ($errLocal === '') {
                        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? LIMIT 1");
                        if ($del) {
                            mysqli_stmt_bind_param($del, 'i', $uid);
                            mysqli_stmt_execute($del);
                            $affected = mysqli_stmt_affected_rows($del);
                            mysqli_stmt_close($del);
                            if ($affected > 0) {
                                unset($_SESSION['delete_challenge']);
                                mysqli_commit($conn);
                                session_unset(); session_destroy();
                                header('Location: index.php?account_deleted=1'); exit;
                            } else {
                                mysqli_rollback($conn);
                                $error = 'Account not found or could not be deleted.';
                            }
                        } else {
                            mysqli_rollback($conn);
                            $error = 'Unable to process deletion at this time.';
                        }
                    } else {
                        mysqli_rollback($conn);
                        $error = $errLocal;
                    }
                }
            }
        }
    }
}

// Ensure CSRF token for delete form
if (empty($_SESSION['csrf_delete'])) {
    try { $_SESSION['csrf_delete'] = bin2hex(random_bytes(16)); } catch(Exception $e) { $_SESSION['csrf_delete'] = bin2hex(openssl_random_pseudo_bytes(16)); }
}

$userQ = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
if($userQ){
    mysqli_stmt_bind_param($userQ, 'i', $uid);
    mysqli_stmt_execute($userQ);
    $res = mysqli_stmt_get_result($userQ);
    $user = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($userQ);
} else {
    $user = null;
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Account</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container" style="padding:28px 0;">
    <div class="glass-card" style="max-width:720px; margin:0 auto;">
        <h2>My Account</h2>
        <?php if($user): ?>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><a href="favorites.php" class="btn-primary-custom">My Favorites</a> <a href="track_order.php" class="btn-primary-custom">My Orders</a></p>
            <hr style="margin:18px 0; border:none; height:1px; background:rgba(255,255,255,0.04)">
            <h3 style="color:#ffb5b5; margin-top:6px">Danger Zone</h3>
            <p class="muted">Permanently delete your account and all personal data stored here. This action is irreversible.</p>
            <?php if(!empty($error)): ?>
                <div class="alert alert-error" style="padding:10px; margin:8px 0; border-radius:8px; background:linear-gradient(90deg,#ffecec,#ffd9d9); color:#6b1b1b;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('This will permanently delete your account. Are you sure?');" style="margin-top:8px; max-width:620px;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_delete']); ?>">
                <input type="hidden" name="action" value="delete_account">

                <div style="display:flex; gap:12px; align-items:center; margin-bottom:10px;">
                    <label style="margin:0; font-weight:700;">Confirmation method:</label>
                    <label style="margin:0;"><input type="radio" name="method" value="challenge" <?php echo (!isset($_POST['method']) || $_POST['method']==='challenge')? 'checked':''; ?>> Type + Words</label>
                    <label style="margin:0;"><input type="radio" name="method" value="password" <?php echo (isset($_POST['method']) && $_POST['method']==='password')? 'checked':''; ?>> Confirm with Password</label>
                </div>

                <div id="challengeBox" style="margin-bottom:10px;">
                    <div class="field">
                        <label for="confirm_delete">Type <strong>DELETE</strong> to confirm permanent deletion</label>
                        <input id="confirm_delete" name="confirm_delete" type="text" placeholder="Type DELETE" style="width:100%; padding:10px 12px; border-radius:8px; margin-top:6px; border:1px solid rgba(255,255,255,0.04); background:rgba(255,255,255,0.02); color:#eafaf1;">
                    </div>
                    <div class="field" style="margin-top:8px;">
                        <label for="confirm_words">Type the following words exactly:</label>
                        <div style="margin-top:6px; padding:10px 12px; border-radius:8px; background:rgba(0,0,0,0.24); border:1px solid rgba(255,255,255,0.04); color:var(--muted); font-weight:700;">
                            <?php echo htmlspecialchars($_SESSION['delete_challenge']); ?>
                        </div>
                        <input id="confirm_words" name="confirm_words" type="text" placeholder="Type the words shown above" style="width:100%; padding:10px 12px; border-radius:8px; margin-top:6px; border:1px solid rgba(255,255,255,0.04); background:rgba(255,255,255,0.02); color:#eafaf1;">
                    </div>
                </div>

                <div id="passwordBox" style="display:none; margin-bottom:10px;">
                    <div class="field">
                        <label for="confirm_password">Enter your account password to confirm</label>
                        <input id="confirm_password" name="confirm_password" type="password" placeholder="Your password" style="width:100%; padding:10px 12px; border-radius:8px; margin-top:6px; border:1px solid rgba(255,255,255,0.04); background:rgba(255,255,255,0.02); color:#eafaf1;">
                    </div>
                </div>

                <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                    <label style="display:flex; align-items:center; gap:8px; margin:0;"><input type="checkbox" name="delete_data" value="1"> Also delete my orders and order items</label>
                </div>
                <div style="margin-top:10px; display:flex; gap:8px;">
                    <button id="deleteBtn" type="submit" class="btn-delete" disabled>Delete Account Permanently</button>
                    <a href="account.php" class="btn-cancel">Cancel</a>
                </div>
            </form>

            <script>
                (function(){
                    const methodRadios = document.querySelectorAll('input[name="method"]');
                    const challengeBox = document.getElementById('challengeBox');
                    const passwordBox = document.getElementById('passwordBox');
                    function update(){
                        const v = document.querySelector('input[name="method"]:checked').value;
                        if(v === 'password'){
                            challengeBox.style.display = 'none';
                            passwordBox.style.display = 'block';
                        } else {
                            challengeBox.style.display = 'block';
                            passwordBox.style.display = 'none';
                        }
                    }
                    methodRadios.forEach(r => r.addEventListener('change', update));
                    // run on load
                    update();
                    // Disable delete button until the "Also delete my orders" checkbox is checked
                    const deleteCheckbox = document.querySelector('input[name="delete_data"]');
                    const deleteBtn = document.getElementById('deleteBtn');
                    function updateDeleteBtn(){
                        if(deleteCheckbox && deleteBtn){
                            deleteBtn.disabled = !deleteCheckbox.checked;
                        }
                    }
                    if(deleteCheckbox){
                        deleteCheckbox.addEventListener('change', updateDeleteBtn);
                        // initialize
                        updateDeleteBtn();
                    }
                })();
            </script>
        <?php else: ?>
            <p class="muted">Unable to load account information. <a href="user_login.php">Login again</a>.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Mahazon
</footer>
</body>
</html>
