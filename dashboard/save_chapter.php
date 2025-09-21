// dashboard/save_chapter.php

<?php
/*
=====================================================
    NovelWorld - Save Chapter Script
    Version: 1.1 (Final - With Telegram Notifier)
=====================================================
    - داده‌های فرم 'manage_chapter.php' را پردازش می‌کند.
    - عملیات INSERT یا UPDATE را بر اساس وجود 'chapter_id' انجام می‌دهد.
    - پس از ایجاد یک چپتر جدید، نوتیفیکیشن تلگرام ارسال می‌کند.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---
// هدر داشبورد (برای امنیت، اتصال دیتابیس و اطلاعات کاربر)
require_once 'header.php';
// ماژول نوتیفیکیشن تلگرام
require_once __DIR__ . '/../telegram_notifier.php';


// --- گام ۲: بررسی‌های اولیه ---
if (!$is_logged_in) {
    die("خطای دسترسی: لطفاً ابتدا وارد شوید.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}


// --- گام ۳: دریافت و پاکسازی داده‌های فرم ---
$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0;
$chapter_number = isset($_POST['chapter_number']) ? intval($_POST['chapter_number']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';
$is_editing = $chapter_id > 0;

// اعتبارسنجی داده‌ها
if ($novel_id === 0 || $chapter_number === 0 || empty($title) || empty($content)) {
    die("خطا: اطلاعات ناقص است. شماره چپتر، عنوان و محتوا الزامی هستند.");
}


// --- گام ۴: تعامل با دیتابیس و ارسال نوتیفیکیشن ---
try {
    // ۱. بررسی امنیتی: آیا کاربر فعلی مالک این ناول است؟
    $stmt_check = $conn->prepare("SELECT title, cover_url, author FROM novels WHERE id = ? AND author_id = ?");
    $stmt_check->execute([$novel_id, $user_id]);
    $novel_info = $stmt_check->fetch();
    if (!$novel_info) {
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
        
        // دریافت ID چپتری که همین الان ایجاد شد
        $new_chapter_id = $conn->lastInsertId();

        // --- ۳. ارسال نوتیفیکیشن تلگرام فقط برای چپترهای جدید ---
        if ($new_chapter_id && $novel_info) {
            $caption = "🔥 <b>چپتر جدید منتشر شد!</b> 🔥\n\n";
            $caption .= "<b>" . htmlspecialchars($novel_info['title']) . "</b>\n";
            $caption .= "<i>" . htmlspecialchars($novel_info['author']) . "</i>";
            
            sendTelegramNotification(
                $novel_info['cover_url'],
                $caption,
                "📖 خواندن چپتر " . htmlspecialchars($chapter_number),
                "read_chapter.php?id=" . $new_chapter_id
            );
        }
    }
    
    // ۴. هدایت کاربر به صفحه جزئیات ناول با پیام موفقیت
    header("Location: ../novel_detail.php?id=" . $novel_id . "&status=chapter_saved#chapters");
    exit();

} catch (PDOException $e) {
    // مدیریت خطای شماره چپتر تکراری
    if ($e->getCode() == '23505') {
        die("خطا: شماره چپتر <b>" . htmlspecialchars($chapter_number) . "</b> برای این ناول تکراری است. لطفاً به صفحه قبل بازگشته و یک شماره دیگر انتخاب کنید.");
    }
    
    // مدیریت سایر خطاهای دیتابیس
    error_log("Save Chapter DB Error: " . $e->getMessage());
    die("خطای دیتابیس. عملیات با شکست مواجه شد.");
}
?>
