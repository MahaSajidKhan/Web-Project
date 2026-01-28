<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo">Mahazon</div>
        <p>Your one-stop local shop for curated products — secure checkout, fast tracking, and dedicated support. Built with care for small businesses and shoppers.</p>
        <div class="social-icons" aria-hidden="false">
          <a href="#" aria-label="Follow on Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 12.07C22 6.48 17.52 2 12 2S2 6.48 2 12.07C2 17.08 5.66 21.15 10.44 21.95v-6.96H7.9v-2.99h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.2 2.23.2v2.45h-1.25c-1.23 0-1.61.77-1.61 1.56v1.87h2.74l-.44 2.99h-2.3v6.96C18.34 21.15 22 17.08 22 12.07z" fill="currentColor"/></svg></a>
          <a href="#" aria-label="Follow on Twitter"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 5.9c-.6.27-1.25.45-1.93.53.7-.42 1.23-1.07 1.48-1.85-.66.39-1.4.67-2.18.83C18.7 4.6 17.78 4 16.75 4c-1.6 0-2.9 1.29-2.9 2.88 0 .23.02.46.07.67C10.8 7.33 8 5.74 6 3.2c-.25.44-.4.95-.4 1.5 0 1.03.52 1.94 1.31 2.47-.48-.02-.94-.15-1.34-.36v.04c0 1.45 1.03 2.66 2.4 2.94-.25.07-.52.1-.8.1-.2 0-.4-.02-.6-.06.4 1.23 1.52 2.13 2.86 2.16C9.8 14.8 8.5 15.4 7.1 15.4c-.43 0-.85-.03-1.26-.12 1.16.73 2.53 1.16 3.99 1.16 4.79 0 7.41-3.96 7.41-7.4v-.33c.5-.35.93-.8 1.27-1.31-.45.2-.95.34-1.45.4z" fill="currentColor"/></svg></a>
          <a href="#" aria-label="Follow on Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm5 6.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6zM20.5 7.1a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0z" fill="currentColor"/></svg></a>
        </div>
      </div>

      <div class="footer-links" aria-label="Footer links">
        <ul>
          <li><strong>Quick Links</strong></li>
          <li><a href="index.php">Home</a></li>
          <li><a href="categories_list.php">Categories</a></li>
          <li><a href="cart.php">Cart</a></li>
          <li><a href="contact_user.php">Contact</a></li>
        </ul>
        <ul>
          <li><strong>Company</strong></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="help.php">Help</a></li>
          <li><a href="careers.php">Careers</a></li>
        </ul>
      </div>

      <div class="newsletter" aria-label="Newsletter signup">
        <strong style="color:#eafaf1;">Join our newsletter</strong>
        <p style="margin:0;color:var(--muted);font-size:14px;">Get updates on deals and new arrivals — we send occasional emails only.</p>
        <form action="#" method="post" onsubmit="alert('Thank you — this demo form does not submit.'); return false;" style="display:flex; gap:8px; margin-top:8px;">
          <input type="email" name="email" placeholder="you@example.com" aria-label="Email address" required>
          <button type="submit">Subscribe</button>
        </form>
        <div class="footer-contact" style="margin-top:14px;">
          <div class="contact-item"><strong>Support:</strong>&nbsp;<span>support@example.com</span></div>
          <div class="contact-item"><strong>Phone:</strong>&nbsp;<span>+92 300 0000000</span></div>
          <div class="contact-item"><strong>Address:</strong>&nbsp;<span>Karachi, Pakistan</span></div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div>&copy; <?php echo date('Y'); ?> Mahazon — All rights reserved.</div>
      <div>
        <a href="privacy.php">Privacy</a>
        <a href="terms.php">Terms</a>
        <a href="#" id="cookiePrefsLink" style="margin-left:8px">Cookie preferences</a>
      </div>
    </div>
  </div>
</footer>
<!-- Cookie consent assets -->
<script>
// expose admin flag to client so admins can be exempt from forced cookie popup
window.isAdmin = <?php echo isset($_SESSION['admin']) ? 'true' : 'false'; ?>;
</script>
<script src="assets/js/cookies.js"></script>
<script>
// wire footer cookie preferences link to the consent UI
document.addEventListener('DOMContentLoaded', function(){
  var link = document.getElementById('cookiePrefsLink');
  if(!link) return;
  link.addEventListener('click', function(e){ e.preventDefault(); try{ if(window.CookieConsent && typeof window.CookieConsent.showPreferences === 'function'){ window.CookieConsent.showPreferences(); } else { document.dispatchEvent(new Event('showCookiePreferences')); } }catch(err){ console.error(err); } });
});
</script>
