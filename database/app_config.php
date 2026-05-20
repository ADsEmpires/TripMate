<?php
// config/app_config.php
// Shared configuration and utility functions for TripMate

// ============================================
// CRITICAL: Do NOT include dbconfig.php here if session_init.php already includes it
// This prevents circular dependencies and duplicate function declarations
// ============================================

// ============================================
// Base URL Detection (Single Source of Truth)
// ============================================
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        static $base_url = null;
        
        if ($base_url !== null) {
            return $base_url;
        }
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        
        // Remove the script name and any subdirectory path to get base
        $path = dirname($script_name);
        
        // If we're in /user/ directory, go up one level
        $path = preg_replace('#/user$#', '', $path);
        $path = preg_replace('#/admin$#', '', $path);
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        $base_url = $protocol . "://" . $host . $path;
        return $base_url;
    }
}

// ============================================
// Image URL Helper Functions
// ============================================
if (!function_exists('getDestinationImageUrl')) {
    function getDestinationImageUrl($destination, $base_url = null) {
        if ($base_url === null) {
            $base_url = getBaseUrl();
        }
        
        $default_image = $base_url . '/images/no-image.jpg';
        
        // Try to get images from 'images' column first
        if (!empty($destination['images'])) {
            $decoded_images = json_decode($destination['images'], true);
            if (is_array($decoded_images) && !empty($decoded_images)) {
                return $base_url . '/uploads/destinations/' . basename($decoded_images[0]);
            }
        }
        
        // Fallback to image_urls column
        if (!empty($destination['image_urls'])) {
            $decoded_images = json_decode($destination['image_urls'], true);
            if (is_array($decoded_images) && !empty($decoded_images)) {
                return $base_url . '/uploads/destinations/' . basename($decoded_images[0]);
            }
        }
        
        // Fallback to profile_pic
        if (!empty($destination['profile_pic'])) {
            return $base_url . '/uploads/destinations/' . basename($destination['profile_pic']);
        }
        
        return $default_image;
    }
}

if (!function_exists('getHotelImageUrl')) {
    function getHotelImageUrl($hotel, $base_url = null) {
        if ($base_url === null) {
            $base_url = getBaseUrl();
        }
        
        if (!empty($hotel['image_url'])) {
            $image_path = $hotel['image_url'];
            if (preg_match('/^https?:\/\//', $image_path)) {
                return $image_path;
            }
            return $base_url . '/uploads/hotels/' . basename($image_path);
        }
        
        return $base_url . '/images/hotel-placeholder.jpg';
    }
}

if (!function_exists('getCuisineImageUrl')) {
    function getCuisineImageUrl($image_path, $base_url = null) {
        if ($base_url === null) {
            $base_url = getBaseUrl();
        }
        
        if (empty($image_path)) {
            return $base_url . '/images/cuisine-placeholder.png';
        }
        
        return $base_url . '/uploads/cuisines/' . basename($image_path);
    }
}

// ============================================
// JSON Field Helpers
// ============================================
if (!function_exists('safeJsonDecode')) {
    function safeJsonDecode($json, $default = []) {
        if (empty($json)) {
            return $default;
        }
        
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $default;
    }
}

if (!function_exists('normalizeListField')) {
    function normalizeListField($raw) {
        if (empty($raw)) return [];
        $trim = trim($raw);
        if (strpos($trim, '[') === 0) {
            $arr = json_decode($trim, true);
            return is_array($arr) ? array_values($arr) : [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

// ============================================
// User Helper Functions
// ============================================
if (!function_exists('isUserLoggedIn')) {
    function isUserLoggedIn() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) || isset($_SESSION['userid']);
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? $_SESSION['userid'] ?? null;
    }
}

if (!function_exists('getCurrentUserName')) {
    function getCurrentUserName() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Guest';
    }
}

// ============================================
// FIXED: CSRF Protection with function_exists check
// These functions are also defined in session_init.php
// The function_exists checks prevent redeclaration errors
// ============================================
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('getCSRFTokenField')) {
    function getCSRFTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// ============================================
// Date/Time Helpers
// ============================================
if (!function_exists('formatDateForDisplay')) {
    function formatDateForDisplay($date, $format = 'M d, Y') {
        if (empty($date)) return '';
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return $diff . ' seconds ago';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' days ago';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' months ago';
        } else {
            return floor($diff / 31536000) . ' years ago';
        }
    }
}

// ============================================
// Security Helpers
// ============================================
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('generateSecureToken')) {
    function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}