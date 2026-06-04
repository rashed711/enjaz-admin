<?php
/**
 * Footer - نظام إنجاز للحلول الذكية
 */
$companyName = getSetting('company_name', APP_NAME);
?>
    </main>
    <!-- End Page Content -->

    <!-- Footer -->
    <footer style="padding: 16px 24px; text-align: center; font-size: 12px;
                   color: var(--text-muted); border-top: 1px solid var(--border-color);
                   background: var(--card-bg);">
      <?= e($companyName) ?> &copy; <?= date('Y') ?> — جميع الحقوق محفوظة
    </footer>

  </div>
  <!-- ══ End Main Content ════════════════════════════════════ -->

</div><!-- .layout -->

<!-- Toast Container (will be created by JS if needed) -->

<!-- Main JS -->
<script src="<?= str_repeat('../', $depth ?? 0) ?>assets/js/main.js"></script>

<?php if (!empty($extraJs)): ?>
  <?= $extraJs ?>
<?php endif; ?>

</body>
</html>
