<?php
/**
 * نظام المصادقة والجلسات - نظام إنجاز
 */

/**
 * التحقق من أن المستخدم مسجّل الدخول
 * إذا لم يكن كذلك، يُعيد توجيهه لصفحة الدخول
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $loginUrl = getBaseUrl() . '/login.php';
        header("Location: $loginUrl");
        exit;
    }
}

/**
 * التحقق من صلاحية معينة للمستخدم الحالي
 */
function hasPermission(string $permission): bool {
    if (empty($_SESSION['user_id'])) return false;
    // المدير له كل الصلاحيات
    if (($_SESSION['user_role'] ?? '') === 'admin') return true;
    $permissions = $_SESSION['user_permissions'] ?? [];
    return in_array($permission, $permissions, true);
}

/**
 * التحقق من صلاحية وإعادة التوجيه إذا لم تكن موجودة
 */
function requirePermission(string $permission): void {
    requireLogin();
    if (!hasPermission($permission)) {
        $baseUrl = getBaseUrl();
        header("Location: $baseUrl/403.php");
        exit;
    }
}

/**
 * هل المستخدم مدير؟
 */
function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * معرّف المستخدم الحالي
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * اسم المستخدم الحالي
 */
function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'مجهول';
}

/**
 * تسجيل الدخول - التحقق من بيانات المستخدم
 */
function loginUser(string $username, string $password): array {
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM `users` WHERE `username` = ? AND `status` = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'اسم المستخدم غير صحيح'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'كلمة المرور غير صحيحة'];
        }

        // تحديث آخر تسجيل دخول
        $db->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?")->execute([$user['id']]);

        // تعيين بيانات الجلسة
        $_SESSION['user_id']          = (int)$user['id'];
        $_SESSION['user_name']        = $user['full_name'];
        $_SESSION['user_username']    = $user['username'];
        $_SESSION['user_role']        = $user['role'];
        $_SESSION['user_permissions'] = json_decode($user['permissions'] ?? '[]', true) ?? [];

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'خطأ في الخادم'];
    }
}

/**
 * تسجيل الخروج
 */
function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}
