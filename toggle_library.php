<?php
// toggle_library.php

/*
=====================================================
    NovelWorld - Toggle Library Item (AJAX Endpoint)
    Version: 1.1 (Final, Core-Dependent)
=====================================================
    - این اسکریپت برای افزودن/حذف ناول از کتابخانه کاربر عمل می‌کند.
    - به فایل core.php برای اتصال دیتابیس و احراز هویت وابسته است.
    - خروجی آن همیشه در فرمت JSON است.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---
require_once 'core.php';
header('Content-Type: application/json');


// --- گام ۲: بررسی‌های امنیتی و اولیه ---

// بررسی می‌کنیم که آیا اتصال به دیتابیس در core.php موفقیت‌آمیز بوده است یا نه
if ($conn === null) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'سرویس در حال حاضر در دسترس نیست. لطفاً بعداً تلاش کنید.']);
    exit();
}

// بررسی لاگین بودن کاربر
if (!$is_logged_in) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد شوید.']);
    exit();
}


// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
$novel_id = isset($data['novel_id']) ? intval($data['novel_id']) : 0;

if ($novel_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'شناسه ناول نامعتبر است.']);
    exit();
}


// --- گام ۴: منطق اصلی (Toggle) ---
try {
    // بررسی اینکه آیا ناول از قبل در کتابخانه هست یا نه
    $stmt_check = $conn->prepare("SELECT id FROM library_items WHERE user_id = ? AND novel_id = ?");
    $stmt_check->execute([$user_id, $novel_id]);
    $existing_item = $stmt_check->fetch();

    if ($existing_item) {
        // اگر بود، آن را حذف کن
        $stmt_delete = $conn->prepare("DELETE FROM library_items WHERE id = ?");
        $stmt_delete->execute([$existing_item['id']]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'اثر از کتابخانه حذف شد.']);
    } else {
        // اگر نبود، آن را اضافه کن
        $stmt_insert = $conn->prepare("INSERT INTO library_items (user_id, novel_id) VALUES (?, ?)");
        $stmt_insert->execute([$user_id, $novel_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'اثر به کتابخانه اضافه شد.']);
    }

} catch (PDOException $e) {
    error_log("Toggle Library DB Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'خطایی در سرور رخ داد.']);
}
?>
