<?php
// test_unsplash.php - Test Unsplash API integration
require_once 'fetch_images.php';

// Test configuration
$test_queries = [
    'Paris France',
    'Taj Mahal India', 
    'Beach Goa',
    'Mountain Nepal'
];

foreach ($test_queries as $query) {
    echo "Testing: $query\n";
    echo str_repeat("-", 50) . "\n";
    
    $fetcher = new UnsplashImageFetcher(UNSPLASH_ACCESS_KEY);
    $result = $fetcher->fetchImages($query, 3);
    
    if (isset($result['error'])) {
        echo "ERROR: " . $result['error'] . "\n";
    } else {
        echo "SUCCESS: Found " . count($result['images']) . " images\n";
        foreach ($result['images'] as $image) {
            echo "  - " . $image['description'] . " (" . $image['photographer']['name'] . ")\n";
        }
    }
    echo "\n";
}