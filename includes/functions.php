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
// الدول والعملات
// ─────────────────────────────────────────────────────────────────

/**
 * قائمة الدول المدعومة في النظام
 */
function getSupportedCountries(): array {
    return [
        'EG' => ['name' => 'مصر', 'flag' => '🇪🇬', 'code' => '+20', 'currency' => 'EGP', 'currency_label' => 'جنيه'],
        'SA' => ['name' => 'السعودية', 'flag' => '🇸🇦', 'code' => '+966', 'currency' => 'SAR', 'currency_label' => 'ريال'],
        'AE' => ['name' => 'الإمارات', 'flag' => '🇦🇪', 'code' => '+971', 'currency' => 'AED', 'currency_label' => 'درهم'],
        'KW' => ['name' => 'الكويت', 'flag' => '🇰🇼', 'code' => '+965', 'currency' => 'KWD', 'currency_label' => 'دينار كويتي'],
        'QA' => ['name' => 'قطر', 'flag' => '🇶🇦', 'code' => '+974', 'currency' => 'QAR', 'currency_label' => 'ريال قطري'],
        'OM' => ['name' => 'سلطنة عمان', 'flag' => '🇴🇲', 'code' => '+968', 'currency' => 'OMR', 'currency_label' => 'ريال عماني'],
        'BH' => ['name' => 'البحرين', 'flag' => '🇧🇭', 'code' => '+973', 'currency' => 'BHD', 'currency_label' => 'دينار بحريني'],
        'SD' => ['name' => 'السودان', 'flag' => '🇸🇩', 'code' => '+249', 'currency' => 'SDG', 'currency_label' => 'جنيه سوداني'],
        'OTHER' => ['name' => 'دولة أخرى', 'flag' => '🌐', 'code' => '', 'currency' => 'USD', 'currency_label' => 'دولار / أخرى'],
    ];
}

/**
 * رسم علم الدولة بجودة عالية كـ SVG لضمان ظهوره كعلم ملون على جميع الأجهزة والأنظمة (بما فيها Windows)
 */
function getCountryFlagSvg(?string $countryCode, int $width = 22, int $height = 16): string {
    $code = strtoupper(trim($countryCode ?? 'EG'));
    $w = (int)$width;
    $h = (int)$height;
    
    switch ($code) {
        case 'EG':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#ce1126"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><path d="M320 200c-7 0-14 8-14 20 0 16 14 30 14 30s14-14 14-30c0-12-7-20-14-20z" fill="#c09300"/><circle cx="320" cy="225" r="5" fill="#ffffff"/></svg>', $w, $h);
        case 'SA':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#006c35"/><path d="M160 320h320v14H160z" fill="#ffffff"/><polygon points="160,327 195,312 195,342" fill="#ffffff"/><circle cx="450" cy="327" r="10" fill="#ffffff"/><text x="320" y="235" font-family="Arial, sans-serif" font-size="62" font-weight="900" fill="#ffffff" text-anchor="middle" letter-spacing="4">لا إله إلا الله</text></svg>', $w, $h);
        case 'AE':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#00732f"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><rect width="180" height="480" fill="#ff0000"/></svg>', $w, $h);
        case 'KW':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#007a3d"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#ce1126"/><polygon points="0,0 200,160 200,320 0,480" fill="#000000"/></svg>', $w, $h);
        case 'QA':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#8d1b3d"/><polygon points="0,0 200,0 240,27 200,53 240,80 200,107 240,133 200,160 240,187 200,213 240,240 200,267 240,293 200,320 240,347 200,373 240,400 200,427 240,453 200,480 0,480" fill="#ffffff"/></svg>', $w, $h);
        case 'OM':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#ffffff"/><rect y="160" width="640" height="160" fill="#db161e"/><rect y="320" width="640" height="160" fill="#008000"/><rect width="180" height="480" fill="#db161e"/><circle cx="90" cy="80" r="22" fill="#ffffff" opacity="0.9"/></svg>', $w, $h);
        case 'BH':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#ce1126"/><polygon points="0,0 180,0 240,48 180,96 240,144 180,192 240,240 180,288 240,336 180,384 240,432 180,480 0,480" fill="#ffffff"/></svg>', $w, $h);
        case 'SD':
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#d21034"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><polygon points="0,0 220,240 0,480" fill="#007229"/></svg>', $w, $h);
        default:
            return sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="%d" height="%d" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.18);vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#2563eb"/><circle cx="320" cy="240" r="160" fill="none" stroke="#ffffff" stroke-width="20"/><ellipse cx="320" cy="240" rx="90" ry="160" fill="none" stroke="#ffffff" stroke-width="16"/><line x1="160" y1="240" x2="480" y2="240" stroke="#ffffff" stroke-width="18"/><line x1="190" y1="160" x2="450" y2="160" stroke="#ffffff" stroke-width="14"/><line x1="190" y1="320" x2="450" y2="320" stroke="#ffffff" stroke-width="14"/></svg>', $w, $h);
    }
}

/**
 * جلب معلومات دولة معينة
 */
function getCountryInfo(?string $countryCode): array {
    $code = strtoupper(trim($countryCode ?? 'EG'));
    $countries = getSupportedCountries();
    $info = $countries[$code] ?? $countries['OTHER'];
    $info['svg'] = getCountryFlagSvg($code, 22, 16);
    return $info;
}

/**
 * عرض علم أو رمز الدولة كـ HTML أنيق
 */
function getCountryFlagBadge(?string $countryCode, bool $withName = false, string $customStyle = '', int $flagWidth = 22, int $flagHeight = 16): string {
    $info = getCountryInfo($countryCode);
    $code = strtoupper(trim($countryCode ?? 'EG'));
    $svg = getCountryFlagSvg($code, $flagWidth, $flagHeight);
    
    if ($withName) {
        return '<span class="country-badge" style="display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:6px;background:rgba(36,86,164,0.06);font-size:12px;font-weight:600;color:var(--text-primary);' . $customStyle . '" title="' . e($info['name']) . '">'
             . $svg
             . '<span>' . e($info['name']) . '</span>'
             . '</span>';
    }
    return '<span class="country-flag-icon" style="display:inline-flex;align-items:center;justify-content:center;line-height:1;' . $customStyle . '" title="' . e($info['name']) . '">' . $svg . '</span>';
}

/**
 * قائمة العملات المدعومة
 */
function getSupportedCurrencies(): array {
    return [
        'EGP' => ['label' => 'جنيه مصري (EGP)', 'symbol' => 'ج.م', 'name' => 'جنيه'],
        'SAR' => ['label' => 'ريال سعودي (SAR)', 'symbol' => 'ر.س', 'name' => 'ريال'],
        'AED' => ['label' => 'درهم إماراتي (AED)', 'symbol' => 'د.إ', 'name' => 'درهم'],
        'USD' => ['label' => 'دولار أمريكي (USD)', 'symbol' => '$', 'name' => 'دولار'],
        'KWD' => ['label' => 'دينار كويتي (KWD)', 'symbol' => 'د.ك', 'name' => 'دينار كويتي'],
        'QAR' => ['label' => 'ريال قطري (QAR)', 'symbol' => 'ر.ق', 'name' => 'ريال قطري'],
        'OMR' => ['label' => 'ريال عماني (OMR)', 'symbol' => 'ر.ع', 'name' => 'ريال عماني'],
        'BHD' => ['label' => 'دينار بحريني (BHD)', 'symbol' => 'د.ب', 'name' => 'دينار بحريني'],
    ];
}

/**
 * تنسيق سعر الباقة لعرض العملة الأصلية والمقابل بالجنيه المصري
 */
function formatPlanPrice(float $egpPrice, ?string $currency = 'EGP', ?float $originalPrice = null): string {
    $curr = strtoupper(trim($currency ?? 'EGP'));
    $currencies = getSupportedCurrencies();
    $currInfo = $currencies[$curr] ?? ['symbol' => $curr, 'name' => $curr];
    
    if ($curr !== 'EGP' && $originalPrice !== null && $originalPrice > 0) {
        return number_format($originalPrice, 2) . ' ' . $currInfo['symbol'] . ' <span style="font-size:12px;opacity:0.8;font-weight:normal;">(يعادل ' . number_format($egpPrice, 2) . ' ج.م)</span>';
    }
    
    return formatMoney($egpPrice);
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

function formatDate(?string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    return date('d/m/Y', strtotime($date));
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
function subscriptionStatusBadge(string $status, ?string $endDate = ''): string {
    $warningDays = (int)getSetting('renewal_warning_days', '60');
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
    $warningDays = (int)getSetting('renewal_warning_days', '60');
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

/**
 * توحيد صيغة أرقام الهواتف لتتوافق مع نظام الواتساب الدولي
 */
function normalizeMobile(string $mobile): string {
    // إزالة أي رموز أو مسافات، والاحتفاظ بالأرقام فقط
    $mobile = preg_replace('/\D/', '', $mobile);
    
    // إزالة أصفار البداية المزدوجة (مثال: 00966 -> 966)
    if (str_starts_with($mobile, '00')) {
        $mobile = substr($mobile, 2);
    }
    
    // إذا كان الرقم يبدأ بصفر واحد
    if (str_starts_with($mobile, '0')) {
        // حالة مصر: يبدأ بـ 01 وطوله 11 رقم (مثال: 01028855779 -> 201028855779)
        if (strlen($mobile) === 11 && str_starts_with($mobile, '01')) {
            return '2' . $mobile;
        }
        // حالة السعودية: يبدأ بـ 05 وطوله 10 أرقام (مثال: 0598012129 -> 966598012129)
        if (strlen($mobile) === 10 && str_starts_with($mobile, '05')) {
            return '966' . substr($mobile, 1);
        }
    }
    
    return $mobile;
}

/**
 * إرسال بريد إلكتروني عبر SMTP باستخدام المقابس (Sockets)
 */
function sendSMTPMail(string $to, string $subject, string $htmlMessage): bool {
    $host = 'mail.enjaz.app';
    $port = 465;
    $username = 'noreplay@enjaz.app';
    $password = 'Aa@01028855';
    
    $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 15);
    if (!$socket) {
        return false;
    }

    $readResponse = function($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        return $data;
    };

    $readResponse($socket); // 220 Welcome

    fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
    $readResponse($socket);

    fwrite($socket, "AUTH LOGIN\r\n");
    $readResponse($socket);

    fwrite($socket, base64_encode($username) . "\r\n");
    $readResponse($socket);

    fwrite($socket, base64_encode($password) . "\r\n");
    $authRes = $readResponse($socket);
    if (strpos($authRes, '235') === false) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "MAIL FROM:<" . $username . ">\r\n");
    $readResponse($socket);

    fwrite($socket, "RCPT TO:<" . $to . ">\r\n");
    $readResponse($socket);

    fwrite($socket, "DATA\r\n");
    $readResponse($socket);

    $headers = "From: " . APP_NAME . " <" . $username . ">\r\n";
    $headers .= "To: <" . $to . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    fwrite($socket, $headers . "\r\n" . $htmlMessage . "\r\n.\r\n");
    $readResponse($socket);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}

