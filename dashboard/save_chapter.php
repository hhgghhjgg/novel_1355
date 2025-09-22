<?php
// dashboard/save_chapter.php

/*
=====================================================
    NovelWorld - Save Chapter Script (Multi-Type)
    Version: 2.0
=====================================================
    - این اسکریپت داده‌های فرم manage_chapter.php را برای هر دو نوع
      محتوای متنی و تصویری (ZIP) پردازش می‌کند.
    - منطق آپلود، استخراج و پردازش فایل ZIP را پیاده‌سازی می‌کند.
*/

require_once 'header.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../telegram_notifier.php';

use Cloudinary\Cloudinary;

// --- گام ۱: دریافت و پاکسازی داده‌های فرم ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
$novel_type = isset($_POST['novel_type']) ? $_POST['novel_type'] : 'novel';
$chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0;
$chapter_number = isset($_POST['chapter_number']) ? intval($_POST['chapter_number']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$is_editing = $chapter_id > 0;
$content_for_db = '';

if ($novel_id === 0 || $chapter_number === 0 || empty($title)) {
    die("خطا: اطلاعات ضروری (شناسه ناول، شماره چپتر، عنوان) ارسال نشده است.");
}


// --- گام ۲: منطق پردازش بر اساس نوع اثر ---
try {
    // ۲.۱: بررسی مالکیت اثر (مشترک برای هر دو نوع)
    $stmt_check = $conn->prepare("SELECT title, cover_url, author FROM novels WHERE id = ? AND author_id = ?");
    $stmt_check->execute([$novel_id, $user_id]);
    $novel_info = $stmt_check->fetch();
    if (!$novel_info) {
        die("خطای امنیتی: شما مجوز دسترسی به این اثر را ندارید.");
    }

    // ۲.۲: پردازش محتوا
    if ($novel_type === 'novel') {
        // --- پردازش برای ناول متنی ---
        $content_for_db = isset($_POST['content_text']) ? $_POST['content_text'] : '';
        if (empty($content_for_db)) {
            die("خطا: محتوای چپتر برای ناول نمی‌تواند خالی باشد.");
        }
    } else {
        // --- پردازش برای مانهوا/مانگا (فایل ZIP) ---
        if (isset($_FILES['content_zip']) && $_FILES['content_zip']['error'] === UPLOAD_ERR_OK) {
            
            $zip_file = $_FILES['content_zip']['tmp_name'];
            $zip = new ZipArchive;
            if ($zip->open($zip_file) !== TRUE) {
                die("خطا: فایل ZIP قابل باز شدن نیست.");
            }

            // ایجاد یک پوشه موقت منحصر به فرد
            $temp_dir = sys_get_temp_dir() . '/' . uniqid('chapter_');
            if (!mkdir($temp_dir)) {
                die("خطا: امکان ایجاد پوشه موقت وجود ندارد.");
            }
            $zip->extractTo($temp_dir);
            $zip->close();
            
            $image_files = [];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $files_in_dir = scandir($temp_dir);

            foreach ($files_in_dir as $file) {
                if ($file !== '.' && $file !== '..') {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $image_files[] = $temp_dir . '/' . $file;
                    }
                }
            }

            // مرتب‌سازی تصاویر بر اساس نام فایل (مثلاً 01.jpg, 02.jpg, ...)
            sort($image_files, SORT_NATURAL);

            if (empty($image_files)) {
                die("خطا: هیچ فایل تصویر معتبری در فایل ZIP یافت نشد.");
            }

            $cloudinary_urls = [];
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            
            foreach ($image_files as $image_path) {
                $uploadResult = $cloudinary->uploadApi()->upload($image_path, [
                    'folder' => "chapters/{$novel_id}/{$chapter_number}",
                    'resource_type' => 'image'
                ]);
                $cloudinary_urls[] = $uploadResult['secure_url'];
            }

            // تبدیل آرایه URL ها به رشته JSON برای ذخیره در دیتابیس
            $content_for_db = json_encode($cloudinary_urls);
            
            // پاکسازی فایل‌های موقت
            foreach ($image_files as $image_path) { unlink($image_path); }
            rmdir($temp_dir);

        } elseif (!$is_editing) {
            die("خطا: برای ایجاد چپتر جدید تصویری، ارسال فایل ZIP الزامی است.");
        }
        // اگر در حالت ویرایش هستیم و فایلی ارسال نشده، $content_for_db خالی می‌ماند
        // و ما در کوئری UPDATE، ستون content را به‌روز نمی‌کنیم.
    }


    // --- گام ۳: ذخیره در دیتابیس ---
    if ($is_editing) {
        // --- حالت ویرایش ---
        if (!empty($content_for_db)) {
            // اگر محتوای جدیدی (متن یا تصاویر) ارسال شده بود، آن را آپدیت کن
            $sql = "UPDATE chapters SET chapter_number = ?, title = ?, content = ?, updated_at = NOW() WHERE id = ? AND novel_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$chapter_number, $title, $content_for_db, $chapter_id, $novel_id]);
        } else {
            // اگر محتوای جدیدی نبود، فقط شماره و عنوان را آپدیت کن
            $sql = "UPDATE chapters SET chapter_number = ?, title = ?, updated_at = NOW() WHERE id = ? AND novel_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$chapter_number, $title, $chapter_id, $novel_id]);
        }
    } else {
        // --- حالت ایجاد ---
        $sql = "INSERT INTO chapters (novel_id, chapter_number, title, content) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$novel_id, $chapter_number, $title, $content_for_db]);
        
        $new_chapter_id = $conn->lastInsertId();

        // ارسال نوتیفیکیشن تلگرام
        if ($new_chapter_id && $novel_info) {
            $caption = "🔥 <b>چپتر جدید منتشر شد!</b> 🔥\n\n<b>" . htmlspecialchars($novel_info['title']) . "</b>";
            sendTelegramNotification(
                $novel_info['cover_url'],
                $caption,
                "📖 خواندن چپتر " . htmlspecialchars($chapter_number),
                "read_chapter.php?id=" . $new_chapter_id
            );
        }
    }
    
    // هدایت به صفحه جزئیات ناول
    header("Location: ../novel_detail.php?id=" . $novel_id . "&status=chapter_saved#chapters");
    exit();

} catch (PDOException $e) {
    if ($e->getCode() == '23505') die("خطا: شماره چپتر تکراری است.");
    die("خطای دیتابیس: " . $e->getMessage());
} catch (Exception $e) {
    die("خطای عمومی: " . $e->getMessage());
}
?>
