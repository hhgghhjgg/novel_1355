<?php
// dashboard/create_novel.php

/*
=====================================================
    NovelWorld - Create New Work Page
    Version: 2.2 (Multi-Type Ready)
=====================================================
    - فرم و منطق ایجاد یک اثر جدید (ناول، مانهوا، مانگا).
    - شامل فیلد جدید برای انتخاب نوع اثر.
    - تمام قابلیت‌های قبلی مانند آپلود کاور و نوتیفیکیشن تلگرام حفظ شده است.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---
require_once 'header.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../telegram_notifier.php';

use Cloudinary\Cloudinary;

// --- گام ۲: آماده‌سازی متغیرها ---
$errors = [];

// --- گام ۳: پردازش فرم ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // دریافت و پاکسازی اطلاعات فرم
    $type = isset($_POST['type']) && in_array($_POST['type'], ['novel', 'manhwa', 'manga']) ? $_POST['type'] : 'novel';
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $genres = trim($_POST['genres']);
    $author = trim($_POST['author']);
    $artist = trim($_POST['artist']);
    $rating = floatval($_POST['rating']);
    $status = $_POST['status'];
    $author_id = $user_id;

    // --- ۳.۱: پردازش آپلود کاور در Cloudinary ---
    $cover_url_for_db = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        // ... (منطق آپلود فایل بدون تغییر باقی می‌ماند) ...
        try {
            $cloudinary_url = getenv('CLOUDINARY_URL');
            if (!$cloudinary_url) throw new Exception("متغیر CLOUDINARY_URL تنظیم نشده است.");
            $cloudinary = new Cloudinary($cloudinary_url);
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
            // *** تغییر کلیدی: اضافه کردن ستون type به کوئری ***
            $sql = "INSERT INTO novels (author_id, title, summary, cover_url, genres, author, artist, rating, status, type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            $stmt->execute([$author_id, $title, $summary, $cover_url_for_db, $genres, $author, $artist, $rating, $status, $type]);

            $new_novel_id = $conn->lastInsertId();

            // ارسال نوتیفیکیشن تلگرام
            if ($new_novel_id) {
                $type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];
                $caption = "✨ <b>" . $type_persian[$type] . " جدیدی منتشر شد!</b> ✨\n\n";
                $caption .= "<b>" . htmlspecialchars($title) . "</b>\n";
                $caption .= "<i>نویسنده: " . htmlspecialchars($author) . "</i>";
                
                sendTelegramNotification(
                    $cover_url_for_db,
                    $caption,
                    "📖 مشاهده و شروع خواندن",
                    "novel_detail.php?id=" . $new_novel_id
                );
            }

            header("Location: index.php?status=novel_created");
            exit();

        } catch (PDOException $e) {
            error_log("Create Novel DB Error: " . $e->getMessage());
            $errors[] = "خطا در ذخیره اطلاعات در دیتابیس.";
        }
    }
}
?>

<!-- --- گام ۴: رندر کردن بخش HTML فرم --- -->
<title>ایجاد اثر جدید - پنل نویسندگی</title>

<div class="page-header">
    <h2>ایجاد اثر جدید</h2>
</div>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="create_novel.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group-grid">
            <div class="form-group">
                <!-- *** فیلد جدید برای انتخاب نوع اثر *** -->
                <label for="type">نوع اثر:</label>
                <select id="type" name="type" required>
                    <option value="novel" selected>ناول (داستان متنی)</option>
                    <option value="manhwa">مانهوا (وب‌تون کره‌ای)</option>
                    <option value="manga">مانگا (کمیک ژاپنی)</option>
                </select>
            </div>
            <div class="form-group" style="flex-grow: 2;">
                <label for="title">عنوان اثر:</label>
                <input type="text" id="title" name="title" required>
            </div>
        </div>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="author">نویسنده:</label>
                <input type="text" id="author" name="author" value="<?php echo $username; ?>">
            </div>
            <div class="form-group">
                <label for="artist">آرتیست (اختیاری):</label>
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
                <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="0.0" required>
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
            <button type="submit" class="btn btn-primary">ایجاد و انتشار اثر</button>
            <a href="index.php" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<?php 
require_once 'footer.php'; 
?>
