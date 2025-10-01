<?php
/*
=====================================================
    NovelWorld - Chapter Reader Page (Final, Unabridged, with Paywall)
    Version: 2.4
=====================================================
*/

// --- گام ۱: فراخوانی فایل هسته برای اتصال و احراز هویت ---
require_once 'core.php';

// --- گام ۲: دریافت ID و بررسی حالت پیش‌نمایش ---
$chapter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($chapter_id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    die("خطا: شناسه چپتر نامعتبر است.");
}

$is_admin = false;
if ($is_logged_in) {
    try {
        $stmt_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt_role->execute([$user_id]);
        $user_role = $stmt_role->fetchColumn();
        if ($user_role === 'admin') {
            $is_admin = true;
        }
    } catch (PDOException $e) { /* خطا در بررسی نقش */ }
}
$is_preview_mode = (isset($_GET['preview']) && $_GET['preview'] === 'true' && $is_admin);

// --- گام ۳: واکشی اطلاعات چپتر و ناول ---
try {
    // واکشی اطلاعات چپتر به همراه نوع و عنوان ناول والد
    $stmt = $conn->prepare(
        "SELECT c.*, n.title as novel_title, n.type as novel_type, n.origin as novel_origin
         FROM chapters c 
         JOIN novels n ON c.novel_id = n.id 
         WHERE c.id = ?"
    );
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        header('HTTP/1.0 404 Not Found');
        die("چپتر مورد نظر یافت نشد.");
    }

    // واکشی چپتر قبلی و بعدی
    $stmt_prev = $conn->prepare("SELECT id FROM chapters WHERE novel_id = ? AND chapter_number < ? AND status = 'approved' AND published_at <= NOW() ORDER BY chapter_number DESC LIMIT 1");
    $stmt_prev->execute([$chapter['novel_id'], $chapter['chapter_number']]);
    $prev_chapter = $stmt_prev->fetch();
    $stmt_next = $conn->prepare("SELECT id FROM chapters WHERE novel_id = ? AND chapter_number > ? AND status = 'approved' AND published_at <= NOW() ORDER BY chapter_number ASC LIMIT 1");
    $stmt_next->execute([$chapter['novel_id'], $chapter['chapter_number']]);
    $next_chapter = $stmt_next->fetch();

} catch (PDOException $e) {
    error_log("Reader Page DB Error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    die("خطای دیتابیس.");
}

// --- گام ۴: منطق بررسی دسترسی (Access Logic) ---
$has_access = false;
$is_locked = ($chapter['mana_price'] !== null && $chapter['mana_price'] > 0);

// چه کسانی دسترسی دارند؟
// ۱. ادمین‌ها همیشه دسترسی دارند.
// ۲. نویسنده اثر همیشه به اثر خودش دسترسی دارد.
// ۳. اگر چپتر قفل نباشد، همه دسترسی دارند.
$author_id_of_work = $conn->query("SELECT author_id FROM novels WHERE id = {$chapter['novel_id']}")->fetchColumn();
if ($is_admin || ($is_logged_in && $user_id == $author_id_of_work) || !$is_locked) {
    $has_access = true;
}

// ۴. اگر کاربر عادی است و چپتر قفل است، بررسی می‌کنیم که آیا آن را خریده است یا نه.
if (!$has_access && $is_logged_in) {
    $stmt_check_purchase = $conn->prepare("SELECT id FROM purchased_chapters WHERE user_id = ? AND chapter_id = ?");
    $stmt_check_purchase->execute([$user_id, $chapter_id]);
    if ($stmt_check_purchase->fetch()) {
        $has_access = true;
    }
}

// ۵. بررسی نهایی وضعیت و تاریخ انتشار (برای کاربران غیر ادمین)
if (!$is_preview_mode && ($chapter['status'] !== 'approved' || strtotime($chapter['published_at']) > time())) {
    if (!$is_admin && !($is_logged_in && $user_id == $author_id_of_work)) {
        die("این چپتر هنوز منتشر نشده یا در انتظار تایید است.");
    }
}

$is_text_based = ($chapter['novel_type'] === 'novel');
$image_urls = [];
if (!$is_text_based && $has_access) { // فقط در صورت دسترسی، URL ها را پردازش کن
    $decoded_content = json_decode($chapter['content'], true);
    if (is_array($decoded_content)) {
        $image_urls = $decoded_content;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($is_preview_mode ? '[پیش‌نمایش] ' : '') . htmlspecialchars($chapter['novel_title']) . ' - ' . htmlspecialchars($chapter['title']); ?></title>
    <link rel="stylesheet" href="reader-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&family=Lalezar&family=Amiri:wght@400;700&family=Markazi+Text:wght@400;500;700&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
</head>
<body class="theme-dark font-vazirmatn" data-chapter-id="<?php echo $chapter['id']; ?>">

    <?php if ($is_preview_mode): ?>
        <div style="background-color: #ffa000; color: black; text-align: center; padding: 5px; font-weight: bold; position: sticky; top: 0; z-index: 10000;">
            حالت پیش‌نمایش مدیر - <a href="admin/approve_chapters.php" style="color: black; text-decoration: underline;">بازگشت به پنل تایید</a>
        </div>
    <?php endif; ?>

    <header class="reader-bar top-bar">
        <a href="novel_detail.php?id=<?php echo $chapter['novel_id']; ?>" class="bar-btn back-btn" title="بازگشت به صفحه ناول">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </a>
        <div class="chapter-info">
            <h3><?php echo htmlspecialchars($chapter['novel_title']); ?></h3>
            <span>چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?>: <?php echo htmlspecialchars($chapter['title']); ?></span>
        </div>
        <button id="settings-btn" class="bar-btn settings-btn" title="تنظیمات نمایش">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        </button>
    </header>

    <?php if ($has_access && !empty($chapter['chapter_cover'])): ?>
        <section class="chapter-hero-section" style="background-image: url('<?php echo htmlspecialchars($chapter['chapter_cover']); ?>');">
            <div class="chapter-hero-content">
                <p class="chapter-hero-number">چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?></p>
                <h1 class="chapter-hero-title"><?php echo htmlspecialchars($chapter['title']); ?></h1>
            </div>
        </section>
    <?php endif; ?>

    <main id="reader-container" class="reader-container <?php echo $is_text_based ? '' : 'image-based-reader'; ?>">
        <?php if ($has_access): ?>
            <?php if ($is_text_based): ?>
                <div id="reader-content" class="reader-content font-size-medium">
                    <?php echo $chapter['content']; ?>
                </div>
            <?php else: ?>
                <div id="image-reader-content" class="image-reader-content">
                    <?php if (empty($image_urls)): ?>
                        <p style="color: var(--reader-meta); text-align: center;">هیچ تصویری برای این چپتر یافت نشد.</p>
                    <?php else: ?>
                        <?php foreach ($image_urls as $url): ?>
                            <img data-src="<?php echo htmlspecialchars($url); ?>" alt="صفحه چپتر" class="lazy-load">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="paywall">
                <span class="material-symbols-outlined paywall-icon">lock</span>
                <h2>این چپتر قفل است</h2>
                <p>برای دسترسی فوری به این چپتر، شما به <b><?php echo htmlspecialchars($chapter['mana_price']); ?> مانا</b> نیاز دارید.</p>
                <?php if ($is_logged_in): 
                    $user_mana_balance = $conn->query("SELECT mana_balance FROM users WHERE id = $user_id")->fetchColumn();
                ?>
                    <p>موجودی فعلی شما: <b><?php echo htmlspecialchars($user_mana_balance); ?> مانا</b></p>
                    <?php if ($user_mana_balance >= $chapter['mana_price']): ?>
                        <button id="purchase-btn" data-chapter-id="<?php echo $chapter['id']; ?>" class="btn btn-primary">
                            خرید با <?php echo htmlspecialchars($chapter['mana_price']); ?> مانا و خواندن
                        </button>
                    <?php else: ?>
                        <p style="color: var(--danger-color);">موجودی مانای شما کافی نیست.</p>
                        <a href="mana_shop.php" class="btn btn-secondary">کسب مانای بیشتر</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php?redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                        برای خرید ابتدا وارد شوید
                    </a>
                <?php endif; ?>
                <p class="unlock-info">این چپتر پس از مدتی به صورت خودکار رایگان خواهد شد.</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="reader-bar bottom-bar">
        <a href="<?php echo $prev_chapter ? 'read_chapter.php?id='.$prev_chapter['id'] : '#'; ?>" class="nav-btn <?php echo !$prev_chapter ? 'disabled' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>قبلی</span>
        </a>
        <div class="progress-container"><div id="progress-bar" class="progress-bar"></div></div>
        <a href="<?php echo $next_chapter ? 'read_chapter.php?id='.$next_chapter['id'] : '#'; ?>" class="nav-btn <?php echo !$next_chapter ? 'disabled' : ''; ?>">
            <span>بعدی</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </footer>

    <div id="settings-panel" class="settings-panel">
        <div class="settings-header">
            <h4>تنظیمات نمایش</h4>
            <button id="close-settings-btn" class="bar-btn close-btn">&times;</button>
        </div>
        <div class="settings-content">
            <div class="setting-group">
                <label>اندازه فونت</label>
                <div class="font-size-controls">
                    <button data-action="decrease-font" class="setting-btn">کوچک</button>
                    <span>Aa</span>
                    <button data-action="increase-font" class="setting-btn">بزرگ</button>
                </div>
            </div>
            <div class="setting-group">
                <label>فونت متن</label>
                <select id="font-select" class="setting-select">
                    <option value="font-vazirmatn" selected>وزیرمتن</option>
                    <option value="font-amiri">امیری</option>
                    <option value="font-markazi">مرکزی</option>
                    <option value="font-lalezar">لاله‌زار</option>
                    <option value="font-scheherazade">شهرزاد</option>
                </select>
            </div>
            <div class="setting-group">
                <label>قالب نمایش</label>
                <div class="theme-controls">
                    <button class="theme-swatch active" data-theme="theme-dark" style="background: #1a1a20;" title="تاریک"></button>
                    <button class="theme-swatch" data-theme="theme-light" style="background: #f0f0f0;" title="روشن"></button>
                    <button class="theme-swatch" data-theme="theme-sepia" style="background: #fbf0d9;" title="سپیا"></button>
                    <button class="theme-swatch" data-theme="theme-gray" style="background: #464E59;" title="خاکستری"></button>
                    <button class="theme-swatch" data-theme="theme-hacker" style="background: #001a00;" title="هکری"></button>
                </div>
            </div>
        </div>
    </div>
    <div id="settings-overlay" class="overlay"></div>

    <section class="chapter-comments-section">
        <div class="comments-wrapper">
            <h2>نظرات این چپتر</h2>
            <div id="comments-container"><p>در حال بارگذاری نظرات...</p></div>
        </div>
    </section>

    <script>
        const USER_IS_LOGGED_IN = <?php echo json_encode($is_logged_in); ?>;
        const CURRENT_USER_ID = <?php echo json_encode($user_id); ?>;
        const CURRENT_USERNAME = <?php echo json_encode($username); ?>;
        const IS_ADMIN = <?php echo json_encode($is_admin); ?>;
        const NOVEL_ORIGIN = <?php echo json_encode($chapter['novel_origin']); ?>;
    </script>
    <script src="reader-script.js"></script>
</body>
</html>
