// dashboard/save_chapter.php

<?php
/*
=====================================================
    NovelWorld - Save Chapter Script
    Version: 1.0
=====================================================
    - این اسکریپت داده‌های ارسال شده از فرم manage_chapter.php را پردازش می‌کند.
    - بر اساس وجود chapter_id، عملیات INSERT (ایجاد) یا UPDATE (ویرایش) را انجام می‌دهد.
    - برای امنیت، مالکیت ناول توسط کاربر لاگین کرده را قبل از هر عملیاتی بررسی می‌کند.
*/

// --- گام ۱: فراخوانی هدر و بررسی‌های اولیه ---

// فراخوانی هدر داشبورد برای دسترسی به اتصال دیتابیس ($conn)
// و اطلاعات کاربر لاگین کرده ($is_logged_in, $user_id)
require_once 'header.php';

// بررسی می‌کنیم که کاربر لاگین کرده باشد
if (!$is_logged_in) {
    die("خطای دسترسی: لطفاً ابتدا وارد شوید.");
}

// بررسی می‌کنیم که درخواست حتماً از طریق متد POST ارسال شده باشد.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // اگر کسی به صورت مستقیم به این فایل دسترسی پیدا کرد، عملیات را متوقف می‌کنیم.
    header("Location: index.php"); // یا هدایت به صفحه اصلی داشبورد
    exit();
}


// --- گام ۲: دریافت و پاکسازی داده‌های فرم ---

// استفاده از intval برای تبدیل رشته به عدد صحیح و جلوگیری از تزریق کد
$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0; // اگر چپتر جدید باشد، این مقدار 0 خواهد بود
$chapter_number = isset($_POST['chapter_number']) ? intval($_POST['chapter_number']) : 0;

// استفاده از trim برای حذف فضاهای خالی اضافی از ابتدا و انتهای رشته
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
// محتوای HTML از TinyMCE را به همان صورت دریافت می‌کنیم (نباید trim شود)
$content = isset($_POST['content']) ? $_POST['content'] : '';

// تعیین اینکه آیا در حالت ویرایش هستیم یا ایجاد
$is_editing = $chapter_id > 0;


// --- گام ۳: اعتبارسنجی داده‌ها ---

// یک اعتبارسنجی ساده برای اطمینان از اینکه فیلدهای ضروری خالی نیستند.
if ($novel_id === 0 || $chapter_number === 0 || empty($title) || empty($content)) {
    // در یک اپلیکیشن واقعی، بهتر است کاربر را به صفحه قبل با یک پیام خطا بازگردانیم.
    die("خطا: اطلاعات ناقص است. شماره چپتر، عنوان و محتوا الزامی هستند.");
}


// --- گام ۴: تعامل با دیتابیس ---

try {
    // ۱. بررسی امنیتی: آیا کاربر فعلی واقعاً مالک این ناول است؟
    // این کار از ویرایش ناول‌های دیگران توسط یک کاربر جلوگیری می‌کند.
    $stmt_check = $conn->prepare("SELECT id FROM novels WHERE id = ? AND author_id = ?");
    $stmt_check->execute([$novel_id, $user_id]);
    if (!$stmt_check->fetch()) {
        die("خطای امنیتی: شما مجوز دسترسی به این ناول را ندارید.");
    }

    if ($is_editing) {
        // --- ۲.الف: حالت ویرایش -> آپدیت کردن رکورد موجود ---
        $sql = "UPDATE chapters SET chapter_number = ?, title = ?, content = ?, updated_at = NOW() WHERE id = ? AND novel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$chapter_number, $title, $content, $chapter_id, $novel_id]);
    } else {
        // --- ۲.ب: حالت ایجاد -> افزودن رکورد جدید ---
        $sql = "INSERT INTO chapters (novel_id, chapter_number, title, content) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$novel_id, $chapter_number, $title, $content]);
    }
    
    // ۳. هدایت کاربر به صفحه جزئیات ناول با یک پیام موفقیت در URL
    // اضافه کردن #chapters باعث می‌شود صفحه مستقیماً به بخش لیست چپترها اسکرول شود.
    header("Location: ../novel_detail.php?id=" . $novel_id . "&status=chapter_saved#chapters");
    exit();

} catch (PDOException $e) {
    // مدیریت خطاهای احتمالی دیتابیس

    // بررسی خطای شماره چپتر تکراری (unique constraint violation)
    // کد '23505' مخصوص خطای unique violation در PostgreSQL است.
    if ($e->getCode() == '23505') {
        // در این حالت، یک پیام خطای واضح به کاربر نمایش می‌دهیم.
        die("خطا: شماره چپتر تکراری است. لطفاً به صفحه قبل بازگشته و یک شماره دیگر انتخاب کنید.");
    }

    // برای سایر خطاهای دیتابیس، یک پیام عمومی نمایش داده و خطای واقعی را لاگ می‌کنیم.
    error_log("Save Chapter DB Error: " . $e->getMessage());
    die("خطای دیتابیس. عملیات با شکست مواجه شد. لطفاً بعداً تلاش کنید.");
}
?>
