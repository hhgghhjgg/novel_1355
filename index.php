<?php
/*
=====================================================
    NovelWorld - Main Index Page (FINAL & COMPLETE)
    Version: 4.1 (With simplified, user-friendly titles)
=====================================================
    - All creative section titles have been replaced with
      clear and direct headings as per user request.
*/

// --- گام ۱: فراخوانی هدر و اتصال به دیتابیس ---
require_once 'header.php';

// --- گام ۲: واکشی داده‌ها برای تمام بخش‌های جدید ---
$hero_slides = [];
$latest_updates = [];
$editors_picks = [];
$newly_added = [];
$highest_rated = [];

try {
    // تابع کمکی برای افزودن شماره آخرین چپتر به لیست ناول‌ها
    function enrich_novels_with_latest_chapter($conn, $novels_array) {
        if (empty($novels_array)) return [];
        $novel_ids = array_column($novels_array, 'id');
        $placeholders = implode(',', array_fill(0, count($novel_ids), '?'));
        $sql = "SELECT novel_id, MAX(chapter_number) as latest_chapter 
                FROM chapters WHERE novel_id IN ($placeholders) AND status = 'approved' GROUP BY novel_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($novel_ids);
        $chapters_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($novels_array as &$novel) {
            $novel['latest_chapter'] = $chapters_data[$novel['id']] ?? 0;
        }
        return $novels_array;
    }

    // ۱. هیرو اسلایدر (B2)
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url, author, genres FROM novels ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. جدیدترین بروزرسانی‌ها (D1)
    $latest_updates_stmt = $conn->query(
        "SELECT n.id, n.title, n.cover_url, n.rating, n.type
         FROM novels n
         JOIN (
             SELECT novel_id, MAX(published_at) as last_published
             FROM chapters WHERE status = 'approved' AND published_at <= NOW() GROUP BY novel_id
         ) as latest_chapters ON n.id = latest_chapters.novel_id
         ORDER BY latest_chapters.last_published DESC LIMIT 12"
    );
    $latest_updates = $latest_updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    $latest_updates = enrich_novels_with_latest_chapter($conn, $latest_updates);

    // ۳. انتخاب سردبیر (D2) - سه اثر تصادفی با امتیاز بالا
    $editors_picks = $conn->query("SELECT id, title, summary, cover_url, rating, type, genres FROM novels WHERE rating > 8.0 ORDER BY RANDOM() LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    // ۴. تازه‌های کتابخانه (D3)
    $newly_added = $conn->query("SELECT id, title, cover_url, rating, type FROM novels ORDER BY created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $newly_added = enrich_novels_with_latest_chapter($conn, $newly_added);
    
    // ۵. امتیازآورترین‌ها (D4)
    $highest_rated = $conn->query("SELECT id, title, cover_url, rating, type FROM novels ORDER BY rating DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $highest_rated = enrich_novels_with_latest_chapter($conn, $highest_rated);

} catch (PDOException $e) {
    error_log("Index Page Redesign Fetch Error: " . $e->getMessage());
}

// آرایه‌های کمکی برای نمایش
$type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];
$quick_access_genres = [
    ['name' => 'اکشن', 'icon' => 'bolt'], ['name' => 'فانتزی', 'icon' => 'auto_stories'],
    ['name' => 'عاشقانه', 'icon' => 'favorite'], ['name' => 'ماجراجویی', 'icon' => 'explore'],
    ['name' => 'ایسکای', 'icon' => 'public'], ['name' => 'کمدی', 'icon' => 'sentiment_satisfied']
];
?>

<main class="redesigned-homepage">
    <!-- B2. هیرو اسلایدر برجسته -->
    <?php if (!empty($hero_slides)): ?>
    <section class="hero-carousel-section">
        <div class="hero-carousel">
            <?php foreach ($hero_slides as $slide): ?>
                <div class="hero-slide">
                    <div class="hero-slide-bg" style="background-image: url('<?php echo htmlspecialchars($slide['cover_url']); ?>');"></div>
                    <div class="hero-slide-content">
                        <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                        <p class="hero-subtitle"><?php echo htmlspecialchars(explode(',', $slide['genres'])[0]); ?></p>
                        <a href="novel_detail.php?id=<?php echo $slide['id']; ?>" class="btn btn-primary hero-cta">
                            <span class="material-symbols-outlined">auto_stories</span> شروع به خواندن
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-carousel-dots"></div>
    </section>
    <?php endif; ?>

    <!-- C. نوار دسترسی سریع -->
    <section class="quick-access-section">
        <div class="quick-access-bar">
            <?php foreach ($quick_access_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="qa-chip">
                    <span class="material-symbols-outlined"><?php echo $genre['icon']; ?></span>
                    <span><?php echo htmlspecialchars($genre['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- D1. جدیدترین بروزرسانی‌ها -->
    <?php if (!empty($latest_updates)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">جدیدترین بروزرسانی‌ها</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="latest-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="latest-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="latest-carousel">
            <?php foreach ($latest_updates as $novel): ?>
                <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                            <?php if ($novel['latest_chapter'] > 0): ?>
                                <span class="card-chapter">Ch. <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- D2. انتخاب سردبیر -->
    <?php if (!empty($editors_picks)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">پیشنهاد سردبیر</h2>
        </div>
        <div class="editors-pick-container">
            <?php foreach ($editors_picks as $novel): ?>
                <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="editors-pick-card">
                    <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="ep-card-img" loading="lazy">
                    <div class="ep-card-content">
                        <span class="badge type-badge"><?php echo htmlspecialchars(explode(',', $novel['genres'])[0]); ?></span>
                        <h3 class="ep-card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                        <p class="ep-card-summary"><?php echo htmlspecialchars(mb_substr(trim($novel['summary']), 0, 90, "UTF-8")) . '...'; ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- D3. تازه‌های کتابخانه -->
    <?php if (!empty($newly_added)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">اثر‌های جدید</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="newly-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="newly-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="newly-carousel">
            <?php foreach ($newly_added as $novel): ?>
                <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- D4. امتیازآورترین‌ها -->
    <?php if (!empty($highest_rated)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">محبوب‌ترین‌ها</h2>
        </div>
        <div class="highest-rated-grid">
            <?php foreach ($highest_rated as $index => $novel): ?>
                <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="rated-card">
                    <span class="rated-card-rank">#<?php echo $index + 1; ?></span>
                    <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="rated-card-img" loading="lazy">
                    <div class="rated-card-info">
                        <h3 class="rated-card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                        <span class="rated-card-rating">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Hero Carousel Logic ---
    const heroCarousel = document.querySelector('.hero-carousel');
    if (heroCarousel) {
        const slides = heroCarousel.querySelectorAll('.hero-slide');
        const dotsContainer = document.querySelector('.hero-carousel-dots');
        if (slides.length > 1) {
            let currentIndex = 0;
            let slideInterval;
            slides.forEach((_, index) => {
                const dot = document.createElement('button');
                dot.classList.add('hero-dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => {
                    goToSlide(index);
                    resetInterval();
                });
                dotsContainer.appendChild(dot);
            });
            const dots = dotsContainer.querySelectorAll('.hero-dot');

            function goToSlide(index) {
                heroCarousel.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach(dot => dot.classList.remove('active'));
                dots[index].classList.add('active');
                currentIndex = index;
            }

            function nextSlide() {
                goToSlide((currentIndex + 1) % slides.length);
            }
            
            function resetInterval() {
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 5000); // 5 seconds
            }
            resetInterval();
        }
    }

    // --- Works Carousels Navigation Logic ---
    const carousels = document.querySelectorAll('.works-carousel');
    carousels.forEach(carousel => {
        const prevBtn = document.querySelector(`.prev-arrow[data-carousel="${carousel.id}"]`);
        const nextBtn = document.querySelector(`.next-arrow[data-carousel="${carousel.id}"]`);
        
        if (prevBtn && nextBtn) {
            nextBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: carousel.clientWidth * 0.8, behavior: 'smooth' });
            });
            prevBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: -carousel.clientWidth * 0.8, behavior: 'smooth' });
            });
        }
    });
});
</script>

<?php 
require_once 'footer.php'; 
?>
