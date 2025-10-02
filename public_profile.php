<?php
// public_profile.php

/*
=====================================================
    NovelWorld - Public User Profile Page (Final, Unabridged)
    Version: 1.1
=====================================================
    - این صفحه پروفایل عمومی کاربران را با تمام قابلیت‌های اجتماعی نمایش می‌دهد.
*/

require_once 'core.php';

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

    // واکشی آمار
    $works_count = $conn->query("SELECT COUNT(*) FROM novels WHERE author_id = $profile_user_id")->fetchColumn();
    $followers_count = $conn->query("SELECT COUNT(*) FROM followers WHERE following_id = $profile_user_id")->fetchColumn();
    $following_count = $conn->query("SELECT COUNT(*) FROM followers WHERE follower_id = $profile_user_id")->fetchColumn();

    // بررسی دنبال کردن
    $is_following = false;
    if ($is_logged_in) {
        $stmt_follow = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt_follow->execute([$user_id, $profile_user_id]);
        if ($stmt_follow->fetch()) {
            $is_following = true;
        }
    }

    // واکشی استوری‌ها
    $stories = $conn->query("SELECT s.id as story_id, n.id as novel_id, n.cover_url FROM novel_stories s JOIN novels n ON s.novel_id = n.id WHERE s.user_id = $profile_user_id AND s.expires_at > NOW() ORDER BY s.created_at DESC LIMIT 10")->fetchAll();

    // واکشی آثار کاربر
    $user_novels = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE author_id = $profile_user_id ORDER BY created_at DESC")->fetchAll();

    // واکشی ناول‌های کاربر برای مودال استوری (فقط اگر خودش باشد)
    $user_novels_for_story = [];
    if ($is_logged_in && $user_id == $profile_user_id) {
        $stmt_story_novels = $conn->prepare("SELECT id, title FROM novels WHERE author_id = ? ORDER BY title ASC");
        $stmt_story_novels->execute([$user_id]);
        $user_novels_for_story = $stmt_story_novels->fetchAll();
    }

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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css">
    <link rel="stylesheet" href="public_profile_style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
    <?php require_once 'header.php'; ?>

    <main class="profile-page-container">
        <header class="profile-main-header">
            <div class="header-banner" style="background-image: url('<?php echo htmlspecialchars($profile_user['header_image_url'] ?? 'assets/default_header.jpg'); ?>');"></div>
            <div class="header-content">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($profile_user['profile_picture_url'] ?? 'assets/default_avatar.png'); ?>" alt="پروفایل <?php echo htmlspecialchars($profile_user['username']); ?>">
                </div>
                <div class="profile-details">
                    <div class="title-and-actions">
                        <h2><?php echo htmlspecialchars($profile_user['username']); ?></h2>
                        <div class="action-buttons">
                            <?php if ($is_logged_in && $user_id == $profile_user_id): ?>
                                <div class="profile-actions-menu">
                                    <button id="profile-actions-toggle" class="btn btn-secondary">
                                        <span class="material-symbols-outlined">more_horiz</span>
                                    </button>
                                    <div id="profile-actions-dropdown" class="profile-actions-dropdown">
                                        <a href="#" id="create-story-link"><span class="material-symbols-outlined">add_circle</span> استوری ناول جدید</a>
                                        <a href="edit_profile.php"><span class="material-symbols-outlined">edit</span> ویرایش پروفایل</a>
                                        <a href="library.php"><span class="material-symbols-outlined">collections_bookmark</span> کتابخانه من</a>
                                    </div>
                                </div>
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

        <section class="profile-content-tabs">
            <div class="tab-links">
                <button class="tab-link active" data-tab="novels-tab"><span class="material-symbols-outlined">auto_stories</span>آثار</button>
                <button class="tab-link" data-tab="posts-tab"><span class="material-symbols-outlined">article</span>پست‌ها</button>
                <?php if (!empty($profile_user['donation_link'])): ?>
                    <a href="<?php echo htmlspecialchars($profile_user['donation_link']); ?>" target="_blank" rel="noopener noreferrer" class="tab-link donate-link"><span class="material-symbols-outlined">volunteer_activism</span>حمایت مالی</a>
                <?php endif; ?>
            </div>
            <div class="tab-content-container">
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
                <div id="posts-tab" class="tab-content">
                    <?php if ($is_logged_in && $user_id == $profile_user_id): ?>
                        <div class="create-post-box">
                            <h3>یک پست جدید ایجاد کنید</h3>
                            <form id="create-post-form">
                                <textarea name="content" placeholder="به چه چیزی فکر می‌کنید، <?php echo $username; ?>؟" rows="4" required></textarea>
                                <div id="post-image-preview" class="image-preview-container"></div>
                                <div class="form-footer">
                                    <label for="post-image-input" class="image-upload-label">
                                        <span class="material-symbols-outlined">add_photo_alternate</span> افزودن تصویر
                                    </label>
                                    <input type="file" id="post-image-input" name="post_image" accept="image/*" style="display:none;">
                                    <button type="submit" class="btn btn-primary">انتشار پست</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div id="posts-container"><p class="empty-tab-message">برای مشاهده پست‌ها، روی تب کلیک کنید.</p></div>
                </div>
            </div>
        </section>
    </main>

    <?php if ($is_logged_in && $user_id == $profile_user_id): ?>
        <div id="create-story-modal" class="action-modal-overlay">
            <div class="action-modal-content">
                <div class="modal-header">
                    <h3>افزودن ناول به استوری</h3>
                    <button class="close-modal-btn">&times;</button>
                </div>
                <form id="create-story-form">
                    <?php if (empty($user_novels_for_story)): ?>
                        <p style="text-align: center; color: var(--text-secondary-color); padding: 20px;">شما ابتدا باید یک اثر منتشر کنید.</p>
                    <?php else: ?>
                        <div class="form-group">
                            <label for="story-novel-select">کدام اثر را می‌خواهید پروموت کنید؟</label>
                            <select id="story-novel-select" name="novel_id" required>
                                <option value="" disabled selected>یک اثر را انتخاب کنید...</option>
                                <?php foreach ($user_novels_for_story as $novel): ?>
                                    <option value="<?php echo $novel['id']; ?>"><?php echo htmlspecialchars($novel['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="story-title-input">عنوان استوری (اختیاری)</label>
                            <input type="text" id="story-title-input" name="title" placeholder="مثلاً: چپتر جدید در راه است!">
                        </div>
                        <div class="modal-form-footer">
                            <button type="submit" class="btn btn-primary">افزودن به استوری (۲۴ ساعت)</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="public_profile_script.js"></script>

    <?php require_once 'footer.php'; ?>
</body>
</html>
