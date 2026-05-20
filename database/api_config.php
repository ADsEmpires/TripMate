<?php
// database/api_config.php
// Store API keys outside webroot - place this file in a secure directory

// Load environment variables (for production)
// In development, you can define these here, but use environment variables in production

// Google API Configuration
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: 'YOUR_GOOGLE_MAPS_API_KEY');
define('GOOGLE_CUSTOM_SEARCH_API_KEY', getenv('GOOGLE_CUSTOM_SEARCH_API_KEY') ?: 'YOUR_GOOGLE_CSE_API_KEY');
define('GOOGLE_CUSTOM_SEARCH_ENGINE_ID', getenv('GOOGLE_CSE_ID') ?: 'YOUR_GOOGLE_CSE_ID');

// Weather API Configuration (OpenWeatherMap)
define('OPENWEATHER_API_KEY', getenv('OPENWEATHER_API_KEY') ?: 'YOUR_OPENWEATHER_API_KEY');

// Exchange Rate API (free tier - no key required for basic)
define('EXCHANGE_RATE_API_URL', 'https://api.exchangerate.host/latest');

// Function to safely get API key with fallback
function getApiKey($keyName, $fallback = '')
{
    $value = defined($keyName) ? constant($keyName) : $fallback;

    // In production, never use fallback keys
    if ($_SERVER['SERVER_NAME'] !== 'localhost' && $value === $fallback) {
        error_log("API key {$keyName} not configured in production environment");
        return null;
    }

    return $value;
}
