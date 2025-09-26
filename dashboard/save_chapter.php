<?php
// dashboard/save_chapter.php

/*
=====================================================
    NovelWorld - Save Chapter Script (Advanced)
    Version: 2.1
=====================================================
    - این اسکریپت داده‌های فرم پیشرفته manage_chapter.php را پردازش می‌کند.
    - شامل منطق پردازش کاور چپتر و زمان‌بندی انتشار است.
    - همچنان بین محتوای متنی و تصویری (ZIP) تمایز قائل می‌شود.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---
require_once 'header.php'; // شامل امنیت، اتصال دیتابیس و اطلاعات کاربر
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../telegram_notifier.php';

use Cloudinary\Cloudinary;

// --- گام ۲: بررسی‌های اولیه ---
if (!$is_logged_in) die("خطای دسترسی: لطفاً ابتدا وارد شوید.");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// --- گام ۳: دریافت و پاکسازی داده‌های فرم ---
$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$novel_type = isset($_POST['novel_type']) ? $_POST['novel_type'] : 'novel';
$chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0;
$chapter_number = isset($_POST['chapter_number']) ? intval($_POST['chapter_number']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$is_editing = $chapter_id > 0;

// دریافت اطلاعات جدید
$published_at = trim($_POST['published_at']);
$current_cover_url = $_POST['current_cover_url'] ?? null;
$chapter_cover_url = $current_cover_url; // مقدار پیش‌فرض

$content_for_db = '';

if ($novel_id === 0 || $chapter_number === 0 || empty($title)) {
    die("خطا: اطلاعات ضروری ارسال نشده است.");
}

// --- گام ۴: منطق پردازش ---
try {
    // ۴.۱: بررسی مالکیت اثر
    $stmt_check = $conn->prepare("SELECT title, cover_url, author FROM novels WHERE id = ? AND author_id = ?");
    $stmt_check->execute([$novel_id, $user_id]);
    $novel_info = $stmt_check->fetch();
    if (!$novel_info) die("خطای امنیتی: شما مجوز دسترسی به این اثر را ندارید.");

    // ۴.۲: پردازش کاور چپتر (اگر فایل جدیدی آپلود شده باشد)
    if (isset($_FILES['chapter_cover']) && $_FILES['chapter_cover']['error'] === UPLOAD_ERR_OK) {
        try {
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            $uploadResult = $cloudinary->uploadApi()->upload($_FILES['chapter_cover']['tmp_name'], [
                'folder' => "chapter_covers/{$novel_id}"
            ]);
            $chapter_cover_url = $uploadResult['secure_url'];
        } catch (Exception $e) { die("خطا در آپلود کاور چپتر: " . $e->getMessage()); }
    }

    // ۴.۳: پردازش تاریخ انتشار
    // اگر کاربر تاریخی وارد نکرده، از زمان حال برای انتشار فوری استفاده می‌کنیم
    $publish_date_for_db = !empty($published_at) ? $published_at : date('Y-m-d H:i:s');

    // ۴.۴: پردازش محتوای چپتر بر اساس نوع اثر
    if ($novel_type === 'novel') {
        $content_for_db = isset($_POST['content_text']) ? $_POST['content_text'] : '';
        if (empty($content_for_db) && !$is_editing) die("محتوای چپتر نمی‌تواند خالی باشد.");
    } else {
        if (isset($_FILES['content_zip']) && $_FILES['content_zip']['error'] === UPLOAD_ERR_OK) {
            $zip_file = $_FILES['content_zip']['tmp_name'];
            $zip = new ZipArchive;
            if ($zip->open($zip_file) !== TRUE) die("فایل ZIP قابل باز شدن نیست.");
            
            $temp_dir = sys_get_temp_dir() . '/' . uniqid('chapter_');
            if (!mkdir($temp_dir)) die("امکان ایجاد پوشه موقت وجود ندارد.");
            $zip->extractTo($temp_dir);
            $zip->close();
            
            $image_files = [];
            $files_in_dir = scandir($temp_dir);
            foreach ($files_in_dir as $file) {
                if ($file !== '.' && $file !== '..') {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $image_files[] = $temp_dir . '/' . $file;
                    }
                }
            }
            sort($image_files, SORT_NATURAL);

            if (empty($image_files)) die("هیچ فایل تصویر معتبری در فایل ZIP یافت نشد.");

            $cloudinary_urls = [];
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            foreach ($image_files as $image_path) {
                $uploadResult = $cloudinary->uploadApi()->upload($image_path, ['folder' => "chapters/{$novel_id}/{$chapter_number}"]);
                $cloudinary_urls[] = $uploadResult['secure_url'];
            }
            $content_for_db = json_encode($cloudinary_urls);
            
            // پاکسازی
            foreach ($image_files as $image_path) { unlink($image_path); }
            rmdir($temp_dir);
        } elseif (!$is_editing) {
            die("برای ایجاد چپتر تصویری، ارسال فایل ZIP الزامی است.");
        }
    }

    // --- گام ۵: ذخیره در دیتابیس ---
    if ($is_editing) {
        // --- حالت ویرایش ---
        // ما فقط فیلدهایی را آپدیت می‌کنیم که داده جدیدی برایشان وجود دارد
        $update_parts = ["chapter_number = ?", "title = ?", "cover_url = ?", "published_at = ?", "updated_at = NOW()"];
        $params = [$chapter_number, $title, $chapter_cover_url, $publish_date_for_db];
        
        if (!empty($content_for_db)) {
            $update_parts[] = "content = ?";
            $params[] = $content_for_db;
        }
        
        $params[] = $chapter_id;
        $params[] = $novel_id;
        
        $sql = "UPDATE chapters SET " . implode(', ', $update_parts) . " WHERE id = ? AND novel_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        // --- حالت ایجاد ---
        // توجه: وضعیت چپتر جدید به صورت پیش‌فرض 'pending' خواهد بود (طبق تعریف دیتابیس)
        $sql = "INSERT INTO chapters (novel_id, chapter_number, title, content, cover_url, published_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$novel_id, $chapter_number, $title, $content_for_db, $chapter_cover_url, $publish_date_for_db]);
        
        // نوتیفیکیشن تلگرام فقط برای چپترهای جدید (این بخش نیازی به تغییر ندارد)
        // ... (منطق ارسال نوتیفیکیشن تلگرام می‌تواند اینجا باشد، اما بهتر است پس از تایید مدیر ارسال شود)
    }
    
    // --- گام ۶: هدایت به صفحه جزئیات ناول ---
    header("Location: ../novel_detail.php?id=" . $novel_id . "&status=chapter_saved#chapters");
    exit();

} catch (Exception $e) {
    if ($e instanceof PDOException && $e->getCode() == '23505') {
        die("خطا: شماره چپتر <b>" . htmlspecialchars($chapter_number) . "</b> برای این اثر تکراری است.");
    }
    error_log("Save Chapter Error: " . $e->getMessage());
    die("یک خطای غیرمنتظره رخ داد: " . $e->getMessage());
}
?>
