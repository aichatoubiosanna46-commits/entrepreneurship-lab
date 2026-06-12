<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="logo-mark" style="width:36px;height:36px;font-size:16px">E</div>
      <div>
        <div class="logo-name"><?= SITE_NAME ?></div>
        <p class="footer-tagline">Formations en entrepreneuriat<br>adaptées à l'Afrique francophone.</p>
      </div>
    </div>

    <div class="footer-links">
      <div>
        <p class="footer-col-title">Apprendre</p>
        <a href="<?= SITE_URL ?>/index.php#cours">Tous les cours</a>
        <a href="<?= SITE_URL ?>/index.php#parcours">Parcours</a>
        <a href="<?= SITE_URL ?>/register.php">Créer un compte</a>
      </div>
      <div>
        <p class="footer-col-title">À propos</p>
        <a href="#">Notre mission</a>
        <a href="#">Devenir formateur</a>
        <a href="#">Contact</a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <span>© <?= date('Y') ?> <?= SITE_NAME ?> — Cotonou, Bénin</span>
    <div style="display:flex;gap:12px">
      <a href="#"><i class="ti ti-brand-facebook" aria-hidden="true"></i></a>
      <a href="#"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i></a>
      <a href="#"><i class="ti ti-brand-linkedin" aria-hidden="true"></i></a>
    </div>
  </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
