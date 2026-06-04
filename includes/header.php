<?php
/**
 * Header & Sidebar - نظام إنجاز للحلول الذكية
 * يُضمَّن في كل صفحة
 *
 * المتغيرات المتوقعة:
 * $pageTitle      - عنوان الصفحة
 * $pageSubtitle   - وصف مختصر (اختياري)
 * $activePage     - معرّف العنصر النشط في القائمة
 */

// التأكد من تحميل الإعدادات
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

$companyName = getSetting('company_name', APP_NAME);
$currency    = getSetting('currency', 'جنيه');
$userName    = currentUserName();
$userRole    = isAdmin() ? 'مدير النظام' : 'موظف';
$userInitial = mb_substr($userName, 0, 1, 'UTF-8');

// قائمة التنبيهات (اشتراكات تنتهي قريباً)
$renewalCount = 0;
try {
    $db   = getDB();
    $days = (int)getSetting('renewal_warning_days', '30');
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM client_subscriptions
        WHERE status = 'active'
          AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ");
    $stmt->execute([$days]);
    $renewalCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'لوحة التحكم') ?> — <?= e($companyName) ?></title>
  <meta name="description" content="نظام إدارة عملاء <?= e($companyName) ?>">
  <meta name="robots" content="noindex, nofollow">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= str_repeat('../', $depth ?? 0) ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= str_repeat('../', $depth ?? 0) ?>assets/css/print.css" media="print">
  
  <!-- Flatpickr (Date Picker) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <script>
    window.CURRENCY = '<?= e($currency) ?>';
    window.BASE_DEPTH = <?= (int)($depth ?? 0) ?>;
  </script>
</head>
<body>

<div class="layout">

  <!-- ══ Sidebar ══════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <a href="<?= str_repeat('../', $depth ?? 0) ?>dashboard.php" class="sidebar-logo">
      <div class="logo-icon">
        <i class="fas fa-bolt"></i>
      </div>
      <div class="logo-text">
        <span class="brand"><?= e($companyName) ?></span>
        <span class="sub">لوحة التحكم</span>
      </div>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav" role="navigation" aria-label="القائمة الرئيسية">

      <!-- الرئيسية -->
      <ul>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>dashboard.php"
             class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>"
             id="nav-dashboard">
            <i class="fas fa-chart-pie nav-icon"></i>
            <span>لوحة التحكم</span>
          </a>
        </li>
      </ul>

      <?php if (hasPermission('view_clients')): ?>
      <p class="nav-section-title">إدارة العملاء</p>
      <ul>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>clients/index.php"
             class="nav-link <?= ($activePage ?? '') === 'clients' ? 'active' : '' ?>"
             id="nav-clients">
            <i class="fas fa-users nav-icon"></i>
            <span>العملاء</span>
          </a>
        </li>
        <?php if (hasPermission('view_subscriptions')): ?>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>subscriptions/index.php"
             class="nav-link <?= ($activePage ?? '') === 'subscriptions' ? 'active' : '' ?>"
             id="nav-subscriptions">
            <i class="fas fa-file-contract nav-icon"></i>
            <span>الاشتراكات</span>
            <?php if ($renewalCount > 0): ?>
            <span class="nav-badge"><?= $renewalCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('view_payments')): ?>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>payments/index.php"
             class="nav-link <?= ($activePage ?? '') === 'payments' ? 'active' : '' ?>"
             id="nav-payments">
            <i class="fas fa-money-bill-wave nav-icon"></i>
            <span>المدفوعات</span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('print_invoices')): ?>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>invoices/index.php"
             class="nav-link <?= ($activePage ?? '') === 'invoices' ? 'active' : '' ?>"
             id="nav-invoices">
            <i class="fas fa-file-invoice nav-icon"></i>
            <span>الفواتير</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <?php endif; ?>

      <?php if (hasPermission('manage_services')): ?>
      <p class="nav-section-title">الخدمات</p>
      <ul>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>services/index.php"
             class="nav-link <?= ($activePage ?? '') === 'services' ? 'active' : '' ?>"
             id="nav-services">
            <i class="fas fa-concierge-bell nav-icon"></i>
            <span>الخدمات المتاحة</span>
          </a>
        </li>
      </ul>
      <?php endif; ?>

      <?php if (hasPermission('view_reports')): ?>
      <p class="nav-section-title">التقارير</p>
      <ul>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>reports/monthly.php"
             class="nav-link <?= ($activePage ?? '') === 'reports-monthly' ? 'active' : '' ?>"
             id="nav-reports-monthly">
            <i class="fas fa-chart-bar nav-icon"></i>
            <span>الإيرادات الشهرية</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>reports/renewals.php"
             class="nav-link <?= ($activePage ?? '') === 'reports-renewals' ? 'active' : '' ?>"
             id="nav-reports-renewals">
            <i class="fas fa-calendar-exclamation nav-icon"></i>
            <span>تجديدات قريبة</span>
            <?php if ($renewalCount > 0): ?>
            <span class="nav-badge"><?= $renewalCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>reports/services-summary.php"
             class="nav-link <?= ($activePage ?? '') === 'reports-services' ? 'active' : '' ?>"
             id="nav-reports-services">
            <i class="fas fa-layer-group nav-icon"></i>
            <span>ملخص الخدمات</span>
          </a>
        </li>
      </ul>
      <?php endif; ?>

      <?php if (isAdmin()): ?>
      <p class="nav-section-title">الإدارة</p>
      <ul>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>users/index.php"
             class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>"
             id="nav-users">
            <i class="fas fa-user-shield nav-icon"></i>
            <span>المستخدمون</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= str_repeat('../', $depth ?? 0) ?>settings.php"
             class="nav-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>"
             id="nav-settings">
            <i class="fas fa-cog nav-icon"></i>
            <span>إعدادات النظام</span>
          </a>
        </li>
      </ul>
      <?php endif; ?>

    </nav>

    <!-- User Info -->
    <div class="sidebar-footer">
      <div class="sidebar-user-avatar"><?= e($userInitial) ?></div>
      <div class="sidebar-user-info">
        <div class="user-name"><?= e($userName) ?></div>
        <div class="user-role"><?= e($userRole) ?></div>
      </div>
      <a href="<?= str_repeat('../', $depth ?? 0) ?>logout.php"
         class="sidebar-logout"
         data-tooltip="تسجيل الخروج"
         data-confirm="هل تريد تسجيل الخروج؟"
         title="تسجيل الخروج">
        <i class="fas fa-right-from-bracket"></i>
      </a>
    </div>

  </aside>
  <!-- ══ End Sidebar ══════════════════════════════════════════ -->

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ══ Main Content ════════════════════════════════════════ -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-toggle" id="sidebarToggle" aria-label="القائمة">
        <i class="fas fa-bars"></i>
      </button>

      <!-- Breadcrumb -->
      <div class="topbar-breadcrumb">
        <span><?= e($companyName) ?></span>
        <span class="separator"><i class="fas fa-chevron-left fa-xs"></i></span>
        <span class="current"><?= e($pageTitle ?? 'لوحة التحكم') ?></span>
      </div>

      <!-- Actions -->
      <div class="topbar-actions">
        <?php if ($renewalCount > 0): ?>
        <a href="<?= str_repeat('../', $depth ?? 0) ?>reports/renewals.php"
           class="btn btn-sm btn-outline"
           style="color: var(--warning); border-color: var(--warning);">
          <i class="fas fa-bell"></i>
          <?= $renewalCount ?> تجديد
        </a>
        <?php endif; ?>
        <div class="topbar-time" id="liveClock"></div>
      </div>
    </header>

    <!-- Flash Message -->
    <?php
    $flash = getFlash();
    if ($flash): ?>
    <div class="px-4 pt-4">
      <div class="alert alert-<?= e($flash['type']) ?>" data-auto-dismiss="4000">
        <?php
        $flashIcons = ['success' => 'check-circle', 'error' => 'times-circle',
                       'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
        $flashIcon  = $flashIcons[$flash['type']] ?? 'info-circle';
        ?>
        <i class="fas fa-<?= $flashIcon ?>"></i>
        <?= e($flash['message']) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Page Content starts here -->
    <main class="page-content">
