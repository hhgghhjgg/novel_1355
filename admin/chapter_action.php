<?php
// admin/chapter_action.php

/*
=====================================================
    NovelWorld - Chapter Action Processor
    Version: 1.0
=====================================================
    - این اسکریپت درخواست‌های تایید (approve) و رد (reject) چپترها را
      از صفحه approve_chapters.php پردازش می‌کند.
    - دسترسی به این فایل فقط برای مدیران سایت مجاز است.
*/

// --- گام ۱: فراخوانی هدر پنل مدیریت ---
// این فایل مسئولیت احراز هویت و بررسی نقش ادمین را بر عهده دارد.
// اگر کاربر ادمین نباشد، اجرای اسکریپت در همین خط متوقف خواهد شد.
require_once 'header.php';


// --- گام ۲: دریافت و اعتبارسنجی پارامترهای URL ---

// دریافت اکشن (approve یا reject)
$action = isset($_GET['action']) ? $_GET['action'] : '';
// دریافت شناسه چپتر
$chapter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// لیست سفید (Whitelist) برای اقدامات مجاز
$allowed_actions = ['approve', 'reject'];
$new_status = '';

// بر اساس اکشن، وضعیت جدید را تعیین می‌کنیم
if ($action === 'approve') {
    $new_status = 'approved';
} elseif ($action === 'reject') {
    $new_status = 'rejected';
}

// بررسی می‌کنیم که آیا اکشن معتبر و شناسه چپتر صحیح است یا نه
if (!in_array($action, $allowed_actions) || $chapter_id <= 0) {
    // اگر داده‌ها نامعتبر بودند، کاربر را به صفحه قبل بازمی‌گردانیم
    // می‌توانیم یک پیام خطا هم در URL اضافه کنیم
    header("Location: approve_chapters.php?error=invalid_request");
    exit();
}


// --- گام ۳: به‌روزرسانی وضعیت چپتر در دیتابیس ---

try {
    // کوئری برای آپدیت کردن ستون 'status' در جدول 'chapters'
    $stmt = $conn->prepare("UPDATE chapters SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $chapter_id]);
    
    // (اختیاری) می‌توانید بررسی کنید که آیا ردیفی تحت تاثیر قرار گرفته است یا نه
    // if ($stmt->rowCount() > 0) { ... }

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، آن را لاگ کرده و عملیات را متوقف می‌کنیم.
    error_log("Chapter Action DB Error: " . $e->getMessage());
    die("خطایی در هنگام تغییر وضعیت چپتر رخ داد. لطفاً دوباره تلاش کنید.");
}


// --- گام ۴: هدایت مدیر به صفحه قبل ---

// پس از انجام موفقیت‌آمیز عملیات، مدیر را به صفحه لیست چپترهای در انتظار تایید بازمی‌گردانیم.
// اضافه کردن یک پارامتر وضعیت می‌تواند به نمایش یک پیام موفقیت‌آمیز کمک کند.
header("Location: approve_chapters.php?status=success&action=" . $action);
exit();

?>
