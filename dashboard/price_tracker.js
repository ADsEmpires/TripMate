const API_BASE = '../actions';
let priceChart = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDestinations();
    loadUserAlerts();
    updateStats();
    loadBestDeals();
    
    // Attach form listener
    document.getElementById('priceAlertForm').addEventListener('submit', handleCreateAlert);
    
    // Auto-refresh every 5 minutes
    setInterval(updateStats, 5 * 60 * 1000);
});

// Load destinations
function loadDestinations() {
    fetch(`${API_BASE}/get_destinations.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.destinations) {
                const select = document.getElementById('destination');
                data.destinations.forEach(dest => {
                    const option = document.createElement('option');
                    option.value = dest.id;
                    option.textContent = `${dest.name} - ${dest.location}`;
                    select.appendChild(option);
                });
                
                // Load price trends on destination change
                select.addEventListener('change', function() {
                    if (this.value) {
                        loadPriceTrends(this.value);
                    }
                });
            }
        })
        .catch(error => console.error('Error loading destinations:', error));
}

// Create price alert
function handleCreateAlert(e) {
    e.preventDefault();
    
    const destinationId = document.getElementById('destination').value;
    const departureDate = document.getElementById('departureDate').value;
    const returnDate = document.getElementById('returnDate').value;
    const maxPrice = document.getElementById('maxPrice').value;
    const frequency = document.getElementById('frequency').value;
    const method = document.getElementById('notificationMethod').value;
    const alertType = document.getElementById('alertType').value;
    
    if (!destinationId || !departureDate || !returnDate || !maxPrice) {
        showNotification('Please fill in all required fields', 'warning');
        return;
    }
    
    if (new Date(returnDate) <= new Date(departureDate)) {
        showNotification('Return date must be after departure date', 'error');
        return;
    }
    
    const formData = {
        destination_id: parseInt(destinationId),
        alert_type: alertType,
        travel_dates_from: departureDate,
        travel_dates_to: returnDate,
        max_price: parseFloat(maxPrice),
        alert_frequency: frequency,
        notification_method: method
    };
    
    fetch(`${API_BASE}/track_prices.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Price alert created successfully!', 'success');
            document.getElementById('priceAlertForm').reset();
            loadUserAlerts();
            updateStats();
        } else {
            showNotification(data.message || 'Failed to create alert', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error creating alert', 'error');
    });
}

// Load user alerts
function loadUserAlerts() {
    fetch(`${API_BASE}/get_user_alerts.php`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('alertsList');
            
            if (data.status === 'success' && data.alerts && data.alerts.length > 0) {
                let html = '';
                data.alerts.forEach(alert => {
                    const status = alert.is_active ? '🟢 Active' : '⭕ Inactive';
                    html += `
                        <div class="alert-item">
                            <div class="alert-item-content">
                                <h3>${escapeHtml(alert.destination_name)}</h3>
                                <div class="alert-item-details">
                                    <div class="alert-item-detail">
                                        <i class="fas fa-calendar"></i>
                                        ${formatDate(alert.travel_dates_from)} to ${formatDate(alert.travel_dates_to)}
                                    </div>
                                    <div class="alert-item-detail">
                                        <i class="fas fa-money-bill"></i>
                                        Alert at $${parseFloat(alert.max_price).toFixed(2)}
                                    </div>
                                    <div class="alert-item-detail">
                                        <i class="fas fa-bell"></i>
                                        ${capitalizeFirstLetter(alert.alert_frequency)}
                                    </div>
                                    <div class="alert-item-detail">
                                        ${status}
                                    </div>
                                </div>
                            </div>
                            <div class="alert-item-actions">
                                <button class="btn-secondary btn-small" onclick="editAlert(${alert.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-secondary btn-small" onclick="deleteAlert(${alert.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell"></i>
                        <p>No active alerts yet. Create one above to get started!</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading alerts:', error));
}

// Load price trends
function loadPriceTrends(destinationId) {
    fetch(`${API_BASE}/get_prices.php?destination_id=${destinationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data && data.data.price_trends) {
                displayPriceChart(data.data.price_trends);
            }
        })
        .catch(error => console.error('Error loading price trends:', error));
}

// Display price chart
function displayPriceChart(trends) {
    if (!trends || trends.length === 0) return;
    
    const ctx = document.getElementById('priceChart');
    if (!ctx) return;
    
    // Destroy existing chart
    if (priceChart) {
        priceChart.destroy();
    }
    
    const flightTrends = trends.filter(t => t.travel_type === 'flight');
    const hotelTrends = trends.filter(t => t.travel_type === 'hotel');
    
    const labels = trends.map(t => formatDate(t.date));
    const flightData = flightTrends.map(t => parseFloat(t.average_price));
    const hotelData = hotelTrends.map(t => parseFloat(t.average_price));
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '✈️ Flight Prices',
                    data: flightData,
                    borderColor: '#ff6600',
                    backgroundColor: 'rgba(255, 102, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#ff6600',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: '🏨 Hotel Prices',
                    data: hotelData,
                    borderColor: '#00c2cb',
                    backgroundColor: 'rgba(0, 194, 203, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#00c2cb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: 600 },
                        padding: 20,
                        usePointStyle: true
                    }
                },
                filler: {
                    propagate: true
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Price (USD)',
                        font: { size: 12, weight: 600 }
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

// Load best deals
function loadBestDeals() {
    fetch(`${API_BASE}/get_best_deals.php`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('bestDeals');
            
            if (data.status === 'success' && data.deals && data.deals.length > 0) {
                let html = '';
                data.deals.forEach(deal => {
                    html += `
                        <div class="deal-card">
                            <h3>${escapeHtml(deal.destination_name)}</h3>
                            <div class="deal-price">$${parseFloat(deal.price).toFixed(2)}</div>
                            <div class="deal-savings">
                                <i class="fas fa-arrow-down"></i>
                                Save up to ${deal.price_drop}%
                            </div>
                            <div class="deal-info">
                                <div><i class="fas fa-calendar"></i> ${formatDate(deal.travel_date)}</div>
                                <div><i class="fas fa-clock"></i> Book ${deal.best_booking_window} days before</div>
                            </div>
                            <button class="deal-button" onclick="bookNow(${deal.destination_id})">
                                <i class="fas fa-arrow-right"></i> Book Now
                            </button>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No deals available. Set price alerts to discover amazing offers!</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading deals:', error));
}

// Update statistics
function updateStats() {
    fetch(`${API_BASE}/get_price_stats.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('activeAlerts').textContent = data.active_alerts || 0;
                document.getElementById('pricesDown').textContent = data.prices_down || 0;
                document.getElementById('totalSavings').textContent = '$' + (data.total_savings || 0);
                document.getElementById('bestTime').textContent = (data.best_booking_days || 30) + ' days';
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Delete alert
function deleteAlert(alertId) {
    if (!confirm('Are you sure you want to delete this alert?')) return;
    
    fetch(`${API_BASE}/delete_alert.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ alert_id: alertId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Alert deleted successfully', 'success');
            loadUserAlerts();
            updateStats();
        } else {
            showNotification('Failed to delete alert', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting alert', 'error');
    });
}

// Edit alert (placeholder)
function editAlert(alertId) {
    showNotification('Edit feature coming soon!', 'warning');
}

// Book now
function bookNow(destinationId) {
    window.location.href = `../search/search.html?destination=${destinationId}`;
}

// Utility functions
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}