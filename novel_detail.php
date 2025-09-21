<?php
// novel_detail.php (نسخه نهایی - با مدیریت چپتر)

/*
=====================================================
    NovelWorld - Novel Detail Page
    Version: 3.0 (Cookie-Session & Chapter Management)
=====================================================
    - این صفحه جزئیات کامل یک ناول، لیست چپترها و نظرات کاربران را نمایش می‌دهد.
    - از سیستم احراز هویت سشن مبتنی بر کوکی استفاده می‌کند.
    - لیست چپترها را از دیتابیس واکشی کرده و ابزارهای مدیریتی را برای نویسنده نمایش می‌دهد.
*/

// --- گام ۱: فراخوانی هدر و اتصال دیتابیس ---
require_once 'header.php';
require_once 'db_connect.php';

// --- گام ۲: دریافت و اعتبارسنجی ID اثر از URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا: شناسه اثر نامعتبر است.</div>");
}
$novel_id = intval($_GET['id']);

// --- گام ۳: واکشی تمام اطلاعات مورد نیاز از دیتابیس ---
try {
    // ۱. واکشی اطلاعات اصلی ناول
    $stmt_novel = $conn->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt_novel->execute([$novel_id]);
    $novel = $stmt_novel->fetch();

    if (!$novel) {
        die("<div style='text-align:center; padding: 50px; color: white;'>خطا: اثری با این شناسه یافت نشد.</div>");
    }

    // ۲. واکشی لیست چپترها، مرتب شده بر اساس شماره چپتر
    $stmt_chapters = $conn->prepare("SELECT id, chapter_number, title, created_at FROM chapters WHERE novel_id = ? ORDER BY chapter_number ASC");
    $stmt_chapters->execute([$novel_id]);
    $chapters_list = $stmt_chapters->fetchAll();

    // ۳. واکشی تمام نظرات و پاسخ‌ها
    $stmt_comments = $conn->prepare("SELECT * FROM comments WHERE novel_id = ? ORDER BY created_at ASC");
    $stmt_comments->execute([$novel_id]);
    $all_comments_results = $stmt_comments->fetchAll();

} catch (PDOException $e) {
    error_log("Novel Detail Fetch Error: " . $e->getMessage());
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا در بارگذاری اطلاعات. لطفاً بعداً تلاش کنید.</div>");
}

// --- گام ۴: پردازش و مرتب‌سازی نظرات ---
$comments = [];
$replies = [];
foreach ($all_comments_results as $row) {
    if ($row['parent_id'] === null) {
        $comments[] = $row;
    } else {
        $replies[$row['parent_id']][] = $row;
    }
}

// --- گام ۵: بررسی اینکه آیا کاربر فعلی، نویسنده این اثر است یا خیر ---
$is_author = ($is_logged_in && $user_id == $novel['author_id']);

?>
<!-- --- گام ۶: رندر کردن بخش HTML --- -->
<title><?php echo htmlspecialchars($novel['title']); ?> - NovelWorld</title>
<link rel="stylesheet" href="detail-style.css">

<div class="detail-container">
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($novel['cover_url']); ?>');">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="کاور <?php echo htmlspecialchars($novel['title']); ?>" class="hero-cover-img">
            <div class="hero-title-box">
                <h1 class="hero-title"><?php echo htmlspecialchars($novel['title']); ?></h1>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <div class="info-grid">
            <div class="info-item"><span>امتیاز</span><strong><?php echo htmlspecialchars($novel['rating']); ?> ★</strong></div>
            <div class="info-item"><span>وضعیت</span><strong><?php echo htmlspecialchars($novel['status'] ?? 'نامشخص'); ?></strong></div>
            <div class="info-item"><span>نویسنده</span><strong><?php echo htmlspecialchars($novel['author'] ?? 'نامشخص'); ?></strong></div>
            <div class="info-item"><span>آرتیست</span><strong><?php echo htmlspecialchars($novel['artist'] ?? 'نامشخص'); ?></strong></div>
        </div>
        <div class="genres-box">
            <?php 
                $genres = explode(',', $novel['genres']);
                foreach ($genres as $genre) {
                    echo '<span class="genre-tag">' . htmlspecialchars(trim($genre)) . '</span>';
                }
            ?>
        </div>
        <div class="action-buttons">
            <?php if (!empty($chapters_list)): ?>
                <a href="read_chapter.php?id=<?php echo $chapters_list[0]['id']; ?>" class="btn btn-primary">شروع خواندن اولین چپتر</a>
            <?php endif; ?>
            <a href="#" class="btn btn-secondary">افزودن به کتابخانه</a>
        </div>
    </section>

    <section class="tab-system">
        <div class="tab-links">
            <button class="tab-link active" data-tab="summary">خلاصه</button>
            <button class="tab-link" data-tab="chapters">لیست چپترها</button>
            <button class="tab-link" data-tab="comments">نظرات</button>
        </div>

        <div id="summary" class="tab-content active">
            <p><?php echo nl2br(htmlspecialchars($novel['summary'])); ?></p>
        </div>

        <div id="chapters" class="tab-content">
            <?php // نمایش پیام وضعیت پس از مدیریت چپتر
                if (isset($_GET['status'])) {
                    $status_message = '';
                    if ($_GET['status'] === 'chapter_saved') $status_message = 'چپتر با موفقیت ذخیره شد.';
                    if ($_GET['status'] === 'chapter_deleted') $status_message = 'چپتر با موفقیت حذف شد.';
                    if ($status_message) {
                        echo "<div class='success-box' style='margin-bottom: 20px; background-color: #2e7d32; color: white; padding: 15px; border-radius: 8px;'>$status_message</div>";
                    }
                }
            ?>
            <?php if ($is_author): ?>
                <div class="author-actions-header">
                    <a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel['id']; ?>" class="btn btn-add-chapter">
                        <span>افزودن چپتر جدید</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($chapters_list)): ?>
                <p>هنوز چپتری برای این ناول منتشر نشده است.</p>
            <?php else: ?>
                <ul class="chapter-list">
                    <?php foreach ($chapters_list as $chapter): ?>
                        <li class="chapter-item">
                            <a href="read_chapter.php?id=<?php echo $chapter['id']; ?>">
                                چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?>: <?php echo htmlspecialchars($chapter['title']); ?>
                                <span>- منتشر شده در: <?php echo date("Y/m/d", strtotime($chapter['created_at'])); ?></span>
                            </a>
                            <?php if ($is_author): ?>
                                <div class="chapter-author-tools">
                                    <a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel['id']; ?>&chapter_id=<?php echo $chapter['id']; ?>" class="tool-btn edit-btn" title="ویرایش">✏️</a>
                                    <a href="dashboard/delete_chapter.php?novel_id=<?php echo $novel['id']; ?>&chapter_id=<?php echo $chapter['id']; ?>" class="tool-btn delete-btn" title="حذف" onclick="return confirm('آیا از حذف این چپتر مطمئن هستید؟ این عمل غیرقابل بازگشت است.');">🗑️</a>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div id="comments" class="tab-content">
            <?php if ($is_logged_in): ?>
                <div class="comment-form-box">
                    <h3>نظر خود را به عنوان "<?php echo $username; ?>" بنویسید</h3>
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
                        <textarea name="content" placeholder="نظر شما..." rows="4" required></textarea>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">ارسال نظر</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <p class="login-prompt"><a href="login.php">برای ثبت نظر، لطفاً وارد شوید.</a></p>
            <?php endif; ?>

            <div class="comments-container">
                <?php if (empty($comments)): ?>
                    <p>هنوز نظری برای این اثر ثبت نشده است. اولین نفر باشید!</p>
                <?php else: ?>
                    <?php foreach (array_reverse($comments) as $comment): ?>
                        <div class="comment-box" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <span class="username"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                            </div>
                            <div class="comment-body"><p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p></div>
                            <?php if (isset($replies[$comment['id']])): ?>
                                <div class="replies-container">
                                    <?php foreach ($replies[$comment['id']] as $reply): ?>
                                        <div class="comment-box is-reply" id="comment-<?php echo $reply['id']; ?>">
                                            <div class="comment-header"><span class="username"><?php echo htmlspecialchars($reply['user_name']); ?></span></div>
                                            <div class="comment-body"><p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script src="detail-script.js"></script>

<?php 
require_once 'footer.php'; 
?>
