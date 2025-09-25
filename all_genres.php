<?php
// all_genres.php

/*
=====================================================
    NovelWorld - All Genres Page
    Version: 2.0 (Final, Professional UI)
=====================================================
    - این صفحه به عنوان یک هاب مرکزی برای مرور تمام ژانرهای سایت عمل می‌کند.
    - دارای یک هدر بزرگ و جذاب و یک باکس جستجوی زنده (سمت کلاینت) است.
*/

// --- گام ۱: فراخوانی هدر و فوتر اصلی سایت ---
require_once 'header.php';

// --- گام ۲: تعریف آرایه کامل ژانرها ---
// این لیست مرکزی شما برای تمام ژانرهاست. هر زمان که بخواهید، می‌توانید به آن اضافه کنید.
$all_genres = [
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
    ['name' => 'ترسناک', 'icon' => 'mood_bad'],
    ['name' => 'مدرسه‌ای', 'icon' => 'school'],
    ['name' => 'علمی-تخیلی', 'icon' => 'rocket_launch'],
    ['name' => 'ورزشی', 'icon' => 'sports_basketball'],
    ['name' => 'تاریخی', 'icon' => 'castle'],
    ['name' => 'روانشناختی', 'icon' => 'psychology'],
    ['name' => 'برشی از زندگی', 'icon' => 'cake'],
    ['name' => 'پسا-آخرالزمانی', 'icon' => 'landscape'],
    ['name' => 'سیستم', 'icon' => 'data_usage'],
    ['name' => 'ماوراء طبیعی', 'icon' => 'flare'],
];
?>

<!-- --- گام ۳: رندر کردن HTML صفحه --- -->
<title>مرور همه ژانرها - NovelWorld</title>

<!-- لینک به فایل CSS اختصاصی این صفحه -->
<link rel="stylesheet" href="all_genres_style.css">

<!-- فراخوانی فونت آیکون‌های گوگل (Material Symbols) -->
<!-- (اگر این خط از قبل در header.php شما وجود دارد، نیازی به تکرار آن نیست) -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<main class="genres-page">
    <!-- بخش هدر بزرگ و جذاب صفحه -->
    <div class="genres-hero">
        <div class="hero-content">
            <h1>دنیای ژانرها را کشف کنید</h1>
            <p>از میان ده‌ها ژانر، داستان بعدی خود را پیدا کنید.</p>
            <div class="genres-search-box">
                <input type="text" id="genre-search-input" placeholder="مثلاً: فانتزی، عاشقانه، ایسکای ...">
                <span class="material-symbols-outlined search-icon">search</span>
            </div>
        </div>
    </div>

    <!-- کانتینر برای گرید ژانرها -->
    <div class="genres-grid-container">
        <div id="all-genres-grid" class="genre-grid">
            <?php foreach ($all_genres as $genre): ?>
                <a href="genre_results.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card" data-genre-name="<?php echo strtolower(htmlspecialchars($genre['name'])); ?>">
                    <span class="material-symbols-outlined genre-icon"><?php echo $genre['icon']; ?></span>
                    <span class="genre-name"><?php echo htmlspecialchars($genre['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- این پیام زمانی نمایش داده می‌شود که هیچ نتیجه‌ای برای جستجو یافت نشود -->
        <div id="no-genre-found" class="no-results-message" style="display: none;">
            <h3>متاسفانه ژانری با این نام یافت نشد.</h3>
            <p>لطفاً عبارت دیگری را امتحان کنید یا املای کلمه را بررسی کنید.</p>
        </div>
    </div>
</main>

<!-- --- گام ۴: اسکریپت جستجوی زنده (سمت کلاینت) --- -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('genre-search-input');
    const genreCards = document.querySelectorAll('#all-genres-grid .genre-card');
    const noResultMsg = document.getElementById('no-genre-found');

    if (searchInput && genreCards.length > 0) {
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let anyVisible = false;

            genreCards.forEach(card => {
                // ما از data attribute برای جستجو استفاده می‌کنیم که قابل اعتمادتر است
                const genreName = card.dataset.genreName;
                
                if (genreName.includes(searchTerm)) {
                    card.style.display = 'flex'; // اگر مطابقت داشت، کارت را نمایش بده
                    anyVisible = true;
                } else {
                    card.style.display = 'none'; // در غیر این صورت، کارت را مخفی کن
                }
            });

            // نمایش یا مخفی کردن پیام "یافت نشد"
            noResultMsg.style.display = anyVisible ? 'none' : 'block';
        });
    }
});
</script>

<?php 
// --- گام ۵: فراخوانی فوتر اصلی سایت ---
require_once 'footer.php'; 
?>
