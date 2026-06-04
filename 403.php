<?php
/**
 * 403.php - غير مصرح
 */
require_once __DIR__ . '/config/app.php';
requireLogin();
$pageTitle = 'غير مصرح';
$depth     = 0;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="empty-state" style="padding: 80px 20px;">
  <div class="empty-icon" style="font-size:72px; color: var(--danger); opacity:.7;">
    <i class="fas fa-lock"></i>
  </div>
  <h2 class="empty-title" style="font-size:22px; color: var(--danger);">403 — غير مصرح</h2>
  <p class="empty-text">ليس لديك صلاحية للوصول لهذه الصفحة.<br>تواصل مع المدير لمنحك الصلاحية المطلوبة.</p>
  <a href="../dashboard.php" class="btn btn-primary">
    <i class="fas fa-home"></i>
    العودة للرئيسية
  </a>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
