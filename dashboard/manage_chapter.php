<?php
// dashboard/manage_chapter.php

/*
=====================================================
    NovelWorld - Manage Chapter Page
    Version: 1.0
=====================================================
    - این صفحه به نویسندگان اجازه می‌دهد چپترهای جدید اضافه کرده یا چپترهای موجود را ویرایش کنند.
    - از ویرایشگر متن TinyMCE برای نوشتن محتوا استفاده می‌کند.
    - داده‌ها را بر اساس ID های موجود در URL از دیتابیس واکشی می‌کند.
    - مالکیت ناول توسط نویسنده فعلی برای امنیت بررسی می‌شود.
*/

// --- گام ۱: فراخوانی هدر داشبورد ---
// این فایل شامل موارد زیر است:
// 1. اتصال به دیتابیس ($conn)
// 2. احراز هویت کاربر و تنظیم متغیرهای $is_logged_in و $user_id
require_once 'header.php';


// --- گام ۲: دریافت ID ها و آماده‌سازی متغیرهای اولیه ---

// دریافت ID ناول از URL (ضروری)
$novel_id = isset($_GET['novel_id']) ? intval($_GET['novel_id']) : 0;
// دریافت ID چپتر از URL (اختیاری، برای حالت ویرایش)
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;

// تعیین اینکه آیا در حالت ویرایش هستیم یا ایجاد
$is_editing = $chapter_id > 0;
$page_title = $is_editing ? "ویرایش چپتر" : "افزودن چپتر جدید";

// آرایه‌ای برای نگهداری اطلاعات چپتر (برای پر کردن فرم)
$chapter_data = [
    'chapter_number' => '',
    'title' => '',
    'content' => ''
    // در آینده می‌توانید فیلدهای یادداشت نویسنده را هم اضافه کنید
];
$novel_title = '';

// اگر ID ناول وجود نداشت، عملیات متوقف می‌شود
if ($novel_id === 0) {
    die("خطا: شناسه ناول مشخص نشده است.");
}


// --- گام ۳: واکشی اطلاعات از دیتابیس ---

try {
    // ابتدا، بررسی می‌کنیم که آیا کاربر فعلی مالک این ناول است یا نه.
    // این یک بررسی امنیتی بسیار مهم است.
    $stmt_novel = $conn->prepare("SELECT title FROM novels WHERE id = ? AND author_id = ?");
    $stmt_novel->execute([$novel_id, $user_id]);
    $novel = $stmt_novel->fetch();

    if (!$novel) {
        // اگر کاربر مالک ناول نبود یا ناول وجود نداشت، دسترسی را قطع می‌کنیم.
        die("خطا: شما به این ناول دسترسی ندارید یا ناول مورد نظر وجود ندارد.");
    }
    $novel_title = $novel['title'];

    // اگر در حالت ویرایش هستیم، اطلاعات چپتر فعلی را واکشی می‌کنیم
    if ($is_editing) {
        $stmt_chapter = $conn->prepare("SELECT * FROM chapters WHERE id = ? AND novel_id = ?");
        $stmt_chapter->execute([$chapter_id, $novel_id]);
        $fetched_chapter_data = $stmt_chapter->fetch();
        if (!$fetched_chapter_data) {
            die("چپتر مورد نظر برای ویرایش یافت نشد.");
        }
        $chapter_data = $fetched_chapter_data; // جایگزینی آرایه پیش‌فرض با داده‌های واقعی
    } else {
        // اگر در حال ایجاد چپتر جدید هستیم، شماره آخرین چپتر را پیدا کرده و یکی به آن اضافه می‌کنیم
        $stmt_last_chapter = $conn->prepare("SELECT MAX(chapter_number) as max_num FROM chapters WHERE novel_id = ?");
        $stmt_last_chapter->execute([$novel_id]);
        $last_chapter = $stmt_last_chapter->fetch();
        // اگر این اولین چپتر بود، شماره ۱ را قرار می‌دهیم
        $chapter_data['chapter_number'] = ($last_chapter && $last_chapter['max_num']) ? $last_chapter['max_num'] + 1 : 1;
    }

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، عملیات متوقف می‌شود.
    error_log("Manage Chapter Page Error: " . $e->getMessage());
    die("خطای دیتابیس. لطفاً بعداً تلاش کنید.");
}


// --- گام ۴: رندر کردن بخش HTML ---
?>
<title><?php echo $page_title; ?> - پنل نویسندگی</title>

<!-- ۱. فراخوانی کتابخانه TinyMCE از CDN -->
<!-- شما می‌توانید یک API Key رایگان از سایت tiny.cloud بگیرید تا پیغام خطا نمایش داده نشود -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- ۲. مقداردهی اولیه ویرایشگر متن با تنظیمات دلخواه -->
<script>
  tinymce.init({
    selector: '#chapter_content', // انتخاب textarea با این ID
    plugins: 'directionality lists link image media code help wordcount table', // پلاگین‌های فعال
    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | ltr rtl | bullist numlist | link image media | table | code | help',
    height: 450, // ارتفاع ویرایشگر
    menubar: false,
    content_css: 'dark', // استفاده از تم تیره برای هماهنگی با ظاهر داشبورد
    skin: 'oxide-dark',
    directionality: 'rtl' // تنظیم جهت پیش‌فرض متن به راست‌چین
  });
</script>

<div class="page-header">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>
    <p style="color: var(--dash-text-secondary);">برای ناول: "<?php echo htmlspecialchars($novel_title); ?>"</p>
</div>

<div class="form-container">
    <form action="save_chapter.php" method="POST">
        <!-- فیلدهای مخفی برای ارسال IDها به اسکریپت پردازشگر -->
        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
        <?php if ($is_editing): ?>
            <input type="hidden" name="chapter_id" value="<?php echo $chapter_id; ?>">
        <?php endif; ?>

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div class="form-group" style="width: 20%;">
                <label for="chapter_number">شماره چپتر:</label>
                <input type="number" id="chapter_number" name="chapter_number" value="<?php echo htmlspecialchars($chapter_data['chapter_number']); ?>" required>
            </div>
            <div class="form-group" style="width: 80%;">
                <label for="title">عنوان چپتر:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($chapter_data['title']); ?>" placeholder="مثال: فصل اول - یک شروع تازه" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="chapter_content">محتوای چپتر:</label>
            <textarea id="chapter_content" name="content" rows="18"><?php echo htmlspecialchars($chapter_data['content']); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">ذخیره چپتر</button>
            <a href="../novel_detail.php?id=<?php echo $novel_id; ?>#chapters" class="btn btn-secondary">انصراف و بازگشت</a>
        </div>
    </form>
</div>

<?php 
// فراخوانی فوتر داشبورد
require_once 'footer.php'; 
?>
