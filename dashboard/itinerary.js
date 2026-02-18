const API_BASE = '../actions';
const DESTINATIONS_API = '../actions/get_destinations.php';
const ITINERARY_API = '../actions/generate_itinerary.php';
const GET_ITINERARY_API = '../actions/get_itinerary.php';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setMinimumDates();
    loadDestinations();
    attachFormListeners();
});

// Set minimum date to today
function setMinimumDates() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('departureDate').min = today;
    
    document.getElementById('departureDate').addEventListener('change', function() {
        document.getElementById('returnDate').min = this.value;
    });
}

// Load destinations from database
function loadDestinations() {
    fetch(DESTINATIONS_API)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.destinations) {
                const select = document.getElementById('destination');
                
                // Clear existing options (except the first one)
                while (select.options.length > 1) {
                    select.remove(1);
                }

                // Add destination options
                data.destinations.forEach(dest => {
                    const option = document.createElement('option');
                    option.value = dest.id;
                    option.textContent = `${dest.name} - ${dest.location}`;
                    select.appendChild(option);
                });
            } else {
                console.error('Failed to load destinations');
            }
        })
        .catch(error => {
            console.error('Error loading destinations:', error);
            showError('Failed to load destinations. Please refresh the page.');
        });
}

// Attach form listeners
function attachFormListeners() {
    document.getElementById('itineraryForm').addEventListener('submit', handleFormSubmit);
}

// Handle form submission
function handleFormSubmit(e) {
    e.preventDefault();

    const destinationId = document.getElementById('destination').value;
    const departureDate = document.getElementById('departureDate').value;
    const returnDate = document.getElementById('returnDate').value;
    const budget = document.getElementById('budget').value;
    const travelStyle = document.getElementById('travelStyle').value;
    const companions = document.getElementById('companions').value;

    // Validate form
    if (!destinationId || !departureDate || !returnDate || !budget || !travelStyle || !companions) {
        showError('Please fill in all required fields');
        return;
    }

    // Check if return date is after departure date
    if (new Date(returnDate) <= new Date(departureDate)) {
        showError('Return date must be after departure date');
        return;
    }

    // Get interests
    const interests = Array.from(document.querySelectorAll('input[name="interests"]:checked'))
        .map(cb => cb.value);

    const formData = {
        destination_id: parseInt(destinationId),
        start_date: departureDate,
        end_date: returnDate,
        budget: parseFloat(budget),
        travel_style: travelStyle,
        companions: companions,
        preferences: {
            interests: interests
        }
    };

    generateItinerary(formData);
}

// Generate itinerary
function generateItinerary(formData) {
    // Show loading state
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('itineraryResult').innerHTML = '';

    fetch(ITINERARY_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingState').style.display = 'none';

        if (data.status === 'success') {
            showSuccess('Itinerary created successfully!');
            fetchAndDisplayItinerary(data.itinerary_id);
        } else {
            showError(data.message || 'Failed to generate itinerary');
        }
    })
    .catch(error => {
        document.getElementById('loadingState').style.display = 'none';
        console.error('Error:', error);
        showError('Error generating itinerary: ' + error.message);
    });
}

// Fetch and display itinerary
function fetchAndDisplayItinerary(itineraryId) {
    fetch(`${GET_ITINERARY_API}?itinerary_id=${itineraryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayItinerary(data.itinerary);
                document.getElementById('itineraryForm').reset();
            } else {
                showError('Failed to load itinerary');
            }
        })
        .catch(error => {
            console.error('Error fetching itinerary:', error);
            showError('Error loading itinerary');
        });
}

// Display itinerary
function displayItinerary(itinerary) {
    let html = `
        <div class="card itinerary-display">
            <div class="itinerary-header">
                <h2>${escapeHtml(itinerary.title)}</h2>
                <div class="itinerary-meta">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${escapeHtml(itinerary.destination_name)}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>${formatDate(itinerary.start_date)} to ${formatDate(itinerary.end_date)}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-wallet"></i>
                        <span>Budget: $${parseFloat(itinerary.budget).toLocaleString()}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-heart"></i>
                        <span>Style: ${capitalizeFirstLetter(itinerary.travel_style)}</span>
                    </div>
                </div>
            </div>
    `;

    // Calculate totals
    let totalCost = 0;
    let totalActivities = 0;

    if (itinerary.days && Array.isArray(itinerary.days)) {
        itinerary.days.forEach(day => {
            html += `
                <div class="day-card">
                    <h3>📅 ${escapeHtml(day.title)}</h3>
                    <p>${escapeHtml(day.description || 'Explore and enjoy this day')}</p>
                    
                    <div class="activities-list">
            `;

            if (day.activities && Array.isArray(day.activities)) {
                day.activities.forEach(activity => {
                    totalActivities++;
                    const cost = parseFloat(activity.estimated_cost || 0);
                    totalCost += cost;

                    html += `
                        <div class="activity">
                            <div class="activity-info">
                                <h4>${escapeHtml(activity.activity_name)}</h4>
                                <div class="activity-meta">
                                    <span><i class="fas fa-clock"></i> ${activity.time_required || 120} mins</span>
                                    <span><i class="fas fa-sun"></i> ${capitalizeFirstLetter(activity.time_of_day)}</span>
                                    <span><i class="fas fa-tag"></i> ${capitalizeFirstLetter(activity.activity_type)}</span>
                                </div>
                            </div>
                            <div class="activity-cost">$${cost.toFixed(2)}</div>
                        </div>
                    `;
                });
            }

            html += `
                    </div>
                    <p style="text-align: right; margin-top: 1rem; color: #666; font-weight: 600;">
                        Daily budget: $${parseFloat(day.estimated_cost || 0).toFixed(2)}
                    </p>
                </div>
            `;
        });
    }

    // Add summary
    html += `
            <div class="itinerary-summary">
                <div class="summary-item">
                    <div class="summary-label">Total Days</div>
                    <div class="summary-value">${itinerary.days ? itinerary.days.length : 0}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Activities</div>
                    <div class="summary-value">${totalActivities}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Est. Total Cost</div>
                    <div class="summary-value">$${totalCost.toFixed(2)}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Daily Average</div>
                    <div class="summary-value">$${itinerary.days ? (totalCost / itinerary.days.length).toFixed(2) : 0}</div>
                </div>
            </div>

            <div class="itinerary-actions">
                <button class="btn-action primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Itinerary
                </button>
                <button class="btn-action secondary" onclick="saveItinerary(${itinerary.id})">
                    <i class="fas fa-save"></i> Save to My Itineraries
                </button>
                <button class="btn-action secondary" onclick="shareItinerary(${itinerary.id})">
                    <i class="fas fa-share-alt"></i> Share
                </button>
            </div>
        </div>
    `;

    document.getElementById('itineraryResult').innerHTML = html;
    document.getElementById('itineraryResult').scrollIntoView({ behavior: 'smooth' });
}

// Save itinerary
function saveItinerary(itineraryId) {
    fetch(`${API_BASE}/save_itinerary.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ itinerary_id: itineraryId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess('Itinerary saved successfully!');
        } else {
            showError(data.message || 'Failed to save itinerary');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error saving itinerary');
    });
}

// Share itinerary
function shareItinerary(itineraryId) {
    const shareUrl = `${window.location.origin}/TripMate/itinerary/view.php?id=${itineraryId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'My TripMate Itinerary',
            text: 'Check out my travel itinerary!',
            url: shareUrl
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(shareUrl).then(() => {
            showSuccess('Itinerary link copied to clipboard!');
        });
    }
}

// Utility functions
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const container = document.getElementById('itineraryResult');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'card error-message';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    container.insertBefore(errorDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => errorDiv.remove(), 5000);
}

function showSuccess(message) {
    const container = document.getElementById('itineraryResult');
    const successDiv = document.createElement('div');
    successDiv.className = 'card success-message';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    container.insertBefore(successDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => successDiv.remove(), 5000);
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('minimized');
}