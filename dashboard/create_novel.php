// dashboard/create_novel.php

<?php
/*
=====================================================
    NovelWorld - Create New Novel Page
    Version: 2.0 (Serverless Ready - Cloudinary Upload)
=====================================================
    - این فایل فرم و منطق ایجاد یک ناول جدید را مدیریت می‌کند.
    - احراز هویت نویسنده از طریق سیستم JWT (که در header.php پیاده‌سازی شده) بررسی می‌شود.
    - تصویر کاور با استفاده از Cloudinary SDK آپلود شده و URL آن در دیتابیس ذخیره می‌شود.
    - اطلاعات ناول با استفاده از PDO در دیتابیس PostgreSQL (Neon) ذخیره می‌شود.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---

// فراخوانی هدر اختصاصی داشبورد
// این فایل شامل اتصال به دیتابیس (PDO) و بررسی وضعیت لاگین (JWT) است.
// همچنین متغیرهایی مانند $user_id و $is_logged_in را در دسترس قرار می‌دهد.
require_once 'header.php';

// فراخوانی Autoloader کامپوزر برای استفاده از کتابخانه Cloudinary
require_once '../vendor/autoload.php';

// استفاده از کلاس‌های Cloudinary
use Cloudinary\Cloudinary;
use Cloudinary\Api\Exception\ApiError;


// --- گام ۲: بررسی امنیت و مجوز دسترسی ---
// اگرچه این بررسی در header.php انجام می‌شود، تکرار آن در اینجا امنیت را افزایش می‌دهد.
if (!$is_logged_in) {
    // اگر کاربر لاگین نکرده بود، او را به صفحه لاگین اصلی سایت هدایت می‌کنیم.
    header("Location: ../login.php"); 
    exit();
}


// --- گام ۳: پردازش فرم ---

$errors = []; // آرایه‌ای برای نگهداری و نمایش خطاها

// بررسی می‌کنیم که آیا فرم با متد POST ارسال شده است
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // دریافت تمام اطلاعات از فرم و پاکسازی آن‌ها
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $genres = trim($_POST['genres']);
    $author = trim($_POST['author']);
    $artist = trim($_POST['artist']);
    $rating = floatval($_POST['rating']);
    $status = $_POST['status'];
    // ID نویسنده از متغیر سراسری که در header.php تعریف شده خوانده می‌شود.
    $author_id = $user_id; 

    // --- گام ۳.۱: پردازش آپلود فایل کاور با Cloudinary ---
    $cover_url_for_db = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        
        // اعتبارسنجی اولیه نوع فایل
        $file_info = pathinfo($_FILES['cover_image']['name']);
        $file_ext = strtolower($file_info['extension']);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_exts)) {
            try {
                // ۱. کانفیگ کردن Cloudinary با استفاده از متغیرهای محیطی
                $cloudinary_url = getenv('CLOUDINARY_URL');
                if (!$cloudinary_url) {
                    throw new Exception("متغیر CLOUDINARY_URL در سرور تنظیم نشده است.");
                }
                $cloudinary = new Cloudinary($cloudinary_url);

                // ۲. آپلود فایل به Cloudinary
                $uploadResult = $cloudinary->uploadApi()->upload(
                    $_FILES['cover_image']['tmp_name'],
                    [
                        'folder' => 'novel_covers', // نام پوشه‌ای که تصاویر در آن ذخیره می‌شوند
                        'resource_type' => 'image',
                        // (اختیاری) بهینه‌سازی خودکار تصویر هنگام آپلود
                        'transformation' => [
                            ['width' => 800, 'height' => 1200, 'crop' => 'limit'],
                            ['fetch_format' => 'auto', 'quality' => 'auto']
                        ]
                    ]
                );
                
                // ۳. دریافت URL امن و بهینه شده از نتیجه آپلود
                $cover_url_for_db = $uploadResult['secure_url'];

            } catch (Exception $e) {
                $errors[] = "خطا در آپلود فایل کاور: " . $e->getMessage();
            }
        } else {
            $errors[] = "فرمت فایل کاور مجاز نیست (فقط jpg, jpeg, png, webp).";
        }
    } else {
        $errors[] = "لطفاً یک تصویر برای کاور انتخاب کنید.";
    }

    // --- گام ۳.۲: ذخیره اطلاعات در دیتابیس ---
    // اگر خطایی در مراحل قبل (به خصوص آپلود فایل) وجود نداشت، اطلاعات را ذخیره کن
    if (empty($errors)) {
        try {
            // کوئری INSERT با استفاده از سینتکس PDO
            $sql = "INSERT INTO novels (author_id, title, summary, cover_url, genres, author, artist, rating, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            // اجرای کوئری با ارسال آرایه‌ای از مقادیر
            $stmt->execute([
                $author_id, 
                $title, 
                $summary, 
                $cover_url_for_db, 
                $genres, 
                $author, 
                $artist, 
                $rating, 
                $status
            ]);

            // بازگشت به صفحه اصلی داشبورد پس از ثبت موفق
            header("Location: index.php?status=novel_created");
            exit();

        } catch (PDOException $e) {
            $errors[] = "خطا در ذخیره اطلاعات در دیتابیس: " . $e->getMessage();
            // در محیط واقعی، بهتر است این خطا را لاگ کنید.
        }
    }
}
?>

<!-- --- گام ۴: رندر کردن بخش HTML فرم --- -->
<title>ایجاد ناول جدید - پنل نویسندگی</title>

<div class="page-header">
    <h2>ایجاد ناول جدید</h2>
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
        <div class="form-group">
            <label for="title">عنوان ناول:</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group-grid">
            <div class="form-group">
                <label for="author">نویسنده:</label>
                <input type="text" id="author" name="author">
            </div>
            <div class="form-group">
                <label for="artist">آرتیست:</label>
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
                <label for="rating">امتیاز (از ۱۰):</label>
                <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="0.0" required>
            </div>
            <div class="form-group">
                <label for="status">وضعیت انتشار:</label>
                <select id="status" name="status" required>
                    <option value="ongoing">در حال انتشار</option>
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
            <button type="submit" class="btn btn-primary">ایجاد ناول</button>
            <a href="index.php" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<?php 
// فراخوانی فوتر اختصاصی داشبورد
require_once 'footer.php'; 
?>
