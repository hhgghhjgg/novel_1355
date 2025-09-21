<?php
// submit_comment.php

/*
=====================================================
    NovelWorld - Submit Comment Script
    Version: 2.0 (Serverless Ready - JWT Auth)
=====================================================
    - این اسکریپت وظیفه دریافت و ذخیره نظرات جدید و پاسخ‌ها را در دیتابیس بر عهده دارد.
    - هویت کاربر از طریق سیستم JWT که در header.php پیاده‌سازی شده، تایید می‌شود.
    - داده‌ها با استفاده از PDO در جدول `comments` دیتابیس PostgreSQL (Neon) ذخیره می‌شوند.
    - پس از ثبت موفق، کاربر به صفحه جزئیات همان ناول بازگردانده می‌شود.
*/

// --- گام ۱: فراخوانی هدر اصلی سایت ---
// این فایل شامل اتصال دیتابیس ($conn) و اطلاعات کاربر لاگین کرده
// ($is_logged_in, $user_id, $username) است.
require_once 'header.php';

// --- گام ۲: بررسی امنیت و مجوز دسترسی ---

// بررسی می‌کنیم که درخواست حتماً از طریق متد POST ارسال شده باشد.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // اگر کسی به صورت مستقیم به این فایل دسترسی پیدا کرد، او را به صفحه اصلی بفرست.
    header("Location: index.php");
    exit();
}

// بررسی می‌کنیم که کاربر لاگین کرده باشد.
if (!$is_logged_in) {
    // اگر لاگین نکرده بود، می‌توانیم او را به صفحه ورود هدایت کنیم.
    // ارسال یک پیام خطا در URL می‌تواند به نمایش پیغام مناسب در صفحه بعد کمک کند.
    header("Location: login.php?error=not_logged_in");
    exit();
}


// --- گام ۳: دریافت، پاکسازی و اعتبارسنجی داده‌های فرم ---

// استفاده از intval برای اطمینان از اینکه IDها عدد هستند.
$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
// پاکسازی محتوای کامنت از فضاهای خالی اضافی در ابتدا و انتها.
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// دریافت اطلاعات کاربر از متغیرهای سراسری (که از توکن JWT استخراج شده‌اند).
$user_id_from_token = $user_id;
$user_name_from_token = $username;

// بررسی اینکه آیا کامنت به عنوان اسپویلر علامت خورده است یا نه.
$is_spoiler = isset($_POST['is_spoiler']) ? 1 : 0;

// بررسی اینکه آیا این یک ریپلای است یا یک کامنت اصلی.
// اگر parent_id ارسال شده و عددی بزرگتر از صفر بود، آن را به عنوان parent_id در نظر می‌گیریم.
$parent_id = (isset($_POST['parent_id']) && intval($_POST['parent_id']) > 0) ? intval($_POST['parent_id']) : null;


// اعتبارسنجی نهایی: مطمئن می‌شویم که ID اثر و متن کامنت خالی نیستند.
if ($novel_id <= 0 || empty($content)) {
    // اگر داده‌ها نامعتبر بودند، کاربر را به صفحه قبل بازمی‌گردانیم و یک پیام خطا نمایش می‌دهیم.
    // این روش بهتر از نمایش یک صفحه سفید با پیام خطا است.
    header("Location: novel_detail.php?id=" . $novel_id . "&error=invalid_data");
    exit();
}


// --- گام ۴: ذخیره اطلاعات در دیتابیس با استفاده از PDO ---

try {
    // کوئری INSERT برای ذخیره کامنت در دیتابیس
    // از placeholderهای (?) برای جلوگیری از حملات SQL Injection استفاده می‌کنیم.
    $sql = "INSERT INTO comments (novel_id, parent_id, user_id, user_name, content, is_spoiler) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // اجرای کوئری با ارسال آرایه‌ای از مقادیر به ترتیب placeholderها
    $stmt->execute([
        $novel_id,
        $parent_id,
        $user_id_from_token,
        $user_name_from_token,
        $content,
        $is_spoiler
    ]);

    // در صورت موفقیت، کاربر را به صفحه جزئیات همان ناول بازمی‌گردانیم.
    // اضافه کردن یک پارامتر success می‌تواند به نمایش یک پیام "نظر شما ثبت شد" کمک کند.
    // همچنین با اضافه کردن #comments، صفحه مستقیماً به بخش نظرات اسکرول می‌شود.
    header("Location: novel_detail.php?id=" . $novel_id . "&status=comment_success#comments");
    exit();

} catch (PDOException $e) {
    // در صورت بروز خطا در دیتابیس، آن را لاگ کرده و یک پیام خطای عمومی نمایش می‌دهیم.
    error_log("Submit Comment Error: " . $e->getMessage());
    
    // کاربر را به صفحه قبل بازمی‌گردانیم و یک پیام خطای عمومی نشان می‌دهیم.
    header("Location: novel_detail.php?id=" . $novel_id . "&error=db_error#comments");
    exit();
}

?>
