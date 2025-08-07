</div>
</div>
<footer class="mt-auto" style="background-color:#004d4d;">
  <div class="container">
    <p style="text-align: center; padding: 10px; color: #e6e6e6;">&copy; Algonquin College 2010 -
      <?php date_default_timezone_set("America/Toronto");
      print Date("Y"); ?>
      . All Rights Reserved
    </p>
  </div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function hideMessage() {
    const messageElement = document.querySelector('.disappearing-message');
    if (messageElement) {
      messageElement.style.height = messageElement.scrollHeight + 'px';

      messageElement.style.paddingTop = getComputedStyle(messageElement).paddingTop;
      messageElement.style.paddingBottom = getComputedStyle(messageElement).paddingBottom;
      messageElement.style.marginTop = getComputedStyle(messageElement).marginTop;
      messageElement.style.marginBottom = getComputedStyle(messageElement).marginBottom;
      messageElement.offsetHeight;


      setTimeout(() => {
        messageElement.style.height = '0';
        messageElement.style.paddingTop = '0';
        messageElement.style.paddingBottom = '0';
        messageElement.style.marginTop = '0';
        messageElement.style.marginBottom = '0';
        messageElement.style.opacity = '0';


        messageElement.addEventListener('transitionend', function (event) {
          if (event.propertyName === 'height') {
            messageElement.remove();
          }
        }, { once: true });
      }, 3000);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    hideMessage();

    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    // Update theme icon based on current theme
    function updateThemeIcon() {
      const currentTheme = document.documentElement.getAttribute('data-bs-theme');
      if (currentTheme === 'dark') {
        themeIcon.className = 'fas fa-sun';
        themeToggle.title = 'Switch to Light Mode';
      } else {
        themeIcon.className = 'fas fa-moon';
        themeToggle.title = 'Switch to Dark Mode';
      }
    }

    // Initialize icon
    updateThemeIcon();

    // Theme toggle click handler
    if (themeToggle) {
      themeToggle.addEventListener('click', function () {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Update theme
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('bs-theme', newTheme);

        // Update navbar classes
        updateNavbarTheme(newTheme);

        // Update icon
        updateThemeIcon();
      });
    }

    // Function to update navbar theme
    function updateNavbarTheme(theme) {
      const navbar = document.getElementById('main-navbar');
      if (navbar) {
        if (theme === 'dark') {
          navbar.classList.add('navbar-dark');
          navbar.classList.remove('navbar-light');
        } else {
          navbar.classList.add('navbar-light');
          navbar.classList.remove('navbar-dark');
        }
      }
    }

    // Initialize navbar theme
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    updateNavbarTheme(currentTheme);
  });
</script>
</body>

</html>