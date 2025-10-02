<?php
// toggle_follow.php

/*
=====================================================
    NovelWorld - Toggle Follow (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای دنبال کردن یا لغو دنبال کردن
      یک کاربر توسط کاربر دیگر عمل می‌کند.
    - ورودی را به صورت JSON دریافت کرده و خروجی را نیز به صورت JSON برمی‌گرداند.
    - از core.php برای احراز هویت و اتصال به دیتابیس استفاده می‌کند.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---
require_once 'core.php';
header('Content-Type: application/json');

// --- گام ۲: بررسی‌های امنیتی و اولیه ---

if ($conn === null) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'سرویس در حال حاضر در دسترس نیست.']);
    exit();
}

if (!$is_logged_in) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'برای دنبال کردن کاربران، لطفاً ابتدا وارد شوید.']);
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
$profile_id_to_follow = isset($data['profile_id']) ? intval($data['profile_id']) : 0;
$follower_id = $user_id; // شناسه کاربری که در حال انجام این عمل است

if ($profile_id_to_follow <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'شناسه پروفایل نامعتبر است.']);
    exit();
}

// یک کاربر نمی‌تواند خودش را دنبال کند
if ($follower_id == $profile_id_to_follow) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'شما نمی‌توانید خودتان را دنبال کنید.']);
    exit();
}

// --- گام ۴: منطق اصلی (Toggle) و تعامل با دیتابیس ---
try {
    // ۱. بررسی می‌کنیم که آیا این کاربر از قبل دنبال شده است یا نه.
    $stmt_check = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt_check->execute([$follower_id, $profile_id_to_follow]);
    $existing_follow = $stmt_check->fetch();

    if ($existing_follow) {
        // --- ۲.الف: اگر دنبال شده بود، آن را لغو می‌کنیم (Unfollow) ---
        $stmt_delete = $conn->prepare("DELETE FROM followers WHERE id = ?");
        $stmt_delete->execute([$existing_follow['id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'unfollowed',
            'message' => 'کاربر با موفقیت لغو دنبال شد.'
        ]);
    } else {
        // --- ۲.ب: اگر دنبال نشده بود، آن را اضافه می‌کنیم (Follow) ---
        $stmt_insert = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt_insert->execute([$follower_id, $profile_id_to_follow]);
        
        echo json_encode([
            'success' => true,
            'action' => 'followed',
            'message' => 'کاربر با موفقیت دنبال شد.'
        ]);
    }

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، آن را لاگ کرده و یک پاسخ خطای عمومی برمی‌گردانیم.
    error_log("Toggle Follow DB Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'خطایی در سرور رخ داد. لطفاً دوباره تلاش کنید.']);
}
?>
