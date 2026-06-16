/**
 * session_guard.js  —  TripMate Universal Session Guard
 * =====================================================
 * Include this script on EVERY page (HTML & PHP).
 * It runs on every page load and:
 *
 *   1. Calls the server (check_session.php) to see if the PHP session is alive.
 *   2. If the server says NOT logged in  →  wipes all client-side storage
 *      (localStorage + sessionStorage) so stale data never lingers.
 *   3. If the server says STILL logged in  →  keeps storage in sync.
 *   4. Implements 'beforeunload' beacon to log out exactly when the browser tab is closed.
 *
 * HOW TO INCLUDE:
 *   <script src="../scripts/session_guard.js"></script>
 */

(function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────────────────────

    function clearClientSession() {
        const lsKeys = [
            'tripmate_active_user_id',
            'tripmate_active_user_name',
            'tripmate_session_start',
        ];
        const ssKeys = [
            'user_id', 'user_name',
            'userid',  'username',
            'auth_provider', 'user_email', 'user_pic',
            'lastActiveTime',
        ];
        lsKeys.forEach(k => localStorage.removeItem(k));
        ssKeys.forEach(k => sessionStorage.removeItem(k));
        document.body.classList.remove('user-logged-in');
    }

    function syncClientSession(data) {
        sessionStorage.setItem('user_id',   data.user_id);
        sessionStorage.setItem('user_name', data.user_name);
        localStorage.setItem('tripmate_active_user_id',   data.user_id);
        localStorage.setItem('tripmate_active_user_name', data.user_name);
        document.body.classList.add('user-logged-in');
    }

    function resolveUrl(filename) {
        const scripts = document.querySelectorAll('script[src]');
        let base = null;
        scripts.forEach(s => {
            if (s.src && s.src.includes('session_guard.js')) {
                base = s.src.replace(/scripts\/session_guard\.js.*$/, '');
            }
        });

        if (base) {
            return base + 'auth/' + filename;
        }

        const parts = window.location.pathname.split('/');
        parts.pop();
        parts.pop();
        return parts.join('/') + '/auth/' + filename;
    }

    // ── Main guard logic ──────────────────────────────────────────────────────
    function runGuard() {
        const checkUrl = resolveUrl('check_session.php');

        fetch(checkUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('check_session returned ' + res.status);
            return res.json();
        })
        .then(data => {
            if (!data.logged_in) {
                const hadSession = !!(
                    sessionStorage.getItem('user_id') ||
                    localStorage.getItem('tripmate_active_user_id')
                );

                clearClientSession();

                if (hadSession) {
                    console.info('[SessionGuard] Stale session cleared. User must log in again.');
                    document.dispatchEvent(new CustomEvent('tripmate:session-cleared'));
                }
            } else {
                syncClientSession(data);
                document.dispatchEvent(new CustomEvent('tripmate:session-valid', { detail: data }));
            }
        })
        .catch(err => {
            console.warn('[SessionGuard] Could not reach check_session.php:', err.message);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runGuard);
    } else {
        runGuard();
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            runGuard();
        }
    });

    window.TripMateSessionGuard = { check: runGuard, clear: clearClientSession };

    // ── Browser Close Auto-Logout (Beacon) ───────────────────────────────────
    let isNavigatingAway = false;

    // Detect link clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (link && !link.getAttribute('href').startsWith('#') && !link.getAttribute('href').startsWith('javascript')) {
            isNavigatingAway = true;
        }
    }, true);

    // Detect form submissions
    document.addEventListener('submit', () => {
        isNavigatingAway = true;
    }, true);

    // Detect programmatic navigation
    const origAssign = window.location.assign;
    const origReplace = window.location.replace;
    if (origAssign) {
        window.location.assign = function() {
            isNavigatingAway = true;
            return origAssign.apply(this, arguments);
        };
    }
    if (origReplace) {
        window.location.replace = function() {
            isNavigatingAway = true;
            return origReplace.apply(this, arguments);
        };
    }

    // When the window is unloading or page is hiding (more reliable on modern browsers/mobile)
    function handleUnload() {
        if (isNavigatingAway) {
            // It's a normal page navigation, do not kill session
            return;
        }
        
        const hasSession = !!(sessionStorage.getItem('user_id') || localStorage.getItem('tripmate_active_user_id'));
        if (hasSession && navigator.sendBeacon) {
            const logoutUrl = resolveUrl('session_config.php');
            const formData = new FormData();
            formData.append('action', 'logout');
            // Send the logout signal to the server
            navigator.sendBeacon(logoutUrl, formData);
            // Clear client data immediately
            clearClientSession();
        }
    }

    window.addEventListener('beforeunload', handleUnload);
    window.addEventListener('pagehide', handleUnload);

})();
