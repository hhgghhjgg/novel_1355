// toggle_library.php

<?php
/*
=====================================================
    NovelWorld - Toggle Library Item (AJAX Endpoint)
    Version: 1.0
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای افزودن یا حذف یک ناول
      از کتابخانه شخصی کاربر عمل می‌کند.
    - ورودی را به صورت JSON دریافت کرده و خروجی را نیز به صورت JSON برمی‌گرداند.
    - از core.php برای احراز هویت و اتصال به دیتابیس استفاده می‌کند.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---

// فراخوانی فایل هسته که شامل اتصال دیتابیس ($conn) و اطلاعات کاربر
// ($is_logged_in, $user_id) است و هیچ خروجی HTML ای ندارد.
require_once 'core.php';

// تنظیم هدر خروجی به application/json تا مرورگر بداند با یک پاسخ JSON روبرو است.
header('Content-Type: application/json');


// --- گام ۲: بررسی امنیت و مجوز دسترسی ---
if (!$is_logged_in) {
    // اگر کاربر لاگین نکرده بود، با کد خطای 401 (Unauthorized) پاسخ می‌دهیم.
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'برای افزودن به کتابخانه، لطفاً ابتدا وارد شوید.']);
    exit();
}


// --- گام ۳: دریافت و اعتبارسنجی ورودی ---

// داده‌ها به صورت JSON خام از بدنه درخواست ارسال می‌شوند.
$data = json_decode(file_get_contents('php://input'), true);
$novel_id = isset($data['novel_id']) ? intval($data['novel_id']) : 0;

if ($novel_id <= 0) {
    // اگر شناسه ناول نامعتبر بود، با کد خطای 400 (Bad Request) پاسخ می‌دهیم.
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'شناسه ناول نامعتبر است.']);
    exit();
}


// --- گام ۴: منطق اصلی (Toggle) و تعامل با دیتابیس ---

try {
    // ۱. بررسی می‌کنیم که آیا این ناول از قبل در کتابخانه کاربر وجود دارد یا نه.
    $stmt_check = $conn->prepare("SELECT id FROM library_items WHERE user_id = ? AND novel_id = ?");
    $stmt_check->execute([$user_id, $novel_id]);
    $existing_item = $stmt_check->fetch();

    if ($existing_item) {
        // --- ۲.الف: اگر وجود داشت، آن را حذف می‌کنیم ---
        $stmt_delete = $conn->prepare("DELETE FROM library_items WHERE id = ?");
        $stmt_delete->execute([$existing_item['id']]);
        
        // پاسخ موفقیت‌آمیز با اکشن 'removed'
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'اثر از کتابخانه شما حذف شد.'
        ]);

    } else {
        // --- ۲.ب: اگر وجود نداشت، آن را اضافه می‌کنیم ---
        $stmt_insert = $conn->prepare("INSERT INTO library_items (user_id, novel_id) VALUES (?, ?)");
        $stmt_insert->execute([$user_id, $novel_id]);
        
        // پاسخ موفقیت‌آمیز با اکشن 'added'
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'اثر با موفقیت به کتابخانه شما اضافه شد.'
        ]);
    }

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، آن را لاگ کرده و یک پاسخ خطای عمومی برمی‌گردانیم.
    error_log("Toggle Library DB Error: " . $e->getMessage());
    // ارسال کد خطای 500 (Internal Server Error)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطایی در سرور رخ داد. لطفاً دوباره تلاش کنید.']);
}
?>
