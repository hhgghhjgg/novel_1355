<?php
// dashboard/manage_chapter.php

/*
=====================================================
    NovelWorld - Manage Chapter Page
    Version: 2.0 (Multi-Type Ready, ZIP Upload)
=====================================================
    - این صفحه به صورت هوشمند فرم مناسب را بر اساس نوع اثر (ناول یا تصویری) نمایش می‌دهد.
    - برای ناول‌ها، ویرایشگر متن TinyMCE را نمایش می‌دهد.
    - برای مانهوا/مانگا، فیلد آپلود فایل ZIP را نمایش می‌دهد.
*/

require_once 'header.php';

// --- گام ۱: دریافت ID ها و آماده‌سازی متغیرها ---
$novel_id = isset($_GET['novel_id']) ? intval($_GET['novel_id']) : 0;
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;
$is_editing = $chapter_id > 0;
$page_title = $is_editing ? "ویرایش چپتر" : "افزودن چپتر جدید";

if ($novel_id === 0) die("خطا: شناسه اثر مشخص نشده است.");

$chapter_data = ['chapter_number' => '', 'title' => '', 'content' => ''];
$novel_info = null;

// --- گام ۲: واکشی اطلاعات از دیتابیس ---
try {
    // واکشی اطلاعات ناول (شامل نوع اثر) و بررسی مالکیت
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

<!-- --- گام ۳: رندر کردن بخش HTML --- -->
<title><?php echo $page_title; ?> - پنل نویسندگی</title>

<!-- فقط در صورتی که اثر متنی باشد، اسکریپت TinyMCE را لود می‌کنیم -->
<?php if ($is_text_based): ?>
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#chapter_content_text',
    plugins: 'directionality lists link code help wordcount',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | ltr rtl | bullist numlist | link | code',
    height: 400,
    menubar: false,
    content_css: 'dark',
    skin: 'oxide-dark',
  });
</script>
<?php endif; ?>

<div class="page-header">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>
    <p style="color: var(--dash-text-secondary);">برای اثر: "<?php echo htmlspecialchars($novel_info['title']); ?>" (نوع: <?php echo $novel_info['type']; ?>)</p>
</div>

<div class="form-container">
    <!-- فرم به save_chapter.php ارسال می‌شود که منطق هر دو نوع را مدیریت خواهد کرد -->
    <form action="save_chapter.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
        <input type="hidden" name="novel_type" value="<?php echo $novel_info['type']; ?>">
        <?php if ($is_editing): ?>
            <input type="hidden" name="chapter_id" value="<?php echo $chapter_id; ?>">
        <?php endif; ?>

        <div class="form-group-grid">
            <div class="form-group">
                <label for="chapter_number">شماره چپتر:</label>
                <input type="number" id="chapter_number" name="chapter_number" value="<?php echo htmlspecialchars($chapter_data['chapter_number']); ?>" required>
            </div>
            <div class="form-group" style="flex-grow: 3;">
                <label for="title">عنوان چپتر:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($chapter_data['title']); ?>" required>
            </div>
        </div>
        
        <!-- *** بخش شرطی بر اساس نوع اثر *** -->
        <?php if ($is_text_based): ?>
            <!-- فرم برای ناول (متنی) -->
            <div id="text-editor-group" class="form-group">
                <label for="chapter_content_text">محتوای چپتر:</label>
                <textarea id="chapter_content_text" name="content_text" rows="15"><?php echo htmlspecialchars($chapter_data['content']); ?></textarea>
            </div>
        <?php else: ?>
            <!-- فرم برای مانهوا/مانگا (تصویری) -->
            <div id="image-uploader-group" class="form-group">
                <label for="chapter_content_zip">فایل ZIP چپتر:</label>
                <input type="file" id="chapter_content_zip" name="content_zip" accept=".zip" <?php echo $is_editing ? '' : 'required'; ?>>
                <?php if ($is_editing): ?>
                    <p style="font-size: 0.8em; color: var(--dash-text-secondary); margin-top: 5px;">
                        توجه: ارسال یک فایل جدید، تمام تصاویر قبلی این چپتر را جایگزین خواهد کرد. اگر قصد تغییر ندارید، این فیلد را خالی بگذارید.
                    </p>
                    <?php 
                        // نمایش تعداد تصاویر فعلی
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

<?php 
require_once 'footer.php'; 
?>
