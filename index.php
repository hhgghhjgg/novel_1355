// index.php

<?php
/*
=====================================================
    NovelWorld - Main Index Page
    Version: 2.3 (Final, Unabridged, All Features)
=====================================================
*/

// --- گام ۱: فراخوانی هدر و اتصال به دیتابیس ---
require_once 'header.php';

// --- گام ۲: واکشی داده‌ها برای تمام بخش‌ها ---
$hero_slides = [];
$newest_originals = [];
$top_translated = [];
$top_manhwas = [];
$newest_novels = [];

try {
    // ۱. اسلایدر اصلی
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url FROM novels ORDER BY rating DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. جدیدترین آثار تالیفی (ایرانی)
    $newest_originals = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE origin = 'original' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // ۳. برترین آثار ترجمه شده
    $top_translated = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE origin = 'translated' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // ۴. مانهواهای پرطرفدار
    $top_manhwas = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'manhwa' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // ۵. جدیدترین ناول‌های متنی
    $newest_novels = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'novel' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Index Page Fetch Error: " . $e->getMessage());
}

// --- گام ۳: تعریف آرایه ژانرهای برتر برای نمایش در صفحه اصلی ---
$top_genres = [
    ['name' => 'اکشن', 'icon' => 'bolt'],
    ['name' => 'فانتزی', 'icon' => 'auto_stories'],
    ['name' => 'عاشقانه', 'icon' => 'favorite'],
    ['name' => 'ماجراجویی', 'icon' => 'explore'],
    ['name' => 'کمدی', 'icon' => 'sentiment_satisfied'],
    ['name' => 'درام', 'icon' => 'theater_comedy'],
    ['name' => 'ایسکای', 'icon' => 'public'],
    ['name' => 'تناسخ', 'icon' => 'history_toggle_off'],
    ['name' => 'هنرهای رزمی', 'icon' => 'sports_martial_arts'],
    ['name' => 'معمایی', 'icon' => 'search'],
];
?>

<!-- --- گام ۴: رندر کردن بخش HTML --- -->
<title>دنیای ناول - NovelWorld</title>
<!-- (فراخوانی آیکون‌های گوگل باید در header.php باشد) -->

<main>
    <!-- ۱. اسلایدر اصلی -->
    <section class="hero-slider">
        <div class="slider-container">
            <?php if (!empty($hero_slides)): ?>
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: linear-gradient(to right, rgba(19, 17, 11, 1) 25%, rgba(19, 17, 11, 0.7) 50%, rgba(19, 17, 11, 0.1) 100%), url('<?php echo htmlspecialchars($slide['cover_url']); ?>');">
                        <div class="slide-content">
                            <h1 class="slide-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <p class="slide-description"><?php echo htmlspecialchars($slide['summary']); ?></p>
                            <a href="novel_detail.php?id=<?php echo $slide['id']; ?>" class="btn btn-primary">مشاهده جزئیات</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active" style="background-color: var(--surface-color); display:flex; align-items:center; justify-content:center;"><p>اثری برای نمایش یافت نشد.</p></div>
            <?php endif; ?>
        </div>
        <div class="slider-dots">
            <?php foreach ($hero_slides as $index => $slide): ?><span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span><?php endforeach; ?>
        </div>
    </section>

    <!-- ۲. بخش جدیدترین آثار تالیفی (ایرانی) -->
    <?php if (!empty($newest_originals)): ?>
    <section class="content-section">
        <div class="section-header"><h2 class="section-title">جدیدترین آثار تالیفی</h2><a href="search.php?origin=original" class="view-all">مشاهده همه</a></div>
        <div class="manhwa-carousel">
            <?php foreach ($newest_originals as $novel): ?>
                <div class="manhwa-card"><a href="novel_detail.php?id=<?php echo $novel['id']; ?>"><div class="card-image-container"><img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg><?php echo htmlspecialchars($novel['rating']); ?></span></div></div><div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div></a></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ۳. بخش برترین آثار ترجمه شده -->
    <?php if (!empty($top_translated)): ?>
    <section class="content-section">
        <div class="section-header"><h2 class="section-title">برترین‌های ترجمه</h2><a href="search.php?origin=translated" class="view-all">مشاهده همه</a></div>
        <div class="manhwa-carousel">
            <?php foreach ($top_translated as $novel): ?>
                <div class="manhwa-card"><a href="novel_detail.php?id=<?php echo $novel['id']; ?>"><div class="card-image-container"><img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg><?php echo htmlspecialchars($novel['rating']); ?></span></div></div><div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div></a></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ۴. بخش مانهواهای پرطرفدار -->
    <?php if (!empty($top_manhwas)): ?>
    <section class="content-section">
        <div class="section-header"><h2 class="section-title">مانهواهای پرطرفدار</h2><a href="search.php?type=manhwa" class="view-all">مشاهده همه</a></div>
        <div class="manhwa-carousel">
            <?php foreach ($top_manhwas as $comic): ?>
                <div class="manhwa-card"><a href="novel_detail.php?id=<?php echo $comic['id']; ?>"><div class="card-image-container"><img src="<?php echo htmlspecialchars($comic['cover_url']); ?>" alt="<?php echo htmlspecialchars($comic['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg><?php echo htmlspecialchars($comic['rating']); ?></span></div></div><div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($comic['title']); ?></h3></div></a></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- ۵. بخش جدیدترین ناول‌های متنی -->
    <?php if (!empty($newest_novels)): ?>
    <section class="content-section">
        <div class="section-header"><h2 class="section-title">جدیدترین ناول‌ها</h2><a href="search.php?type=novel" class="view-all">مشاهده همه</a></div>
        <div class="manhwa-carousel">
            <?php foreach ($newest_novels as $novel): ?>
                <div class="manhwa-card"><a href="novel_detail.php?id=<?php echo $novel['id']; ?>"><div class="card-image-container"><img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg><?php echo htmlspecialchars($novel['rating']); ?></span></div></div><div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div></a></div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- ۶. بخش ژانرها (نسخه جدید) -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">جستجو بر اساس ژانر</h2>
            <a href="all_genres.php" class="view-all">مشاهده همه ژانرها</a>
        </div>
        <div class="genre-grid">
            <?php foreach ($top_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card">
                    <span class="material-symbols-outlined genre-icon"><?php echo $genre['icon']; ?></span>
                    <span class="genre-name"><?php echo htmlspecialchars($genre['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php 
require_once 'footer.php'; 
?>
