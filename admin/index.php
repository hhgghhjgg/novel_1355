<?php
// admin/index.php

/*
=====================================================
    NovelWorld - Admin Dashboard Index
    Version: 1.1 (With Translation Stats)
=====================================================
    - این صفحه به عنوان صفحه اصلی پنل مدیریت عمل می‌کند.
    - آمارهای کلیدی سایت، شامل تفکیک آثار تالیفی و ترجمه شده را نمایش می‌دهد.
*/

// --- گام ۱: فراخوانی هدر پنل مدیریت ---
require_once 'header.php';


// --- گام ۲: واکشی آمار کلی از دیتابیس ---
$stats = [
    'pending_chapters' => 0,
    'total_users' => 0,
    'total_novels' => 0,
    'original_works' => 0,
    'translated_works' => 0,
];

try {
    // شمارش چپترهای در انتظار تایید
    $stats['pending_chapters'] = $conn->query("SELECT COUNT(*) FROM chapters WHERE status = 'pending'")->fetchColumn();
    
    // شمارش کل کاربران
    $stats['total_users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // شمارش کل آثار
    $stats['total_novels'] = $conn->query("SELECT COUNT(*) FROM novels")->fetchColumn();

    // شمارش آثار تالیفی
    $stats['original_works'] = $conn->query("SELECT COUNT(*) FROM novels WHERE origin = 'original'")->fetchColumn();

    // شمارش آثار ترجمه شده
    $stats['translated_works'] = $conn->query("SELECT COUNT(*) FROM novels WHERE origin = 'translated'")->fetchColumn();

} catch (PDOException $e) {
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
    display: block;
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
/* استایل‌های رنگی برای تمایز کارت‌ها */
.stat-card.pending { border-left-color: #ffa000; } /* نارنجی */
.stat-card.users { border-left-color: #1e88e5; } /* آبی */
.stat-card.originals { border-left-color: #43a047; } /* سبز */
.stat-card.translated { border-left-color: #8e24aa; } /* بنفش */
.quick-actions { display: flex; gap: 15px; flex-wrap: wrap; }
</style>

<div class="page-header">
    <h2>نمای کلی سایت</h2>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'work_added'): ?>
    <div class="success-box" style="margin-bottom: 20px; background-color: #2e7d32; color: white; padding: 15px; border-radius: 8px;">
        اثر ترجمه شده با موفقیت اضافه شد.
    </div>
<?php endif; ?>

<div class="stat-grid">
    <a href="approve_chapters.php" class="stat-card pending">
        <h3>چپترهای در انتظار تایید</h3>
        <p class="stat-number"><?php echo $stats['pending_chapters']; ?></p>
    </a>
    
    <a href="manage_novels.php" class="stat-card originals">
        <h3>آثار تالیفی</h3>
        <p class="stat-number"><?php echo $stats['original_works']; ?></p>
    </a>

    <a href="manage_novels.php?origin=translated" class="stat-card translated">
        <h3>آثار ترجمه شده</h3>
        <p class="stat-number"><?php echo $stats['translated_works']; ?></p>
    </a>
    
    <a href="manage_users.php" class="stat-card users">
        <h3>تعداد کل کاربران</h3>
        <p class="stat-number"><?php echo $stats['total_users']; ?></p>
    </a>
</div>

<div class="page-header" style="margin-top: 40px;">
    <h2>دسترسی سریع</h2>
</div>
<div class="quick-actions">
    <a href="add_translated_work.php" class="btn btn-primary">افزودن اثر ترجمه شده</a>
    <a href="../dashboard/create_novel.php" class="btn btn-secondary">افزودن اثر تالیفی (نمای کاربر)</a>
    <a href="approve_chapters.php" class="btn btn-secondary">بررسی چپترها</a>
</div>

<?php 
// --- گام ۴: فراخوانی فوتر پنل مدیریت ---
require_once 'footer.php'; 
?>
