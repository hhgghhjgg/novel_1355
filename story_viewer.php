<?php
// story_viewer.php

/*
=====================================================
    NovelWorld - Novel Story Viewer
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این صفحه به عنوان نمایشگر تمام‌صفحه برای "استوری ناول‌ها" عمل می‌کند.
    - UI آن شبیه به استوری‌های اینستاگرام طراحی شده است.
    - اطلاعات استوری فعلی و استوری‌های بعدی همان کاربر را واکشی می‌کند.
*/

// --- گام ۱: فراخوانی فایل هسته ---
require_once 'core.php';

// --- گام ۲: واکشی اطلاعات استوری‌ها ---
$story_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($story_id <= 0) {
    die("شناسه استوری نامعتبر است.");
}

$stories_data = [];

try {
    // واکشی اطلاعات استوری فعلی
    $stmt_current = $conn->prepare(
        "SELECT s.*, n.cover_url, n.title as novel_title, u.username, u.profile_picture_url
         FROM novel_stories s
         JOIN novels n ON s.novel_id = n.id
         JOIN users u ON s.user_id = u.id
         WHERE s.id = ?"
    );
    $stmt_current->execute([$story_id]);
    $current_story = $stmt_current->fetch();

    if (!$current_story) {
        die("استوری مورد نظر یافت نشد یا منقضی شده است.");
    }

    // واکشی تمام استوری‌های فعال این کاربر برای نمایش در نوار پیشرفت
    $stmt_all = $conn->prepare(
        "SELECT s.id, s.title, n.cover_url, n.id as novel_id
         FROM novel_stories s
         JOIN novels n ON s.novel_id = n.id
         WHERE s.user_id = ? AND s.expires_at > NOW()
         ORDER BY s.created_at ASC"
    );
    $stmt_all->execute([$current_story['user_id']]);
    $stories_data = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    // افزایش شمارنده بازدید (در یک درخواست جداگانه بهتر است، اما برای سادگی اینجا قرار می‌دهیم)
    $conn->exec("UPDATE novel_stories SET views = views + 1 WHERE id = $story_id");

} catch (PDOException $e) {
    die("خطا در بارگذاری استوری: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استوری <?php echo htmlspecialchars($current_story['username']); ?> - NovelWorld</title>
    <link rel="stylesheet" href="story_viewer_style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

    <div class="story-viewer-container">
        <!-- نوارهای پیشرفت در بالا -->
        <div class="progress-bars">
            <?php foreach ($stories_data as $story): ?>
                <div class="progress-bar-container"><div class="progress-bar" id="progress-<?php echo $story['id']; ?>"></div></div>
            <?php endforeach; ?>
        </div>

        <!-- هدر استوری (اطلاعات کاربر) -->
        <header class="story-header">
            <a href="public_profile.php?username=<?php echo urlencode($current_story['username']); ?>" class="user-info">
                <img src="<?php echo htmlspecialchars($current_story['profile_picture_url'] ?? 'default_avatar.png'); ?>" alt="پروفایل">
                <span><?php echo htmlspecialchars($current_story['username']); ?></span>
            </a>
            <a href="index.php" class="close-btn">&times;</a>
        </header>

        <!-- بخش‌های کلیک برای ناوبری -->
        <div class="nav-tap left" id="tap-prev"></div>
        <div class="nav-tap right" id="tap-next"></div>

        <!-- محتوای اصلی استوری -->
        <div class="story-content">
            <div class="story-background" style="background-image: url('<?php echo htmlspecialchars($current_story['cover_url']); ?>');"></div>
            <div class="story-overlay"></div>
            <div class="story-details">
                <?php if (!empty($current_story['title'])): ?>
                    <h2 class="story-title"><?php echo htmlspecialchars($current_story['title']); ?></h2>
                <?php endif; ?>
                <h3 class="novel-title"><?php echo htmlspecialchars($current_story['novel_title']); ?></h3>
                <a href="novel_detail.php?id=<?php echo $current_story['novel_id']; ?>" class="view-novel-btn">مشاهده اثر</a>
            </div>
        </div>

        <!-- فوتر استوری (تعداد بازدید) -->
        <footer class="story-footer">
            <div class="view-count">
                <span class="material-symbols-outlined">visibility</span>
                <span><?php echo htmlspecialchars($current_story['views']); ?></span>
            </div>
        </footer>
    </div>

    <script>
        // پاس دادن اطلاعات استوری‌ها به جاوااسکریپت
        const stories = <?php echo json_encode($stories_data); ?>;
        const currentStoryId = <?php echo $story_id; ?>;
    </script>
    <script>
        // story_viewer_script.js
        document.addEventListener('DOMContentLoaded', () => {
            let currentIndex = stories.findIndex(story => story.id == currentStoryId);
            if (currentIndex === -1) return;

            const tapPrev = document.getElementById('tap-prev');
            const tapNext = document.getElementById('tap-next');
            let storyTimer;
            const DURATION = 5000; // 5 ثانیه برای هر استوری

            function goToStory(index) {
                if (index < 0 || index >= stories.length) {
                    // در آینده، اینجا منطق بارگذاری استوری کاربر بعدی را اضافه کنید
                    // fetch('load_next_stories.php?currentUser=...')
                    console.log("End of stories for this user. Load next user's stories.");
                    window.location.href = 'index.php'; // بازگشت به صفحه اصلی
                    return;
                }
                const nextStoryId = stories[index].id;
                window.location.href = `story_viewer.php?id=${nextStoryId}`;
            }

            function startStory() {
                // پر کردن نوارهای پیشرفت قبلی
                for (let i = 0; i < currentIndex; i++) {
                    const progressBar = document.getElementById(`progress-${stories[i].id}`);
                    if(progressBar) progressBar.style.width = '100%';
                }

                // شروع انیمیشن برای نوار پیشرفت فعلی
                const currentProgressBar = document.getElementById(`progress-${stories[currentIndex].id}`);
                if(currentProgressBar) {
                    currentProgressBar.style.transition = `width ${DURATION / 1000}s linear`;
                    requestAnimationFrame(() => {
                        currentProgressBar.style.width = '100%';
                    });
                }
                
                // تایمر برای رفتن به استوری بعدی
                clearTimeout(storyTimer);
                storyTimer = setTimeout(() => {
                    goToStory(currentIndex + 1);
                }, DURATION);
            }

            tapPrev.addEventListener('click', () => {
                goToStory(currentIndex - 1);
            });

            tapNext.addEventListener('click', () => {
                goToStory(currentIndex + 1);
            });

            startStory();
        });
    </script>

</body>
</html>
