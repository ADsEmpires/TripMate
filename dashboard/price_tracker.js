// Price Tracker functionality
let priceChart = null;
const API_BASE = '../actions';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDestinations();
    loadActiveAlerts();
    loadPriceTrends();
    loadBestDeals();
    updateStats();
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('departureDate').min = today;
    document.getElementById('returnDate').min = today;
    
    // Form submission
    document.getElementById('priceAlertForm').addEventListener('submit', handleCreateAlert);
});

// Load destinations for dropdown
function loadDestinations() {
    fetch(`${API_BASE}/get_destinations.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const select = document.getElementById('destination');
                data.destinations.forEach(dest => {
                    const option = document.createElement('option');
                    option.value = dest.id;
                    option.textContent = dest.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading destinations:', error));
}

// Handle form submission
function handleCreateAlert(e) {
    e.preventDefault();
    
    const formData = {
        destination_id: parseInt(document.getElementById('destination').value),
        alert_type: document.getElementById('alertType').value,
        travel_dates_from: document.getElementById('departureDate').value,
        travel_dates_to: document.getElementById('returnDate').value,
        max_price: parseFloat(document.getElementById('maxPrice').value),
        alert_frequency: document.getElementById('frequency').value,
        notification_method: document.getElementById('notificationMethod').value
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
            loadActiveAlerts();
            updateStats();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error creating alert', 'error');
        console.error('Error:', error);
    });
}

// Load and display active alerts
function loadActiveAlerts() {
    fetch(`${API_BASE}/get_user_alerts.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.alerts.length > 0) {
                displayAlerts(data.alerts);
            } else {
                document.getElementById('alertsList').innerHTML = `
                    <div class="empty-state">
                        <p>No active alerts yet. Create one above to get started!</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading alerts:', error));
}

// Display alerts
function displayAlerts(alerts) {
    const container = document.getElementById('alertsList');
    container.innerHTML = alerts.map(alert => `
        <div class="alert-item">
            <div class="alert-item-content">
                <h3>${alert.destination_name}</h3>
                <div class="alert-item-details">
                    <div class="alert-item-detail">
                        <span>📅</span>
                        <span>${formatDate(alert.travel_dates_from)} to ${formatDate(alert.travel_dates_to)}</span>
                    </div>
                    <div class="alert-item-detail">
                        <span>💰</span>
                        <span>Alert at $${alert.max_price}</span>
                    </div>
                    <div class="alert-item-detail">
                        <span>📬</span>
                        <span>${alert.alert_frequency}</span>
                    </div>
                    <div class="alert-item-detail">
                        <span>${alert.is_active ? '🟢' : '⭕'}</span>
                        <span>${alert.is_active ? 'Active' : 'Inactive'}</span>
                    </div>
                </div>
            </div>
            <div class="alert-item-actions">
                <button class="btn-secondary" onclick="editAlert(${alert.id})">Edit</button>
                <button class="btn-secondary" onclick="deleteAlert(${alert.id})">Delete</button>
            </div>
        </div>
    `).join('');
}

// Load price trends and chart
function loadPriceTrends() {
    const destinationId = document.getElementById('destination').value;
    
    if (!destinationId) return;
    
    fetch(`${API_BASE}/get_prices.php?destination_id=${destinationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
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
    
    // Destroy existing chart if it exists
    if (priceChart) {
        priceChart.destroy();
    }
    
    const labels = trends.map(t => formatDate(t.date));
    const flightPrices = trends.filter(t => t.travel_type === 'flight').map(t => t.average_price);
    const hotelPrices = trends.filter(t => t.travel_type === 'hotel').map(t => t.average_price);
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '✈️ Flight Prices',
                    data: flightPrices,
                    borderColor: '#ff6600',
                    backgroundColor: 'rgba(255, 102, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#ff6600'
                },
                {
                    label: '🏨 Hotel Prices',
                    data: hotelPrices,
                    borderColor: '#00c2cb',
                    backgroundColor: 'rgba(0, 194, 203, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#00c2cb'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Price (USD)'
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
            if (data.status === 'success' && data.deals.length > 0) {
                displayDeals(data.deals);
            }
        })
        .catch(error => console.error('Error loading deals:', error));
}

// Display deals
function displayDeals(deals) {
    const container = document.getElementById('bestDeals');
    container.innerHTML = deals.map(deal => `
        <div class="deal-card">
            <h3>${deal.destination_name}</h3>
            <div class="deal-price">$${deal.price}</div>
            <div class="deal-savings">Save up to ${deal.price_drop}%</div>
            <div class="deal-info">
                <div>📅 ${formatDate(deal.travel_date)}</div>
                <div>⏱️ Best booked ${deal.best_booking_window} days before</div>
            </div>
            <button class="deal-button" onclick="bookNow('${deal.destination_id}')">Book Now</button>
        </div>
    `).join('');
}

// Update statistics
function updateStats() {
    fetch(`${API_BASE}/get_price_stats.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('activeAlerts').textContent = data.active_alerts;
                document.getElementById('pricesDown').textContent = data.prices_down;
                document.getElementById('totalSavings').textContent = '$' + data.total_savings;
                document.getElementById('bestTime').textContent = data.best_booking_days + ' days';
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
            loadActiveAlerts();
            updateStats();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Utility functions
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('minimized');
}

function bookNow(destinationId) {
    window.location.href = `search.html?destination=${destinationId}`;
}