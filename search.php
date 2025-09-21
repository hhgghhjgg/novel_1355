<?php
// search.php

/*
=====================================================
    NovelWorld - Search Page
    Version: 2.0 (Serverless Ready - PDO & PostgreSQL FTS)
=====================================================
    - این صفحه منطق جستجوی ساده و پیشرفته را مدیریت می‌کند.
    - از توابع Full-Text Search قدرتمند PostgreSQL برای جستجوی متنی استفاده می‌کند.
    - کوئری‌ها به صورت داینامیک بر اساس فیلترهای اعمال شده (جستجو، ژانر، امتیاز) ساخته می‌شوند.
    - تمام ورودی‌ها برای جلوگیری از SQL Injection با استفاده از PDO-style placeholders پارامتری می‌شوند.
*/

// --- گام ۱: فراخوانی فایل‌های مشترک ---
require_once 'header.php';
require_once 'db_connect.php';

// --- گام ۲: آماده‌سازی و اعتبارسنجی ورودی‌ها ---

// لیستی از ژانرهای موجود برای نمایش در فیلترها (می‌تواند از دیتابیس هم خوانده شود)
$all_genres = ["اکشن", "فانتزی", "کمدی", "ماجراجویی", "درام", "عاشقانه", "هنرهای رزمی", "تناسخ", "ایسکای"];

// دریافت پارامترها از URL و پاکسازی آن‌ها
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_genres = isset($_GET['genres']) && is_array($_GET['genres']) ? $_GET['genres'] : [];
$min_rating = isset($_GET['rating_min']) && is_numeric($_GET['rating_min']) ? floatval($_GET['rating_min']) : 0;

// --- گام ۳: ساخت داینامیک کوئری SQL ---

// آرایه‌هایی برای نگهداری بخش‌های مختلف کوئری و پارامترها
$base_sql = "SELECT id, title, cover_url, rating FROM novels WHERE 1=1";
$conditions = [];
$params = [];

// ۱. افزودن شرط جستجوی Full-Text
if (!empty($search_term)) {
    // to_tsvector ستون‌ها را به فرمت قابل جستجو تبدیل می‌کند.
    // to_tsquery عبارت جستجو را به فرمت کوئری تبدیل می‌کند.
    // @@ عملگر تطابق بین این دو است.
    $conditions[] = "to_tsvector('simple', title || ' ' || summary) @@ to_tsquery('simple', ?)";
    // عبارت جستجو را برای کوئری آماده می‌کنیم (جایگزینی فضا با '&')
    $params[] = implode('&', explode(' ', $search_term));
}

// ۲. افزودن شرط ژانرها
if (!empty($selected_genres)) {
    foreach ($selected_genres as $genre) {
        // اعتبارسنجی برای اطمینان از اینکه ژانر انتخابی در لیست مجاز است
        if (in_array($genre, $all_genres)) {
            // استفاده از LIKE برای جستجوی ژانر در رشته genres
            $conditions[] = "',' || genres || ',' LIKE ?";
            $params[] = '%,'. $genre .',%';
        }
    }
}

// ۳. افزودن شرط حداقل امتیاز
if ($min_rating > 0) {
    $conditions[] = "rating >= ?";
    $params[] = $min_rating;
}

// --- گام ۴: اجرای کوئری و واکشی نتایج ---

$search_results = [];
// فقط در صورتی کوئری را اجرا می‌کنیم که حداقل یک فیلتر اعمال شده باشد.
if (!empty($conditions)) {
    // ترکیب تمام شرط‌ها با 'AND'
    $sql = $base_sql . ' AND ' . implode(' AND ', $conditions);
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search Page Fetch Error: " . $e->getMessage());
        // در صورت خطا، آرایه نتایج خالی باقی می‌ماند.
    }
}
?>

<!-- --- گام ۵: رندر کردن بخش HTML --- -->
<title>جستجو - NovelWorld</title>
<link rel="stylesheet" href="search-style.css">

<div class="search-page-container">
    <main class="results-content">
        <div class="search-header">
            <form action="search.php" method="GET" class="results-search-bar">
                <input type="search" name="q" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="جستجوی عنوان یا خلاصه..." autofocus>
                <button type="submit">🔍</button>
            </form>
            <button id="open-filters-btn" class="advanced-filter-btn" title="فیلترهای پیشرفته">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            </button>
        </div>

        <div class="results-grid">
            <?php if (!empty($search_results)): ?>
                <?php foreach ($search_results as $novel): ?>
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
            <?php elseif (!empty($conditions)): // اگر فیلتری اعمال شده بود اما نتیجه‌ای نداشت ?>
                <div class="no-results">
                    <h3>هیچ نتیجه‌ای با فیلترهای انتخابی شما یافت نشد.</h3>
                    <p>لطفاً فیلترها را تغییر دهید یا عبارت دیگری را امتحان کنید.</p>
                </div>
            <?php else: // اگر هیچ فیلتری اعمال نشده بود ?>
                 <div class="no-results">
                    <h3>برای شروع، عبارت مورد نظر خود را جستجو کرده یا از فیلترهای پیشرفته استفاده کنید.</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- مودال فیلترها (بدون تغییر) -->
<div id="filters-modal" class="modal-overlay">
    <div class="modal-content">
        <button id="close-modal-btn" class="close-modal-btn">&times;</button>
        <aside class="filters-panel">
            <h4>فیلترهای پیشرفته</h4>
            <form action="search.php" method="GET">
                <!-- ارسال مجدد عبارت جستجو شده در یک فیلد مخفی -->
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_term); ?>">
                
                <div class="filter-group">
                    <label>حداقل امتیاز: <strong id="rating_value"><?php echo $min_rating; ?></strong></label>
                    <div class="rating-slider">
                        <input type="range" min="0" max="10" value="<?php echo htmlspecialchars($min_rating); ?>" step="0.1" name="rating_min" oninput="document.getElementById('rating_value').textContent = this.value">
                    </div>
                </div>

                <div class="filter-group">
                    <label>ژانرها:</label>
                    <div class="genre-tags">
                        <?php foreach ($all_genres as $genre): ?>
                            <div class="genre-tag">
                                <?php $checked = in_array($genre, $selected_genres) ? 'checked' : ''; ?>
                                <input type="checkbox" id="genre-<?php echo urlencode($genre); ?>" name="genres[]" value="<?php echo htmlspecialchars($genre); ?>" <?php echo $checked; ?>>
                                <label for="genre-<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn-filter">اعمال فیلترها</button>
            </form>
        </aside>
    </div>
</div>

<script src="search-script.js"></script>

<?php 
// فراخوانی فوتر مشترک سایت
require_once 'footer.php'; 
?>
