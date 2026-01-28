(function(){
  // Simple cookie helper and consent banner management
  function setCookie(name, value, days){
    var d = new Date(); d.setTime(d.getTime() + (days*24*60*60*1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/;SameSite=Lax";
  }
  function getCookie(name){
    var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? decodeURIComponent(v.pop()) : null;
  }
  function removeCookie(name){ setCookie(name,'',-1); }

  // expose to window for other scripts
  window.CookieConsent = {
    get: function(){ var c = getCookie('site_cookie_consent'); return c ? JSON.parse(c) : null; },
    acceptAll: function(){ var obj = { analytics: true, preferences: true, marketing: true, acceptedAt: Date.now() }; setCookie('site_cookie_consent', JSON.stringify(obj), 365); document.dispatchEvent(new CustomEvent('cookieConsentChanged', { detail: obj })); hideBanner(); },
    rejectNonEssential: function(){ var obj = { analytics: false, preferences: true, marketing: false, acceptedAt: Date.now() }; setCookie('site_cookie_consent', JSON.stringify(obj), 365); document.dispatchEvent(new CustomEvent('cookieConsentChanged', { detail: obj })); hideBanner(); },
    remove: function(){ removeCookie('site_cookie_consent'); document.dispatchEvent(new CustomEvent('cookieConsentChanged', { detail: null })); },
    // show the preferences/banner so the user can change choices (non-forced)
    showPreferences: function(){ createBanner(false); }
  };

  // Banner logic
  function createBanner(force){
    // if banner already exists, remove it first
    var existing = document.querySelector('.cookie-banner'); if(existing) existing.parentNode.removeChild(existing);
    var existingOverlay = document.querySelector('.cookie-modal-overlay'); if(existingOverlay) existingOverlay.parentNode.removeChild(existingOverlay);

    // when force is true we create a blocking overlay and require the user to pick Accept or Reject
    var overlay = null;
    if(force){
      overlay = document.createElement('div'); overlay.className = 'cookie-modal-overlay'; overlay.setAttribute('aria-hidden','true'); document.body.appendChild(overlay);
    }

    var banner = document.createElement('div'); banner.className = 'cookie-banner'; banner.setAttribute('role','dialog'); banner.setAttribute('aria-live','polite');
    // preference toggles + actions
    banner.innerHTML = '\n      <div class="cookie-text">We use cookies to improve your experience and analyze site usage. Choose which cookies you allow:</div>\n      <div style="display:flex; flex-direction:column; gap:8px; margin-left:12px; flex:0 0 360px;">\n        <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" id="cookieToggleAnalytics"> <span>Analytics (site usage & performance)</span></label>\n        <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" id="cookieToggleMarketing"> <span>Marketing (personalized ads)</span></label>\n        <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" id="cookieTogglePreferences" checked> <span>Preferences (remember choices)</span></label>\n        <div style="display:flex; gap:8px; margin-top:6px;">\n          <button class="btn btn-ghost" id="cookieReject">Reject non-essential</button>\n          <button class="btn btn-primary" id="cookieSave">Save preferences</button>\n          <button class="btn btn-primary" id="cookieAccept">Accept all</button>\n        </div>\n      </div>';

    // if not forced, add a small close control so user can dismiss while changing preferences
    if(!force){
      var close = document.createElement('button'); close.className = 'cookie-close'; close.id = 'cookieClose'; close.setAttribute('aria-label','Close cookie preferences'); close.style.background='transparent'; close.style.border='0'; close.style.color='rgba(234,250,241,0.6)'; close.style.fontWeight='700'; close.style.marginLeft='12px'; close.innerText = '✕'; banner.appendChild(close);
    }

    document.body.appendChild(banner);

      // prefill toggles from existing consent when reopening (non-forced)
      try{
        var existing = window.CookieConsent.get();
        if(existing){
          var a = document.getElementById('cookieToggleAnalytics'); if(a) a.checked = !!existing.analytics;
          var m = document.getElementById('cookieToggleMarketing'); if(m) m.checked = !!existing.marketing;
          var p = document.getElementById('cookieTogglePreferences'); if(p) p.checked = (existing.preferences === undefined ? true : !!existing.preferences);
        }
      }catch(e){}

    document.getElementById('cookieAccept').addEventListener('click', function(){ window.CookieConsent.acceptAll(); });
    document.getElementById('cookieReject').addEventListener('click', function(){ window.CookieConsent.rejectNonEssential(); });
    var saveBtn = document.getElementById('cookieSave');
    if(saveBtn){ saveBtn.addEventListener('click', function(){
      var a = document.getElementById('cookieToggleAnalytics');
      var m = document.getElementById('cookieToggleMarketing');
      var p = document.getElementById('cookieTogglePreferences');
      var obj = {
        analytics: !!(a && a.checked),
        marketing: !!(m && m.checked),
        preferences: !!(p && p.checked),
        acceptedAt: Date.now()
      };
      setCookie('site_cookie_consent', JSON.stringify(obj), 365);
      document.dispatchEvent(new CustomEvent('cookieConsentChanged', { detail: obj }));
      hideBanner();
    }); }
    if(!force){ var cb = document.getElementById('cookieClose'); if(cb) cb.addEventListener('click', function(){ hideBanner(); }); }

    // prevent clicks on overlay from closing when forced
    if(overlay){ overlay.addEventListener('click', function(e){ e.stopPropagation(); }); }

    // prevent escape key from closing when forced
    if(force){
      var escHandler = function(e){ if(e.key === 'Escape'){ e.preventDefault(); e.stopPropagation(); } };
      document.addEventListener('keydown', escHandler, true);
      // store handler so it can be removed when banner hidden
      banner._escHandler = escHandler;
    }
  }

  function hideBanner(){
    var b = document.querySelector('.cookie-banner'); if(!b) return; b.style.transition = 'opacity .28s ease, transform .28s ease'; b.style.opacity = '0'; b.style.transform = 'translateY(12px)';
    var ov = document.querySelector('.cookie-modal-overlay'); if(ov) ov.style.transition = 'opacity .22s ease'; if(ov) ov.style.opacity = '0';
    // remove keydown handler if present
    if(b && b._escHandler) { try{ document.removeEventListener('keydown', b._escHandler, true); }catch(e){} }
    setTimeout(function(){ if(b && b.parentNode) b.parentNode.removeChild(b); if(ov && ov.parentNode) ov.parentNode.removeChild(ov); }, 320);
  }

  // Initialize
  document.addEventListener('DOMContentLoaded', function(){
    try{
      var consent = window.CookieConsent.get();
      // allow forcing banner for testing via query param e.g. ?show_cookie_banner=1
      var params = new URLSearchParams(window.location.search);
      if(params.get('show_cookie_banner') === '1'){
        try{ removeCookie('site_cookie_consent'); consent = null; }catch(e){}
      }
      if(!consent){
        // do not show forced popup to admins
        if(!(window.isAdmin)){ createBanner(true); }
      }
      else { document.documentElement.setAttribute('data-cookie-consent', JSON.stringify(consent)); }
      // listen for programmatic requests to show preferences later (non-forced)
      document.addEventListener('showCookiePreferences', function(){ createBanner(false); });
    }catch(e){ console.error('CookieConsent init error', e); }
  });
})();
