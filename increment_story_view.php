<?php
// increment_story_view.php

/*
=====================================================
    NovelWorld - Increment Story View (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای افزایش شمارنده بازدید
      یک "استوری ناول" عمل می‌کند.
    - این اسکریپت بسیار سبک است و برای فراخوانی‌های مکرر بهینه شده است.
    - برای جلوگیری از افزایش بازدید توسط ربات‌ها یا تقلب، می‌توان در آینده
      مکانیسم‌های امنیتی بیشتری به آن اضافه کرد.
    - خروجی آن یک پاسخ خالی با کد وضعیت موفقیت است.
*/

// --- گام ۱: فراخوانی فایل هسته ---
// فقط برای اتصال به دیتابیس
require_once 'core.php';

// --- گام ۲: بررسی‌های اولیه ---

// ما به یک پاسخ JSON نیاز نداریم، اما مطمئن می‌شویم که خطاها نمایش داده نشوند.
header('Content-Type: text/plain');

if ($conn === null) {
    // اگر اتصال دیتابیس برقرار نبود، با یک خطای سرور خارج می‌شویم.
    header('HTTP/1.1 503 Service Unavailable');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
$story_id = isset($data['story_id']) ? intval($data['story_id']) : 0;

if ($story_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

// --- گام ۴: منطق اصلی (افزایش شمارنده) ---
try {
    // کوئری UPDATE برای افزایش یک واحدی ستون `views`
    // این یک عملیات اتمی (atomic) در دیتابیس است و بسیار سریع اجرا می‌شود.
    $sql = "UPDATE novel_stories SET views = views + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$story_id]);

    // اگر عملیات موفق بود، یک پاسخ "204 No Content" ارسال می‌کنیم.
    // این بهترین نوع پاسخ برای درخواست‌هایی است که با موفقیت انجام شده‌اند
    // اما نیازی به بازگرداندن هیچ محتوایی ندارند.
    http_response_code(204);

} catch (PDOException $e) {
    // در صورت بروز خطا، آن را در لاگ سرور ثبت می‌کنیم.
    // نیازی نیست به کاربر خطایی نمایش دهیم، چون این یک عملیات پس‌زمینه است.
    error_log("Increment Story View Error: " . $e->getMessage());
    
    // با یک کد خطای سرور پاسخ می‌دهیم.
    http_response_code(500);
}

// پایان اجرای اسکریپت
exit();
?>
