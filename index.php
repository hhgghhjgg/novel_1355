<?php
/*
=====================================================
    NovelWorld - Main Index Page (FINAL & COMPLETE)
    Version: 3.2 (With Genre Carousel Restored)
=====================================================
    - This is the final, complete code for the redesigned homepage.
    - Includes the cinematic Hero Slider.
    - Includes the restored Genre Carousel.
    - Includes all 6 work carousels: Latest Updates, Newest Arrivals,
      Completed Works, Highest Rated, Top Originals, and Top Translated.
*/

// --- گام ۱: فراخوانی هدر و اتصال به دیتابیس ---
require_once 'header.php';

// --- گام ۲: واکشی داده‌ها برای تمام بخش‌ها ---
$hero_slides = [];
$latest_updates = [];
$top_originals = [];
$top_translated = [];
// تعریف متغیرها برای اسلایدرهای جدید
$newest_arrivals = [];
$completed_works = [];
$highest_rated = [];

try {
    // تابع کمکی برای افزودن شماره آخرین چپتر به لیست ناول‌ها
    function enrich_novels_with_latest_chapter($conn, $novels_array) {
        if (empty($novels_array)) return [];
        
        $novel_ids = array_column($novels_array, 'id');
        $placeholders = implode(',', array_fill(0, count($novel_ids), '?'));
        
        $sql = "SELECT novel_id, MAX(chapter_number) as latest_chapter 
                FROM chapters 
                WHERE novel_id IN ($placeholders) AND status = 'approved'
                GROUP BY novel_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($novel_ids);
        $chapters_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($novels_array as &$novel) {
            $novel['latest_chapter'] = $chapters_data[$novel['id']] ?? 0;
        }
        return $novels_array;
    }

    // ۱. اسلایدر اصلی (۵ اثر جدید و پرطرفدار)
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url, author FROM novels ORDER BY created_at DESC, rating DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. جدیدترین بروزرسانی‌ها (آثاری که به تازگی چپتر جدید دریافت کرده‌اند)
    $latest_updates_stmt = $conn->query(
        "SELECT n.id, n.title, n.cover_url, n.rating, n.type
         FROM novels n
         JOIN (
             SELECT novel_id, MAX(published_at) as last_published
             FROM chapters
             WHERE status = 'approved' AND published_at <= NOW()
             GROUP BY novel_id
         ) as latest_chapters ON n.id = latest_chapters.novel_id
         ORDER BY latest_chapters.last_published DESC
         LIMIT 12"
    );
    $latest_updates = $latest_updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    $latest_updates = enrich_novels_with_latest_chapter($conn, $latest_updates);

    // ۳. برترین آثار تالیفی (ایرانی)
    $top_originals = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE origin = 'original' ORDER BY rating DESC, created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $top_originals = enrich_novels_with_latest_chapter($conn, $top_originals);
    
    // ۴. برترین آثار ترجمه شده
    $top_translated = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE origin = 'translated' ORDER BY rating DESC, created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $top_translated = enrich_novels_with_latest_chapter($conn, $top_translated);

    // ۵. آثار تازه اضافه شده
    $newest_arrivals = $conn->query("SELECT id, title, cover_url, rating, type FROM novels ORDER BY created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $newest_arrivals = enrich_novels_with_latest_chapter($conn, $newest_arrivals);

    // ۶. آثار تکمیل شده (مرتب شده بر اساس امتیاز)
    $completed_works = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE status = 'completed' ORDER BY rating DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $completed_works = enrich_novels_with_latest_chapter($conn, $completed_works);

    // ۷. آثار با بالاترین امتیاز
    $highest_rated = $conn->query("SELECT id, title, cover_url, rating, type FROM novels ORDER BY rating DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $highest_rated = enrich_novels_with_latest_chapter($conn, $highest_rated);


} catch (PDOException $e) {
    error_log("Index Page Fetch Error: " . $e->getMessage());
}

// آرایه کمکی برای تبدیل نوع اثر به نام فارسی
$type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];

// تعریف آرایه ژانرها برای نمایش
$all_genres = [
    ['name' => 'اکشن', 'icon' => 'bolt'], ['name' => 'فانتزی', 'icon' => 'auto_stories'],
    ['name' => 'عاشقانه', 'icon' => 'favorite'], ['name' => 'ماجراجویی', 'icon' => 'explore'],
    ['name' => 'کمدی', 'icon' => 'sentiment_satisfied'], ['name' => 'درام', 'icon' => 'theater_comedy'],
    ['name' => 'ایسکای', 'icon' => 'public'], ['name' => 'تناسخ', 'icon' => 'history_toggle_off'],
    ['name' => 'هنرهای رزمی', 'icon' => 'sports_martial_arts'], ['name' => 'معمایی', 'icon' => 'search'],
    ['name' => 'ترسناک', 'icon' => 'mood_bad'], ['name' => 'مدرسه‌ای', 'icon' => 'school'],
];
$top_genres = array_slice($all_genres, 0, 10);
?>

<main class="homepage-main">
    <!-- بخش ۱: هیرو اسلایدر -->
    <?php if (!empty($hero_slides)): ?>
    <section class="hero-slider-section">
        <div class="hero-slider">
            <?php foreach ($hero_slides as $slide): ?>
                <div class="hero-slide" data-id="<?php echo $slide['id']; ?>">
                    <div class="hero-slide-bg" style="background-image: url('<?php echo htmlspecialchars($slide['cover_url']); ?>');"></div>
                    <div class="hero-slide-overlay"></div>
                    <div class="hero-slide-content">
                        <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                        <p class="hero-author">اثر <?php echo htmlspecialchars($slide['author']); ?></p>
                        <p class="hero-summary"><?php echo htmlspecialchars(mb_substr(trim($slide['summary']), 0, 120, "UTF-8")) . '...'; ?></p>
                        <a href="novel_detail.php?id=<?php echo $slide['id']; ?>" class="btn btn-primary hero-btn">
                            <span class="material-symbols-outlined">auto_stories</span> شروع خواندن
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-slider-dots"></div>
    </section>
    <?php endif; ?>
    
    <!-- بخش ۲: اسلایدر ژانرها (بازگردانده شده) -->
    <?php if (!empty($top_genres)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">مرور ژانرها</h2>
            <a href="all_genres.php" class="view-all" style="font-weight: 500; color: var(--text-secondary-color);">مشاهده همه</a>
        </div>
        <div class="genre-carousel">
            <?php foreach ($top_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card">
                    <span class="material-symbols-outlined genre-icon"><?php echo $genre['icon']; ?></span>
                    <span class="genre-name"><?php echo htmlspecialchars($genre['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>


    <!-- بخش ۳: جدیدترین بروزرسانی‌ها -->
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
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- بخش ۴: تازه به NovelWorld اضافه شدند -->
    <?php if (!empty($newest_arrivals)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">تازه به NovelWorld اضافه شدند</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="newest-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="newest-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="newest-carousel">
            <?php foreach ($newest_arrivals as $novel): ?>
                <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>


    <!-- بخش ۵: آثار تکمیل شده -->
    <?php if (!empty($completed_works)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">پیشنهاد آثار تکمیل شده</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="completed-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="completed-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="completed-carousel">
            <?php foreach ($completed_works as $novel): ?>
                 <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- بخش ۶: امتیازآورترین‌ها -->
    <?php if (!empty($highest_rated)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">امتیازآورترین‌ها</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="rated-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="rated-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="rated-carousel">
            <?php foreach ($highest_rated as $novel): ?>
                <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- بخش ۷: برترین آثار تالیفی -->
    <?php if (!empty($top_originals)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">برترین آثار ایرانی</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="originals-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="originals-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="originals-carousel">
            <?php foreach ($top_originals as $novel): ?>
                 <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- بخش ۸: برترین آثار ترجمه -->
    <?php if (!empty($top_translated)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">محبوب‌ترین‌های ترجمه</h2>
            <div class="carousel-nav">
                <button class="nav-arrow prev-arrow" data-carousel="translated-carousel">&lt;</button>
                <button class="nav-arrow next-arrow" data-carousel="translated-carousel">&gt;</button>
            </div>
        </div>
        <div class="works-carousel" id="translated-carousel">
            <?php foreach ($top_translated as $novel): ?>
                <div class="work-card">
                    <a href="novel_detail.php?id=<?php echo $novel['id']; ?>">
                        <div class="card-image-wrapper">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="card-img" loading="lazy">
                            <div class="card-overlay-gradient"></div>
                            <div class="card-top-badges">
                                <span class="badge type-badge"><?php echo $type_persian[$novel['type']]; ?></span>
                                <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                            </div>
                            <div class="card-bottom-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                <?php if ($novel['latest_chapter'] > 0): ?>
                                    <span class="card-chapter">چپتر <?php echo htmlspecialchars($novel['latest_chapter']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Hero Slider Logic ---
    const heroSlider = document.querySelector('.hero-slider');
    if (heroSlider) {
        const slides = heroSlider.querySelectorAll('.hero-slide');
        const dotsContainer = document.querySelector('.hero-slider-dots');
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
                heroSlider.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach(dot => dot.classList.remove('active'));
                dots[index].classList.add('active');
                currentIndex = index;
            }

            function nextSlide() {
                const nextIndex = (currentIndex + 1) % slides.length;
                goToSlide(nextIndex);
            }
            
            function resetInterval() {
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 7000); // 7 seconds
            }

            resetInterval();
        }
    }

    // --- Carousels Navigation Logic ---
    const carousels = document.querySelectorAll('.works-carousel, .genre-carousel');
    carousels.forEach(carousel => {
        const prevBtn = document.querySelector(`.prev-arrow[data-carousel="${carousel.id}"]`);
        const nextBtn = document.querySelector(`.next-arrow[data-carousel="${carousel.id}"]`);
        
        if (prevBtn && nextBtn) {
            nextBtn.addEventListener('click', () => {
                const scrollAmount = carousel.clientWidth * 0.8;
                carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
            prevBtn.addEventListener('click', () => {
                const scrollAmount = carousel.clientWidth * 0.8;
                carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
        }
    });
});
</script>

<?php 
require_once 'footer.php'; 
?>
