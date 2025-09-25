// dashboard/core.php
<?php
/*
    این فایل هسته اصلی پنل نویسندگی است.
    - اتصال دیتابیس را برقرار می‌کند.
    - وضعیت لاگین کاربر را بررسی و از پنل محافظت می‌کند.
    - هیچ خروجی HTML ای تولید نمی‌کند.
*/

// فراخوانی فایل اتصال به دیتابیس از ریشه پروژه
require_once __DIR__ . '/../db_connect.php';

// منطق احراز هویت کاربر
$is_logged_in = false;
$user_id = null;
$username = 'کاربر مهمان';

if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    try {
        $stmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN sessions s ON u.id = s.user_id WHERE s.session_id = ? AND s.expires_at > NOW()");
        $stmt->execute([$session_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $is_logged_in = true;
            $user_id = $user['id'];
            $username = htmlspecialchars($user['username']);
        }
    } catch (PDOException $e) {
        error_log("Dashboard Core Auth Error: " . $e->getMessage());
    }
}

// محافظت از تمام فایل‌هایی که این هسته را فراخوانی می‌کنند
if (!$is_logged_in) {
    header("Location: ../login.php");
    exit();
}
?>
