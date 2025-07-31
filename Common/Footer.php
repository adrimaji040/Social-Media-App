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
  });
</script>
</body>

</html>