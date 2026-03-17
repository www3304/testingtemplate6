<link href="css/footer.css" rel="stylesheet">

<footer id="contact">

  <?= $company['footer_script'] ?? '' ?> <!-- 这里插入 Footer Script -->

  <!-- 第一行 -->
  <div class="footer-top-modern">

    <!-- 左边导航 -->
    <div class="footer-brand-nav">
      <nav class="footer-nav">
        <?php foreach ($navLinks as $nav): ?>
          <?php
          $sectionKey = strtolower($nav['nav_value']);
          // 跳过 home 和 contact
          if (in_array($sectionKey, ['home', 'contact'])) {
            continue;
          }
          if (isSectionActive($sectionKey, $sectionStatus)):
          ?>
            <a href="#<?= $sectionKey ?>">
              <?= htmlspecialchars($nav['nav_name']) ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    </div>

    <!-- 中间竖线 -->
    <div class="footer-separator"></div>

    <!-- 右边 Contact -->
    <div class="footer-contact">
      <h3>Contact Us</h3>
      <?php if (!empty($company['email'])): ?>
        <div class="contact-item">📧 <span><?= htmlspecialchars($company['email']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($company['phone'])): ?>
        <div class="contact-item">📞 <span><?= htmlspecialchars($company['phone']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($company['address'])): ?>
        <div class="contact-item">📍 <span><?= htmlspecialchars($company['address']) ?></span></div>
      <?php endif; ?>

      <!-- 社交按钮 -->
      <div class="footer-socials">
        <?php foreach ($company['socials'] as $social): ?>
          <a href="<?= htmlspecialchars($social['link_url']) ?>"
            target="_blank"
            class="social-link"
            title="<?= htmlspecialchars($social['name']) ?>"
            data-aos="zoom-in">
            <img src="<?= htmlspecialchars($social['icon_path']) ?>"
              alt="<?= htmlspecialchars($social['name']) ?>">
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- 第二行 -->
  <div class="footer-bottom">
    &copy; <?= date('Y') ?> <?= htmlspecialchars($company['name']) ?>. All rights reserved.
  </div>
</footer>