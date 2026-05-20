/* ============================================
   TRIPMATE LOADER SCRIPT (resilient loader)
   ============================================
   This file handles the Lottie animation loader.
   The loader appears only in the main slideshow panel.
   Improvements:
   - Loads the Lottie library asynchronously (local first, CDN fallback)
   - Uses timeouts so a slow network won't block hiding the loader
   ============================================ */

// Global variable to store the Lottie animation instance
let lottieAnimation = null;

// Utility: load a script with a timeout and return a Promise
function loadScript(src, timeoutMs = 1500) {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    let timedOut = false;
    const timer = setTimeout(() => {
      timedOut = true;
      script.onerror = null;
      script.onload = null;
      // Remove the tag to avoid later onerror/onload firing
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
    script.onerror = function (e) {
      if (timedOut) return;
      clearTimeout(timer);
      reject(new Error('Script failed to load: ' + src));
    };

    document.head.appendChild(script);
  });
}

/**
 * Try to ensure lottie is available by attempting to load a local copy first
 * then falling back to the CDN. The function resolves even if lottie fails to
 * load so the page won't be blocked by network problems.
 */
function ensureLottieAvailable() {
  // Try a local copy in the parent `loader/` folder first (relative to pages
  // that include this script from `tripmate/main/`). If you prefer the
  // lottie file inside `main_loder/`, place it there and update this path.
  const localPath = '../loader/lottie.min.js'; // place a local copy here to avoid network dependency
  const cdnPath = 'https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js';

  // Try local first, short timeout. If it fails, try CDN with a slightly larger timeout.
  return loadScript(localPath, 800).catch(() => {
    // Local not found or slow — try CDN but don't block more than ~1800ms
    return loadScript(cdnPath, 1200).catch((err) => {
      console.warn('Both local and CDN Lottie failed to load (or timed out). Proceeding without animation.', err);
      return Promise.resolve(); // resolve so calling code continues
    });
  });
}

/**
 * Initialize the Lottie loader animation
 */
function initLottieLoader() {
  const container = document.getElementById('lottie-container');
  const loader = document.getElementById('lottie-loader');

  // Check if elements exist
  if (!container || !loader) {
    console.warn('Lottie loader elements not found');
    return;
  }

  // Maximum time we allow the loader to remain visible (safety fallback)
  const MAX_VISIBLE_MS = 3000;
  const MIN_VISIBLE_MS = 1000; // show at least this long to avoid flicker
  const startedAt = Date.now();

  // Start the safety fallback first: ensure loader is hidden after MAX_VISIBLE_MS
  const safetyTimer = setTimeout(() => {
    if (loader && !loader.classList.contains('hidden')) {
      loader.classList.add('hidden');
      console.log('Loader hidden by safety timeout');
    }
  }, MAX_VISIBLE_MS);

  // Ensure lottie library is available (local preferred)
  ensureLottieAvailable().then(() => {
    // If library is present, initialize animation; otherwise, skip and hide loader
    if (window.lottie && typeof window.lottie.loadAnimation === 'function') {
      try {
        lottieAnimation = lottie.loadAnimation({
          container: container,
          renderer: 'svg',
          loop: true,
          autoplay: true,
          // The animation JSON is located in the `loader/main_loder/` folder
          // relative to pages in `tripmate/main/`.
          path: '../loader/main_loder/road-trip-animation.json'
        });
  // Expose animation instance to window so other inline scripts can control it
  try { window.lottieAnimation = lottieAnimation; } catch (e) { /* ignore */ }
        console.log('Lottie loader initialized successfully');
      } catch (error) {
        console.error('Error loading Lottie animation:', error);
      }
    } else {
      console.warn('Lottie library not available; animation skipped');
    }

    // Hide loader when page is fully loaded, but respect minimum visible time
    window.addEventListener('load', function() {
      const elapsed = Date.now() - startedAt;
      const wait = Math.max(0, MIN_VISIBLE_MS - elapsed);
      setTimeout(function() {
        if (loader && !loader.classList.contains('hidden')) {
          loader.classList.add('hidden');
          console.log('Loader hidden after page load');
        }
        clearTimeout(safetyTimer);
      }, wait);
    });
  });
}

// Initialize loader when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLottieLoader);
} else {
  // DOM is already ready
  initLottieLoader();
}
