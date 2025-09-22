<?php
// search.php

/*
=====================================================
    NovelWorld - Search Page
    Version: 2.1 (Multi-Type Filter Ready)
=====================================================
    - این صفحه منطق جستجوی ساده و پیشرفته را با فیلتر نوع اثر مدیریت می‌کند.
    - از Full-Text Search در PostgreSQL و PDO برای جستجوی امن و کارآمد استفاده می‌کند.
*/

// --- گام ۱: فراخوانی فایل‌های مشترک ---
require_once 'header.php'; // شامل اتصال دیتابیس و اطلاعات کاربر

// --- گام ۲: آماده‌سازی و اعتبارسنجی ورودی‌ها ---

// لیستی از ژانرهای موجود برای نمایش در فیلترها
$all_genres = ["اکشن", "فانتزی", "کمدی", "ماجراجویی", "درام", "عاشقانه", "هنرهای رزمی", "تناسخ", "ایسکای"];
$all_types = ['novel', 'manhwa', 'manga']; // لیست سفید برای نوع اثر

// دریافت پارامترها از URL و پاکسازی آن‌ها
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_type = isset($_GET['type']) && in_array($_GET['type'], $all_types) ? $_GET['type'] : '';
$selected_genres = isset($_GET['genres']) && is_array($_GET['genres']) ? $_GET['genres'] : [];
$min_rating = isset($_GET['rating_min']) && is_numeric($_GET['rating_min']) ? floatval($_GET['rating_min']) : 0;

// --- گام ۳: ساخت داینامیک کوئری SQL ---

$base_sql = "SELECT id, title, cover_url, rating, author, type FROM novels WHERE 1=1";
$conditions = [];
$params = [];

// ۱. افزودن شرط جستجوی Full-Text
if (!empty($search_term)) {
    $conditions[] = "to_tsvector('simple', title || ' ' || summary) @@ to_tsquery('simple', ?)";
    $params[] = implode('&', explode(' ', $search_term));
}

// ۲. افزودن شرط نوع اثر
if (!empty($selected_type)) {
    $conditions[] = "type = ?";
    $params[] = $selected_type;
}

// ۳. افزودن شرط ژانرها
if (!empty($selected_genres)) {
    foreach ($selected_genres as $genre) {
        if (in_array($genre, $all_genres)) {
            $conditions[] = "',' || genres || ',' LIKE ?";
            $params[] = '%,'. $genre .',%';
        }
    }
}

// ۴. افزودن شرط حداقل امتیاز
if ($min_rating > 0) {
    $conditions[] = "rating >= ?";
    $params[] = $min_rating;
}

// --- گام ۴: اجرای کوئری و واکشی نتایج ---
$search_results = [];
if (!empty($conditions)) {
    $sql = $base_sql . ' AND ' . implode(' AND ', $conditions);
    $sql .= " ORDER BY created_at DESC"; // مرتب‌سازی پیش‌فرض
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search Page Fetch Error: " . $e->getMessage());
    }
}

$type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];
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
                <svg ...></svg>
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
                             <!-- (اختیاری) نمایش یک برچسب برای نوع اثر -->
                             <span class="type-badge" style="position: absolute; top: 10px; left: 10px; background: var(--primary-color); color: var(--bg-color); padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: bold;"><?php echo $type_persian[$novel['type']]; ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!empty($conditions)): ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    <h3>هیچ نتیجه‌ای با فیلترهای انتخابی شما یافت نشد.</h3>
                    <p>لطفاً فیلترها را تغییر دهید یا عبارت دیگری را امتحان کنید.</p>
                </div>
            <?php else: ?>
                 <div class="no-results" style="grid-column: 1 / -1;">
                    <h3>برای شروع، عبارت مورد نظر خود را جستجو کرده یا از فیلترهای پیشرفته استفاده کنید.</h3>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- مودال فیلترها (به‌روز شده) -->
<div id="filters-modal" class="modal-overlay">
    <div class="modal-content">
        <button id="close-modal-btn" class="close-modal-btn">&times;</button>
        <aside class="filters-panel">
            <h4>فیلترهای پیشرفته</h4>
            <form action="search.php" method="GET">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_term); ?>">
                
                <!-- فیلتر جدید نوع اثر -->
                <div class="filter-group">
                    <label>نوع اثر:</label>
                    <div class="genre-tags" style="justify-content: space-around;">
                        <div class="genre-tag">
                            <input type="radio" id="type-any" name="type" value="" <?php echo ($selected_type == '') ? 'checked' : ''; ?>>
                            <label for="type-any">همه</label>
                        </div>
                        <div class="genre-tag">
                            <input type="radio" id="type-novel" name="type" value="novel" <?php echo ($selected_type == 'novel') ? 'checked' : ''; ?>>
                            <label for="type-novel">ناول</label>
                        </div>
                        <div class="genre-tag">
                            <input type="radio" id="type-manhwa" name="type" value="manhwa" <?php echo ($selected_type == 'manhwa') ? 'checked' : ''; ?>>
                            <label for="type-manhwa">مانهوا</label>
                        </div>
                        <div class="genre-tag">
                            <input type="radio" id="type-manga" name="type" value="manga" <?php echo ($selected_type == 'manga') ? 'checked' : ''; ?>>
                            <label for="type-manga">مانگا</label>
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <label>حداقل امتیاز: <strong id="rating_value"><?php echo $min_rating; ?></strong></label>
                    <input type="range" min="0" max="10" value="<?php echo htmlspecialchars($min_rating); ?>" step="0.1" name="rating_min" oninput="document.getElementById('rating_value').textContent = this.value" style="width: 100%;">
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
require_once 'footer.php'; 
?>
