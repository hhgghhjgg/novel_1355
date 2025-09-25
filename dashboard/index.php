<?php
// dashboard/index.php

/*
=====================================================
    NovelWorld - Dashboard Index Page
    Version: 2.0 (Cookie-Session Based)
=====================================================
    - این صفحه به عنوان صفحه اصلی پنل نویسندگی عمل می‌کند.
    - لیستی از تمام ناول‌هایی که توسط کاربر لاگین کرده ایجاد شده‌اند را نمایش می‌دهد.
    - از PDO برای واکشی داده‌ها و از header.php برای احراز هویت استفاده می‌کند.
*/

// --- گام ۱: فراخوانی هدر اختصاصی داشبورد ---
// این فایل شامل اتصال به دیتابیس و بررسی وضعیت لاگین است
// و متغیر $user_id را در دسترس قرار می‌دهد.
require_once 'header.php';


// --- گام ۲: واکشی تمام ناول‌های نوشته شده توسط کاربر لاگین کرده ---
$novels_list = []; // آرایه‌ای برای نگهداری نتایج

try {
    // کوئری برای انتخاب ناول‌هایی که author_id آنها با ID کاربر فعلی برابر است.
    // نتایج بر اساس تاریخ ایجاد به صورت نزولی (جدیدترین‌ها اول) مرتب می‌شوند.
    $stmt = $conn->prepare(
        "SELECT id, title, cover_url, status 
         FROM novels 
         WHERE author_id = ? 
         ORDER BY created_at DESC"
    );
    
    // اجرای کوئری با ارسال user_id به عنوان پارامتر
    $stmt->execute([$user_id]);
    
    // واکشی تمام نتایج به صورت یک آرایه
    $novels_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // در صورت بروز خطای دیتابیس، یک پیام خطا نمایش داده و عملیات را متوقف می‌کنیم.
    // همچنین خطا را در لاگ سرور ثبت می‌کنیم.
    error_log("Dashboard Index Fetch Error: " . $e->getMessage());
    die("خطایی در بارگذاری لیست ناول‌ها رخ داد. لطفاً بعداً تلاش کنید.");
}


// --- گام ۳: رندر کردن بخش HTML ---
?>
<title>مدیریت ناول‌ها - پنل نویسندگی</title>

<div class="page-header">
    <h2>ناول‌های من</h2>
    <div class="header-actions">
        <select name="sort" class="sort-dropdown" onchange="window.location.href=this.value;">
            <option value="?sort=newest">مرتب‌سازی: جدیدترین</option>
            <option value="?sort=oldest">قدیمی‌ترین</option>
            <!-- منطق مرتب‌سازی در آینده می‌تواند در بخش PHP پیاده‌سازی شود -->
        </select>
        <a href="manage_chapter.php" class="btn btn-primary"> <!-- لینک باید به create_novel.php باشد -->
        <a href="create_novel.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>افزودن ناول جدید</span>
        </a>
    </div>
</div>

<?php // بررسی پیام موفقیت از URL (ارسال شده از create_novel.php)
    if (isset($_GET['status']) && $_GET['status'] === 'novel_created') {
        echo "<div class='success-box' style='margin-bottom: 20px; background-color: #2e7d32; color: white; padding: 15px; border-radius: 8px;'>ناول شما با موفقیت ایجاد شد.</div>";
    }
?>

<div class="novels-grid">
    <?php if (empty($novels_list)): ?>
        <div class="no-results-box">
            <h3>شما هنوز هیچ ناولی ننوشته‌اید.</h3>
            <p>برای شروع، روی دکمه زیر کلیک کنید.</p>
            <a href="create_novel.php" class="btn btn-primary">اولین ناول خود را بنویسید!</a>
        </div>
    <?php else: ?>
        <?php foreach ($novels_list as $novel): ?>
            <div class="novel-card">
                <!-- لینک به صفحه جزئیات ناول در سایت اصلی -->
                <a href="../novel_detail.php?id=<?php echo $novel['id']; ?>" class="card-link">
                    <!-- مسیر تصویر از ریشه سایت (../) خوانده نمی‌شود چون URL کامل است -->
                    <img src="<?php echo htmlspecialchars($novel['cover_url'] ?? '../path/to/placeholder.jpg'); ?>" alt="کاور <?php echo htmlspecialchars($novel['title']); ?>">
                    <div class="novel-card-overlay">
                        <h3><?php echo htmlspecialchars($novel['title']); ?></h3>
                    </div>
                    <!-- بج وضعیت در بالای کارت نمایش داده می‌شود -->
                    <span class="status-badge"><?php echo htmlspecialchars($novel['status']); ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
// --- گام ۴: فراخوانی فوتر اختصاصی داشبورد ---
require_once 'footer.php'; 
?>
