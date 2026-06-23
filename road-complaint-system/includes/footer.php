<?php
// ============================================================
// includes/footer.php - Global Footer
// ============================================================
?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <i class="fa-solid fa-road"></i> <?= APP_NAME ?>
    </div>
    <p class="footer-copy">&copy; <?= date('Y') ?> Road Complaint &amp; Resolution Tracking System. All rights reserved.</p>
    <div class="footer-links">
      <a href="<?= APP_URL ?>/about.php">About</a>
      <a href="<?= APP_URL ?>/api/complaint_status.php?id=1">API</a>
    </div>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
