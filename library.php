// library.php

<?php
/*
=====================================================
    NovelWorld - User Library Page
    Version: 1.0
=====================================================
    - این صفحه کتابخانه شخصی (قفسه) کاربر لاگین کرده را نمایش می‌دهد.
    - یک مسیر محافظت شده است و در صورت عدم لاگین، کاربر را به صفحه ورود هدایت می‌کند.
    - قابلیت مرتب‌سازی پیشرفته بر اساس تاریخ افزودن و امتیاز ناول را دارد.
    - از استایل‌ها و اسکریپت‌های صفحه نتایج برای ظاهر خود استفاده مجدد می‌کند.
*/

// --- گام ۱: فراخوانی هدر و بررسی احراز هویت ---

// هدر اصلی سایت شامل اتصال دیتابیس، اطلاعات کاربر و ظاهر کلی صفحه است.
require_once 'header.php';

// اگر کاربر لاگین نکرده بود، اجازه دسترسی به این صفحه را نمی‌دهیم.
if (!$is_logged_in) {
    header("Location: login.php?redirect_url=library.php"); // کاربر را پس از لاگین به اینجا بازمی‌گردانیم
    exit();
}


// --- گام ۲: منطق مرتب‌سازی ---

// دریافت نوع مرتب‌سازی از URL، با مقدار پیش‌فرض 'newest'
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// لیست سفید (Whitelist) برای جلوگیری از تزریق کد در بخش ORDER BY
$allowed_sorts = ['newest', 'oldest', 'popular'];
if (!in_array($sort_order, $allowed_sorts)) {
    $sort_order = 'newest'; // بازگشت به مقدار پیش‌فرض در صورت دستکاری URL
}

// ساخت بخش ORDER BY کوئری SQL بر اساس انتخاب کاربر
$order_by_clause = '';
switch ($sort_order) {
    case 'oldest':
        $order_by_clause = 'ORDER BY l.added_at ASC'; // مرتب‌سازی بر اساس تاریخ افزودن (صعودی)
        break;
    case 'popular':
        $order_by_clause = 'ORDER BY n.rating DESC'; // مرتب‌سازی بر اساس امتیاز ناول (نزولی)
        break;
    case 'newest':
    default:
        $order_by_clause = 'ORDER BY l.added_at DESC'; // مرتب‌سازی بر اساس تاریخ افزودن (نزولی)
        break;
}


// --- گام ۳: واکشی ناول‌های موجود در کتابخانه کاربر ---

$library_novels = []; // آرایه‌ای برای نگهداری نتایج
try {
    // کوئری برای انتخاب تمام اطلاعات لازم از ناول‌ها با JOIN کردن جدول library_items
    $sql = "SELECT n.id, n.title, n.cover_url, n.rating, n.author
            FROM novels n
            JOIN library_items l ON n.id = l.novel_id
            WHERE l.user_id = ?
            {$order_by_clause}";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]); // $user_id از header.php می‌آید
    $library_novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("User Library Fetch Error: " . $e->getMessage());
    // در صورت بروز خطا، یک پیام مناسب به کاربر نمایش می‌دهیم.
    die("<div style='text-align:center; padding: 50px; color: white;'>خطایی در بارگذاری کتابخانه شما رخ داد. لطفاً بعداً تلاش کنید.</div>");
}

// آرایه‌ای برای نمایش نام فارسی مرتب‌سازی در دکمه منوی کشویی
$sort_options_text = [
    'newest' => 'جدیدترین‌ها',
    'oldest' => 'قدیمی‌ترین‌ها',
    'popular' => 'پرطرفدارترین‌ها'
];

// --- گام ۴: رندر کردن بخش HTML ---
?>
<title>کتابخانه من - NovelWorld</title>
<!-- استفاده مجدد از فایل CSS صفحه نتایج برای ظاهر یکپارچه -->
<link rel="stylesheet" href="results-page-style.css">

<main class="results-page-container">
    <header class="results-header">
        <h1>کتابخانه من</h1>
        <p>شما <b><?php echo count($library_novels); ?></b> اثر را در قفسه کتاب خود ذخیره کرده‌اید.</p>
    </header>

    <!-- فقط در صورتی منوی مرتب‌سازی را نمایش می‌دهیم که ناولی در کتابخانه وجود داشته باشد -->
    <?php if (!empty($library_novels)): ?>
        <div class="controls-bar">
            <div class="sort-dropdown-container">
                <button class="sort-dropdown-btn">
                    مرتب‌سازی: <?php echo $sort_options_text[$sort_order]; ?>
                </button>
                <div class="dropdown-menu">
                    <a href="?sort=newest" class="<?php echo ($sort_order == 'newest') ? 'active' : ''; ?>">جدیدترین‌ها</a>
                    <a href="?sort=oldest" class="<?php echo ($sort_order == 'oldest') ? 'active' : ''; ?>">قدیمی‌ترین‌ها</a>
                    <a href="?sort=popular" class="<?php echo ($sort_order == 'popular') ? 'active' : ''; ?>">پرطرفدارترین‌ها</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="results-grid">
        <?php if (empty($library_novels)): ?>
            <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                <h3>کتابخانه شما خالی است!</h3>
                <p style="margin-top: 10px;">با کلیک روی دکمه "افزودن به کتابخانه" در صفحه هر ناول، آن را به اینجا اضافه کنید.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px; background-color: var(--primary-color); color: var(--bg-color);">مرور ناول‌ها</a>
            </div>
        <?php else: ?>
            <?php foreach ($library_novels as $novel): ?>
                <div class="manhwa-card" style="position: relative;">
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

<!-- استفاده مجدد از اسکریپت صفحه نتایج برای فعال کردن منوی کشویی مرتب‌سازی -->
<script src="results-script.js"></script>

<?php 
// فراخوانی فوتر مشترک سایت
require_once 'footer.php'; 
?>
