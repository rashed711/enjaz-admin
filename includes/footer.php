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

<script>
// Background trigger for scheduled messages (with delay to not block rendering)
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const cronPath = '<?= str_repeat('../', $depth ?? 0) ?>whatsapp/cron.php';
        fetch(cronPath).catch(function(e) {
            console.log('Cron trigger error:', e);
        });
    }, 2000);
});
</script>

<?php if (!empty($extraJs)): ?>
  <?= $extraJs ?>
<?php endif; ?>

</body>
</html>
