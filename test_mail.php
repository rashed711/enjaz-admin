<?php
/**
 * test_mail.php - فحص شامل للاتصال بالبريد الإلكتروني من داخل السيرفر
 */
require_once __DIR__ . '/config/app.php';
requireLogin();
if (!isAdmin()) { die('Access denied'); }

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<title>فحص اتصال البريد الإلكتروني</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #f8fafc; padding: 20px; line-height: 1.5; }
  .box { background: #1e293b; border-radius: 8px; padding: 16px; margin-bottom: 16px; border: 1px solid #334155; }
  .success { color: #4ade80; font-weight: bold; }
  .error { color: #f87171; font-weight: bold; }
  .info { color: #38bdf8; }
  h2 { margin-top: 0; color: #f1f5f9; }
  pre { background: #020617; padding: 12px; border-radius: 6px; overflow-x: auto; color: #cbd5e1; }
</style>
</head>
<body>
<h2>🔍 فحص اتصال البريد الإلكتروني وتجربة الإرسال المباشر</h2>

<div class="box">
<?php
$to = 'rashed1711@gmail.com';
$user = 'noreplay@enjaz.app';
$pass = 'Aa@01028855';

echo "<p class='info'>المستلم المستهدف: <strong>$to</strong> | الحساب: <strong>$user</strong></p>";

$targets = [
    ['host' => 'mail.enjaz.app', 'port' => 465, 'ssl' => true],
    ['host' => '127.0.0.1',     'port' => 465, 'ssl' => true],
    ['host' => 'localhost',     'port' => 465, 'ssl' => true],
    ['host' => '127.0.0.1',     'port' => 25,  'ssl' => false],
    ['host' => '127.0.0.1',     'port' => 587, 'ssl' => false],
];

$workingTarget = null;
$socket = null;

foreach ($targets as $idx => $t) {
    $scheme = $t['ssl'] ? 'ssl://' : 'tcp://';
    $remote = $scheme . $t['host'] . ':' . $t['port'];
    echo "<div>جاري فحص: <code>$remote</code> ... ";
    
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $errno = 0;
    $errstr = '';
    $s = @stream_socket_client($remote, $errno, $errstr, 4, STREAM_CLIENT_CONNECT, $ctx);
    
    if ($s) {
        stream_set_timeout($s, 5);
        $greeting = fgets($s, 515);
        echo "<span class='success'>نجح الاتصال! ✅ (" . trim($greeting) . ")</span></div>";
        $workingTarget = $t;
        $socket = $s;
        break;
    } else {
        echo "<span class='error'>فشل: $errno - $errstr ❌</span></div>";
    }
}

if (!$workingTarget || !$socket) {
    echo "<p class='error'>لم ينجح الاتصال بأي منفذ SMTP مباشر. جاري فحص دالة mail() المحلية...</p>";
} else {
    echo "<p class='success'>تم العثور على خادم بريد متصل: <strong>{$workingTarget['host']}:{$workingTarget['port']}</strong>. جاري إرسال رسالة تجريبية الآن مع مرفق...</p>";
    
    function smtpResp($s) {
        $out = '';
        while (!feof($s)) {
            $line = fgets($s, 515);
            if ($line === false) break;
            $out .= $line;
            $meta = stream_get_meta_data($s);
            if (!empty($meta['timed_out'])) break;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $out;
    }
    
    function smtpCmd($s, $cmd) {
        fwrite($s, $cmd . "\r\n");
        return smtpResp($s);
    }
    
    echo "<pre>";
    $r1 = smtpCmd($socket, "EHLO enjaz.app");
    echo "EHLO: " . trim($r1) . "\n";
    
    // Auth login
    $rAuth = smtpCmd($socket, "AUTH LOGIN");
    echo "AUTH: " . trim($rAuth) . "\n";
    smtpCmd($socket, base64_encode($user));
    $rPass = smtpCmd($socket, base64_encode($pass));
    echo "AUTH RES: " . trim($rPass) . "\n";
    
    $rMail = smtpCmd($socket, "MAIL FROM:<$user>");
    echo "MAIL FROM: " . trim($rMail) . "\n";
    
    $rRcpt = smtpCmd($socket, "RCPT TO:<$to>");
    echo "RCPT TO: " . trim($rRcpt) . "\n";
    
    $rData = smtpCmd($socket, "DATA");
    echo "DATA: " . trim($rData) . "\n";
    
    $boundary = "==Test_" . md5(time()) . "==";
    $msg = "From: Enjaz Cloud <$user>\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode("تجربة إرسال مباشر من سيرفر إنجاز") . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $msg .= "Date: " . date('r') . "\r\n\r\n";
    
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= "<h3>تم إرسال هذا البريد بنجاح من داخل سيرفر إنجاز عبر منفذ ({$workingTarget['host']}:{$workingTarget['port']})!</h3>\r\n";
    $msg .= "<p>التوقيت: " . date('Y-m-d H:i:s') . "</p>\r\n\r\n";
    
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8; name=\"test.txt\"\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "Content-Disposition: attachment; filename=\"test.txt\"\r\n\r\n";
    $msg .= chunk_split(base64_encode("Enjaz Direct Server Test")) . "\r\n";
    $msg .= "--$boundary--\r\n";
    
    fwrite($socket, $msg . "\r\n.\r\n");
    $rSent = smtpResp($socket);
    echo "SEND RESULT: " . trim($rSent) . "\n";
    
    smtpCmd($socket, "QUIT");
    fclose($socket);
    echo "</pre>";
    
    if (strpos($rSent, '250') !== false) {
        echo "<h3 class='success'>🎉 تم الإرسال بنجاح 100%! تفقد صندوق الوارد في بريدك ($to).</h3>";
    } else {
        echo "<h3 class='error'>⚠️ حدث خطأ أثناء إتمام الإرسال: " . htmlspecialchars($rSent) . "</h3>";
    }
}
?>
</div>
<p><a href="settings.php" style="color: #38bdf8;">العودة لصفحة الإعدادات</a></p>
</body>
</html>

