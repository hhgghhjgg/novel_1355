// dashboard/edit_novel.php

<?php
/*
=====================================================
    NovelWorld - Edit Novel Page
    Version: 1.0
=====================================================
    - این صفحه به نویسندگان اجازه می‌دهد اطلاعات یک اثر موجود را ویرایش کنند.
    - فرم با داده‌های فعلی اثر از دیتابیس پر می‌شود.
    - مالکیت اثر توسط کاربر فعلی برای امنیت بررسی می‌شود.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---
require_once 'header.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;

// --- گام ۲: واکشی اطلاعات اولیه (برای نمایش فرم) ---
$novel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($novel_id === 0) {
    die("خطا: شناسه اثر مشخص نشده است.");
}

$errors = [];
$novel_data = null;

try {
    // واکشی اطلاعات ناول فقط در صورتی که کاربر فعلی نویسنده آن باشد
    $stmt = $conn->prepare("SELECT * FROM novels WHERE id = ? AND author_id = ?");
    $stmt->execute([$novel_id, $user_id]);
    $novel_data = $stmt->fetch();

    if (!$novel_data) {
        die("خطای دسترسی: شما مجوز ویرایش این اثر را ندارید یا این اثر وجود ندارد.");
    }
} catch (PDOException $e) {
    die("خطا در بارگذاری اطلاعات اثر: " . $e->getMessage());
}


// --- گام ۳: پردازش فرم (در صورت ارسال) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت و پاکسازی اطلاعات فرم
    $posted_novel_id = isset($_POST['novel_id']) ? intval($_POST['novel_id']) : 0;
    $type = $_POST['type'];
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $genres = trim($_POST['genres']);
    $author = trim($_POST['author']);
    $artist = trim($_POST['artist']);
    $rating = floatval($_POST['rating']);
    $status = $_POST['status'];
    $current_cover_url = $_POST['current_cover_url']; // URL کاور فعلی

    // بررسی امنیتی مجدد برای اطمینان از صحت ID
    if ($posted_novel_id !== $novel_id) {
        die("خطای امنیتی: تلاش برای دستکاری شناسه اثر.");
    }

    // --- ۳.۱: پردازش آپلود کاور جدید (اختیاری) ---
    $cover_url_for_db = $current_cover_url; // مقدار پیش‌فرض، همان URL قبلی است
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            $uploadResult = $cloudinary->uploadApi()->upload($_FILES['cover_image']['tmp_name'], ['folder' => 'novel_covers']);
            $cover_url_for_db = $uploadResult['secure_url']; // اگر آپلود موفق بود، URL جدید را جایگزین کن
        } catch (Exception $e) {
            $errors[] = "خطا در آپلود کاور جدید: " . $e->getMessage();
        }
    }

    // --- ۳.۲: به‌روزرسانی دیتابیس ---
    if (empty($errors)) {
        try {
            $sql = "UPDATE novels SET 
                        title = ?, summary = ?, cover_url = ?, genres = ?, 
                        author = ?, artist = ?, rating = ?, status = ?, type = ?, updated_at = NOW()
                    WHERE id = ? AND author_id = ?";
            
            $stmt_update = $conn->prepare($sql);
            $stmt_update->execute([
                $title, $summary, $cover_url_for_db, $genres, $author, 
                $artist, $rating, $status, $type,
                $novel_id, $user_id // شرط WHERE برای امنیت
            ]);

            // بازگشت به صفحه جزئیات ناول با پیام موفقیت
            header("Location: ../novel_detail.php?id=" . $novel_id . "&status=updated");
            exit();

        } catch (PDOException $e) {
            $errors[] = "خطا در به‌روزرسانی دیتابیس.";
            error_log("Edit Novel DB Error: " . $e->getMessage());
        }
    }
}
?>

<!-- --- گام ۴: رندر کردن بخش HTML فرم --- -->
<title>ویرایش اثر: <?php echo htmlspecialchars($novel_data['title']); ?></title>

<div class="page-header">
    <h2>ویرایش اثر</h2>
    <p style="color: var(--dash-text-secondary);">شما در حال ویرایش "<?php echo htmlspecialchars($novel_data['title']); ?>" هستید.</p>
</div>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="edit_novel.php?id=<?php echo $novel_id; ?>" method="POST" enctype="multipart/form-data">
        <!-- فیلدهای مخفی برای ارسال ID و URL کاور فعلی -->
        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
        <input type="hidden" name="current_cover_url" value="<?php echo htmlspecialchars($novel_data['cover_url']); ?>">

        <div class="form-group-grid">
            <div class="form-group">
                <label for="type">نوع اثر:</label>
                <select id="type" name="type" required>
                    <option value="novel" <?php echo ($novel_data['type'] === 'novel') ? 'selected' : ''; ?>>ناول</option>
                    <option value="manhwa" <?php echo ($novel_data['type'] === 'manhwa') ? 'selected' : ''; ?>>مانهوا</option>
                    <option value="manga" <?php echo ($novel_data['type'] === 'manga') ? 'selected' : ''; ?>>مانگا</option>
                </select>
            </div>
            <div class="form-group" style="flex-grow: 2;">
                <label for="title">عنوان اثر:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($novel_data['title']); ?>" required>
            </div>
        </div>

        <!-- ... بقیه فیلدهای فرم با مقادیر پیش‌فرض ... -->
        <div class="form-group-grid">
            <div class="form-group"><label for="author">نویسنده:</label><input type="text" id="author" name="author" value="<?php echo htmlspecialchars($novel_data['author']); ?>"></div>
            <div class="form-group"><label for="artist">آرتیست:</label><input type="text" id="artist" name="artist" value="<?php echo htmlspecialchars($novel_data['artist']); ?>"></div>
        </div>
        <div class="form-group"><label for="summary">خلاصه داستان:</label><textarea id="summary" name="summary" rows="6" required><?php echo htmlspecialchars($novel_data['summary']); ?></textarea></div>
        <div class="form-group"><label for="genres">ژانرها:</label><input type="text" id="genres" name="genres" value="<?php echo htmlspecialchars($novel_data['genres']); ?>" required></div>
        <div class="form-group-grid">
            <div class="form-group"><label for="rating">امتیاز:</label><input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="<?php echo htmlspecialchars($novel_data['rating']); ?>" required></div>
            <div class="form-group">
                <label for="status">وضعیت انتشار:</label>
                <select id="status" name="status" required>
                    <option value="ongoing" <?php echo ($novel_data['status'] === 'ongoing') ? 'selected' : ''; ?>>در حال انتشار</option>
                    <option value="completed" <?php echo ($novel_data['status'] === 'completed') ? 'selected' : ''; ?>>کامل شده</option>
                    <option value="hiatus" <?php echo ($novel_data['status'] === 'hiatus') ? 'selected' : ''; ?>>متوقف شده</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="cover_image">تغییر تصویر کاور (اختیاری):</label>
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="<?php echo htmlspecialchars($novel_data['cover_url']); ?>" alt="کاور فعلی" style="width: 60px; height: 90px; object-fit: cover; border-radius: 4px;">
                <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
            <a href="../novel_detail.php?id=<?php echo $novel_id; ?>" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<?php 
require_once 'footer.php'; 
?>
