// genre_results.php

<?php
/*
=====================================================
    NovelWorld - Genre Results Page
    Version: 2.0 (Serverless Ready - PDO)
=====================================================
    - این صفحه لیستی از ناول‌ها را بر اساس یک ژانر خاص نمایش می‌دهد.
    - ژانر و نوع مرتب‌سازی از پارامترهای URL خوانده می‌شوند.
    - از PDO برای اجرای امن کوئری‌ها در دیتابیس PostgreSQL (Neon) استفاده می‌کند.
*/

// --- گام ۱: فراخوانی فایل‌های مشترک ---
require_once 'header.php';
require_once 'db_connect.php';

// --- گام ۲: دریافت و اعتبارسنجی پارامترهای URL ---

// دریافت ژانر و پاکسازی آن
if (!isset($_GET['genre']) || empty(trim($_GET['genre']))) {
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا: ژانری برای نمایش انتخاب نشده است.</div>");
}
$genre = trim($_GET['genre']);

// دریافت نوع مرتب‌سازی و تعیین مقدار پیش‌فرض
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
// لیست سفید (Whitelist) برای جلوگیری از مقادیر نامعتبر در بخش ORDER BY
$allowed_sort_orders = ['newest', 'popular', 'oldest'];
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = 'newest'; // بازگشت به مقدار پیش‌فرض در صورت دستکاری URL
}

// --- گام ۳: ساخت کوئری SQL به صورت داینامیک بر اساس نوع مرتب‌سازی ---

// بخش اصلی کوئری: جستجوی ژانر در ستون genres
// از عملگر LIKE برای پیدا کردن ژانر در رشته‌ای که با کاما جدا شده استفاده می‌کنیم.
// %...% تضمین می‌کند که ژانر در هر جای رشته پیدا شود.
// || عملگر الحاق رشته در استاندارد SQL و PostgreSQL است.
$sql = "SELECT id, title, cover_url, rating FROM novels WHERE ',' || genres || ',' LIKE ?";

// اضافه کردن بخش ORDER BY بر اساس نوع مرتب‌سازی انتخاب شده
switch ($sort_order) {
    case 'popular':
        $sql .= " ORDER BY rating DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$novels = [];
try {
    // --- گام ۴: اجرای امن کوئری و واکشی نتایج ---
    $stmt = $conn->prepare($sql);
    
    // پارامتر را به صورت '%,genre_name,%' آماده می‌کنیم
    $search_param = '%,'. $genre .',%';
    $stmt->execute([$search_param]);
    
    $novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // در صورت بروز خطا، آن را لاگ کرده و پیام عمومی نمایش می‌دهیم.
    error_log("Genre Results Fetch Error: " . $e->getMessage());
    // در این حالت، آرایه $novels خالی می‌ماند و بخش HTML پیام مناسب را نمایش می‌دهد.
}


// --- گام ۵: آماده‌سازی داده‌ها برای نمایش در HTML ---
// آرایه‌ای برای نمایش نام فارسی مرتب‌سازی در دکمه
$sort_options_text = [
    'newest' => 'جدیدترین',
    'popular' => 'پرطرفدارترین',
    'oldest' => 'قدیمی‌ترین'
];
?>

<!-- --- گام ۶: رندر کردن بخش HTML --- -->
<title>ناول‌های ژانر <?php echo htmlspecialchars($genre); ?></title>
<link rel="stylesheet" href="results-page-style.css">

<main class="results-page-container">
    <header class="results-header">
        <h1>ژانر: <?php echo htmlspecialchars($genre); ?></h1>
        <p>ناول‌های این دسته‌بندی را بر اساس سلیقه خود مرتب کنید.</p>
    </header>

    <div class="controls-bar">
        <div class="sort-dropdown-container">
            <button class="sort-dropdown-btn">
                مرتب‌سازی: <?php echo $sort_options_text[$sort_order]; ?>
            </button>
            <div class="dropdown-menu">
                <a href="?genre=<?php echo urlencode($genre); ?>&sort=newest" class="<?php echo ($sort_order == 'newest') ? 'active' : ''; ?>">جدیدترین</a>
                <a href="?genre=<?php echo urlencode($genre); ?>&sort=popular" class="<?php echo ($sort_order == 'popular') ? 'active' : ''; ?>">پرطرفدارترین</a>
                <a href="?genre=<?php echo urlencode($genre); ?>&sort=oldest" class="<?php echo ($sort_order == 'oldest') ? 'active' : ''; ?>">قدیمی‌ترین</a>
            </div>
        </div>
    </div>

    <div class="results-grid">
        <?php if (empty($novels)): ?>
            <div class="no-results">
                <h3>هنوز ناولی در ژانر "<?php echo htmlspecialchars($genre); ?>" منتشر نشده است.</h3>
            </div>
        <?php else: ?>
            <?php foreach ($novels as $novel): ?>
                <div class="manhwa-card" style="position: relative;"> <!-- افزودن position: relative -->
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                        <div class="card-overlay">
                            <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                            <span class="card-rating">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script src="results-script.js"></script>

<?php 
// فراخوانی فوتر مشترک
require_once 'footer.php'; 
?>
