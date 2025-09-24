// admin/header.php (نسخه دیباگ)
<?php
/*
    این یک نسخه دیباگ برای پیدا کردن مشکل عدم ورود به پنل ادمین است.
*/

require_once __DIR__ . '/../db_connect.php';

// --- شروع بخش دیباگ ---
echo "<pre style='background: #111; color: lime; padding: 15px; font-family: monospace; direction: ltr;'>";
echo "DEBUG MODE ACTIVATED IN admin/header.php\n\n";

// ۱. بررسی کوکی
if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    echo "Cookie 'user_session' found: " . htmlspecialchars($session_id) . "\n";
} else {
    echo "Cookie 'user_session' NOT FOUND. User is not logged in.\n";
    echo "</pre>";
    die(); // اگر کوکی نبود، ادامه نده
}

// ۲. بررسی اتصال به دیتابیس
if ($conn) {
    echo "Database connection successful.\n";
} else {
    echo "Database connection FAILED.\n";
    echo "</pre>";
    die();
}

// ۳. اجرای کوئری و بررسی نتیجه
try {
    $stmt = $conn->prepare(
        "SELECT u.id, u.username, u.role 
         FROM users u 
         JOIN sessions s ON u.id = s.user_id 
         WHERE s.session_id = ? AND s.expires_at > NOW()"
    );
    $stmt->execute([$session_id]);
    $user = $stmt->fetch();
    
    echo "Query executed to find user by session ID.\n";
    
    if ($user) {
        echo "User FOUND in database:\n";
        print_r($user); // چاپ کامل اطلاعات کاربر پیدا شده
        
        if ($user['role'] === 'admin') {
            echo "\nSUCCESS: User role is 'admin'. Access should be granted.\n";
        } else {
            echo "\nERROR: User role is NOT 'admin'. Role found: '" . htmlspecialchars($user['role']) . "'. Access denied.\n";
        }

    } else {
        echo "\nERROR: No valid user found for this session ID. The session might be expired or invalid.\n";
    }

} catch (PDOException $e) {
    echo "DATABASE QUERY FAILED: " . $e->getMessage() . "\n";
}

echo "</pre>";
die(); // --- اجرای اسکریپت را در اینجا متوقف می‌کنیم تا بتوانیم خروجی را ببینیم ---


// کد اصلی شما از اینجا به بعد اجرا نخواهد شد
// ...
?>
