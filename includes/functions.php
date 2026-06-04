<?php
/**
 * الدوال المساعدة العامة - نظام إنجاز
 */

// ─────────────────────────────────────────────────────────────────
// الروابط والمسارات
// ─────────────────────────────────────────────────────────────────

function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // تصحيح: إزالة المسارات الداخلية
    $parts    = explode('/', trim($script, '/'));
    // نرجع لجذر المشروع
    $base = '';
    $dir  = dirname($_SERVER['PHP_SELF'] ?? '');
    // نستخدم الـ ROOT_PATH
    if (defined('ROOT_PATH')) {
        $docRoot   = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $rootPath  = realpath(ROOT_PATH);
        if ($docRoot && $rootPath) {
            $rel  = str_replace('\\', '/', substr($rootPath, strlen($docRoot)));
            $base = rtrim($rel, '/');
        }
    }
    return $protocol . '://' . $host . $base;
}

function url(string $path = ''): string {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

// ─────────────────────────────────────────────────────────────────
// تنسيق البيانات
// ─────────────────────────────────────────────────────────────────

/**
 * تنسيق المبلغ المالي
 */
function formatMoney(float $amount, string $currency = null): string {
    if ($currency === null) {
        $currency = getSetting('currency', 'جنيه');
    }
    return number_format($amount, 2) . ' ' . $currency;
}

/**
 * تنسيق التاريخ للعرض
 */
function formatDate(string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    return date($format, strtotime($date));
}

/**
 * تنسيق التاريخ والوقت
 */
function formatDateTime(string $datetime): string {
    if (empty($datetime)) return '—';
    return date('d/m/Y h:i A', strtotime($datetime));
}

/**
 * الفرق بين تاريخين بالأيام
 */
function daysDiff(string $date1, string $date2 = 'today'): int {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    return (int)$d1->diff($d2)->days * ($d1 < $d2 ? -1 : 1);
}

// ─────────────────────────────────────────────────────────────────
// الاشتراكات والحالات
// ─────────────────────────────────────────────────────────────────

/**
 * حالة الاشتراك كـ badge HTML
 */
function subscriptionStatusBadge(string $status, string $endDate = ''): string {
    $warningDays = (int)getSetting('renewal_warning_days', '30');
    // تحقق من الاقتراب من الانتهاء
    if ($status === 'active' && !empty($endDate)) {
        $daysLeft = daysDiff($endDate, 'today');
        if ($daysLeft >= 0 && $daysLeft <= $warningDays) {
            return '<span class="badge badge-warning">ينتهي قريباً (' . $daysLeft . ' يوم)</span>';
        }
        if ($daysLeft < 0) {
            return '<span class="badge badge-danger">منتهي</span>';
        }
    }
    $map = [
        'active'    => '<span class="badge badge-success">نشط</span>',
        'expired'   => '<span class="badge badge-danger">منتهي</span>',
        'cancelled' => '<span class="badge badge-secondary">ملغي</span>',
        'pending'   => '<span class="badge badge-warning">معلّق</span>',
    ];
    return $map[$status] ?? '<span class="badge badge-secondary">' . e($status) . '</span>';
}

/**
 * حالة الفاتورة كـ badge HTML
 */
function invoiceStatusBadge(string $status): string {
    $map = [
        'draft'     => '<span class="badge badge-secondary">مسودة</span>',
        'sent'      => '<span class="badge badge-info">مُرسلة</span>',
        'paid'      => '<span class="badge badge-success">مسددة</span>',
        'partial'   => '<span class="badge badge-warning">مدفوعة جزئياً</span>',
        'cancelled' => '<span class="badge badge-danger">ملغاة</span>',
    ];
    return $map[$status] ?? '';
}

/**
 * طريقة الدفع بالعربي
 */
function paymentMethodLabel(string $method): string {
    $map = [
        'cash'     => 'كاش',
        'transfer' => 'تحويل بنكي',
        'check'    => 'شيك',
        'other'    => 'أخرى',
    ];
    return $map[$method] ?? $method;
}

// ─────────────────────────────────────────────────────────────────
// الأمان والتنظيف
// ─────────────────────────────────────────────────────────────────

/**
 * تنظيف النص للعرض الآمن (يمنع XSS)
 */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * تنظيف المدخلات النصية
 */
function clean(string $input): string {
    return trim(strip_tags($input));
}

/**
 * CSRF Token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrfToken(), $token);
}

// ─────────────────────────────────────────────────────────────────
// الإشعارات (Flash Messages)
// ─────────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $icon = match($flash['type']) {
        'success' => 'check-circle',
        'error'   => 'times-circle',
        'warning' => 'exclamation-triangle',
        default   => 'info-circle',
    };
    return '<div class="alert alert-' . e($flash['type']) . '">
        <i class="fas fa-' . $icon . '"></i>
        ' . e($flash['message']) . '
    </div>';
}

// ─────────────────────────────────────────────────────────────────
// مساعدات قاعدة البيانات
// ─────────────────────────────────────────────────────────────────

/**
 * رقم الفاتورة التالي
 */
function getNextInvoiceNumber(): string {
    $db      = getDB();
    $prefix  = getSetting('invoice_prefix', 'INV');
    $counter = (int)getSetting('invoice_counter', '1');
    // تحديث العداد
    $db->prepare("UPDATE `settings` SET `value` = ? WHERE `key` = 'invoice_counter'")
       ->execute([$counter + 1]);
    return $prefix . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
}

/**
 * ملخص حساب عميل معين
 */
function getClientSummary(int $clientId): array {
    $db = getDB();

    // إجمالي الاشتراكات
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(price), 0) as total_subscriptions
        FROM client_subscriptions
        WHERE client_id = ? AND status != 'cancelled'
    ");
    $stmt->execute([$clientId]);
    $totalSubs = (float)$stmt->fetchColumn();

    // إجمالي المدفوعات
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_paid
        FROM payments
        WHERE client_id = ?
    ");
    $stmt->execute([$clientId]);
    $totalPaid = (float)$stmt->fetchColumn();

    return [
        'total'     => $totalSubs,
        'paid'      => $totalPaid,
        'remaining' => $totalSubs - $totalPaid,
    ];
}

/**
 * عدد الأيام المتبقية على انتهاء الاشتراك
 */
function daysUntilExpiry(string $endDate): int {
    $end   = new DateTime($endDate);
    $today = new DateTime('today');
    $diff  = $today->diff($end);
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * فحص إذا كان الاشتراك ينتهي قريباً
 */
function isExpiringSoon(string $endDate): bool {
    $days = daysUntilExpiry($endDate);
    $warningDays = (int)getSetting('renewal_warning_days', '30');
    return $days >= 0 && $days <= $warningDays;
}

/**
 * Pagination helper
 */
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}
