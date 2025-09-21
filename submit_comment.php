<?php
// submit_comment.php

/*
=====================================================
    NovelWorld - Submit Comment Script
    Version: 3.0 (Standalone, No Header Include)
=====================================================
    - این اسکریپت نظرات را پردازش می‌کند و برای جلوگیری از خطای "headers already sent"،
      فایل header.php را که شامل کدهای HTML است، فراخوانی نمی‌کند.
    - منطق اتصال به دیتابیس و احراز هویت کاربر را مستقیماً در خود دارد.
*/

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
// این فایل فقط اطلاعات اتصال را فراهم می‌کند و هیچ خروجی‌ای ندارد.
require_once 'db_connect.php';


// --- گام ۲: منطق احراز هویت (کپی شده از هدر برای عملکرد مستقل) ---
$is_logged_in = false;
$user_id = null;
$username = 'کاربر مهمان';

if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    
    try {
        // کوئری برای پیدا کردن کاربر معتبر از طریق شناسه سشن
        $stmt_auth = $conn->prepare(
            "SELECT u.id, u.username 
             FROM users u 
             JOIN sessions s ON u.id = s.user_id 
             WHERE s.session_id = ? AND s.expires_at > NOW()"
        );
        $stmt_auth->execute([$session_id]);
        $user = $stmt_auth->fetch();
        
        if ($user) {
            $is_logged_in = true;
            $user_id = $user['id'];
            $username = $user['username']; // اینجا نیازی به htmlspecialchars نیست
        }
    } catch (PDOException $e) {
        // در صورت بروز خطا، کاربر لاگین نشده باقی می‌ماند.
        error_log("Submit Comment Auth Error: " . $e->getMessage());
    }
}


// --- گام ۳: بررسی امنیت و مجوز دسترسی ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

if (!$is_logged_in) {
    // اگر کاربر لاگین نکرده بود، او را به صفحه ورود هدایت می‌کنیم.
    header("Location: login.php?error=not_logged_in");
    exit();
}


// --- گام ۴: دریافت، پاکسازی و اعتبارسنجی داده‌های فرم ---

$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$is_spoiler = isset($_POST['is_spoiler']) ? 1 : 0;
$parent_id = (isset($_POST['parent_id']) && intval($_POST['parent_id']) > 0) ? intval($_POST['parent_id']) : null;

// اعتبارسنجی نهایی
if ($novel_id <= 0 || empty($content)) {
    header("Location: novel_detail.php?id=" . $novel_id . "&error=invalid_data");
    exit();
}


// --- گام ۵: ذخیره اطلاعات در دیتابیس ---

try {
    $sql = "INSERT INTO comments (novel_id, parent_id, user_id, user_name, content, is_spoiler) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        $novel_id,
        $parent_id,
        $user_id, // از گام ۲ می‌آید
        $username, // از گام ۲ می‌آید
        $content,
        $is_spoiler
    ]);

    // هدایت کاربر به صفحه قبل با پیام موفقیت
    header("Location: novel_detail.php?id=" . $novel_id . "&status=comment_success#comments");
    exit();

} catch (PDOException $e) {
    error_log("Submit Comment DB Error: " . $e->getMessage());
    header("Location: novel_detail.php?id=" . $novel_id . "&error=db_error#comments");
    exit();
}
?>
