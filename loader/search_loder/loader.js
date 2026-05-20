/* ============================================
   SEARCH PAGE LOCATION PIN LOADER SCRIPT
   ============================================
   This file handles the location pin Lottie animation loader.
   The loader appears only on the search page.
   ============================================ */

// Global variable to store the Lottie animation instance
let searchLottieAnimation = null;

// Utility: load a script with a timeout and return a Promise
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
    script.onerror = function (e) {
      if (timedOut) return;
      clearTimeout(timer);
      reject(new Error('Script failed to load: ' + src));
    };

    document.head.appendChild(script);
  });
}

/**
 * Load Lottie library for search page
 */
function ensureSearchLottieAvailable() {
  const localPath = '../loader/lottie.min.js'; // Relative path from search directory
  const cdnPath = 'https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js';

  return loadScript(localPath, 800).catch(() => {
    return loadScript(cdnPath, 1200).catch((err) => {
      console.warn('Lottie failed to load. Proceeding without animation.', err);
      return Promise.resolve();
    });
  });
}

/**
 * Initialize the search page Lottie loader animation
 */
function initSearchLottieLoader() {
  const container = document.getElementById('search-lottie-container');
  const loader = document.getElementById('search-lottie-loader');

  // Check if elements exist
  if (!container || !loader) {
    console.warn('Search Lottie loader elements not found');
    return;
  }

  // Load Lottie library
  ensureSearchLottieAvailable().then(() => {
    if (window.lottie && typeof window.lottie.loadAnimation === 'function') {
      try {
        searchLottieAnimation = lottie.loadAnimation({
          container: container,
          renderer: 'svg',
          loop: true,
          autoplay: true,
          // Path to your location pin loader JSON (relative to search.html)
          path: '../loader/search_loder/location-pin-loader.json'
        });
        console.log('Search page Lottie loader initialized successfully');
      } catch (error) {
        console.error('Error loading search page Lottie animation:', error);
        insertFallbackSpinner(container);
      }
    } else {
      // Lottie not available — insert a simple fallback spinner so the UI
      // still indicates loading instead of silently doing nothing.
      console.warn('Lottie library not available for search page; inserting fallback spinner');
      insertFallbackSpinner(container);
    }
  });
}

/**
 * Insert a small fallback spinner into the container when Lottie isn't available.
 * This avoids a blank loader area on slow networks or when scripts are blocked.
 */
function insertFallbackSpinner(container) {
  if (!container) return;
  // Do not insert multiple spinners
  if (container.querySelector('.fallback-spinner')) return;
  const spinner = document.createElement('div');
  spinner.className = 'fallback-spinner';
  // Center the spinner inside the container
  spinner.style.margin = '0 auto';
  // Clear any existing content (e.g., partial Lottie DOM) and add spinner
  container.innerHTML = '';
  container.appendChild(spinner);
}

/**
 * Show search loader
 */
function showSearchLoader() {
  const loader = document.getElementById('search-lottie-loader');
  if (loader) {
    loader.classList.remove('hidden');
    if (searchLottieAnimation) {
      searchLottieAnimation.play();
    }
  }
}

/**
 * Hide search loader
 */
function hideSearchLoader() {
  const loader = document.getElementById('search-lottie-loader');
  if (loader) {
    loader.classList.add('hidden');
    if (searchLottieAnimation) {
      searchLottieAnimation.stop();
    }
  }
}

/**
 * Initialize search page loader with delay
 */
function initSearchPageLoader() {
  // Check URL flag: if the page was opened with ?showLoader=1 we should
  // keep the loader visible until the app hides it (don't auto-hide).
  const params = new URLSearchParams(window.location.search);
  const showLoaderOnOpen = params.get('showLoader') === '1';

  // Only auto-hide after 1s when NOT explicitly requested via URL flag.
  if (!showLoaderOnOpen) {
    setTimeout(() => {
      const loader = document.getElementById('search-lottie-loader');
      if (loader && !loader.classList.contains('hidden')) {
        loader.classList.add('hidden');
      }
    }, 1000);
  }

  // Initialize Lottie
  initSearchLottieLoader();
  
  // Attach search loader to search button
  const searchButton = document.getElementById('search-button');
  if (searchButton) {
    searchButton.addEventListener('click', function() {
      showSearchLoader();
      
      // Automatically hide after 3 seconds (for demo)
      // In real use, hide when search results load
      setTimeout(hideSearchLoader, 3000);
    });
  }
}

// If page was opened with ?showLoader=1, ensure the loader is visible once
// the Lottie initialization or fallback completes. This makes the UX smooth
// when navigating from main -> search with the loader flag.
function ensureLoaderVisibleIfRequested() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('showLoader') === '1') {
    const loader = document.getElementById('search-lottie-loader');
    if (loader) loader.classList.remove('hidden');
    // If Lottie is ready, play it. If not, the init function will try to play
    // when it becomes available.
    if (searchLottieAnimation && typeof searchLottieAnimation.play === 'function') {
      try { searchLottieAnimation.play(); } catch (e) { /* ignore */ }
    }
  }
}

// Ensure visibility again after Lottie init/fallback insertion
// Hook into initSearchLottieLoader by calling this after DOM is ready.
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', ensureLoaderVisibleIfRequested);
} else {
  ensureLoaderVisibleIfRequested();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSearchPageLoader);
} else {
  initSearchPageLoader();
}