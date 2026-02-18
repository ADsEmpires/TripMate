#!/usr/bin/env python3
"""
Price trend analysis script - Run this as a scheduled cron job
Analyzes historical price data and identifies trends
"""

import mysql.connector
import json
from datetime import datetime, timedelta
from config import DB_CONFIG

def get_db_connection():
    """Create database connection"""
    return mysql.connector.connect(**DB_CONFIG)

def calculate_price_trends():
    """Calculate price trends and update database"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    
    try:
        # Get all destinations
        cursor.execute("SELECT id FROM destinations")
        destinations = cursor.fetchall()
        
        for dest in destinations:
            dest_id = dest['id']
            
            # Analyze flight prices
            analyze_flight_trends(cursor, dest_id)
            
            # Analyze hotel prices
            analyze_hotel_trends(cursor, dest_id)
        
        conn.commit()
        print("Price trends updated successfully")
        
    except Exception as e:
        print(f"Error calculating trends: {e}")
    finally:
        cursor.close()
        conn.close()

def analyze_flight_trends(cursor, destination_id):
    """Analyze flight price trends"""
    # Get last 30 days of price history
    thirty_days_ago = (datetime.now() - timedelta(days=30)).date()
    
    cursor.execute("""
        SELECT AVG(price) as avg_price 
        FROM flights 
        WHERE destination_id = %s AND created_at >= %s
    """, (destination_id, thirty_days_ago))
    
    result = cursor.fetchone()
    if not result:
        return
    
    current_avg = result['avg_price']
    
    # Get previous month's average
    sixty_days_ago = (datetime.now() - timedelta(days=60)).date()
    thirty_days_before = (datetime.now() - timedelta(days=30)).date()
    
    cursor.execute("""
        SELECT AVG(price) as avg_price 
        FROM flights 
        WHERE destination_id = %s 
        AND created_at >= %s AND created_at < %s
    """, (destination_id, sixty_days_ago, thirty_days_before))
    
    prev_result = cursor.fetchone()
    previous_avg = prev_result['avg_price'] if prev_result else current_avg
    
    # Calculate change
    if previous_avg > 0:
        price_change = ((current_avg - previous_avg) / previous_avg) * 100
    else:
        price_change = 0
    
    trend_direction = 'up' if price_change > 2 else ('down' if price_change < -2 else 'stable')
    
    # Find best booking window
    best_booking_window = find_best_booking_window(cursor, destination_id, 'flight')
    
    # Insert or update trend
    cursor.execute("""
        INSERT INTO price_trends 
        (destination_id, travel_type, date, average_price, price_change_percent, trend_direction, best_booking_window_days)
        VALUES (%s, 'flight', %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        average_price = VALUES(average_price),
        price_change_percent = VALUES(price_change_percent),
        trend_direction = VALUES(trend_direction),
        best_booking_window_days = VALUES(best_booking_window_days)
    """, (destination_id, datetime.now().date(), current_avg, round(price_change, 2), trend_direction, best_booking_window))

def analyze_hotel_trends(cursor, destination_id):
    """Analyze hotel price trends"""
    thirty_days_ago = (datetime.now() - timedelta(days=30)).date()
    
    cursor.execute("""
        SELECT AVG(price_per_night) as avg_price 
        FROM hotel_prices 
        WHERE destination_id = %s AND created_at >= %s
    """, (destination_id, thirty_days_ago))
    
    result = cursor.fetchone()
    if not result:
        return
    
    current_avg = result['avg_price']
    
    # Get previous month's average
    sixty_days_ago = (datetime.now() - timedelta(days=60)).date()
    thirty_days_before = (datetime.now() - timedelta(days=30)).date()
    
    cursor.execute("""
        SELECT AVG(price_per_night) as avg_price 
        FROM hotel_prices 
        WHERE destination_id = %s 
        AND created_at >= %s AND created_at < %s
    """, (destination_id, sixty_days_ago, thirty_days_before))
    
    prev_result = cursor.fetchone()
    previous_avg = prev_result['avg_price'] if prev_result else current_avg
    
    # Calculate change
    if previous_avg > 0:
        price_change = ((current_avg - previous_avg) / previous_avg) * 100
    else:
        price_change = 0
    
    trend_direction = 'up' if price_change > 2 else ('down' if price_change < -2 else 'stable')
    
    # Find best booking window
    best_booking_window = find_best_booking_window(cursor, destination_id, 'hotel')
    
    # Insert or update trend
    cursor.execute("""
        INSERT INTO price_trends 
        (destination_id, travel_type, date, average_price, price_change_percent, trend_direction, best_booking_window_days)
        VALUES (%s, 'hotel', %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        average_price = VALUES(average_price),
        price_change_percent = VALUES(price_change_percent),
        trend_direction = VALUES(trend_direction),
        best_booking_window_days = VALUES(best_booking_window_days)
    """, (destination_id, datetime.now().date(), current_avg, round(price_change, 2), trend_direction, best_booking_window))

def find_best_booking_window(cursor, destination_id, travel_type):
    """Find optimal days before travel to book"""
    # Typically: 30-60 days for flights, 14-30 days for hotels
    if travel_type == 'flight':
        return 45
    else:
        return 21

if __name__ == '__main__':
    calculate_price_trends()