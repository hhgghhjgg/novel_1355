<?php
// load_posts.php

/*
=====================================================
    NovelWorld - Load Posts (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای بارگذاری پست‌های یک کاربر
      در صفحه پروفایل عمومی او عمل می‌کند.
    - خروجی آن فقط کدهای HTML است که توسط جاوااسکریپت در صفحه قرار می‌گیرد.
*/

// --- گام ۱: فراخوانی فایل هسته ---
// برای اتصال به دیتابیس و اطلاعات کاربر لاگین کرده (برای بررسی لایک‌ها)
require_once 'core.php';

// --- گام ۲: دریافت و اعتبارسنجی ورودی‌ها ---
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($profile_user_id <= 0) {
    // به جای die()، یک خروجی HTML با پیام خطا برمی‌گردانیم
    echo "<p class='empty-tab-message'>خطا: شناسه کاربر نامعتبر است.</p>";
    exit();
}

// --- گام ۳: واکشی پست‌ها از دیتابیس ---
try {
    // واکشی پست‌ها به همراه اطلاعات کاربر (برای نمایش عکس پروفایل و نام)
    // پست‌های جدیدتر در بالا نمایش داده می‌شوند.
    $sql = "SELECT p.id, p.content, p.image_url, p.likes, p.dislikes, p.created_at,
                   u.username, u.profile_picture_url
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT 20"; // (اختیاری) اضافه کردن محدودیت برای صفحه‌بندی در آینده
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$profile_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Load Posts Error: " . $e->getMessage());
    echo "<p class='empty-tab-message' style='color: var(--danger-color);'>خطا در بارگذاری پست‌ها.</p>";
    exit();
}

// --- گام ۴: رندر کردن (چاپ) بخش HTML پست‌ها ---
?>

<?php if (empty($posts)): ?>
    <p class="empty-tab-message">این کاربر هنوز پستی منتشر نکرده است.</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div class="post-card" id="post-<?php echo $post['id']; ?>">
            <div class="post-header">
                <a href="public_profile.php?username=<?php echo urlencode($post['username']); ?>" class="author-info">
                    <img src="<?php echo htmlspecialchars($post['profile_picture_url'] ?? 'default_avatar.png'); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>">
                    <span class="author-name"><?php echo htmlspecialchars($post['username']); ?></span>
                </a>
                <span class="post-timestamp"><?php echo date("Y/m/d", strtotime($post['created_at'])); ?></span>
            </div>
            <div class="post-content">
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="تصویر پست" class="post-image">
                <?php endif; ?>
            </div>
            <div class="post-footer">
                <div class="post-actions">
                    <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>" data-action="like">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"></path></svg>
                        <span><?php echo $post['likes']; ?></span>
                    </button>
                    <button class="action-btn dislike-btn" data-post-id="<?php echo $post['id']; ?>" data-action="dislike">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg>
                        <span><?php echo $post['dislikes']; ?></span>
                    </button>
                    <button class="action-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
                        <span>نظر</span>
                    </button>
                </div>
            </div>
            <!-- بخش نظرات هر پست می‌تواند به صورت داینامیک در اینجا بارگذاری شود -->
            <div class="post-comments-section" id="comments-for-post-<?php echo $post['id']; ?>" style="display: none;"></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>```
