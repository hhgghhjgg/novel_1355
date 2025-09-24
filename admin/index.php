<?php
// admin/index.php

/*
=====================================================
    NovelWorld - Admin Dashboard Index
    Version: 1.0
=====================================================
    - این صفحه به عنوان صفحه اصلی پنل مدیریت عمل می‌کند.
    - آمارهای کلیدی و مهم سایت را برای مدیر نمایش می‌دهد.
    - دسترسی به این صفحه فقط برای کاربران با نقش 'admin' مجاز است.
*/

// --- گام ۱: فراخوانی هدر پنل مدیریت ---
// این فایل مسئولیت احراز هویت و بررسی نقش ادمین را بر عهده دارد.
// همچنین ساختار بصری (سایدبار و هدر) را رندر می‌کند.
require_once 'header.php';


// --- گام ۲: واکشی آمار کلی از دیتابیس ---
$stats = [
    'pending_chapters' => 0,
    'approved_chapters' => 0,
    'total_users' => 0,
    'total_novels' => 0
];

try {
    // شمارش چپترهای در انتظار تایید
    $stats['pending_chapters'] = $conn->query("SELECT COUNT(*) FROM chapters WHERE status = 'pending'")->fetchColumn();
    
    // شمارش چپترهای تایید شده (منتشر شده)
    $stats['approved_chapters'] = $conn->query("SELECT COUNT(*) FROM chapters WHERE status = 'approved'")->fetchColumn();

    // شمارش کل کاربران ثبت‌نام کرده
    $stats['total_users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // شمارش کل آثار (ناول، مانهوا، مانگا)
    $stats['total_novels'] = $conn->query("SELECT COUNT(*) FROM novels")->fetchColumn();

} catch (PDOException $e) {
    // در صورت بروز خطا، یک پیام خطا نمایش داده می‌شود.
    error_log("Admin Dashboard Stats Error: " . $e->getMessage());
    die("خطایی در واکشی آمار سایت رخ داد.");
}


// --- گام ۳: رندر کردن بخش HTML ---
?>
<title>داشبورد مدیریت - NovelWorld</title>

<!-- استایل‌های سفارشی برای کارت‌های آماری -->
<style>
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
}
.stat-card {
    background: var(--dash-surface);
    padding: 25px;
    border-radius: 12px;
    border-left: 5px solid var(--primary-color);
    text-decoration: none;
    color: var(--dash-text);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.stat-card h3 {
    margin: 0 0 10px;
    font-size: 1rem;
    font-weight: 500;
    color: var(--dash-text-secondary);
}
.stat-card .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
}
/* استایل خاص برای کارت "در انتظار تایید" برای جلب توجه */
.stat-card.pending {
    border-left-color: #ffa000; /* نارنجی */
}
.stat-card.users {
    border-left-color: #1e88e5; /* آبی */
}
.stat-card.novels {
    border-left-color: #43a047; /* سبز */
}
</style>

<div class="page-header">
    <h2>نمای کلی سایت</h2>
</div>

<div class="stat-grid">
    <a href="approve_chapters.php" class="stat-card pending">
        <h3>چپترهای در انتظار تایید</h3>
        <p class="stat-number"><?php echo $stats['pending_chapters']; ?></p>
    </a>
    
    <a href="#" class="stat-card novels"> <!-- در آینده به manage_novels.php لینک می‌شود -->
        <h3>مجموع کل آثار</h3>
        <p class="stat-number"><?php echo $stats['total_novels']; ?></p>
    </a>

    <a href="#" class="stat-card"> <!-- این کارت می‌تواند به لیستی از تمام چپترها لینک شود -->
        <h3>چپترهای منتشر شده</h3>
        <p class="stat-number"><?php echo $stats['approved_chapters']; ?></p>
    </a>
    
    <a href="#" class="stat-card users"> <!-- در آینده به manage_users.php لینک می‌شود -->
        <h3>تعداد کل کاربران</h3>
        <p class="stat-number"><?php echo $stats['total_users']; ?></p>
    </a>
</div>

<div class="page-header" style="margin-top: 40px;">
    <h2>دسترسی سریع</h2>
</div>
<div class="quick-actions">
    <!-- در اینجا می‌توانید لینک‌هایی به کارهای پرتکرار اضافه کنید -->
    <a href="../dashboard/create_novel.php" class="btn btn-primary">افزودن اثر جدید</a>
    <a href="approve_chapters.php" class="btn btn-secondary">بررسی چپترها</a>
</div>

<?php 
// --- گام ۴: فراخوانی فوتر پنل مدیریت ---
require_once 'footer.php'; 
?>
