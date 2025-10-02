<?php
// public_profile.php

/*
=====================================================
    NovelWorld - Public User Profile Page
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این صفحه پروفایل عمومی هر کاربر را با استایل اینستاگرام-مانند نمایش می‌دهد.
    - اطلاعات کاربر، آمارها، استوری‌ها و تب‌های محتوا را از دیتابیس واکشی می‌کند.
*/

// --- گام ۱: فراخوانی فایل هسته و دریافت اطلاعات کاربر ---
require_once 'core.php'; // برای اتصال دیتابیس و اطلاعات کاربر لاگین کرده ($user_id)

$profile_username = isset($_GET['username']) ? trim($_GET['username']) : '';
if (empty($profile_username)) {
    die("خطا: نام کاربری مشخص نشده است.");
}

try {
    // واکشی اطلاعات کاربری که پروفایلش در حال مشاهده است
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$profile_username]);
    $profile_user = $stmt->fetch();

    if (!$profile_user) {
        die("کاربری با این نام یافت نشد.");
    }
    $profile_user_id = $profile_user['id'];

    // واکشی آمار: تعداد آثار، دنبال‌کنندگان، دنبال‌شوندگان
    $works_count = $conn->query("SELECT COUNT(*) FROM novels WHERE author_id = $profile_user_id")->fetchColumn();
    $followers_count = $conn->query("SELECT COUNT(*) FROM followers WHERE following_id = $profile_user_id")->fetchColumn();
    $following_count = $conn->query("SELECT COUNT(*) FROM followers WHERE follower_id = $profile_user_id")->fetchColumn();

    // بررسی اینکه آیا کاربر فعلی، این پروفایل را دنبال می‌کند یا نه
    $is_following = false;
    if ($is_logged_in) {
        $stmt_follow = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt_follow->execute([$user_id, $profile_user_id]);
        if ($stmt_follow->fetch()) {
            $is_following = true;
        }
    }

    // واکشی استوری ناول‌ها (حداکثر ۱۰ تا)
    $stories = $conn->query("SELECT s.id as story_id, n.id as novel_id, n.cover_url FROM novel_stories s JOIN novels n ON s.novel_id = n.id WHERE s.user_id = $profile_user_id ORDER BY s.created_at DESC LIMIT 10")->fetchAll();

    // واکشی آثار (برای تب اول)
    $user_novels = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE author_id = $profile_user_id ORDER BY created_at DESC")->fetchAll();

} catch (PDOException $e) {
    die("خطا در بارگذاری اطلاعات پروفایل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پروفایل <?php echo htmlspecialchars($profile_user['username']); ?> - NovelWorld</title>
    <!-- فایل‌های CSS اصلی -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css">
    <!-- فایل CSS اختصاصی این صفحه -->
    <link rel="stylesheet" href="public_profile_style.css">
    <!-- فونت آیکون‌ها -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
    <?php require_once 'header.php'; // هدر اصلی سایت را نمایش می‌دهیم ?>

    <main class="profile-page-container">
        <header class="profile-main-header">
            <div class="header-banner" style="background-image: url('<?php echo htmlspecialchars($profile_user['header_image_url'] ?? 'default_header.jpg'); ?>');"></div>
            <div class="header-content">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($profile_user['profile_picture_url'] ?? 'default_avatar.png'); ?>" alt="پروفایل <?php echo htmlspecialchars($profile_user['username']); ?>">
                </div>
                <div class="profile-details">
                    <div class="title-and-actions">
                        <h2><?php echo htmlspecialchars($profile_user['username']); ?></h2>
                        <div class="action-buttons">
                            <?php if ($is_logged_in && $user_id == $profile_user_id): ?>
                                <a href="edit_profile.php" class="btn btn-secondary">ویرایش پروفایل</a>
                            <?php elseif ($is_logged_in): ?>
                                <button id="follow-toggle-btn" class="btn <?php echo $is_following ? 'btn-secondary' : 'btn-primary'; ?>" data-profile-id="<?php echo $profile_user_id; ?>">
                                    <?php echo $is_following ? 'لغو دنبال' : 'دنبال کردن'; ?>
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">دنبال کردن</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="profile-stats">
                        <div class="stat"><strong><?php echo $works_count; ?></strong> اثر</div>
                        <div class="stat"><strong><?php echo $followers_count; ?></strong> دنبال‌کننده</div>
                        <div class="stat"><strong><?php echo $following_count; ?></strong> دنبال‌شونده</div>
                    </div>
                    <div class="profile-bio">
                        <p><?php echo nl2br(htmlspecialchars($profile_user['bio'] ?? 'بیوگرافی ثبت نشده است.')); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- بخش استوری ناول‌ها -->
        <?php if (!empty($stories)): ?>
            <section class="story-section">
                <div class="story-carousel">
                    <?php foreach ($stories as $story): ?>
                        <a href="story_viewer.php?id=<?php echo $story['story_id']; ?>" class="story-circle">
                            <img src="<?php echo htmlspecialchars($story['cover_url']); ?>" alt="استوری">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- سیستم تب‌بندی -->
        <section class="profile-content-tabs">
            <div class="tab-links">
                <button class="tab-link active" data-tab="novels-tab"><span class="material-symbols-outlined">auto_stories</span>آثار</button>
                <button class="tab-link" data-tab="posts-tab"><span class="material-symbols-outlined">article</span>پست‌ها</button>
                <?php if (!empty($profile_user['donation_link'])): ?>
                    <a href="<?php echo htmlspecialchars($profile_user['donation_link']); ?>" target="_blank" class="tab-link donate-link"><span class="material-symbols-outlined">volunteer_activism</span>دونیت</a>
                <?php endif; ?>
            </div>
            <div class="tab-content-container">
                <!-- تب آثار -->
                <div id="novels-tab" class="tab-content active">
                    <div class="works-grid">
                        <?php if (empty($user_novels)): ?>
                            <p class="empty-tab-message">این کاربر هنوز اثری منتشر نکرده است.</p>
                        <?php else: ?>
                            <?php foreach ($user_novels as $novel): ?>
                                <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="work-card">
                                    <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>">
                                    <div class="work-card-overlay">
                                        <h3><?php echo htmlspecialchars($novel['title']); ?></h3>
                                        <span class="rating">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- تب پست‌ها -->
                <div id="posts-tab" class="tab-content">
                    <!-- محتوای پست‌ها به صورت داینامیک با AJAX در اینجا بارگذاری می‌شود -->
                    <div id="posts-container">
                        <p class="empty-tab-message">در حال بارگذاری پست‌ها...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // منطق تب‌بندی
            const tabLinks = document.querySelectorAll('.profile-content-tabs .tab-link:not(.donate-link)');
            const tabContents = document.querySelectorAll('.profile-content-tabs .tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', () => {
                    const tabId = link.dataset.tab;
                    tabLinks.forEach(item => item.classList.remove('active'));
                    tabContents.forEach(item => item.classList.remove('active'));
                    link.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // منطق AJAX برای دکمه دنبال کردن
            const followBtn = document.getElementById('follow-toggle-btn');
            if (followBtn) {
                followBtn.addEventListener('click', async () => {
                    const profileId = followBtn.dataset.profileId;
                    followBtn.disabled = true;

                    try {
                        const response = await fetch('toggle_follow.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ profile_id: profileId })
                        });
                        const data = await response.json();
                        if (data.success) {
                            if (data.action === 'followed') {
                                followBtn.textContent = 'لغو دنبال';
                                followBtn.classList.remove('btn-primary');
                                followBtn.classList.add('btn-secondary');
                            } else {
                                followBtn.textContent = 'دنبال کردن';
                                followBtn.classList.remove('btn-secondary');
                                followBtn.classList.add('btn-primary');
                            }
                            // آپدیت تعداد دنبال‌کنندگان (نیاز به querySelector دارد)
                        } else {
                            alert(data.message);
                        }
                    } catch (error) {
                        alert('خطای ارتباط با سرور.');
                    } finally {
                        followBtn.disabled = false;
                    }
                });
            }

            // بارگذاری اولیه پست‌ها
            const postsTab = document.querySelector('[data-tab="posts-tab"]');
            const postsContainer = document.getElementById('posts-container');
            let postsLoaded = false;
            
            postsTab.addEventListener('click', () => {
                if (!postsLoaded) {
                    // شما باید یک فایل load_posts.php بسازید
                    // fetch(`load_posts.php?user_id=<?php echo $profile_user_id; ?>`)
                    //     .then(res => res.text())
                    //     .then(html => {
                    //         postsContainer.innerHTML = html;
                    //         postsLoaded = true;
                    //     })
                    //     .catch(err => postsContainer.innerHTML = '<p>خطا در بارگذاری پست‌ها.</p>');
                    
                    // کد نمونه برای نمایش
                    postsContainer.innerHTML = '<p class="empty-tab-message">این کاربر هنوز پستی منتشر نکرده است.</p>';
                    postsLoaded = true;
                }
            });
        });
    </script>

    <?php require_once 'footer.php'; ?>
</body>
</html>
