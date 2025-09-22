<?php
// index.php

/*
=====================================================
    NovelWorld - Main Index Page
    Version: 2.1 (Multi-Type Content Ready)
=====================================================
    - این صفحه به عنوان ویترین اصلی وب‌سایت عمل می‌کند.
    - کاروسل‌های جداگانه‌ای برای انواع مختلف آثار (ناول، مانهوا، مانگا) نمایش می‌دهد.
    - تمام داده‌ها با استفاده از PDO از دیتابیس واکشی می‌شوند.
*/

// --- گام ۱: فراخوانی هدر و اتصال به دیتابیس ---
require_once 'header.php'; // شامل اتصال دیتابیس ($conn) و اطلاعات کاربر

// --- گام ۲: واکشی داده‌ها برای تمام بخش‌ها ---

$hero_slides = [];
$newest_novels = [];
$top_rated_novels = [];
$newest_manhwas = [];
$top_rated_manhwas = [];
$newest_mangas = [];
$top_rated_mangas = [];

try {
    // ۱. اسلایدر اصلی (آثار ویژه - می‌توانید منطق آن را پیچیده‌تر کنید)
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url FROM novels ORDER BY rating DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. ناول‌های متنی
    $newest_novels = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'novel' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $top_rated_novels = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'novel' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // ۳. مانهوا (وب‌تون)
    $newest_manhwas = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'manhwa' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $top_rated_manhwas = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'manhwa' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // ۴. مانگا
    $newest_mangas = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'manga' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $top_rated_mangas = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE type = 'manga' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Index Page Fetch Error: " . $e->getMessage());
    // در صورت خطا، آرایه‌ها خالی می‌مانند و بخش HTML به درستی این وضعیت را مدیریت می‌کند.
}

// --- گام ۳: تعریف آرایه ژانرها (بدون تغییر) ---
$top_genres = [
    // ... (آرایه ژانرهای شما) ...
    ['name' => 'اکشن', 'icon' => '...', 'color' => '#d00000'],
    ['name' => 'فانتزی', 'icon' => '...', 'color' => '#8338ec'],
    // ...
];

?>
<!-- --- گام ۴: رندر کردن بخش HTML --- -->
<title>دنیای ناول - NovelWorld</title>

<main>
    <!-- ۱. اسلایدر اصلی -->
    <section class="hero-slider">
        <div class="slider-container">
            <?php if (!empty($hero_slides)): ?>
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: linear-gradient(to right, rgba(19, 17, 11, 1) 25%, transparent), url('<?php echo htmlspecialchars($slide['cover_url']); ?>');">
                        <div class="slide-content">
                            <h1 class="slide-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <p class="slide-description"><?php echo htmlspecialchars($slide['summary']); ?></p>
                            <a href="novel_detail.php?id=<?php echo $slide['id']; ?>" class="btn btn-primary">مشاهده جزئیات</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active" style="..."><p>اثری برای نمایش یافت نشد.</p></div>
            <?php endif; ?>
        </div>
        <div class="slider-dots">
            <?php foreach ($hero_slides as $index => $slide): ?><span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span><?php endforeach; ?>
        </div>
    </section>

    <!-- ۲. بخش مانهواها -->
    <?php if (!empty($newest_manhwas)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">جدیدترین مانهواها</h2>
            <a href="search.php?type=manhwa" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
            <?php foreach ($newest_manhwas as $comic): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $comic['id']; ?>">
                        <div class="card-image-container"><img src="<?php echo htmlspecialchars($comic['cover_url']); ?>" alt="<?php echo htmlspecialchars($comic['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge">★ <?php echo htmlspecialchars($comic['rating']); ?></span></div></div>
                        <div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($comic['title']); ?></h3></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ۳. بخش ناول‌های متنی -->
    <?php if (!empty($top_rated_novels)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">برترین ناول‌ها</h2>
            <a href="search.php?type=novel" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
            <?php foreach ($top_rated_novels as $novel): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-container"><img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span></div></div>
                        <div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- ۴. بخش مانگاها -->
    <?php if (!empty($top_rated_mangas)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">مانگاهای پرطرفدار</h2>
            <a href="search.php?type=manga" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
            <?php foreach ($top_rated_mangas as $comic): ?>
                 <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $comic['id']; ?>">
                        <div class="card-image-container"><img src="<?php echo htmlspecialchars($comic['cover_url']); ?>" alt="<?php echo htmlspecialchars($comic['title']); ?>" class="card-img"><div class="card-badges"><span class="badge rating-badge">★ <?php echo htmlspecialchars($comic['rating']); ?></span></div></div>
                        <div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($comic['title']); ?></h3></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- ۵. بخش ژانرها (بدون تغییر) -->
    <section class="content-section">
        <div class="section-header"><h2 class="section-title">جستجو بر اساس ژانر</h2></div>
        <div class="genre-carousel">
            <?php foreach ($top_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card" style="background-color: <?php echo $genre['color']; ?>">
                    <div class="genre-icon"><?php echo $genre['icon']; ?></div>
                    <span class="genre-name"><?php echo $genre['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php 
require_once 'footer.php'; 
?>
