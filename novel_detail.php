// novel_detail.php

<?php
/*
=====================================================
    NovelWorld - Novel Detail Page
    Version: 2.0 (Serverless Ready - PDO & JWT)
=====================================================
    - این صفحه جزئیات کامل یک ناول، لیست چپترها و نظرات کاربران را نمایش می‌دهد.
    - از PDO برای واکشی تمام اطلاعات از دیتابیس PostgreSQL (Neon) استفاده می‌کند.
    - وضعیت لاگین کاربر را از طریق سیستم JWT (پیاده‌سازی شده در header.php) بررسی کرده
      و محتوای صفحه را بر اساس آن تنظیم می‌کند.
*/

// --- گام ۱: فراخوانی هدر اصلی سایت ---
// این فایل شامل اتصال دیتابیس ($conn) و اطلاعات کاربر ($is_logged_in, $user_id, و غیره) است.
require_once 'header.php';

// --- گام ۲: دریافت و اعتبارسنجی ID اثر از URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // اگر ID نامعتبر بود، با یک پیام خطا، اجرای اسکریپت را متوقف کن.
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا: شناسه اثر نامعتبر است.</div>");
}
$novel_id = intval($_GET['id']);

// --- گام ۳: واکشی اطلاعات کامل ناول از دیتابیس با استفاده از PDO ---
try {
    // ۱. واکشی اطلاعات اصلی ناول
    $stmt_novel = $conn->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt_novel->execute([$novel_id]);
    $novel = $stmt_novel->fetch();

    // اگر ناولی با این شناسه یافت نشد، خطا نمایش بده.
    if (!$novel) {
        die("<div style='text-align:center; padding: 50px; color: white;'>خطا: اثری با این شناسه یافت نشد.</div>");
    }

    // ۲. واکشی تمام نظرات و پاسخ‌های مربوط به این ناول در یک کوئری
    $stmt_comments = $conn->prepare("SELECT * FROM comments WHERE novel_id = ? ORDER BY created_at ASC");
    $stmt_comments->execute([$novel_id]);
    $all_comments_results = $stmt_comments->fetchAll();

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، آن را لاگ کرده و پیام عمومی نمایش بده.
    error_log("Novel Detail Fetch Error: " . $e->getMessage());
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا در بارگذاری اطلاعات. لطفاً بعداً تلاش کنید.</div>");
}

// --- گام ۴: پردازش و مرتب‌سازی نظرات ---
// این روش بهینه است زیرا تنها یک بار به دیتابیس مراجعه می‌کنیم.
$comments = []; // آرایه‌ای برای نظرات اصلی (parent)
$replies = [];  // آرایه‌ای برای پاسخ‌ها، گروه‌بندی شده بر اساس parent_id

foreach ($all_comments_results as $row) {
    if ($row['parent_id'] === null) {
        // اگر parent_id نداشت، یک نظر اصلی است.
        $comments[] = $row;
    } else {
        // در غیر این صورت، یک پاسخ است و آن را در گروه مربوط به پدرش قرار می‌دهیم.
        $replies[$row['parent_id']][] = $row;
    }
}

// --- گام ۵: بررسی اینکه آیا کاربر فعلی، نویسنده این اثر است یا خیر ---
// از متغیرهای سراسری که در header.php از توکن JWT استخراج شده‌اند، استفاده می‌کنیم.
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
            <a href="#" class="btn btn-primary">شروع خواندن اولین چپتر</a>
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
            <?php if ($is_author): ?>
                <div class="author-actions-header">
                    <a href="dashboard/add_chapter.php?novel_id=<?php echo $novel['id']; ?>" class="btn btn-add-chapter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>افزودن چپتر جدید</span>
                    </a>
                </div>
            <?php endif; ?>

            <ul class="chapter-list">
                <!-- در آینده، اینجا لیست چپترها از دیتابیس واکشی و نمایش داده می‌شود -->
                <li class="chapter-item">
                    <a href="#">چپتر ۱ <span>- ۲ روز پیش</span></a>
                    <?php if ($is_author): ?>
                        <div class="chapter-author-tools">
                            <button class="tool-btn edit-btn" title="ویرایش">✏️</button>
                            <button class="tool-btn delete-btn" title="حذف">🗑️</button>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </div>

        <div id="comments" class="tab-content">
            <?php // بررسی پیام‌های موفقیت یا خطا از URL (ارسال شده از submit_comment.php)
                if (isset($_GET['status']) && $_GET['status'] === 'comment_success') {
                    echo "<div class='success-box' style='margin-bottom: 20px; background-color: #2e7d32; color: white; padding: 15px; border-radius: 8px;'>نظر شما با موفقیت ثبت شد.</div>";
                }
                if (isset($_GET['error'])) {
                     echo "<div class='error-box' style='margin-bottom: 20px;'>خطایی در پردازش درخواست شما رخ داد.</div>";
                }
            ?>

            <?php if ($is_logged_in): ?>
                <div class="comment-form-box">
                    <h3>نظر خود را به عنوان "<?php echo $username; ?>" بنویسید</h3>
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
                        <textarea name="content" placeholder="نظر شما..." rows="4" required></textarea>
                        <div class="form-footer">
                            <div class="spoiler-box">
                                <input type="checkbox" id="is_spoiler" name="is_spoiler" value="1">
                                <label for="is_spoiler">این نظر حاوی اسپویلر است</label>
                            </div>
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
                    <?php foreach (array_reverse($comments) as $comment): // معکوس کردن آرایه برای نمایش جدیدترین‌ها در بالا ?>
                        <div class="comment-box" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <span class="username">
                                    <?php echo htmlspecialchars($comment['user_name']); ?>
                                    <?php if ($comment['user_id'] == $novel['author_id']): ?>
                                        <span class="author-badge">نویسنده اثر ✔</span>
                                    <?php endif; ?>
                                </span>
                                <span class="timestamp"><?php echo date("Y/m/d", strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-body <?php if ($comment['is_spoiler']) echo 'spoiler'; ?>">
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                            <div class="comment-footer">
                                <div class="actions">
                                    <button class="action-btn reply-btn"><span>پاسخ</span></button>
                                    <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $comment['id']; ?>">
                                        👍 <span><?php echo $comment['likes']; ?></span>
                                    </button>
                                    <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $comment['id']; ?>">
                                        👎 <span><?php echo $comment['dislikes']; ?></span>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (isset($replies[$comment['id']])): ?>
                                <div class="replies-container">
                                    <?php foreach ($replies[$comment['id']] as $reply): ?>
                                        <div class="comment-box is-reply" id="comment-<?php echo $reply['id']; ?>">
                                            <div class="comment-header">
                                                 <span class="username">
                                                    <?php echo htmlspecialchars($reply['user_name']); ?>
                                                    <?php if ($reply['user_id'] == $novel['author_id']): ?>
                                                        <span class="author-badge">نویسنده اثر ✔</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="timestamp"><?php echo date("Y/m/d", strtotime($reply['created_at'])); ?></span>
                                            </div>
                                            <div class="comment-body <?php if ($reply['is_spoiler']) echo 'spoiler'; ?>">
                                                <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                            </div>
                                            <div class="comment-footer">
                                               <div class="actions">
                                                    <button class="action-btn reply-btn"><span>پاسخ</span></button>
                                                    <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $reply['id']; ?>">
                                                        👍 <span><?php echo $reply['likes']; ?></span>
                                                    </button>
                                                    <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $reply['id']; ?>">
                                                        👎 <span><?php echo $reply['dislikes']; ?></span>
                                                    </button>
                                                </div>
                                            </div>
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
// فراخوانی فوتر مشترک سایت
require_once 'footer.php'; 
?>header.php
