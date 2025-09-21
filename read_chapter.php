<?php
// read_chapter.php

/*
=====================================================
    NovelWorld - Chapter Reader Page
    Version: 1.0
=====================================================
    - این صفحه محتوای یک چپتر را برای خواندن نمایش می‌دهد.
    - یک تجربه تمام‌صفحه و بدون هدر و فوتر اصلی سایت فراهم می‌کند.
    - اطلاعات چپتر، ناول و ناوبری (چپتر قبلی/بعدی) را از دیتابیس واکشی می‌کند.
*/

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
require_once 'db_connect.php';

// --- گام ۲: واکشی اطلاعات چپتر ---
$chapter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($chapter_id <= 0) {
    // اگر شناسه نامعتبر بود، یک صفحه خطای ساده نمایش می‌دهیم.
    die("خطای ۴۰۴: شناسه چپتر نامعتبر است.");
}

try {
    // ۱. واکشی اطلاعات اصلی چپتر و ناول مربوطه با یک JOIN
    $stmt = $conn->prepare(
        "SELECT c.id, c.novel_id, c.chapter_number, c.title, c.content, 
                n.title as novel_title
         FROM chapters c 
         JOIN novels n ON c.novel_id = n.id 
         WHERE c.id = ?"
    );
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        die("خطای ۴۰۴: چپتر مورد نظر یافت نشد.");
    }

    // ۲. واکشی شناسه چپتر قبلی (چپتری با شماره کمتر)
    $stmt_prev = $conn->prepare(
        "SELECT id FROM chapters 
         WHERE novel_id = ? AND chapter_number < ? 
         ORDER BY chapter_number DESC LIMIT 1"
    );
    $stmt_prev->execute([$chapter['novel_id'], $chapter['chapter_number']]);
    $prev_chapter = $stmt_prev->fetch();

    // ۳. واکشی شناسه چپتر بعدی (چپتری با شماره بیشتر)
    $stmt_next = $conn->prepare(
        "SELECT id FROM chapters 
         WHERE novel_id = ? AND chapter_number > ? 
         ORDER BY chapter_number ASC LIMIT 1"
    );
    $stmt_next->execute([$chapter['novel_id'], $chapter['chapter_number']]);
    $next_chapter = $stmt_next->fetch();

} catch (PDOException $e) {
    error_log("Reader Page DB Error: " . $e->getMessage());
    die("خطایی در ارتباط با دیتابیس رخ داد. لطفاً بعداً تلاش کنید.");
}


// --- گام ۳: رندر کردن HTML صفحه ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter['novel_title']) . ' - ' . htmlspecialchars($chapter['title']); ?></title>
    
    <!-- لینک به فایل CSS اختصاصی صفحه خواندن -->
    <link rel="stylesheet" href="reader-style.css">
    
    <!-- فراخوانی فونت‌های مورد نیاز برای منوی تنظیمات -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&family=Lalezar&family=Amiri:wght@400;700&family=Markazi+Text:wght@400;500;700&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="theme-dark font-vazirmatn" data-chapter-id="<?php echo $chapter['id']; ?>">

    <!-- نوار بالایی (در حالت عادی مخفی) -->
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

    <!-- محتوای اصلی چپتر -->
    <main id="reader-container" class="reader-container">
        <div id="reader-content" class="reader-content font-size-medium">
            <?php echo $chapter['content']; // محتوای HTML از TinyMCE بدون escaping نمایش داده می‌شود ?>
        </div>
    </main>

    <!-- نوار پایینی (در حالت عادی مخفی) -->
    <footer class="reader-bar bottom-bar">
        <a href="<?php echo $prev_chapter ? 'read_chapter.php?id='.$prev_chapter['id'] : '#'; ?>" class="nav-btn <?php echo !$prev_chapter ? 'disabled' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>قبلی</span>
        </a>
        <div class="progress-container">
            <div id="progress-bar" class="progress-bar"></div>
        </div>
        <a href="<?php echo $next_chapter ? 'read_chapter.php?id='.$next_chapter['id'] : '#'; ?>" class="nav-btn <?php echo !$next_chapter ? 'disabled' : ''; ?>">
            <span>بعدی</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </footer>

    <!-- منوی تنظیمات (در حالت عادی مخفی) -->
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
                    <option value="font-vazirmatn" selected>وزیرمتن (پیش‌فرض)</option>
                    <option value="font-amiri">امیری (رسمی)</option>
                    <option value="font-markazi">مرکزی (خوانا)</option>
                    <option value="font-lalezar">لاله‌زار (عنوانی)</option>
                    <option value="font-scheherazade">شهرزاد (کلاسیک)</option>
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

    <!-- بخش جدید نظرات برای چپتر -->
    <section class="chapter-comments-section">
        <div class="comments-wrapper">
            <h2>نظرات این چپتر</h2>
            <!-- نظرات به صورت داینامیک توسط جاوااسکریپت در اینجا بارگذاری می‌شوند -->
            <div id="comments-container">
                <!-- این بخش توسط reader-script.js پر خواهد شد -->
                <p>در حال بارگذاری نظرات...</p>
            </div>
        </div>
    </section>

    <script src="reader-script.js"></script>
</body>
</html>
