// novel_detail.php

<?php
/*
=====================================================
    NovelWorld - Novel Detail Page
    Version: 2.2 (Final, Unabridged, Patched Comment Query)
=====================================================
*/

// --- گام ۱: فراخوانی هدر اصلی سایت ---
require_once 'header.php';


// --- گام ۲: دریافت و اعتبارسنجی ID اثر از URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا: شناسه اثر نامعتبر است.</div>");
}
$novel_id = intval($_GET['id']);


// --- گام ۳: واکشی جامع اطلاعات از دیتابیس ---
try {
    // ۱. واکشی اطلاعات اصلی ناول
    $stmt_novel = $conn->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt_novel->execute([$novel_id]);
    $novel = $stmt_novel->fetch();

    if (!$novel) {
        die("<div style='text-align:center; padding: 50px; color: white;'>خطا: اثری با این شناسه یافت نشد.</div>");
    }

    // ۲. واکشی لیست چپترها
    $stmt_chapters = $conn->prepare("SELECT id, chapter_number, title, created_at FROM chapters WHERE novel_id = ? ORDER BY chapter_number ASC");
    $stmt_chapters->execute([$novel_id]);
    $chapters_list = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);
    
    // ۳. واکشی نظرات و پاسخ‌ها (با اصلاحیه مهم)
    // *** تغییر کلیدی: اضافه کردن شرط "WHERE chapter_id IS NULL" ***
    $stmt_comments = $conn->prepare("SELECT * FROM comments WHERE novel_id = ? AND chapter_id IS NULL ORDER BY created_at ASC");
    $stmt_comments->execute([$novel_id]);
    $all_comments_results = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Novel Detail Fetch Error: " . $e->getMessage());
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا در بارگذاری اطلاعات.</div>");
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


// --- گام ۵: بررسی‌های شرطی برای نمایش دکمه‌ها ---
$is_author = ($is_logged_in && $user_id == $novel['author_id']);
$is_in_library = false;
if ($is_logged_in) {
    try {
        $stmt_check = $conn->prepare("SELECT id FROM library_items WHERE user_id = ? AND novel_id = ?");
        $stmt_check->execute([$user_id, $novel_id]);
        if ($stmt_check->fetch()) {
            $is_in_library = true;
        }
    } catch (PDOException $e) {
        error_log("Library check failed: " . $e->getMessage());
    }
}

// --- گام ۶: رندر کردن بخش HTML ---
?>
<title><?php echo htmlspecialchars($novel['title']); ?> - NovelWorld</title>
<link rel="stylesheet" href="detail-style.css">
<style>
.btn-danger { background-color: #d32f2f; color: white; } 
.btn-danger:hover { background-color: #c62828; }
.success-box { margin-bottom: 20px; background-color: #2e7d32; color: white; padding: 15px; border-radius: 8px; text-align: center; }
.error-box { margin-bottom: 20px; background-color: #d32f2f; color: white; padding: 15px; border-radius: 8px; text-align: center; }
</style>

<div class="detail-container">
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($novel['cover_url']); ?>');">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="کاور <?php echo htmlspecialchars($novel['title']); ?>" class="hero-cover-img">
            <div class="hero-title-box"><h1 class="hero-title"><?php echo htmlspecialchars($novel['title']); ?></h1></div>
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
            <?php foreach (explode(',', $novel['genres']) as $genre) { echo '<span class="genre-tag">' . htmlspecialchars(trim($genre)) . '</span>'; } ?>
        </div>
        <div class="action-buttons">
            <?php if (!empty($chapters_list)): ?>
                <a href="read_chapter.php?id=<?php echo $chapters_list[0]['id']; ?>" class="btn btn-primary">شروع خواندن</a>
            <?php endif; ?>
            
            <?php if ($is_logged_in && !$is_author): ?>
                <button id="library-toggle-btn" class="btn <?php echo $is_in_library ? 'btn-danger' : 'btn-secondary'; ?>" data-novel-id="<?php echo $novel['id']; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px; vertical-align: middle;"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    <span><?php echo $is_in_library ? 'حذف از کتابخانه' : 'افزودن به کتابخانه'; ?></span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <section class="tab-system">
        <div class="tab-links">
            <button class="tab-link active" data-tab="summary">خلاصه</button>
            <button class="tab-link" data-tab="chapters">لیست چپترها (<?php echo count($chapters_list); ?>)</button>
            <button class="tab-link" data-tab="comments">نظرات (<?php echo count($comments); ?>)</button>
        </div>

        <div id="summary" class="tab-content active"><p><?php echo nl2br(htmlspecialchars($novel['summary'])); ?></p></div>

        <div id="chapters" class="tab-content">
            <?php if ($is_author): ?>
                <div class="author-actions-header"><a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel['id']; ?>" class="btn-add-chapter"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>افزودن چپتر جدید</span></a></div>
            <?php endif; ?>

            <?php if (empty($chapters_list)): ?>
                <p style="text-align: center; margin-top: 20px;">هنوز چپتری برای این ناول منتشر نشده است.</p>
            <?php else: ?>
                <ul class="chapter-list">
                    <?php foreach ($chapters_list as $chapter): ?>
                        <li class="chapter-item">
                            <a href="read_chapter.php?id=<?php echo $chapter['id']; ?>">
                                چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?>: <?php echo htmlspecialchars($chapter['title']); ?>
                                <span style="font-size: 0.8em; color: var(--text-secondary-color); margin-right: 10px;">- منتشر شده در <?php echo date("Y/m/d", strtotime($chapter['created_at'])); ?></span>
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
            <?php if (isset($_GET['status']) && $_GET['status'] === 'comment_success') { echo "<div class='success-box'>نظر شما با موفقیت ثبت شد.</div>"; } ?>
            <?php if (isset($_GET['error'])) { echo "<div class='error-box'>خطایی در پردازش نظر شما رخ داد.</div>"; } ?>

            <?php if ($is_logged_in): ?>
                <div class="comment-form-box">
                    <h3>نظر خود را به عنوان "<?php echo $username; ?>" بنویسید</h3>
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
                        <textarea name="content" placeholder="نظر شما درباره این اثر..." rows="4" required></textarea>
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
                    <p style="text-align: center; margin-top: 20px;">هنوز نظری برای این اثر ثبت نشده است. اولین نفر باشید!</p>
                <?php else: ?>
                    <?php foreach (array_reverse($comments) as $comment): ?>
                        <div class="comment-box" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <span class="username">
                                    <?php echo htmlspecialchars($comment['user_name']); ?>
                                    <?php if ($comment['user_id'] == $novel['author_id']): ?><span class="author-badge">نویسنده اثر ✔</span><?php endif; ?>
                                </span>
                                <span class="timestamp"><?php echo date("Y/m/d", strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-body <?php if ($comment['is_spoiler']) echo 'spoiler'; ?>"><p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p></div>
                            <div class="comment-footer">
                                <div class="actions">
                                    <button class="action-btn reply-btn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M10 11h-4v-2h4v-2l4 3-4 3v-2zm10.28-5.22c-1.16-1.16-2.68-1.78-4.28-1.78s-3.12.62-4.28 1.78c-2.34 2.34-2.34 6.14 0 8.48l2.82-2.82c-.78-.78-.78-2.04 0-2.82s2.04-.78 2.82 0 2.04.78 2.82 0 .78-2.04 0-2.82l2.82-2.82zM4.1 20.28c-2.34-2.34-2.34-6.14 0-8.48l2.82 2.82c.78.78.78 2.04 0 2.82s-2.04-.78-2.82 0-2.04-.78-2.82 0-.78 2.04 0 2.82L4.1 20.28z"></path></svg><span>پاسخ</span></button>
                                    <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $comment['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path></svg><span><?php echo $comment['likes']; ?></span></button>
                                    <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $comment['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg><span><?php echo $comment['dislikes']; ?></span></button>
                                </div>
                            </div>
                            
                            <?php if (isset($replies[$comment['id']])): ?>
                                <div class="replies-container">
                                    <?php foreach ($replies[$comment['id']] as $reply): ?>
                                        <div class="comment-box is-reply" id="comment-<?php echo $reply['id']; ?>">
                                            <div class="comment-header"><span class="username"><?php echo htmlspecialchars($reply['user_name']); ?><?php if ($reply['user_id'] == $novel['author_id']): ?><span class="author-badge">نویسنده اثر ✔</span><?php endif; ?></span><span class="timestamp"><?php echo date("Y/m/d", strtotime($reply['created_at'])); ?></span></div>
                                            <div class="comment-body <?php if ($reply['is_spoiler']) echo 'spoiler'; ?>"><p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p></div>
                                            <div class="comment-footer"><div class="actions">
                                                <button class="action-btn reply-btn"><svg ...></svg><span>پاسخ</span></button>
                                                <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $reply['id']; ?>"><svg ...></svg><span><?php echo $reply['likes']; ?></span></button>
                                                <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $reply['id']; ?>"><svg ...></svg><span><?php echo $reply['dislikes']; ?></span></button>
                                            </div></div>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('library-toggle-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            toggleBtn.disabled = true;
            const novelId = toggleBtn.dataset.novelId;
            const btnSpan = toggleBtn.querySelector('span');
            try {
                const response = await fetch('toggle_library.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ novel_id: novelId })
                });
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'خطای سرور');
                }
                const data = await response.json();
                if (data.success) {
                    if (data.action === 'added') {
                        if (btnSpan) btnSpan.textContent = 'حذف از کتابخانه';
                        toggleBtn.classList.remove('btn-secondary');
                        toggleBtn.classList.add('btn-danger');
                    } else {
                        if (btnSpan) btnSpan.textContent = 'افزودن به کتابخانه';
                        toggleBtn.classList.remove('btn-danger');
                        toggleBtn.classList.add('btn-secondary');
                    }
                } else {
                    alert(data.message || 'خطایی رخ داد.');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                toggleBtn.disabled = false;
            }
        });
    }
});
</script>

<?php 
require_once 'footer.php'; 
?>
