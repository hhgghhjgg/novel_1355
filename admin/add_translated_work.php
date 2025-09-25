// admin/add_translated_work.php

<?php
/*
=====================================================
    NovelWorld - Add Translated Work (Admin Tool)
    Version: 1.0
=====================================================
    - این صفحه فرم اختصاصی مدیر برای افزودن آثار ترجمه شده است.
    - دسترسی به این صفحه فقط برای کاربران با نقش 'admin' مجاز است.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---
require_once 'header.php'; // شامل امنیت، اتصال دیتابیس و اطلاعات ادمین
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../telegram_notifier.php';

use Cloudinary\Cloudinary;

// --- گام ۲: آماده‌سازی متغیرها ---
$errors = [];

// --- گام ۳: پردازش فرم ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // دریافت و پاکسازی اطلاعات فرم
    $type = $_POST['type'];
    $origin = 'translated'; // مقدار منشاء به صورت ثابت تنظیم می‌شود
    $translator = trim($_POST['translator']);
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $genres = trim($_POST['genres']);
    $author = trim($_POST['author']);
    $artist = trim($_POST['artist']);
    $rating = floatval($_POST['rating']);
    $status = $_POST['status'];
    $author_id = $user_id; // ID ادمینی که اثر را ثبت می‌کند

    if (empty($translator)) {
        $errors[] = "نام مترجم برای آثار ترجمه شده الزامی است.";
    }

    // --- ۳.۱: پردازش آپلود کاور در Cloudinary ---
    $cover_url_for_db = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            $uploadResult = $cloudinary->uploadApi()->upload($_FILES['cover_image']['tmp_name'], ['folder' => 'novel_covers']);
            $cover_url_for_db = $uploadResult['secure_url'];
        } catch (Exception $e) {
            $errors[] = "خطا در آپلود فایل کاور: " . $e->getMessage();
        }
    } else {
        $errors[] = "لطفاً یک تصویر برای کاور انتخاب کنید.";
    }

    // --- ۳.۲: ذخیره در دیتابیس و ارسال نوتیفیکیشن ---
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO novels (author_id, title, summary, cover_url, genres, author, artist, rating, status, type, origin, translator) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $author_id, $title, $summary, $cover_url_for_db, $genres, $author, 
                $artist, $rating, $status, $type, $origin, $translator
            ]);

            $new_novel_id = $conn->lastInsertId();

            if ($new_novel_id) {
                $caption = "📚 <b>اثر ترجمه شده جدیدی به سایت اضافه شد!</b> 📚\n\n";
                $caption .= "<b>" . htmlspecialchars($title) . "</b>\n";
                $caption .= "<i>مترجم: " . htmlspecialchars($translator) . "</i>";
                
                sendTelegramNotification(
                    $cover_url_for_db, $caption,
                    "📖 مشاهده و شروع خواندن", "novel_detail.php?id=" . $new_novel_id
                );
            }

            // بازگشت به صفحه اصلی پنل مدیریت با پیام موفقیت
            header("Location: index.php?status=work_added");
            exit();

        } catch (PDOException $e) {
            error_log("Admin Add Work DB Error: " . $e->getMessage());
            $errors[] = "خطا در ذخیره اطلاعات در دیتابیس.";
        }
    }
}
?>

<!-- --- گام ۴: رندر کردن بخش HTML فرم --- -->
<title>افزودن اثر ترجمه شده - پنل مدیریت</title>

<div class="page-header">
    <h2>افزودن اثر ترجمه شده جدید</h2>
</div>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="error-box" style="background-color: var(--primary-color); color: white; border: none;">
            <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="add_translated_work.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label for="title">عنوان اصلی اثر:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="type">نوع اثر:</label>
                <select id="type" name="type" required>
                    <option value="novel">ناول (متنی)</option>
                    <option value="manhwa" selected>مانهوا (تصویری)</option>
                    <option value="manga">مانگا (تصویری)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="translator">نام مترجم / تیم ترجمه:</label>
                <input type="text" id="translator" name="translator" required>
            </div>
        </div>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="author">نویسنده اصلی:</label>
                <input type="text" id="author" name="author" required>
            </div>
            <div class="form-group">
                <label for="artist">آرتیست اصلی (اختیاری):</label>
                <input type="text" id="artist" name="artist">
            </div>
        </div>

        <div class="form-group">
            <label for="summary">خلاصه داستان:</label>
            <textarea id="summary" name="summary" rows="6" required></textarea>
        </div>
        <div class="form-group">
            <label for="genres">ژانرها (جدا شده با کاما ،):</label>
            <input type="text" id="genres" name="genres" placeholder="اکشن, فانتزی, عاشقانه" required>
        </div>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="rating">امتیاز اولیه (از ۱۰):</label>
                <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="7.0" required>
            </div>
            <div class="form-group">
                <label for="status">وضعیت انتشار:</label>
                <select id="status" name="status" required>
                    <option value="ongoing" selected>در حال انتشار</option>
                    <option value="completed">کامل شده</option>
                    <option value="hiatus">متوقف شده</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="cover_image">تصویر کاور:</label>
            <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">افزودن اثر</button>
            <a href="index.php" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<?php 
require_once 'footer.php'; 
?>
