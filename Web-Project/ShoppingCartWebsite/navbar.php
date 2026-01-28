<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- include global stylesheet (safe in body for older pages) -->
<link rel="stylesheet" href="assets/css/style.css">

<div class="site-navbar">

    <!-- LEFT SIDE -->
    <div class="nav-left">
        <!-- Hamburger: show additional navbar items when clicked -->
        <div style="position:relative;">
            <button id="navHamburger" class="hamburger-btn" aria-label="Open menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div id="hamburgerMenu" class="hamburger-menu" aria-hidden="true">
                <button id="hamburgerClose" class="hamburger-close" aria-label="Close menu">×</button>
                <div class="hamburger-header">
                    <h3>Menu</h3>
                    <p class="muted">Quick access to site sections</p>
                </div>
                <nav class="hamburger-nav" aria-label="Main navigation">
                    <ul class="hamburger-list">
                        <li><a href="index.php">Home</a></li>
                        <?php if(!isset($_SESSION['admin'])): ?>
                        <li><a href="cart.php">Cart</a></li>
                        <?php endif; ?>
                        <li class="divider"></li>
                        <li><a href="help.php">Help</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li class="divider"></li>
                        <?php
                            // Inject dynamic categories into the hamburger menu
                            require_once __DIR__ . '/db.php';
                            $catQ = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
                            if ($catQ && mysqli_num_rows($catQ) > 0) {
                                echo '<li class="group-title">Categories</li>'; 
                                while ($cat = mysqli_fetch_assoc($catQ)) {
                                    $cname = htmlspecialchars($cat['name']);
                                    echo "<li><a href=\"category.php?id={$cat['id']}\">{$cname}</a></li>";
                                }
                                echo '<li class="divider"></li>';
                            }

                            // user / admin links
                            $current = basename($_SERVER['PHP_SELF'] ?? '');
                            $adminPages = ['admin_dashboard.php','admin_login.php','create_admin.php','add_category.php','add_product.php','edit_product.php','manage_products.php','manage_users.php','manage_orders.php'];
                            $isManagePrefix = strpos($current, 'manage_') === 0;
                            $isAdminPage = in_array($current, $adminPages) || $isManagePrefix;

                            if(isset($_SESSION['admin']) && $isAdminPage){
                                echo '<li><a href="admin_dashboard.php">Admin (' . htmlspecialchars($_SESSION['admin']) . ')</a></li>';
                                echo '<li><a href="logout.php">Logout</a></li>';
                            } elseif(isset($_SESSION['user_id'])){
                                echo '<li><a href="account.php">My Account (' . htmlspecialchars($_SESSION['user_name']) . ')</a></li>';
                                echo '<li><a href="favorites.php">Favorites</a></li>';
                                echo '<li><a href="track_order.php">Track Order</a></li>';
                                echo '<li><a href="contact_user.php">Contact Admin</a></li>';
                                echo '<li><a href="my_issues.php">My Issues</a></li>';
                                echo '<li><a href="logout.php">Logout</a></li>';
                            } elseif(isset($_SESSION['admin'])){
                                echo '<li><a href="admin_dashboard.php">Admin Panel (' . htmlspecialchars($_SESSION['admin']) . ')</a></li>';
                                echo '<li><a href="admin_issues.php">User Issues</a></li>';
                                echo '<li><a href="logout.php">Logout</a></li>';
                            } else {
                                // Removed inline Login/Register links from hamburger menu
                                // Auth buttons are rendered in the top navbar for better visibility and consistent styling
                            }
                        ?>
                    </ul>
                </nav>
                <!-- Custom scrollbar / slider for long menu content -->
                <div class="menu-scrollbar" aria-hidden="true">
                    <div class="menu-scroll-track"></div>
                    <div class="menu-scroll-thumb" id="menuScrollThumb"></div>
                </div>
            </div>
        </div>

        <!-- Center: Search bar fills the freed navbar space -->
        <?php
            // Pages where the global navbar search is not desirable
            $current = basename($_SERVER['PHP_SELF'] ?? '');
            $noSearchPages = [
                // admin / management pages
                'admin_login.php','admin_dashboard.php','create_admin.php','add_category.php','add_product.php','edit_product.php',
                'manage_products.php','manage_users.php','manage_orders.php','admin_issues.php', 'favorite_action.php',
                // informational / utility pages where search is not needed
                'about.php','help.php','contact_user.php','privacy.php','terms.php','careers.php',
                // auth / registration pages
                'user_login.php','user_signup.php','register.php'
            ];
            // Allow pages to explicitly opt-out by setting $hide_nav_search = true before including navbar.php
            $showSearch = !in_array($current, $noSearchPages);
            if(isset($hide_nav_search) && $hide_nav_search) $showSearch = false;
            // If an admin is logged in, show the search by default (unless page explicitly hides it)
            if(isset($_SESSION['admin']) && !isset($hide_nav_search)){
                $showSearch = true;
            }
        ?>
        <?php if($showSearch): ?>
        <div class="nav-search">
            <form action="search.php" method="GET" class="search-inline">
                <input type="text" name="q" placeholder="Search products, categories, brands..." aria-label="Search">
                <button type="submit" class="btn-primary-custom">Search</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT SIDE -->
    <div style="margin-left:auto; display:flex; gap:12px; align-items:center;">
        <!-- Cart icon (replaces text link) - hide for admins -->
        <?php if(!isset($_SESSION['admin'])): ?>
        <?php
            $cartCount = 0;
            if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])){
                foreach($_SESSION['cart'] as $item){
                    $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
                    if($qty > 0) $cartCount += $qty;
                }
            }
        ?>
        <a href="cart.php" class="nav-cart-icon" aria-label="View cart">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 6h15l-1.5 9h-11L6 6z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="10" cy="20" r="1" fill="currentColor"/>
                <circle cx="18" cy="20" r="1" fill="currentColor"/>
            </svg>
            <?php if($cartCount > 0): ?>
            <span class="cart-count-badge" aria-label="Cart item count"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- AUTH ACTIONS: login / register or account / logout -->
        <div class="nav-auth" aria-live="polite">
            <?php
                // Reuse the same session checks but render compact buttons in the navbar
                if(isset($_SESSION['admin']) && $isAdminPage){
                    echo '<a href="admin_dashboard.php" class="btn-auth btn-outline">Admin</a>';
                    echo '<a href="logout.php" class="btn-auth btn-ghost">Logout</a>';
                } elseif(isset($_SESSION['user_id'])){
                    $uname = htmlspecialchars($_SESSION['user_name'] ?? 'Account');
                    echo "<a href=\"account.php\" class=\"btn-auth btn-outline\">$uname</a>";
                    echo '<a href="logout.php" class="btn-auth btn-ghost">Logout</a>';
                } elseif(isset($_SESSION['admin'])){
                    echo '<a href="admin_dashboard.php" class="btn-auth btn-outline">Admin Panel</a>';
                    echo '<a href="admin_issues.php" class="btn-auth btn-ghost">Issues</a>';
                    echo '<a href="logout.php" class="btn-auth btn-ghost">Logout</a>';
                } else {
                    // Make Login green as well and point Register to `register.php`
                    echo '<a href="user_login.php" class="btn-auth btn-primary">Login</a>';
                    echo '<a href="register.php" class="btn-auth btn-primary">Register</a>';
                }
            ?>
        </div>
    </div>
</div>
<div id="navBackdrop" class="nav-backdrop" aria-hidden="true"></div>
<script>
// Hamburger open/close behavior with backdrop, close button and Escape key
(function(){
    const btn = document.getElementById('navHamburger');
    let menu = document.getElementById('hamburgerMenu');
    let closeBtn = document.getElementById('hamburgerClose');
    let backdrop = document.getElementById('navBackdrop');
    if(!btn || !menu || !backdrop) return;

    // Move menu and backdrop to document.body to avoid ancestor stacking/transform issues
    document.addEventListener('DOMContentLoaded', function(){
        // re-query in case DOM changed
        menu = document.getElementById('hamburgerMenu');
        backdrop = document.getElementById('navBackdrop');
        closeBtn = document.getElementById('hamburgerClose');
        if(menu && menu.parentNode !== document.body) document.body.appendChild(menu);
        if(backdrop && backdrop.parentNode !== document.body) document.body.appendChild(backdrop);
    });

    function openMenu(){
        menu.classList.add('show');
        backdrop.classList.add('show');
        menu.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        if(closeBtn) closeBtn.focus();
    }
    function closeMenu(){
        menu.classList.remove('show');
        backdrop.classList.remove('show');
        menu.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
    }

    btn.addEventListener('click', function(e){ e.stopPropagation(); openMenu(); });
    if(closeBtn) closeBtn.addEventListener('click', function(e){ e.stopPropagation(); closeMenu(); });
    backdrop.addEventListener('click', function(){ closeMenu(); });

    // close on Escape
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeMenu(); });
})();
</script>
<script>
// Sync draggable scrollbar thumb with menu scroll and allow dragging
(function(){
    let menu = document.getElementById('hamburgerMenu');
    let thumb = document.getElementById('menuScrollThumb');
    if(!menu || !thumb) return;

    function updateThumb(){
        const visible = menu.clientHeight;
        const total = menu.scrollHeight;
        const trackHeight = Math.max(menu.clientHeight - 80, 80);
        const ratio = visible / total;
        const thumbH = Math.max(Math.round(trackHeight * ratio), 48);
        thumb.style.height = thumbH + 'px';
        const maxThumbTop = trackHeight - thumbH;
        const scrollRatio = menu.scrollTop / (total - visible || 1);
        const top = Math.round(12 + (maxThumbTop * scrollRatio));
        thumb.style.top = top + 'px';
    }

    // Update when menu scrolls or resizes
    menu.addEventListener('scroll', updateThumb);
    window.addEventListener('resize', updateThumb);

    // initial
    setTimeout(updateThumb, 120);

    // Dragging logic
    let dragging = false;
    let startY = 0;
    let startScroll = 0;

    thumb.addEventListener('mousedown', function(e){
        dragging = true; startY = e.clientY; startScroll = menu.scrollTop; document.body.classList.add('no-select');
        e.preventDefault();
    });

    document.addEventListener('mousemove', function(e){
        if(!dragging) return;
        const dy = e.clientY - startY;
        const visible = menu.clientHeight;
        const total = menu.scrollHeight;
        const trackHeight = Math.max(menu.clientHeight - 80, 80);
        const ratio = visible / total;
        const thumbH = Math.max(Math.round(trackHeight * ratio), 48);
        const maxThumbTop = trackHeight - thumbH;
        const scrollable = total - visible || 1;
        const scrollPerPx = scrollable / (maxThumbTop || 1);
        const deltaScroll = Math.round(dy * scrollPerPx);
        menu.scrollTop = Math.max(0, Math.min(total, startScroll + deltaScroll));
    });

    document.addEventListener('mouseup', function(){ if(dragging){ dragging=false; document.body.classList.remove('no-select'); } });

    // Click on track to jump
    const track = thumb.parentElement.querySelector('.menu-scroll-track');
    if(track){
        track.addEventListener('click', function(e){
            const rect = track.getBoundingClientRect();
            const clickY = e.clientY - rect.top;
            const visible = menu.clientHeight;
            const total = menu.scrollHeight;
            const trackHeight = rect.height;
            const thumbH = parseInt(window.getComputedStyle(thumb).height,10) || 48;
            const maxThumbTop = Math.max(trackHeight - thumbH, 1);
            const clickRatio = Math.max(0, Math.min(1, (clickY - (thumbH/2)) / maxThumbTop));
            menu.scrollTop = Math.round(clickRatio * (total - visible));
        });
    }
})();
</script>
</script>
