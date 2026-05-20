// Admin loader script - attempts to load Lottie (local first, then CDN)
let adminLottieAnimation = null;

function loadScript(src, timeoutMs = 1500) {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    let timedOut = false;
    const timer = setTimeout(() => {
      timedOut = true;
      script.onerror = null;
      script.onload = null;
      if (script.parentNode) script.parentNode.removeChild(script);
      reject(new Error('Script load timeout: ' + src));
    }, timeoutMs);

    script.src = src;
    script.async = true;
    script.onload = function () {
      if (timedOut) return;
      clearTimeout(timer);
      resolve();
    };
    script.onerror = function () {
      if (timedOut) return;
      clearTimeout(timer);
      reject(new Error('Script failed to load: ' + src));
    };
    document.head.appendChild(script);
  });
}

function ensureAdminLottieAvailable() {
  const localPath = '../loader/lottie.min.js';
  const cdnPath = 'https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js';
  return loadScript(localPath, 800).catch(() => loadScript(cdnPath, 1200).catch((err) => {
    console.warn('Lottie failed to load for admin loader:', err);
    return Promise.resolve();
  }));
}

function insertFallbackSpinner(container) {
  if (!container) return;
  if (container.querySelector('.fallback-spinner')) return;
  const spinner = document.createElement('div');
  spinner.className = 'fallback-spinner';
  container.innerHTML = '';
  container.appendChild(spinner);
}

function initAdminLottieLoader() {
  const container = document.getElementById('admin-lottie-container');
  const loader = document.getElementById('admin-lottie-loader');
  if (!container || !loader) return;

  ensureAdminLottieAvailable().then(() => {
    if (window.lottie && typeof window.lottie.loadAnimation === 'function') {
      try {
        adminLottieAnimation = lottie.loadAnimation({
          container: container,
          renderer: 'svg',
          loop: true,
          autoplay: true,
          // Path relative to the admin page (admin/admin_login.php).
          // Point to the copy bundled with this loader so the animation loads reliably.
          path: '../loader/admin_loder/admin_login_loader/Login.json'
        });
        // expose globally in case other scripts want to control it
        window.adminLottieAnimation = adminLottieAnimation;
      } catch (err) {
        console.error('Error initializing admin Lottie animation:', err);
        insertFallbackSpinner(container);
      }
    } else {
      // Lottie not available
      insertFallbackSpinner(container);
    }
  });
}

function showAdminLoader() {
  const loader = document.getElementById('admin-lottie-loader');
  if (loader) loader.classList.remove('hidden');
  if (adminLottieAnimation && typeof adminLottieAnimation.play === 'function') {
    try { adminLottieAnimation.play(); } catch (e) { /* ignore */ }
  }
}

function hideAdminLoader() {
  const loader = document.getElementById('admin-lottie-loader');
  if (loader) loader.classList.add('hidden');
  if (adminLottieAnimation && typeof adminLottieAnimation.stop === 'function') {
    try { adminLottieAnimation.stop(); } catch (e) { /* ignore */ }
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdminLottieLoader);
} else {
  initAdminLottieLoader();
}

// Expose controls for other scripts (login flows) to show/hide loader
window.showAdminLoader = showAdminLoader;
window.hideAdminLoader = hideAdminLoader;