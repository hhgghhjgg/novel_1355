<?php
// load_comments.php

/*
=====================================================
    NovelWorld - Load Comments (AJAX Endpoint, Unabridged)
    Version: 1.0
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای بارگذاری داینامیک
      بخش نظرات یک ناول یا یک چپتر خاص عمل می‌کند.
    - خروجی آن فقط کدهای HTML است که توسط جاوااسکریپت در صفحه قرار می‌گیرد.
*/

// --- گام ۱: فراخوانی فایل هسته ---
require_once 'core.php';

// --- گام ۲: دریافت و اعتبارسنجی ورودی‌ها ---
$novel_id = isset($_GET['novel_id']) ? intval($_GET['novel_id']) : null;
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : null;

if (!$novel_id && !$chapter_id) {
    echo "<p>خطا: شناسه محتوا مشخص نشده است.</p>";
    exit();
}

// --- گام ۳: واکشی اطلاعات لازم از دیتابیس ---
try {
    $author_id_of_work = null;
    if ($novel_id) {
        $stmt_author = $conn->prepare("SELECT author_id FROM novels WHERE id = ?");
        $stmt_author->execute([$novel_id]);
        $author_id_of_work = $stmt_author->fetchColumn();
    } elseif ($chapter_id) {
        $stmt_author = $conn->prepare("SELECT n.author_id FROM novels n JOIN chapters c ON n.id = c.novel_id WHERE c.id = ?");
        $stmt_author->execute([$chapter_id]);
        $author_id_of_work = $stmt_author->fetchColumn();
    }

    if ($chapter_id) {
        $sql = "SELECT * FROM comments WHERE chapter_id = ? ORDER BY created_at ASC";
        $params = [$chapter_id];
    } else {
        $sql = "SELECT * FROM comments WHERE novel_id = ? AND chapter_id IS NULL ORDER BY created_at ASC";
        $params = [$novel_id];
    }
    
    $stmt_comments = $conn->prepare($sql);
    $stmt_comments->execute($params);
    $all_comments_results = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Load Comments Error: " . $e->getMessage());
    echo "<p style='color: #ff8a8a;'>خطا در بارگذاری نظرات. لطفاً صفحه را رفرش کنید.</p>";
    exit();
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

// --- گام ۵: رندر کردن (چاپ) بخش HTML نظرات ---
?>

<?php if ($is_logged_in): ?>
    <div class="comment-form-box">
        <h3>نظر خود را به عنوان "<?php echo $username; ?>" بنویسید</h3>
        <form action="submit_comment.php" method="POST" class="ajax-comment-form">
            <?php if ($novel_id): ?><input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>"><?php endif; ?>
            <?php if ($chapter_id): ?><input type="hidden" name="chapter_id" value="<?php echo $chapter_id; ?>"><?php endif; ?>
            
            <textarea name="content" placeholder="نظر شما..." rows="4" required></textarea>
            <div class="form-footer">
                <div class="spoiler-box">
                    <input type="checkbox" id="is_spoiler_dynamic" name="is_spoiler" value="1">
                    <label for="is_spoiler_dynamic">این نظر حاوی اسپویلر است</label>
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
        <p style="text-align: center; margin-top: 20px;">هنوز نظری ثبت نشده است. اولین نفر باشید!</p>
    <?php else: ?>
        <?php foreach (array_reverse($comments) as $comment): ?>
            <div class="comment-box" id="comment-<?php echo $comment['id']; ?>">
                <div class="comment-header">
                    <span class="username">
                        <?php echo htmlspecialchars($comment['user_name']); ?>
                        <?php if ($author_id_of_work && $comment['user_id'] == $author_id_of_work): ?>
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
                        <button class="action-btn reply-btn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M10 11h-4v-2h4v-2l4 3-4 3v-2zm10.28-5.22c-1.16-1.16-2.68-1.78-4.28-1.78s-3.12.62-4.28 1.78c-2.34 2.34-2.34 6.14 0 8.48l2.82-2.82c-.78-.78-.78-2.04 0-2.82s2.04-.78 2.82 0 2.04.78 2.82 0 .78-2.04 0-2.82l2.82-2.82zM4.1 20.28c-2.34-2.34-2.34-6.14 0-8.48l2.82 2.82c.78.78.78 2.04 0 2.82s-2.04-.78-2.82 0-2.04-.78-2.82 0-.78 2.04 0 2.82L4.1 20.28z"></path></svg><span>پاسخ</span></button>
                        <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $comment['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path></svg><span><?php echo $comment['likes']; ?></span></button>
                        <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $comment['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg><span><?php echo $comment['dislikes']; ?></span></button>
                    </div>
                </div>
                
                <?php if (isset($replies[$comment['id']])): ?>
                    <div class="replies-container">
                        <?php foreach ($replies[$comment['id']] as $reply): ?>
                            <div class="comment-box is-reply" id="comment-<?php echo $reply['id']; ?>">
                                <div class="comment-header">
                                    <span class="username">
                                        <?php echo htmlspecialchars($reply['user_name']); ?>
                                        <?php if ($author_id_of_work && $reply['user_id'] == $author_id_of_work): ?>
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
                                        <button class="action-btn reply-btn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M10 11h-4v-2h4v-2l4 3-4 3v-2zm10.28-5.22c-1.16-1.16-2.68-1.78-4.28-1.78s-3.12.62-4.28 1.78c-2.34 2.34-2.34 6.14 0 8.48l2.82-2.82c-.78-.78-.78-2.04 0-2.82s2.04-.78 2.82 0 2.04.78 2.82 0 .78-2.04 0-2.82l2.82-2.82zM4.1 20.28c-2.34-2.34-2.34-6.14 0-8.48l2.82 2.82c.78.78.78 2.04 0 2.82s-2.04-.78-2.82 0-2.04-.78-2.82 0-.78 2.04 0 2.82L4.1 20.28z"></path></svg><span>پاسخ</span></button>
                                        <button class="action-btn like-btn" data-action="like" data-comment-id="<?php echo $reply['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path></svg><span><?php echo $reply['likes']; ?></span></button>
                                        <button class="action-btn dislike-btn" data-action="dislike" data-comment-id="<?php echo $reply['id']; ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg><span><?php echo $reply['dislikes']; ?></span></button>
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
