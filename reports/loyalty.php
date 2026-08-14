<?php
/**
 * reports/loyalty.php - تحليل تجديدات وولاء العملاء
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();

// الفلاتر والبحث
$search = clean($_GET['search'] ?? '');
$renewalFilter = clean($_GET['renewal_status'] ?? 'all'); // 'all', 'renewed', 'needs_renewal', 'expiring_soon', 'none'
$clientStatus = clean($_GET['client_status'] ?? 'active'); // 'active', 'suspended', 'all'

// دالة لحساب المدة الزمنية بالعربية بشكل منسق
function formatDurationArabic($startDateStr) {
    if (!$startDateStr) return '—';
    $start = new DateTime($startDateStr);
    $end = new DateTime(); // اليوم
    
    if ($start > $end) {
        return 'لم يبدأ بعد';
    }
    
    $diff = $start->diff($end);
    $years = $diff->y;
    $months = $diff->m;
    $days = $diff->d;
    
    $parts = [];
    
    if ($years > 0) {
        if ($years == 1) {
            $parts[] = 'سنة';
        } elseif ($years == 2) {
            $parts[] = 'سنتين';
        } elseif ($years >= 3 && $years <= 10) {
            $parts[] = $years . ' سنوات';
        } else {
            $parts[] = $years . ' سنة';
        }
    }
    
    if ($months > 0) {
        if ($months == 1) {
            $parts[] = 'شهر';
        } elseif ($months == 2) {
            $parts[] = 'شهران';
        } elseif ($months >= 3 && $months <= 10) {
            $parts[] = $months . ' أشهر';
        } else {
            $parts[] = $months . ' شهراً';
        }
    }
    
    if (empty($parts)) {
        if ($days == 0) {
            return 'اليوم';
        } elseif ($days == 1) {
            return 'يوم واحد';
        } elseif ($days == 2) {
            return 'يومان';
        } elseif ($days >= 3 && $days <= 10) {
            return $days . ' أيام';
        } else {
            return $days . ' يوماً';
        }
    }
    
    return implode(' و ', $parts);
}

// جلب كل العملاء مع تفاصيل اشتراكاتهم ومدفوعاتهم
$where = ['1=1'];
$params = [];

if ($clientStatus === 'active') {
    $where[] = "c.status = 1";
} elseif ($clientStatus === 'suspended') {
    $where[] = "c.status = 0";
}

if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
}

$whereStr = implode(' AND ', $where);

// جلب العملاء أولاً
$clientsQuery = $db->prepare("
    SELECT c.id, c.name, c.company_name, c.mobile, c.status as client_status, c.created_at,
           (SELECT MIN(start_date) FROM client_subscriptions WHERE client_id = c.id AND status != 'cancelled') as first_sub_date,
           (SELECT SUM(amount) FROM payments WHERE client_id = c.id) as total_payments
    FROM clients c
    WHERE $whereStr
    ORDER BY first_sub_date ASC, c.created_at DESC
");
$clientsQuery->execute($params);
$rawClients = $clientsQuery->fetchAll();

// جلب جميع الاشتراكات لجميع العملاء لتحليل التجديدات
$subsQuery = $db->query("
    SELECT cs.*, s.name as service_name
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
    ORDER BY cs.client_id, cs.service_id, cs.end_date DESC, cs.start_date DESC
");
$allSubscriptions = $subsQuery->fetchAll();

// تجميع الاشتراكات حسب العميل
$clientSubsMap = [];
foreach ($allSubscriptions as $sub) {
    $clientSubsMap[$sub['client_id']][] = $sub;
}

$processedClients = [];

// إحصائيات عامة للتقرير
$stats = [
    'total' => 0,
    'renewed' => 0,
    'needs_renewal' => 0,
    'expiring_soon' => 0,
    'none' => 0
];

foreach ($rawClients as $client) {
    $cId = $client['id'];
    $subs = $clientSubsMap[$cId] ?? [];
    
    // تحليل التجديدات لكل خدمة
    // نأخذ أحدث اشتراك لكل خدمة (لأن الاستعلام مرتب تنازلياً حسب end_date و start_date)
    $latestServiceSubs = [];
    foreach ($subs as $sub) {
        $sId = $sub['service_id'];
        if (!isset($latestServiceSubs[$sId])) {
            $latestServiceSubs[$sId] = $sub;
        }
    }
    
    $status = 'none'; // الافتراضي لا يوجد اشتراكات
    $expiredServices = [];
    $expiringServices = [];
    $activeServices = [];
    
    if (!empty($latestServiceSubs)) {
        $hasExpired = false;
        $hasExpiringSoon = false;
        
        foreach ($latestServiceSubs as $sId => $sub) {
            $endDate = $sub['end_date'] ? new DateTime($sub['end_date']) : null;
            $today = new DateTime();
            
            if ($sub['status'] === 'expired' || ($endDate && $endDate < $today)) {
                $hasExpired = true;
                $expiredServices[] = $sub['service_name'];
            } else {
                // فحص إذا كان ينتهي خلال 30 يوم
                if ($endDate) {
                    $interval = $today->diff($endDate);
                    $daysLeft = $interval->days;
                    if ($endDate < $today) {
                        $daysLeft = -$daysLeft;
                    }
                    
                    if ($daysLeft >= 0 && $daysLeft <= 30) {
                        $hasExpiringSoon = true;
                        $expiringServices[] = $sub['service_name'] . ' (' . $daysLeft . ' يوم)';
                    } else {
                        $activeServices[] = $sub['service_name'];
                    }
                } else {
                    $activeServices[] = $sub['service_name'];
                }
            }
        }
        
        if ($hasExpired) {
            $status = 'needs_renewal';
        } elseif ($hasExpiringSoon) {
            $status = 'expiring_soon';
        } else {
            $status = 'renewed';
        }
    }
    
    // تحديث الإحصائيات العامة قبل تصفية الفلاتر
    $stats['total']++;
    $stats[$status]++;
    
    $processedClients[] = [
        'id' => $cId,
        'name' => $client['name'],
        'company_name' => $client['company_name'],
        'mobile' => $client['mobile'],
        'client_status' => $client['client_status'],
        'first_sub_date' => $client['first_sub_date'],
        'total_payments' => (float)$client['total_payments'],
        'renewal_status' => $status,
        'expired_services' => $expiredServices,
        'expiring_services' => $expiringServices,
        'active_services' => $activeServices,
        'subs_count' => count($subs)
    ];
}

// تطبيق فلتر حالة التجديد
if ($renewalFilter !== 'all') {
    $processedClients = array_filter($processedClients, function($c) use ($renewalFilter) {
        return $c['renewal_status'] === $renewalFilter;
    });
}

$pageTitle = "تحليل تجديدات وولاء العملاء";
$activePage = 'reports-loyalty';
$depth = 1;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="reports-container" style="padding: 24px 0;">
    
    <!-- رأس الصفحة والبحث -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
        <div>
            <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--text-primary);">
                <i class="fas fa-handshake" style="color:var(--primary); margin-left:8px;"></i>
                تحليل تجديدات وولاء العملاء
            </h2>
            <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:13.5px;">
                تقرير متقدم يوضح مدة اشتراك كل عميل، تاريخ انضمامه وحالة تجديداته للخدمات المختلفة.
            </p>
        </div>
    </div>

    <!-- بطاقات الإحصائيات (KPIs) -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="card" style="border-right: 4px solid var(--primary); padding:16px; background:#fff; margin:0; display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(36,86,164,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">إجمالي العملاء</div>
                <div style="font-size:20px; font-weight:800; color:var(--text-primary); margin-top:4px;"><?= $stats['total'] ?></div>
            </div>
        </div>

        <div class="card" style="border-right: 4px solid var(--success); padding:16px; background:#fff; margin:0; display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(16,185,129,0.1); color:var(--success); display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">مجددون بالكامل</div>
                <div style="font-size:20px; font-weight:800; color:var(--success); margin-top:4px;"><?= $stats['renewed'] ?></div>
            </div>
        </div>

        <div class="card" style="border-right: 4px solid var(--danger); padding:16px; background:#fff; margin:0; display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(239,68,68,0.1); color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">بحاجة لتجديد</div>
                <div style="font-size:20px; font-weight:800; color:var(--danger); margin-top:4px;"><?= $stats['needs_renewal'] ?></div>
            </div>
        </div>

        <div class="card" style="border-right: 4px solid var(--warning); padding:16px; background:#fff; margin:0; display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(245,158,11,0.1); color:var(--warning); display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">ينتهون قريباً (30 يوم)</div>
                <div style="font-size:20px; font-weight:800; color:var(--warning); margin-top:4px;"><?= $stats['expiring_soon'] ?></div>
            </div>
        </div>
    </div>

    <!-- شريط الفلاتر والبحث المتقدم -->
    <div class="card" style="margin-bottom:24px; padding:16px;">
        <form method="GET" action="" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
            <div style="flex:1; min-width:250px;">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="بحث باسم العميل، الشركة، الجوال..." class="form-control" style="width:100%; height:42px; border-radius:8px;">
            </div>
            <div>
                <select name="renewal_status" class="form-control" style="height:42px; width:180px; border-radius:8px;" onchange="this.form.submit()">
                    <option value="all" <?= $renewalFilter === 'all' ? 'selected' : '' ?>>كل حالات التجديد</option>
                    <option value="renewed" <?= $renewalFilter === 'renewed' ? 'selected' : '' ?>>مجدد بالكامل</option>
                    <option value="needs_renewal" <?= $renewalFilter === 'needs_renewal' ? 'selected' : '' ?>>بحاجة لتجديد</option>
                    <option value="expiring_soon" <?= $renewalFilter === 'expiring_soon' ? 'selected' : '' ?>>ينتهي قريباً</option>
                    <option value="none" <?= $renewalFilter === 'none' ? 'selected' : '' ?>>بدون اشتراكات</option>
                </select>
            </div>
            <div>
                <select name="client_status" class="form-control" style="height:42px; width:150px; border-radius:8px;" onchange="this.form.submit()">
                    <option value="active" <?= $clientStatus === 'active' ? 'selected' : '' ?>>عملاء نشطون</option>
                    <option value="suspended" <?= $clientStatus === 'suspended' ? 'selected' : '' ?>>عملاء موقوفون</option>
                    <option value="all" <?= $clientStatus === 'all' ? 'selected' : '' ?>>كل العملاء</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="height:42px; border-radius:8px; font-weight:700; padding:0 24px;">
                    <i class="fas fa-filter" style="margin-left:6px;"></i> تصفية
                </button>
                <?php if ($search || $renewalFilter !== 'all' || $clientStatus !== 'active'): ?>
                <a href="loyalty.php" class="btn btn-outline" style="height:42px; border-radius:8px; font-weight:700; line-height:38px; display:inline-block; vertical-align:middle; text-decoration:none;">
                    إعادة تعيين
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- جدول البيانات -->
    <div class="card" style="margin-bottom:24px;">
        <div class="table-wrapper" style="overflow-x: auto;">
            <table class="data-table" style="width:100%; text-align:center;">
                <thead>
                    <tr style="background:#f8fafc; font-weight:700;">
                        <th style="width: 50px;">#</th>
                        <th style="text-align:right;">العميل / الشركة</th>
                        <th>تاريخ الانضمام</th>
                        <th>مدة التعامل</th>
                        <th>حالة التجديد</th>
                        <th>الاشتراكات الحالية</th>
                        <th>إجمالي المدفوعات</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($processedClients)): ?>
                    <tr>
                        <td colspan="8" style="padding:40px;">
                            <div class="empty-state">
                                <div class="empty-icon" style="color:var(--text-muted);"><i class="fas fa-users-slash"></i></div>
                                <p class="empty-title">لا يوجد عملاء يطابقون خيارات التصفية الحالية</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php 
                    $index = 1;
                    foreach ($processedClients as $c): 
                    ?>
                    <tr>
                        <td class="text-muted"><?= $index++ ?></td>
                        <td style="text-align:right;">
                            <a href="../clients/view.php?id=<?= $c['id'] ?>" style="font-weight:700; color:var(--primary); text-decoration:none;">
                                <?= e($c['name']) ?>
                            </a>
                            <?php if (!$c['client_status']): ?>
                            <span class="badge badge-danger" style="font-size:10px; padding:2px 6px; margin-right:4px;">موقوف</span>
                            <?php endif; ?>
                            <?php if ($c['company_name']): ?>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;"><?= e($c['company_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size:13px;">
                                <?= $c['first_sub_date'] ? formatDate($c['first_sub_date']) : 'لم يشترك بعد' ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold" style="color:var(--text-primary); font-size:13px;">
                                <?= $c['first_sub_date'] ? formatDurationArabic($c['first_sub_date']) : '—' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($c['renewal_status'] === 'renewed'): ?>
                                <span class="badge badge-success" style="font-weight:700; padding:6px 12px; border-radius:6px; font-size:11.5px;">مجدد ونشط</span>
                            <?php elseif ($c['renewal_status'] === 'needs_renewal'): ?>
                                <span class="badge badge-danger" style="font-weight:700; padding:6px 12px; border-radius:6px; font-size:11.5px;" title="<?= e(implode('، ', $c['expired_services'])) ?>">
                                    بحاجة لتجديد
                                    <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                                </span>
                                <div style="font-size:10px; color:var(--danger); margin-top:4px; font-weight:600; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e(implode('، ', $c['expired_services'])) ?>">
                                    <?= e(implode('، ', $c['expired_services'])) ?>
                                </div>
                            <?php elseif ($c['renewal_status'] === 'expiring_soon'): ?>
                                <span class="badge badge-warning" style="font-weight:700; padding:6px 12px; border-radius:6px; font-size:11.5px;" title="<?= e(implode('، ', $c['expiring_services'])) ?>">
                                    ينتهي قريباً
                                    <i class="fas fa-clock" style="margin-right:4px;"></i>
                                </span>
                                <div style="font-size:10px; color:var(--warning); margin-top:4px; font-weight:600; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e(implode('، ', $c['expiring_services'])) ?>">
                                    <?= e(implode('، ', $c['expiring_services'])) ?>
                                </div>
                            <?php else: ?>
                                <span class="badge" style="background:#e2e8f0; color:#475569; font-weight:700; padding:6px 12px; border-radius:6px; font-size:11.5px;">بدون اشتراكات</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info" style="font-size:12px; font-weight:700;">
                                <?= $c['subs_count'] ?> <?= $c['subs_count'] > 10 ? 'اشتراكات' : ($c['subs_count'] >= 3 ? 'اشتراكات' : 'اشتراك') ?>
                            </span>
                        </td>
                        <td style="font-weight:800; color:var(--success);">
                            <?= formatMoney($c['total_payments']) ?>
                        </td>
                        <td>
                            <a href="../clients/view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" style="padding:4px 10px; font-size:12px; border-radius:6px; font-weight:700;">
                                <i class="fas fa-eye" style="margin-left:4px;"></i> ملف العميل
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>
