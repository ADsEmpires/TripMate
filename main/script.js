let currentSlide = 0;
const slides = document.querySelectorAll('.slide');

function showSlide(index) {
  slides.forEach((slide, i) => {
    slide.classList.toggle('active', i === index);
  });
}

function changeSlide(direction) {
  currentSlide = (currentSlide + direction + slides.length) % slides.length;
  showSlide(currentSlide);
}

function autoSlide() {
  changeSlide(1);
}

setInterval(autoSlide, 5000); // Auto-change every 5 seconds

showSlide(currentSlide);

document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('registerModal');
  const successModal = document.getElementById('successModal');
  const registerBtn = document.querySelector('.register-btn');
  const closeBtn = document.querySelector('.close');
  const registerForm = document.getElementById('registerForm');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirmPassword');
  const passwordError = document.getElementById('passwordError');

  // Open modal when Register button is clicked
  registerBtn.addEventListener('click', function() {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  });

  // Close modal when X is clicked
  closeBtn.addEventListener('click', function() {
    closeModal();
  });

  // Close modal when clicking outside of it
  window.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    registerForm.reset();
    passwordError.textContent = '';
  }

  function showSuccess() {
    successModal.style.display = 'block';
    setTimeout(() => {
      successModal.style.display = 'none';
      closeModal();
    }, 2000); // Auto-close after 2 seconds
  }

  // Validate password match on form submit
  registerForm.addEventListener('submit', function(event) {
    event.preventDefault();
    
    // Clear previous errors
    passwordError.textContent = '';
    
    // Validate inputs
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    // Validate required fields
    if (!username || !email || !password || !confirmPassword) {
      passwordError.textContent = 'All fields are required';
      return;
    }

    // Validate email format
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      passwordError.textContent = 'Please enter a valid email address';
      return;
    }

    // Validate passwords match
    if (password !== confirmPassword) {
      passwordError.textContent = 'Passwords do not match';
      confirmPasswordInput.focus();
      return;
    }
    
    // Validate password length
    if (password.length < 6) {
      passwordError.textContent = 'Password must be at least 6 characters';
      passwordInput.focus();
      return;
    }
    
    // Show loading state
    const submitBtn = registerForm.querySelector('.submit-btn');
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'Registering...';
    submitBtn.disabled = true;

    // Send form data to server
    fetch('register.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(new FormData(registerForm))
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        showSuccess();
      } else {
        passwordError.textContent = data.message || 'Registration failed';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      passwordError.textContent = 'An error occurred. Please try again.';
    })
    .finally(() => {
      submitBtn.textContent = originalBtnText;
      submitBtn.disabled = false;
    });
  });

  // Clear password error when user starts typing
  [passwordInput, confirmPasswordInput].forEach(input => {
    input.addEventListener('input', function() {
      if (passwordError.textContent !== '') {
        passwordError.textContent = '';
      }
    });
  });

  // Check if user is logged in
  if (sessionStorage.getItem('user_id')) {
      // Show user session float
      const userFloat = document.querySelector('.user-session-float');
      if (userFloat) {
          userFloat.style.display = 'flex';
          const userName = userFloat.querySelector('.user-name');
          if (userName) {
              userName.textContent = sessionStorage.getItem('user_name') || 'User';
          }
      }

      // Add user menu content
      const userMenu = document.querySelector('.user-session-float');
      if (userMenu) {
        userMenu.innerHTML = `
            <i class="fas fa-user-circle"></i>
            <span class="user-name">${sessionStorage.getItem('user_name') || 'User'}</span>
            <div class="user-menu">
                <a href="../user/user_dashboard.php"><i class="fas fa-columns"></i> Dashboard</a>
                <a href="../user/user-profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../auth/logout.php" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        `;
      }

      // Hide register button if it exists
      const registerBtn = document.querySelector('.register-btn');
      if (registerBtn) {
        registerBtn.style.display = 'none';
      }
  }
});
