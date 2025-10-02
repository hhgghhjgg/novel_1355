<?php
// search.php

/*
=====================================================
    NovelWorld - Search Page (Final, Unabridged)
    Version: 2.2 (with User Search)
=====================================================
    - این صفحه منطق جستجوی جامع برای آثار و کاربران را مدیریت می‌کند.
    - نتایج را در دو بخش مجزای "آثار" و "کاربران" نمایش می‌دهد.
    - شامل مودال فیلترهای پیشرفته برای جستجوی آثار است.
*/

// --- گام ۱: فراخوانی فایل هسته ---
require_once 'core.php';

// --- گام ۲: آماده‌سازی و اعتبارسنجی ورودی‌ها ---
$all_genres = ["اکشن", "فانتزی", "کمدی", "ماجراجویی", "درام", "عاشقانه", "هنرهای رزمی", "تناسخ", "ایسکای"];
$all_types = ['novel', 'manhwa', 'manga'];
$all_origins = ['original', 'translated'];

// دریافت پارامترها از URL
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_type = isset($_GET['type']) && in_array($_GET['type'], $all_types) ? $_GET['type'] : '';
$selected_origin = isset($_GET['origin']) && in_array($_GET['origin'], $all_origins) ? $_GET['origin'] : '';
$selected_genres = isset($_GET['genres']) && is_array($_GET['genres']) ? $_GET['genres'] : [];
$min_rating = isset($_GET['rating_min']) && is_numeric($_GET['rating_min']) ? floatval($_GET['rating_min']) : 0;

$works_results = [];
$users_results = [];

// --- گام ۳: اجرای کوئری‌ها فقط در صورتی که عبارتی برای جستجو وجود داشته باشد ---
if (!empty($search_term) || !empty($selected_type) || !empty($selected_origin) || !empty($selected_genres) || $min_rating > 0) {
    try {
        // --- کوئری برای جستجوی آثار ---
        $works_sql = "SELECT id, title, cover_url, rating, author, type FROM novels WHERE 1=1";
        $works_conditions = [];
        $works_params = [];

        if (!empty($search_term)) {
            $works_conditions[] = "to_tsvector('simple', title || ' ' || summary) @@ to_tsquery('simple', ?)";
            $works_params[] = implode('&', explode(' ', $search_term));
        }
        if (!empty($selected_type)) { $works_conditions[] = "type = ?"; $works_params[] = $selected_type; }
        if (!empty($selected_origin)) { $works_conditions[] = "origin = ?"; $works_params[] = $selected_origin; }
        if ($min_rating > 0) { $works_conditions[] = "rating >= ?"; $works_params[] = $min_rating; }
        if (!empty($selected_genres)) {
            foreach ($selected_genres as $genre) {
                if (in_array($genre, $all_genres)) {
                    $works_conditions[] = "',' || genres || ',' LIKE ?";
                    $works_params[] = '%,'. $genre .',%';
                }
            }
        }
        
        if (!empty($works_conditions)) {
            $works_sql .= ' AND ' . implode(' AND ', $works_conditions) . " ORDER BY created_at DESC";
            $stmt_works = $conn->prepare($works_sql);
            $stmt_works->execute($works_params);
            $works_results = $stmt_works->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- کوئری برای جستجوی کاربران (فقط بر اساس نام کاربری) ---
        if (!empty($search_term)) {
            $users_sql = "SELECT username, profile_picture_url FROM users WHERE username ILIKE ? ORDER BY username ASC LIMIT 10";
            $stmt_users = $conn->prepare($users_sql);
            $stmt_users->execute(['%' . $search_term . '%']);
            $users_results = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        error_log("Search Page Fetch Error: " . $e->getMessage());
    }
}
$type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جستجو - NovelWorld</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css">
    <link rel="stylesheet" href="search-style.css">
</head>
<body>
    <?php require_once 'header.php'; ?>

<div class="search-page-container">
    <main class="results-content">
        <div class="search-header">
            <form action="search.php" method="GET" class="results-search-bar">
                <input type="search" name="q" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="جستجوی اثر یا کاربر..." autofocus>
                <button type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path></svg>
                </button>
            </form>
            <button id="open-filters-btn" class="advanced-filter-btn" title="فیلترهای پیشرفته">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            </button>
        </div>
        
        <?php if (empty($search_term) && empty($selected_genres) && empty($selected_type) && empty($selected_origin)): ?>
            <div class="no-results" style="grid-column: 1 / -1;"><p>برای شروع، عبارت مورد نظر خود را جستجو کنید یا از فیلترها استفاده نمایید.</p></div>
        <?php else: ?>
            <!-- بخش نتایج کاربران -->
            <?php if (!empty($users_results)): ?>
                <h3 class="results-section-title">کاربران یافت شده</h3>
                <div class="users-grid">
                    <?php foreach ($users_results as $user): ?>
                        <a href="public_profile.php?username=<?php echo urlencode($user['username']); ?>" class="user-card">
                            <img src="<?php echo htmlspecialchars($user['profile_picture_url'] ?? 'default_avatar.png'); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- بخش نتایج آثار -->
            <h3 class="results-section-title">آثار یافت شده</h3>
            <div class="results-grid">
                <?php if (!empty($works_results)): ?>
                    <?php foreach ($works_results as $novel): ?>
                        <div class="manhwa-card" style="position: relative;">
                            <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                                <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                                <div class="card-overlay">
                                    <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                    <span class="card-rating">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                                </div>
                                <span class="type-badge" style="position: absolute; top: 10px; left: 10px; background: var(--primary-color); color: var(--bg-color); padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: bold;"><?php echo $type_persian[$novel['type']]; ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results" style="grid-column: 1 / -1;"><p>هیچ اثری با این مشخصات یافت نشد.</p></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<div id="filters-modal" class="modal-overlay">
    <div class="modal-content">
        <button id="close-modal-btn" class="close-modal-btn">&times;</button>
        <aside class="filters-panel">
            <h4>فیلترهای پیشرفته آثار</h4>
            <form action="search.php" method="GET">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_term); ?>">
                
                <div class="filter-group">
                    <label>نوع اثر:</label>
                    <div class="tag-group">
                        <input type="radio" id="type-any" name="type" value="" <?php echo ($selected_type == '') ? 'checked' : ''; ?>><label for="type-any">همه</label>
                        <input type="radio" id="type-novel" name="type" value="novel" <?php echo ($selected_type == 'novel') ? 'checked' : ''; ?>><label for="type-novel">ناول</label>
                        <input type="radio" id="type-manhwa" name="type" value="manhwa" <?php echo ($selected_type == 'manhwa') ? 'checked' : ''; ?>><label for="type-manhwa">مانهوا</label>
                        <input type="radio" id="type-manga" name="type" value="manga" <?php echo ($selected_type == 'manga') ? 'checked' : ''; ?>><label for="type-manga">مانگا</label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>منشاء اثر:</label>
                    <div class="tag-group">
                        <input type="radio" id="origin-any" name="origin" value="" <?php echo ($selected_origin == '') ? 'checked' : ''; ?>><label for="origin-any">همه</label>
                        <input type="radio" id="origin-original" name="origin" value="original" <?php echo ($selected_origin == 'original') ? 'checked' : ''; ?>><label for="origin-original">تالیفی</label>
                        <input type="radio" id="origin-translated" name="origin" value="translated" <?php echo ($selected_origin == 'translated') ? 'checked' : ''; ?>><label for="origin-translated">ترجمه</label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>حداقل امتیاز: <strong id="rating_value"><?php echo $min_rating; ?></strong></label>
                    <input type="range" min="0" max="10" value="<?php echo htmlspecialchars($min_rating); ?>" step="0.1" name="rating_min" oninput="document.getElementById('rating_value').textContent = this.value" style="width: 100%;">
                </div>

                <div class="filter-group">
                    <label>ژانرها:</label>
                    <div class="tag-group checkbox-group">
                        <?php foreach ($all_genres as $genre): ?>
                            <div>
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
</body>
</html>
