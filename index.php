<?php
/*
=====================================================
    NovelWorld - Main Index Page
    Version: 2.3 (Final, Cinematic UI, Horizontal Genre Carousel)
=====================================================
    - این نسخه نهایی، شامل بازطراحی کامل صفحه اصلی با هیرو اسلایدر سینمایی،
      اسلایدر آخرین چپترها، و اسلایدر افقی برای ژانرها است.
*/

// --- گام ۱: فراخوانی هدر و اتصال به دیتابیس ---
require_once 'header.php'; // شامل اتصال دیتابیس ($conn) و اطلاعات کاربر

// --- گام ۲: واکشی داده‌ها برای تمام بخش‌ها ---
$hero_slides = [];
$latest_chapters = [];
$newest_originals = [];
$top_translated = [];
// ... (هر آرایه دیگری که نیاز دارید)

try {
    // ۱. اسلایدر اصلی (۵ اثر برتر)
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url FROM novels ORDER BY rating DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. آخرین چپترهای منتشر شده
    $latest_chapters_stmt = $conn->query(
        "SELECT c.id, c.chapter_number, c.title as chapter_title, 
                n.id as novel_id, n.title as novel_title, n.cover_url
         FROM chapters c
         JOIN novels n ON c.novel_id = n.id
         WHERE c.status = 'approved' AND c.published_at <= NOW()
         ORDER BY c.published_at DESC 
         LIMIT 10"
    );
    $latest_chapters = $latest_chapters_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ۳. ناول‌های ایرانی (تالیفی)
    $newest_originals = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE origin = 'original' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // ۴. آثار ترجمه شده
    $top_translated = $conn->query("SELECT id, title, cover_url, rating FROM novels WHERE origin = 'translated' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Index Page Fetch Error: " . $e->getMessage());
}

// --- گام ۳: تعریف آرایه کامل ژانرها ---
$all_genres = [
    ['name' => 'اکشن', 'icon' => 'bolt'], ['name' => 'فانتزی', 'icon' => 'auto_stories'],
    ['name' => 'عاشقانه', 'icon' => 'favorite'], ['name' => 'ماجراجویی', 'icon' => 'explore'],
    ['name' => 'کمدی', 'icon' => 'sentiment_satisfied'], ['name' => 'درام', 'icon' => 'theater_comedy'],
    ['name' => 'ایسکای', 'icon' => 'public'], ['name' => 'تناسخ', 'icon' => 'history_toggle_off'],
    ['name' => 'هنرهای رزمی', 'icon' => 'sports_martial_arts'], ['name' => 'معمایی', 'icon' => 'search'],
    ['name' => 'ترسناک', 'icon' => 'mood_bad'], ['name' => 'مدرسه‌ای', 'icon' => 'school'],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دنیای ناول - NovelWorld</title>
    <!-- فراخوانی CSS ها باید در header.php باشد، اما برای اطمینان اینجا هم می‌آوریم -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

<main>
    <!-- ۱. هیرو اسلایدر سینمایی -->
    <section class="cinematic-hero">
        <?php if (!empty($hero_slides)): $first_slide = $hero_slides[0]; ?>
            <div class="hero-background" style="background-image: url('<?php echo htmlspecialchars($first_slide['cover_url']); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="hero-text">
                    <h1 id="hero-title"><?php echo htmlspecialchars($first_slide['title']); ?></h1>
                    <p id="hero-summary"><?php echo htmlspecialchars(mb_substr(trim($first_slide['summary']), 0, 150, "UTF-8")) . '...'; ?></p>
                    <a id="hero-link" href="novel_detail.php?id=<?php echo $first_slide['id']; ?>" class="btn btn-primary">مشاهده جزئیات</a>
                </div>
                <div class="hero-carousel">
                    <?php foreach ($hero_slides as $index => $slide): ?>
                        <div class="hero-card <?php echo $index === 0 ? 'active' : ''; ?>" 
                             data-title="<?php echo htmlspecialchars($slide['title']); ?>"
                             data-summary="<?php echo htmlspecialchars(mb_substr(trim($slide['summary']), 0, 150, "UTF-8")) . '...'; ?>"
                             data-link="novel_detail.php?id=<?php echo $slide['id']; ?>"
                             data-bg="<?php echo htmlspecialchars($slide['cover_url']); ?>">
                            <img src="<?php echo htmlspecialchars($slide['cover_url']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ۲. اسلایدر ژانرها (افقی) -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title"><span class="material-symbols-outlined">category</span>مرور ژانرها</h2>
            <a href="all_genres.php" class="view-all">همه ژانرها</a>
        </div>
        <div class="genre-carousel">
            <?php foreach ($all_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card">
                    <span class="material-symbols-outlined genre-icon"><?php echo $genre['icon']; ?></span>
                    <span class="genre-name"><?php echo htmlspecialchars($genre['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- ۳. اسلایدر آخرین چپترها -->
    <?php if (!empty($latest_chapters)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title"><span class="material-symbols-outlined">history</span>آخرین چپترهای منتشر شده</h2>
        </div>
        <div class="latest-chapters-carousel">
            <?php foreach ($latest_chapters as $chapter): ?>
                <a href="read_chapter.php?id=<?php echo $chapter['id']; ?>" class="chapter-card">
                    <img src="<?php echo htmlspecialchars($chapter['cover_url']); ?>" alt="<?php echo htmlspecialchars($chapter['novel_title']); ?>" class="chapter-card-img">
                    <div class="chapter-card-overlay">
                        <h4 class="chapter-card-novel-title"><?php echo htmlspecialchars($chapter['novel_title']); ?></h4>
                        <p class="chapter-card-title">چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ۴. اسلایدر ناول ایرانی (تالیفی) -->
    <?php if (!empty($newest_originals)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title"><span class="material-symbols-outlined">pen_spark</span>ناول‌های ایرانی</h2>
            <a href="search.php?origin=original" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
            <?php foreach ($newest_originals as $novel): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-container">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                            <div class="card-badges"><span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span></div>
                        </div>
                        <div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ۵. اسلایدر آثار ترجمه شده -->
    <?php if (!empty($top_translated)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title"><span class="material-symbols-outlined">translate</span>برترین‌های ترجمه</h2>
            <a href="search.php?origin=translated" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
            <?php foreach ($top_translated as $novel): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-container">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                            <div class="card-badges"><span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span></div>
                        </div>
                        <div class="card-content"><h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php 
require_once 'footer.php'; 
?>
</body>
</html>
