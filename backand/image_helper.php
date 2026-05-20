<?php

/**
 * image_helper.php - Helper functions for image handling
 * Simplified version - we're now handling images directly in search.php
 */

/**
 * Decode images field from database
 * @param string|array|null $imagesField The images field value
 * @return array Array of image filenames
 */
function decodeImagesField($imagesField)
{
    if (empty($imagesField)) {
        return [];
    }

    // If it's already an array
    if (is_array($imagesField)) {
        return $imagesField;
    }

    // Try to decode JSON
    $decoded = json_decode($imagesField, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    // Try to handle CSV format
    if (strpos($imagesField, ',') !== false) {
        return array_map('trim', explode(',', $imagesField));
    }

    // Single image
    return [$imagesField];
}

/**
 * Get destination image URL - matches admin panel pattern
 * NOTE: This function is defined in database/app_config.php with different signature
 * Only define here if it doesn't exist in app_config.php
 * @param string $filename The image filename (with unique prefix)
 * @param string $baseUrl Base URL
 * @return string Full image URL
 */
if (!function_exists('getDestinationImageUrl')) {
    function getDestinationImageUrl($filename, $baseUrl = null)
    {
        if (empty($filename)) {
            $placeholder = $baseUrl ? $baseUrl . '/images/no-image.jpg' : '/images/no-image.jpg';
            return $placeholder;
        }

        // If it's already a full URL
        if (preg_match('/^https?:///', $filename)) {
            return $filename;
        }

        // Clean the filename (remove any path)
        $clean_filename = basename($filename);

        // Return the path - use provided baseUrl or construct from current location
        if ($baseUrl) {
            return $baseUrl . '/uploads/destinations/' . $clean_filename;
        }

        // Default to relative path for global extraction
        return '/uploads/destinations/' . $clean_filename;
    }
}

/**
 * Get cuisine images mapping
 * @param string|array|null $cuisineImagesField The cuisine images field
 * @param string $baseUrl Base URL (optional, uses relative paths if not provided)
 * @return array Associative array of cuisine name => image URL
 */
function getCuisineImages($cuisineImagesField, $baseUrl = null)
{
    $result = [];

    if (empty($cuisineImagesField)) {
        return $result;
    }

    // Decode JSON if it's a string
    $data = $cuisineImagesField;
    if (is_string($cuisineImagesField)) {
        $data = json_decode($cuisineImagesField, true);
        if (!is_array($data)) {
            return $result;
        }
    }

    // Process each cuisine image
    foreach ($data as $cuisineName => $imagePath) {
        if (!empty($imagePath)) {
            $clean_path = basename($imagePath);
            if ($baseUrl) {
                $result[$cuisineName] = $baseUrl . '/uploads/cuisines/' . $clean_path;
            } else {
                // Default to relative path for global extraction
                $result[$cuisineName] = '/uploads/cuisines/' . $clean_path;
            }
        }
    }

    return $result;
}

/**
 * Get destination cover image
 * @param array $destination Destination data
 * @param string $baseUrl Base URL (optional, uses relative paths if not provided)
 * @return string Cover image URL
 */
function getDestinationCoverImage($destination, $baseUrl = null)
{
    // Check for profile_pic first
    if (!empty($destination['profile_pic'])) {
        return getDestinationImageUrl($destination['profile_pic'], $baseUrl);
    }

    // Check images field
    $images = decodeImagesField($destination['images'] ?? '');
    if (!empty($images)) {
        return getDestinationImageUrl($images[0], $baseUrl);
    }

    // Fallback to placeholder
    $placeholder = $baseUrl ? $baseUrl . '/images/no-image.jpg' : '/images/no-image.jpg';
    return $placeholder;
}
