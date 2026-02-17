<?php
/**
 * Unsplash Image API Handler
 * File: actions/fetch_images.php
 * 
 * This PHP script acts as a server-side proxy to fetch images from Unsplash
 * Keeps API key secure and handles rate limiting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: GET, POST');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for debugging

// Configuration
define('UNSPLASH_ACCESS_KEY', 'wipMl4L19Hpszv2ur_Z4UMWnkLMpGo3sCJmeOxisQ8g'); // REPLACE WITH YOUR ACTUAL KEY
define('UNSPLASH_API_URL', 'https://api.unsplash.com');
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 86400); // 24 hours

// Simple logging function
function log_message($message) {
    $log_file = __DIR__ . '/../logs/unsplash_api.log';
    if (!is_dir(dirname($log_file))) {
        @mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

class UnsplashImageFetcher {
    private $accessKey;
    private $cacheDir;
    
    public function __construct($accessKey) {
        $this->accessKey = $accessKey;
        $this->cacheDir = __DIR__ . '/../cache/images/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Fetch images from Unsplash API
     */
    public function fetchImages($query, $count = 12) {
        // Sanitize inputs
        $query = trim($query);
        $count = min(max(1, intval($count)), 30);
        
        if (empty($query)) {
            return ['error' => 'Query parameter is required'];
        }
        
        if (empty($this->accessKey)) {
            return ['error' => 'Unsplash API key not configured'];
        }
        
        // Check cache first
        if (CACHE_ENABLED) {
            $cached = $this->getFromCache($query, $count);
            if ($cached !== null) {
                log_message("Cache hit for query: $query");
                return $cached;
            }
        }
        
        // Build API request
        $url = UNSPLASH_API_URL . '/search/photos?' . http_build_query([
            'query' => $query,
            'per_page' => $count,
            'orientation' => 'landscape',
            'content_filter' => 'high'
        ]);
        
        log_message("Fetching from Unsplash: $query");
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Client-ID ' . $this->accessKey,
                'Accept-Version: v1'
            ],
            CURLOPT_USERAGENT => 'TripMate/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            log_message("cURL error for $query: $error");
            return ['error' => 'Network error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            $error_msg = $this->handleHttpError($httpCode, $response);
            log_message("HTTP error $httpCode for $query: $error_msg");
            return ['error' => $error_msg];
        }
        
        // Parse response
        $data = json_decode($response, true);
        if (!$data || !isset($data['results'])) {
            log_message("Invalid API response for $query");
            return ['error' => 'Invalid API response'];
        }
        
        // Transform data
        $images = $this->transformImages($data['results']);
        
        if (empty($images)) {
            log_message("No images found for query: $query");
            return ['error' => 'No images found for this destination'];
        }
        
        // Cache the result
        if (CACHE_ENABLED) {
            $this->saveToCache($query, $count, $images);
        }
        
        log_message("Successfully fetched " . count($images) . " images for: $query");
        
        return [
            'success' => true,
            'images' => $images,
            'total' => $data['total'] ?? count($images)
        ];
    }
    
    /**
     * Transform Unsplash API response to our format
     */
    private function transformImages($photos) {
        $images = [];
        
        foreach ($photos as $photo) {
            $images[] = [
                'id' => $photo['id'],
                'urls' => [
                    'full' => $photo['urls']['full'],
                    'regular' => $photo['urls']['regular'],
                    'small' => $photo['urls']['small'],
                    'thumb' => $photo['urls']['thumb']
                ],
                'description' => $photo['description'] ?? $photo['alt_description'] ?? '',
                'photographer' => [
                    'name' => $photo['user']['name'],
                    'url' => $photo['user']['links']['html'] . '?utm_source=TripMate&utm_medium=referral'
                ],
                'download_location' => $photo['links']['download_location'],
                'dimensions' => [
                    'width' => $photo['width'],
                    'height' => $photo['height']
                ],
                'color' => $photo['color'] ?? '#cccccc'
            ];
        }
        
        return $images;
    }
    
    /**
     * Trigger download tracking (required by Unsplash API guidelines)
     */
    public function triggerDownload($downloadLocation) {
        if (empty($downloadLocation) || empty($this->accessKey)) {
            return false;
        }
        
        log_message("Triggering download: $downloadLocation");
        
        $ch = curl_init($downloadLocation);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Authorization: Client-ID ' . $this->accessKey
            ]
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        log_message("Download trigger response: $httpCode");
        
        return $httpCode === 200;
    }
    
    /**
     * Handle HTTP errors
     */
    private function handleHttpError($code, $response) {
        $errors = [
            401 => 'Invalid Unsplash API key. Please configure a valid access key.',
            403 => 'API rate limit exceeded or access denied. Please try again later.',
            404 => 'No images found for this query',
            500 => 'Unsplash server error. Please try again later.',
            503 => 'Unsplash service unavailable. Please try again later.'
        ];
        
        $message = $errors[$code] ?? 'API error: ' . $code;
        
        // Try to get more details from response
        $data = @json_decode($response, true);
        if ($data && isset($data['errors'])) {
            $message .= ' - ' . implode(', ', $data['errors']);
        }
        
        return $message;
    }
    
    /**
     * Get cached images
     */
    private function getFromCache($query, $count) {
        $cacheKey = md5($query . '_' . $count);
        $cacheFile = $this->cacheDir . $cacheKey . '.json';
        
        if (file_exists($cacheFile)) {
            $age = time() - filemtime($cacheFile);
            if ($age < CACHE_DURATION) {
                $content = file_get_contents($cacheFile);
                return json_decode($content, true);
            } else {
                // Delete expired cache
                unlink($cacheFile);
            }
        }
        
        return null;
    }
    
    /**
     * Save images to cache
     */
    private function saveToCache($query, $count, $images) {
        $cacheKey = md5($query . '_' . $count);
        $cacheFile = $this->cacheDir . $cacheKey . '.json';
        
        file_put_contents($cacheFile, json_encode([
            'success' => true,
            'images' => $images,
            'cached_at' => time()
        ]));
    }
}

// Handle the request
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = $_GET['query'] ?? '';
        $count = $_GET['count'] ?? 12;
        
        if (empty($query)) {
            echo json_encode(['error' => 'Query parameter is required']);
            exit;
        }
        
        $fetcher = new UnsplashImageFetcher(UNSPLASH_ACCESS_KEY);
        $result = $fetcher->fetchImages($query, $count);
        
        echo json_encode($result);
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_download'])) {
        // Trigger download tracking
        $downloadLocation = $_POST['download_location'] ?? '';
        $fetcher = new UnsplashImageFetcher(UNSPLASH_ACCESS_KEY);
        $success = $fetcher->triggerDownload($downloadLocation);
        
        echo json_encode(['success' => $success]);
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    log_message("Unhandled exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}