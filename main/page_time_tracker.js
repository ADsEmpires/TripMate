/**
 * Universal Page Time Tracker
 * Tracks time spent on pages and sends data to server
 */

(function() {
    'use strict';
    
    let startTime = Date.now();
    let clickCount = 0;
    let isActive = true;
    let lastActivityTime = Date.now();
    let visibilityCheckInterval = null;
    
    // Get page information
    function getPageInfo() {
        const path = window.location.pathname;
        const filename = path.split('/').pop() || 'index';
        const pageName = filename.replace(/\.(html|php)$/i, '') || 'home';
        return {
            name: pageName,
            url: window.location.href,
            path: path
        };
    }
    
    // Get user ID from localStorage
    function getUserId() {
        return localStorage.getItem('tripmate_active_user_id') || null;
    }
    
    // Track clicks
    document.addEventListener('click', function() {
        clickCount++;
        lastActivityTime = Date.now();
    }, true);
    
    // Track keyboard activity
    document.addEventListener('keydown', function() {
        lastActivityTime = Date.now();
    }, true);
    
    // Track mouse movement
    document.addEventListener('mousemove', function() {
        lastActivityTime = Date.now();
    }, true);
    
    // Check if user is active (not idle)
    function checkActivity() {
        const now = Date.now();
        const idleTime = now - lastActivityTime;
        const idleThreshold = 60000; // 60 seconds of inactivity
        
        if (idleTime > idleThreshold && isActive) {
            isActive = false;
        } else if (idleTime <= idleThreshold && !isActive) {
            isActive = true;
            startTime = now - (Date.now() - startTime); // Adjust start time
        }
    }
    
    // Handle page visibility changes (tab switching)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, pause tracking
            isActive = false;
        } else {
            // Page is visible again, resume tracking
            isActive = true;
            lastActivityTime = Date.now();
        }
    });
    
    // Start visibility check interval
    visibilityCheckInterval = setInterval(checkActivity, 5000); // Check every 5 seconds
    
    // Send tracking data to server
    function sendTrackingData() {
        const endTime = Date.now();
        const totalTime = endTime - startTime;
        const activeTime = isActive ? Math.round(totalTime / 1000) : 0; // Convert to seconds
        
        // Only send if user was active for at least 1 second
        if (activeTime < 1) {
            return;
        }
        
        const pageInfo = getPageInfo();
        const userId = getUserId();
        
        const data = {
            user_id: userId,
            page_name: pageInfo.name,
            page_url: pageInfo.url,
            time_spent: activeTime,
            click_count: clickCount
        };
        
        // Determine the correct path to the backend script
        // The script is always in /main/, so we need to go up one level to reach /backand/
        const scriptPath = (function() {
            // Try to find the script element to determine its location
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                if (scripts[i].src && scripts[i].src.includes('page_time_tracker.js')) {
                    try {
                        const scriptUrl = new URL(scripts[i].src, window.location.href);
                        const scriptPath = scriptUrl.pathname;
                        // Remove the filename and /main/ directory
                        const basePath = scriptPath.substring(0, scriptPath.lastIndexOf('/'));
                        const rootPath = basePath.substring(0, basePath.lastIndexOf('/'));
                        return rootPath + '/backand/page_activity.php';
                    } catch (e) {
                        // If URL parsing fails, use fallback
                        break;
                    }
                }
            }
            // Fallback: determine from current page location
            const path = window.location.pathname;
            if (path.includes('/main/')) return '../backand/page_activity.php';
            if (path.includes('/search/')) return '../backand/page_activity.php';
            if (path.includes('/auth/')) return '../backand/page_activity.php';
            if (path.includes('/user/')) return '../backand/page_activity.php';
            if (path.includes('/admin/')) return '../backand/page_activity.php';
            // Default fallback
            return '../backand/page_activity.php';
        })();
        
        // Use sendBeacon for reliable delivery even if page is closing
        if (navigator.sendBeacon) {
            const formData = new URLSearchParams(data);
            navigator.sendBeacon(scriptPath, formData);
        } else {
            // Fallback to fetch if sendBeacon is not available
            fetch(scriptPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data),
                keepalive: true
            }).catch(function(error) {
                console.error('Error sending tracking data:', error);
            });
        }
    }
    
    // Send data when page is about to unload
    window.addEventListener('beforeunload', function() {
        clearInterval(visibilityCheckInterval);
        sendTrackingData();
    });
    
    // Also send data periodically (every 30 seconds) for long sessions
    setInterval(function() {
        if (isActive && (Date.now() - startTime) > 30000) {
            sendTrackingData();
            // Reset counters but keep tracking
            startTime = Date.now();
            clickCount = 0;
        }
    }, 30000);
    
    // Send data when page is hidden (mobile browsers)
    document.addEventListener('pagehide', function() {
        clearInterval(visibilityCheckInterval);
        sendTrackingData();
    });
    
})();

