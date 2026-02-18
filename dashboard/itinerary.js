// Capitalize first letter
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show error notification
function showError(message) {
    const container = document.getElementById('itineraryResult');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    container.insertBefore(errorDiv, container.firstChild);
    
    setTimeout(() => errorDiv.remove(), 5000);
}

// Show success notification
function showSuccess(message) {
    const container = document.getElementById('itineraryResult');
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    container.insertBefore(successDiv, container.firstChild);
    
    setTimeout(() => successDiv.remove(), 5000);
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('minimized');
}