#!/usr/bin/env python3
"""
Unsplash Image Fetcher for TripMate
This script fetches images from Unsplash API based on destination queries
"""

import requests
import json
import sys
from typing import List, Dict, Optional

class UnsplashImageFetcher:
    """
    Fetches images from Unsplash API for destination galleries
    """
    
    def __init__(self, access_key: str):
        """
        Initialize the Unsplash fetcher
        
        Args:
            access_key: Your Unsplash API access key
        """
        self.access_key = access_key
        self.base_url = "https://api.unsplash.com"
        self.headers = {
            "Authorization": f"Client-ID {access_key}"
        }
    
    def fetch_images(self, query: str, count: int = 12) -> List[Dict]:
        """
        Fetch images based on search query
        
        Args:
            query: Search term (e.g., "Paris France landmarks")
            count: Number of images to fetch (max 30 per request)
            
        Returns:
            List of image data dictionaries
        """
        try:
            endpoint = f"{self.base_url}/search/photos"
            params = {
                "query": query,
                "per_page": min(count, 30),
                "orientation": "landscape",
                "content_filter": "high"
            }
            
            response = requests.get(
                endpoint,
                headers=self.headers,
                params=params,
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                images = []
                
                for photo in data.get('results', []):
                    images.append({
                        'id': photo['id'],
                        'url_full': photo['urls']['full'],
                        'url_regular': photo['urls']['regular'],
                        'url_small': photo['urls']['small'],
                        'url_thumb': photo['urls']['thumb'],
                        'description': photo.get('description') or photo.get('alt_description', ''),
                        'photographer': photo['user']['name'],
                        'photographer_url': photo['user']['links']['html'],
                        'download_link': photo['links']['download_location'],
                        'width': photo['width'],
                        'height': photo['height']
                    })
                
                return images
            
            elif response.status_code == 401:
                return {'error': 'Invalid API key. Please check your Unsplash access key.'}
            elif response.status_code == 403:
                return {'error': 'API rate limit exceeded. Please try again later.'}
            else:
                return {'error': f'API error: {response.status_code}'}
                
        except requests.exceptions.Timeout:
            return {'error': 'Request timeout. Please try again.'}
        except requests.exceptions.RequestException as e:
            return {'error': f'Network error: {str(e)}'}
        except Exception as e:
            return {'error': f'Unexpected error: {str(e)}'}
    
    def trigger_download(self, download_location: str):
        """
        Trigger download endpoint as required by Unsplash API guidelines
        This must be called when an image is displayed to comply with API terms
        
        Args:
            download_location: The download_location URL from the photo data
        """
        try:
            requests.get(
                download_location,
                headers=self.headers,
                timeout=5
            )
        except:
            # Silently fail - this is just for tracking
            pass


def main():
    """
    Command-line interface for the image fetcher
    """
    if len(sys.argv) < 3:
        print(json.dumps({
            'error': 'Usage: python fetch_images.py <API_KEY> <QUERY> [COUNT]'
        }))
        sys.exit(1)
    
    api_key = sys.argv[1]
    query = sys.argv[2]
    count = int(sys.argv[3]) if len(sys.argv) > 3 else 12
    
    fetcher = UnsplashImageFetcher(api_key)
    images = fetcher.fetch_images(query, count)
    
    print(json.dumps(images, indent=2))


if __name__ == "__main__":
    main()
