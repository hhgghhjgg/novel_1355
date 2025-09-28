<?php
// dashboard/manage_chapter.php

/*
=====================================================
    NovelWorld - Manage Chapter Page (Advanced)
    Version: 2.1
=====================================================
    - این صفحه فرم مدیریت چپتر (ایجاد/ویرایش) را با قابلیت‌های جدید ارائه می‌دهد.
    - ویرایشگر متن TinyMCE حذف شده و با یک textarea استاندارد جایگزین شده است.
    - قابلیت آپلود کاور اختصاصی برای هر چپتر اضافه شده است.
    - قابلیت زمان‌بندی انتشار چپتر در آینده اضافه شده است.
*/

require_once 'core.php';

// --- گام ۱: دریافت ID ها و آماده‌سازی متغیرها ---
$novel_id = isset($_GET['novel_id']) ? intval($_GET['novel_id']) : 0;
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;
$is_editing = $chapter_id > 0;
$page_title = $is_editing ? "ویرایش چپتر" : "افزودن چپتر جدید";

if ($novel_id === 0) die("خطا: شناسه اثر مشخص نشده است.");

$chapter_data = [
    'chapter_number' => '', 'title' => '', 'content' => '', 
    'cover_url' => null, 'published_at' => null
];
$novel_info = null;

// --- گام ۲: واکشی اطلاعات از دیتابیس ---
try {
    // واکشی اطلاعات ناول و بررسی مالکیت
    $stmt_novel = $conn->prepare("SELECT id, title, type FROM novels WHERE id = ? AND author_id = ?");
    $stmt_novel->execute([$novel_id, $user_id]);
    $novel_info = $stmt_novel->fetch();

    if (!$novel_info) {
        die("خطا: شما به این اثر دسترسی ندارید یا این اثر وجود ندارد.");
    }

    // اگر در حالت ویرایش هستیم، اطلاعات چپتر فعلی را واکشی می‌کنیم
    if ($is_editing) {
        $stmt_chapter = $conn->prepare("SELECT * FROM chapters WHERE id = ? AND novel_id = ?");
        $stmt_chapter->execute([$chapter_id, $novel_id]);
        $fetched_chapter_data = $stmt_chapter->fetch();
        if (!$fetched_chapter_data) die("چپتر مورد نظر یافت نشد.");
        $chapter_data = $fetched_chapter_data;
    } else {
        // در غیر این صورت، شماره چپتر بعدی را پیشنهاد می‌دهیم
        $stmt_last = $conn->prepare("SELECT MAX(chapter_number) as max_num FROM chapters WHERE novel_id = ?");
        $stmt_last->execute([$novel_id]);
        $last_chapter = $stmt_last->fetch();
        $chapter_data['chapter_number'] = ($last_chapter && $last_chapter['max_num']) ? $last_chapter['max_num'] + 1 : 1;
    }
} catch (PDOException $e) {
    die("خطای دیتابیس: " . $e->getMessage());
}

$is_text_based = ($novel_info['type'] === 'novel');
?>

<!-- --- گام ۳: رندر کردن HTML صفحه --- -->
<title><?php echo $page_title; ?> - پنل نویسندگی</title>
<!-- کتابخانه Flatpickr برای انتخابگر تاریخ و زمان -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- (اختیاری) فارسی‌ساز برای Flatpickr -->
<script src="https://npmcdn.com/flatpickr/dist/l10n/fa.js"></script>

<div class="page-header">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>
    <p style="color: var(--dash-text-secondary);">برای اثر: "<?php echo htmlspecialchars($novel_info['title']); ?>"</p>
</div>

<div class="form-container">
    <form action="save_chapter.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
        <input type="hidden" name="novel_type" value="<?php echo $novel_info['type']; ?>">
        <?php if ($is_editing): ?>
            <input type="hidden" name="chapter_id" value="<?php echo $chapter_id; ?>">
            <input type="hidden" name="current_cover_url" value="<?php echo htmlspecialchars($chapter_data['cover_url'] ?? ''); ?>">
        <?php endif; ?>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="chapter_number">شماره چپتر</label>
                <input type="number" id="chapter_number" name="chapter_number" value="<?php echo htmlspecialchars($chapter_data['chapter_number']); ?>" required>
            </div>
            <div class="form-group" style="flex-grow: 3;">
                <label for="title">عنوان چپتر</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($chapter_data['title']); ?>" required>
            </div>
        </div>
        
        <div class="form-group-grid" style="align-items: flex-end;">
            <div class="form-group">
                <label for="chapter_cover">کاور چپتر (اختیاری)</label>
                <input type="file" id="chapter_cover" name="chapter_cover" accept="image/jpeg,image/png,image/webp">
                <?php if ($is_editing && !empty($chapter_data['cover_url'])): ?>
                    <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo htmlspecialchars($chapter_data['cover_url']); ?>" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">
                        <span style="font-size: 0.8em;">کاور فعلی بارگذاری شده است. برای تغییر، فایل جدید انتخاب کنید.</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="published_at">زمان‌بندی انتشار</label>
                <input type="text" id="datetime-picker" name="published_at" placeholder="برای انتشار فوری خالی بگذارید">
            </div>
        </div>

        <hr style="border-color: var(--dash-border); margin: 30px 0;">

        <!-- بخش محتوا بر اساس نوع اثر -->
        <?php if ($is_text_based): ?>
            <div class="form-group">
                <label for="content_text">محتوای چپتر</label>
                <textarea id="content_text" name="content_text" rows="25" style="font-family: inherit; font-size: 1rem; line-height: 1.8;" placeholder="متن چپتر خود را اینجا وارد کنید..."><?php echo htmlspecialchars($chapter_data['content']); ?></textarea>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label for="content_zip">فایل ZIP تصاویر چپتر</label>
                <input type="file" id="content_zip" name="content_zip" accept=".zip" <?php echo $is_editing ? '' : 'required'; ?>>
                <?php if ($is_editing): ?>
                    <p style="font-size: 0.8em; color: var(--dash-text-secondary); margin-top: 5px;">
                        توجه: ارسال فایل جدید، تمام تصاویر قبلی این چپتر را جایگزین می‌کند.
                    </p>
                    <?php 
                        $images = json_decode($chapter_data['content']);
                        if (is_array($images)) {
                            echo "<p>این چپتر در حال حاضر شامل <b>" . count($images) . "</b> تصویر است.</p>";
                        }
                    ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">ذخیره چپتر</button>
            <a href="../novel_detail.php?id=<?php echo $novel_id; ?>#chapters" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>

<script>
    // مقداردهی اولیه انتخابگر تاریخ و زمان با Flatpickr
    flatpickr("#datetime-picker", {
        enableTime: true,        // فعال کردن انتخاب زمان
        dateFormat: "Y-m-d H:i", // فرمت تاریخ برای ارسال به سرور
        locale: "fa",            // فارسی‌سازی تقویم
        time_24hr: true,         // استفاده از فرمت ۲۴ ساعته
        // قرار دادن تاریخ و زمان فعلی چپتر (در حالت ویرایش)
        defaultDate: "<?php echo isset($chapter_data['published_at']) ? date('Y-m-d H:i', strtotime($chapter_data['published_at'])) : '' ?>"
    });
</script>

<?php 
require_once 'footer.php'; 
?>
