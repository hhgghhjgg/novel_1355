<?php
// dashboard/delete_chapter.php

/*
=====================================================
    NovelWorld - Delete Chapter Script
    Version: 1.0
=====================================================
    - این اسکریپت یک چپتر خاص را از دیتابیس حذف می‌کند.
    - ID چپتر و ناول را از طریق URL (متد GET) دریافت می‌کند.
    - مهم‌ترین بخش آن، بررسی امنیتی برای اطمینان از این است که کاربر
      درخواست دهنده، مالک واقعی ناول مربوط به آن چپتر باشد.
*/

// --- گام ۱: فراخوانی هدر و بررسی‌های اولیه ---

// فراخوانی هدر داشبورد برای دسترسی به اتصال دیتابیس ($conn)
// و اطلاعات کاربر لاگین کرده ($is_logged_in, $user_id)
require_once 'header.php';

// بررسی می‌کنیم که کاربر لاگین کرده باشد
if (!$is_logged_in) {
    die("خطای دسترسی: لطفاً ابتدا وارد شوید.");
}

// بررسی می‌کنیم که آیا ID های لازم در URL ارسال شده‌اند یا نه
if (!isset($_GET['chapter_id']) || !isset($_GET['novel_id'])) {
    die("خطا: اطلاعات لازم برای حذف چپتر ارسال نشده است.");
}


// --- گام ۲: دریافت و پاکسازی داده‌های URL ---

// استفاده از intval برای تبدیل رشته به عدد صحیح و امنیت
$chapter_id = intval($_GET['chapter_id']);
$novel_id = intval($_GET['novel_id']);

if ($chapter_id <= 0 || $novel_id <= 0) {
    die("خطا: شناسه‌های نامعتبر.");
}


// --- گام ۳: تعامل با دیتابیس و حذف چپتر ---

try {
    // ۱. بررسی امنیتی بسیار مهم: آیا کاربر فعلی مالک این چپتر است؟
    // ما با JOIN کردن جدول novels و chapters، چک می‌کنیم که author_id
    // ناول مربوط به این چپتر، با user_id کاربر لاگین کرده یکی باشد.
    $sql_check_owner = "
        SELECT n.id 
        FROM novels n 
        JOIN chapters c ON n.id = c.novel_id 
        WHERE c.id = ? AND n.author_id = ?
    ";
    
    $stmt_check = $conn->prepare($sql_check_owner);
    $stmt_check->execute([$chapter_id, $user_id]);

    // اگر کوئری بالا هیچ ردیفی را برنگرداند، یعنی کاربر مالک نیست.
    if (!$stmt_check->fetch()) {
        die("خطای امنیتی: شما مجوز حذف این چپتر را ندارید.");
    }

    // ۲. اگر بررسی امنیتی موفق بود، چپتر را حذف می‌کنیم.
    $stmt_delete = $conn->prepare("DELETE FROM chapters WHERE id = ?");
    $stmt_delete->execute([$chapter_id]);
    
    // ۳. هدایت کاربر به صفحه جزئیات ناول با یک پیام موفقیت در URL
    header("Location: ../novel_detail.php?id=" . $novel_id . "&status=chapter_deleted#chapters");
    exit();

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، یک پیام عمومی نمایش داده و خطای واقعی را لاگ می‌کنیم.
    error_log("Delete Chapter DB Error: " . $e->getMessage());
    die("خطای دیتابیس. عملیات حذف با شکست مواجه شد.");
}
?>
