<?php
/**
 * login.php - صفحة تسجيل الدخول
 */
require_once __DIR__ . '/config/app.php';

// لو مسجّل دخول بالفعل
if (!empty($_SESSION['user_id'])) {
    header('Location: reports/renewals.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'خطأ في التحقق من الأمان، حاول مرة أخرى.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
        } else {
            $result = loginUser($username, $password);
            if ($result['success']) {
                header('Location: reports/renewals.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$companyName = getSetting('company_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول — <?= e($companyName) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { direction: rtl; font-size: 15px; overflow-x: hidden; height: 100%; }
    body {
      font-family: 'Cairo', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0e1e35;
      position: relative;
      overflow-x: hidden;
      line-height: 1.6;
      width: 100%;
    }

    /* Background decoration on .bg-grid instead of body to prevent scrolling overflow */
    .bg-grid::before {
      content: '';
      position: absolute;
      top: -120px; right: -120px;
      width: 420px; height: 420px;
      background: radial-gradient(circle, rgba(240,165,0,.15) 0%, transparent 70%);
      border-radius: 50%;
    }
    .bg-grid::after {
      content: '';
      position: absolute;
      bottom: -100px; left: -100px;
      width: 360px; height: 360px;
      background: radial-gradient(circle, rgba(36,86,164,.2) 0%, transparent 70%);
      border-radius: 50%;
    }

    /* Grid pattern */
    .bg-grid {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
      background-size: 40px 40px;
      overflow: hidden;
      z-index: 1;
    }

    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 440px;
      padding: 20px;
    }

    /* Logo Area */
    .login-logo {
      text-align: center;
      margin-bottom: 32px;
      animation: fadeDown .5s ease both;
    }

    .login-logo .logo-circle {
      width: 72px;
      height: 72px;
      background: linear-gradient(135deg, #f0a500, #c88200);
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      color: #fff;
      margin-bottom: 14px;
      box-shadow: 0 8px 28px rgba(240,165,0,.4);
    }

    .login-logo h1 {
      font-size: 22px;
      font-weight: 900;
      color: #fff;
      margin-bottom: 4px;
    }

    .login-logo p {
      font-size: 13px;
      color: rgba(255,255,255,.5);
    }

    /* Card */
    .login-card {
      background: rgba(255,255,255,.05);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 24px 60px rgba(0,0,0,.4);
      animation: fadeUp .5s ease .1s both;
    }

    .login-card h2 {
      font-size: 18px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 6px;
    }

    .login-card .subtitle {
      font-size: 13px;
      color: rgba(255,255,255,.5);
      margin-bottom: 28px;
    }

    /* Form */
    .form-group { margin-bottom: 18px; }

    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: rgba(255,255,255,.8);
      margin-bottom: 8px;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,.3);
      font-size: 15px;
      pointer-events: none;
    }

    .form-control {
      width: 100%;
      padding: 12px 42px 12px 16px;
      background: rgba(255,255,255,.08);
      border: 1.5px solid rgba(255,255,255,.12);
      border-radius: 10px;
      font-family: 'Cairo', sans-serif;
      font-size: 14px;
      color: #fff;
      outline: none;
      transition: all .22s ease;
      direction: rtl;
    }

    .form-control::placeholder { color: rgba(255,255,255,.3); }

    .form-control:focus {
      border-color: #f0a500;
      background: rgba(255,255,255,.12);
      box-shadow: 0 0 0 3px rgba(240,165,0,.15);
    }

    .toggle-password {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255,255,255,.35);
      cursor: pointer;
      font-size: 15px;
      padding: 4px;
      transition: color .2s;
    }

    .toggle-password:hover { color: rgba(255,255,255,.7); }

    /* Error */
    .alert-error {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      background: rgba(239,68,68,.15);
      border: 1px solid rgba(239,68,68,.3);
      border-radius: 10px;
      color: #fca5a5;
      font-size: 13.5px;
      margin-bottom: 20px;
      animation: shake .4s ease;
    }

    /* Submit Button */
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #f0a500, #c88200);
      border: none;
      border-radius: 10px;
      font-family: 'Cairo', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: #fff;
      cursor: pointer;
      transition: all .22s ease;
      box-shadow: 0 6px 20px rgba(240,165,0,.35);
      margin-top: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(240,165,0,.45);
    }

    .btn-login:active { transform: scale(.98); }

    /* Footer note */
    .login-note {
      text-align: center;
      margin-top: 22px;
      font-size: 12px;
      color: rgba(255,255,255,.3);
    }

    /* Animations */
    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%       { transform: translateX(-6px); }
      40%       { transform: translateX(6px); }
      60%       { transform: translateX(-4px); }
      80%       { transform: translateX(4px); }
    }

    @media (max-width: 480px) {
      .login-card {
        padding: 24px 20px;
      }
      .login-logo {
        margin-bottom: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="bg-grid"></div>

  <div class="login-wrapper">

    <!-- Logo -->
    <div class="login-logo">
      <div class="logo-circle">
        <i class="fas fa-bolt"></i>
      </div>
      <h1><?= e($companyName) ?></h1>
      <p>نظام إدارة العملاء</p>
    </div>

    <!-- Card -->
    <div class="login-card">
      <h2>مرحباً بك 👋</h2>
      <p class="subtitle">سجّل دخولك للمتابعة</p>

      <?php if ($error): ?>
      <div class="alert-error" id="loginError">
        <i class="fas fa-exclamation-circle"></i>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="login.php" id="loginForm">
        <?= csrfField() ?>

        <div class="form-group">
          <label class="form-label" for="username">
            <i class="fas fa-user" style="margin-left: 6px; opacity:.6;"></i>
            اسم المستخدم
          </label>
          <div class="input-wrapper">
            <i class="fas fa-user input-icon"></i>
            <input
              type="text"
              id="username"
              name="username"
              class="form-control"
              placeholder="أدخل اسم المستخدم"
              value="<?= e($_POST['username'] ?? '') ?>"
              autocomplete="username"
              required
              autofocus
            >
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">
            <i class="fas fa-lock" style="margin-left: 6px; opacity:.6;"></i>
            كلمة المرور
          </label>
          <div class="input-wrapper">
            <i class="fas fa-lock input-icon"></i>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="أدخل كلمة المرور"
              autocomplete="current-password"
              required
            >
            <button type="button" class="toggle-password" id="togglePass" aria-label="إظهار/إخفاء">
              <i class="fas fa-eye" id="togglePassIcon"></i>
            </button>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; font-size:13px; color:rgba(255,255,255,.8);">
          <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
            <input type="checkbox" id="rememberMe" style="accent-color:#f0a500; cursor:pointer; width:16px; height:16px;">
            تذكرني
          </label>
        </div>

        <button type="submit" class="btn-login" id="submitBtn">
          <i class="fas fa-right-to-bracket"></i>
          تسجيل الدخول
        </button>
      </form>

      <p class="login-note">
        <i class="fas fa-shield-halved"></i>
        هذا النظام مخصص للموظفين المصرّح لهم فقط
      </p>
    </div>

  </div>

  <script>
    // Toggle Password
    document.getElementById('togglePass')?.addEventListener('click', function() {
      const passInput = document.getElementById('password');
      const icon      = document.getElementById('togglePassIcon');
      if (passInput.type === 'password') {
        passInput.type = 'text';
        icon.className = 'fas fa-eye-slash';
      } else {
        passInput.type = 'password';
        icon.className = 'fas fa-eye';
      }
    });

    // استعادة البيانات المحفوظة عند تحميل الصفحة
    window.addEventListener('DOMContentLoaded', () => {
      const savedUser = localStorage.getItem('remembered_username');
      const savedPass = localStorage.getItem('remembered_password');
      
      if (savedUser && savedPass) {
        const userEl = document.getElementById('username');
        const passEl = document.getElementById('password');
        const remEl  = document.getElementById('rememberMe');
        if (userEl) userEl.value = savedUser;
        if (passEl) passEl.value = savedPass;
        if (remEl)  remEl.checked = true;
      }
    });

    // حفظ أو مسح البيانات عند إرسال النموذج
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
      const remember = document.getElementById('rememberMe')?.checked;
      const user = document.getElementById('username')?.value;
      const pass = document.getElementById('password')?.value;

      if (remember) {
        localStorage.setItem('remembered_username', user);
        localStorage.setItem('remembered_password', pass);
      } else {
        localStorage.removeItem('remembered_username');
        localStorage.removeItem('remembered_password');
      }

      const btn = document.getElementById('submitBtn');
      btn.innerHTML = '<span style="width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;"></span> جاري التحقق...';
      btn.disabled = true;
    });
  </script>
  <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>
