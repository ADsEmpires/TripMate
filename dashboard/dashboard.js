// dashboard.js
function showLoading() {
  document.getElementById('loading').style.display = 'flex';
}

function hideLoading() {
  document.getElementById('loading').style.display = 'none';
}

function showError(elementId, message) {
  const element = document.getElementById(elementId);
  element.innerHTML = `<div class="error-message">${message}</div>`;
  element.style.color = 'red';
}

// Add to your HTML:
// <div id="loading" style="display: none; position: fixed; ...">
//   <div style="background: white; padding: 2rem; ...">
//     <i class="fas fa-spinner fa-spin"></i> Loading...
//   </div>
// </div>