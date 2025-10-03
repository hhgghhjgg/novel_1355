<?php
/*
=====================================================
    NovelWorld - Main Index Page (Visual Overhaul)
    Version: 3.0
=====================================================
    - این یک بازطراحی کامل برای دستیابی به یک UI مدرن و سینمایی است.
    - از ساختار کارت جدید (.master-card) و انیمیشن‌های AOS استفاده می‌کند.
    - شامل بخش جدید "آخرین آپدیت‌ها" به سبک ستونی است.
*/

// --- گام ۱: فراخوانی هدر و آماده‌سازی ---
require_once 'header.php';

// --- گام ۲: تعریف توابع و متغیرهای کمکی ---

// تابع برای نمایش زمان به صورت "X دقیقه/ساعت/روز قبل"
if (!function_exists('time_ago')) {
    function time_ago($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array( 'y' => 'سال', 'm' => 'ماه', 'w' => 'هفته', 'd' => 'روز', 'h' => 'ساعت', 'i' => 'دقیقه', 's' => 'ثانیه' );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' قبل' : 'همین الان';
    }
}

$type_persian = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];

// --- گام ۳: واکشی داده‌ها برای تمام بخش‌ها ---
$hero_slides = [];
$latest_updates = [];
$newest_originals = [];
$top_translated = [];
$recently_completed = [];

try {
    // ۱. اسلایدر اصلی (۵ اثر برتر)
    $hero_slides = $conn->query("SELECT id, title, summary, cover_url FROM novels ORDER BY rating DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // ۲. آخرین آپدیت‌ها (برای بخش ستونی)
    $latest_updates_stmt = $conn->query(
        "SELECT c.chapter_number, n.id as novel_id, n.title as novel_title, n.cover_url, c.published_at
         FROM chapters c
         JOIN novels n ON c.novel_id = n.id
         WHERE c.status = 'approved' AND c.published_at <= NOW()
         ORDER BY c.published_at DESC 
         LIMIT 15"
    );
    $latest_updates = $latest_updates_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ۳. ناول‌های ایرانی (تالیفی) - همراه با نوع اثر برای بج
    $newest_originals = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE origin = 'original' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // ۴. آثار ترجمه شده برتر - همراه با نوع اثر برای بج
    $top_translated = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE origin = 'translated' ORDER BY rating DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // ۵. آثار تکمیل شده جدید
    $recently_completed = $conn->query("SELECT id, title, cover_url, rating, type FROM novels WHERE status = 'completed' ORDER BY updated_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Index Page Fetch Error: " . $e->getMessage());
}

// ۶. تعریف آرایه ژانرها برای نمایش
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

<main>
    <!-- ۱. هیرو اسلایدر سینمایی (با کد جاوااسکریپت اصلاح شده) -->
    <section class="cinematic-hero">
        <?php if (!empty($hero_slides)): $first_slide = $hero_slides[0]; ?>
            <div class="hero-background" style="background-image: url('<?php echo htmlspecialchars($first_slide['cover_url']); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="hero-text" data-aos="fade-left">
                    <h1 id="hero-title"><?php echo htmlspecialchars($first_slide['title']); ?></h1>
                    <p id="hero-summary"><?php echo htmlspecialchars(mb_substr(trim($first_slide['summary']), 0, 150, "UTF-8")) . '...'; ?></p>
                    <a id="hero-link" href="novel_detail.php?id=<?php echo $first_slide['id']; ?>" class="btn btn-primary">مشاهده جزئیات</a>
                </div>
                <div class="hero-carousel" data-aos="fade-right">
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
    <section class="content-section" data-aos="fade-up">
        <div class="section-header">
            <h2 class="section-title"><span class="material-symbols-outlined">category</span>مرور ژانرها</h2>
            <a href="all_genres.php" class="view-all">همه ژانرها</a>
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

    <!-- کانتینر اصلی محتوا با دو ستون -->
    <div class="main-content-grid" data-aos="fade-up" data-aos-delay="200">

        <!-- ستون اصلی (۷۵٪ عرض) -->
        <div class="main-column">
            
            <!-- کاروسل آثار ترجمه شده -->
            <?php if (!empty($top_translated)): ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title"><span class="material-symbols-outlined">translate</span>برترین‌های ترجمه</h2>
                    <a href="search.php?origin=translated" class="view-all">مشاهده همه</a>
                </div>
                <div class="manhwa-carousel">
                    <?php foreach ($top_translated as $novel): ?>
                        <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="master-card">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="master-card-img">
                            <div class="master-card-overlay">
                                <div class="master-card-badges">
                                    <span class="badge type-badge"><?php echo $type_persian[$novel['type']] ?? 'اثر'; ?></span>
                                    <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                                </div>
                                <div class="master-card-content">
                                    <h3 class="master-card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                    <span class="master-card-action">مشاهده اثر</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- کاروسل ناول ایرانی (تالیفی) -->
            <?php if (!empty($newest_originals)): ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title"><span class="material-symbols-outlined">edit</span>جدیدترین آثار ایرانی</h2>
                    <a href="search.php?origin=original" class="view-all">مشاهده همه</a>
                </div>
                <div class="manhwa-carousel">
                    <?php foreach ($newest_originals as $novel): ?>
                        <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="master-card">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="master-card-img">
                            <div class="master-card-overlay">
                                <div class="master-card-badges">
                                    <span class="badge type-badge"><?php echo $type_persian[$novel['type']] ?? 'اثر'; ?></span>
                                    <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                                </div>
                                <div class="master-card-content">
                                    <h3 class="master-card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                    <span class="master-card-action">مشاهده اثر</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

             <!-- کاروسل آثار تکمیل شده -->
            <?php if (!empty($recently_completed)): ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title"><span class="material-symbols-outlined">check_circle</span>آثار تکمیل شده</h2>
                    <a href="search.php?status=completed" class="view-all">مشاهده همه</a>
                </div>
                <div class="manhwa-carousel">
                    <?php foreach ($recently_completed as $novel): ?>
                        <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="master-card">
                            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="master-card-img">
                            <div class="master-card-overlay">
                                <div class="master-card-badges">
                                    <span class="badge type-badge"><?php echo $type_persian[$novel['type']] ?? 'اثر'; ?></span>
                                    <span class="badge rating-badge">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                                </div>
                                <div class="master-card-content">
                                    <h3 class="master-card-title"><?php echo htmlspecialchars($novel['title']); ?></h3>
                                    <span class="master-card-action">مشاهده اثر</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <!-- ستون کناری (۲۵٪ عرض) -->
        <aside class="sidebar-column">
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title" style="font-size: 1.5rem;"><span class="material-symbols-outlined">update</span>آخرین آپدیت‌ها</h2>
                </div>
                <div class="latest-updates-list">
                    <?php if (empty($latest_updates)): ?>
                        <p>هنوز چپتری منتشر نشده است.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($latest_updates as $update): ?>
                                <li>
                                    <a href="novel_detail.php?id=<?php echo $update['novel_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($update['cover_url']); ?>" alt="">
                                        <div class="update-info">
                                            <span class="update-title"><?php echo htmlspecialchars($update['novel_title']); ?></span>
                                            <span class="update-chapter">چپتر <?php echo htmlspecialchars($update['chapter_number']); ?></span>
                                        </div>
                                        <span class="update-time"><?php echo time_ago($update['published_at']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</main>

<script>
// این اسکریپت در فایل script.js قرار داده شود، اما برای کامل بودن اینجا نیز آورده شده است.
document.addEventListener('DOMContentLoaded', () => {
    const heroCarousel = document.querySelector('.hero-carousel');
    if (heroCarousel) {
        const heroCards = heroCarousel.querySelectorAll('.hero-card');
        const heroTitle = document.getElementById('hero-title');
        const heroSummary = document.getElementById('hero-summary');
        const heroLink = document.getElementById('hero-link');
        const heroBackground = document.querySelector('.hero-background');
        let currentIndex = 0;
        let slideInterval;

        function updateHeroContent(index) {
            const card = heroCards[index];
            if (!card) return;
            if (heroTitle) heroTitle.textContent = card.dataset.title;
            if (heroSummary) heroSummary.textContent = card.dataset.summary;
            if (heroLink) heroLink.href = card.dataset.link;
            if (heroBackground) {
                heroBackground.style.opacity = 0;
                setTimeout(() => {
                    heroBackground.style.backgroundImage = `url('${card.dataset.bg}')`;
                    heroBackground.style.opacity = 1;
                }, 300);
            }
            heroCarousel.querySelector('.hero-card.active')?.classList.remove('active');
            card.classList.add('active');
            currentIndex = index;
        }

        function nextSlide() {
            const nextIndex = (currentIndex + 1) % heroCards.length;
            updateHeroContent(nextIndex);
        }

        function startAutoplay() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000); // هر ۵ ثانیه
        }

        heroCards.forEach((card, index) => {
            card.addEventListener('click', () => {
                updateHeroContent(index);
                startAutoplay();
            });
        });
        
        if (heroCards.length > 1) {
             startAutoplay();
        }
        if(heroBackground) {
            heroBackground.style.transition = 'opacity 0.3s ease-in-out';
        }
    }
});
</script>

<?php 
require_once 'footer.php'; 
?>
