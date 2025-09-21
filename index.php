<?php
// index.php

/*
=====================================================
    NovelWorld - Main Index Page
    Version: 2.0 (Serverless Ready - PDO)
=====================================================
    - این فایل به عنوان صفحه اصلی وب‌سایت عمل می‌کند.
    - اطلاعات مورد نیاز برای اسلایدر اصلی، جدیدترین‌ها و برترین‌ها را
      با استفاده از PDO از دیتابیس PostgreSQL (Neon) واکشی می‌کند.
    - برای اتصال به دیتابیس و مدیریت احراز هویت به header.php وابسته است.
*/

// --- گام ۱: فراخوانی هدر مشترک سایت ---
// این فایل شامل اتصال دیتابیس ($conn) و اطلاعات کاربر لاگین کرده است.
// همچنین بخش <head> و تگ‌های ابتدایی <body> را رندر می‌کند.
require_once 'header.php';

// --- گام ۲: فراخوانی اتصال به دیتابیس ---
// اگرچه هدر این کار را انجام می‌دهد، فراخوانی مجدد آن در اینجا برای وضوح بیشتر است
// و اطمینان می‌دهد که متغیر $conn در دسترس است.
require_once 'db_connect.php';

// --- گام ۳: واکشی داده‌ها از دیتابیس با PDO و مدیریت خطا ---

// آرایه‌هایی برای نگهداری نتایج دیتابیس
$hero_slides = [];
$newest_items = [];
$top_rated_items = [];

try {
    // ۱. واکشی داده‌ها برای اسلایدر اصلی (۳ ناول جدید)
    // استفاده از query() برای کوئری‌های ساده بدون پارامتر امن است.
    // fetchAll() تمام نتایج را به صورت یک آرایه برمی‌گرداند.
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url FROM novels ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. واکشی داده‌ها برای کاروسل "جدیدترین‌ها" (۱۰ ناول)
    $newest_items = $conn->query("SELECT id, title, cover_url, rating FROM novels ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // ۳. واکشی داده‌ها برای کاروسل "برترین‌ها" (۱۰ ناول بر اساس امتیاز)
    $top_rated_items = $conn->query("SELECT id, title, cover_url, rating FROM novels ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // در صورت بروز خطا در هر یک از کوئری‌ها، آن را لاگ کرده و یک پیام خطا نمایش می‌دهیم.
    error_log("Index Page Fetch Error: " . $e->getMessage());
    // در این حالت، آرایه‌ها خالی باقی می‌مانند و بخش HTML به درستی این وضعیت را مدیریت می‌کند.
}


// --- گام ۴: تعریف آرایه مدیریت‌شده برای ژانرهای برتر ---
// این بخش نیازی به دیتابیس ندارد و به صورت استاتیک تعریف شده است.
$top_genres = [
    ['name' => 'اکشن', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M19.33 4.67a1 1 0 0 0-1.41 0L13 9.59l-2.29-2.3a1 1 0 0 0-1.42 1.42l3 3a1 1 0 0 0 1.42 0l5.59-5.59a1 1 0 0 0 0-1.41zM14 13l-3.29 3.29a1 1 0 0 0 0 1.42l.59.59a1 1 0 0 0 1.41 0L16 15l1.29 1.29a1 1 0 0 0 1.42 0l.59-.59a1 1 0 0 0 0-1.42L16.41 12l1.88-1.88a1 1 0 0 0-1.42-1.42L13 12.59z"/></svg>', 'color' => '#d00000'],
    ['name' => 'فانتزی', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75zM12 19.5a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75zM5.53 6.94a.75.75 0 0 1 0-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1-1.06 0zM16.35 17.76a.75.75 0 0 1 0-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1-1.06 0zM18.47 6.94a.75.75 0 0 1-1.06-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1 0 1.06zM7.59 17.76a.75.75 0 0 1-1.06-1.06l1.06-1.06a.75.75 0 1 1 1.06 1.06l-1.06 1.06a.75.75 0 0 1 0 1.06zM2.25 12a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75zM19.5 12a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5a.75.75 0 0 1-.75-.75zM12 6a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg>', 'color' => '#8338ec'],
    ['name' => 'عاشقانه', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>', 'color' => '#e63946'],
    ['name' => 'ماجراجویی', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M5 21V3h14v18l-7-3-7 3z"/></svg>', 'color' => '#2a9d8f'],
    ['name' => 'ایسکای', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>', 'color' => '#52b69a'],
];

?>
<!-- --- گام ۵: رندر کردن بخش HTML --- -->
<title>دنیای ناول - NovelWorld</title>

<main>
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
                <div class="slide active" style="background-color: var(--surface-color); display:flex; align-items:center; justify-content:center;">
                    <p>هیچ ناولی برای نمایش یافت نشد یا خطایی در بارگذاری رخ داده است.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="slider-dots">
            <?php foreach ($hero_slides as $index => $slide): ?>
                <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">جدیدترین‌ها</h2>
            <a href="search.php" class="view-all">مشاهده همه</a>
        </div>
        <div class="manhwa-carousel">
             <?php foreach ($newest_items as $novel): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-container">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                            <div class="card-badges">
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <section class="content-section">
         <div class="section-header">
            <h2 class="section-title">برترین‌ها</h2>
            <a href="search.php" class="view-all">مشاهده همه</a>
        </div>
         <div class="manhwa-carousel">
             <?php foreach ($top_rated_items as $novel): ?>
                <div class="manhwa-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-container">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img">
                            <div class="card-badges">
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">جستجو بر اساس ژانر</h2>
        </div>
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
// فراخوانی فوتر مشترک سایت
require_once 'footer.php'; 
?>
