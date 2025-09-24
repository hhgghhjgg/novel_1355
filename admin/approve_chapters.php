<?php
// admin/approve_chapters.php

/*
=====================================================
    NovelWorld - Approve Chapters Page
    Version: 1.0
=====================================================
    - این صفحه لیستی از تمام چپترهایی که توسط نویسندگان ارسال شده و
      در وضعیت 'pending' (در انتظار تایید) هستند را نمایش می‌دهد.
    - به مدیر اجازه می‌دهد تا چپترها را پیش‌نمایش کرده، تایید یا رد کند.
*/

// --- گام ۱: فراخوانی هدر پنل مدیریت ---
// این فایل مسئولیت احراز هویت و بررسی نقش ادمین را بر عهده دارد.
require_once 'header.php';


// --- گام ۲: واکشی چپترهای در انتظار تایید از دیتابیس ---
$pending_chapters = []; // آرایه‌ای برای نگهداری نتایج

try {
    // واکشی تمام چپترهای در انتظار تایید به همراه اطلاعات ناول و نویسنده
    $sql = "SELECT 
                c.id, 
                c.title, 
                c.chapter_number, 
                n.title as novel_title,
                n.id as novel_id,
                u.username as author_name
            FROM chapters c
            JOIN novels n ON c.novel_id = n.id
            JOIN users u ON n.author_id = u.id
            WHERE c.status = 'pending'
            ORDER BY c.created_at ASC"; // چپترهای قدیمی‌تر اول نمایش داده می‌شوند

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pending_chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Approve Chapters Fetch Error: " . $e->getMessage());
    die("خطایی در واکشی لیست چپترها رخ داد.");
}


// --- گام ۳: رندر کردن بخش HTML ---
?>
<title>تایید چپترها - پنل مدیریت</title>

<!-- استایل‌های سفارشی برای جدول -->
<style>
.table-container {
    background-color: var(--dash-surface);
    border-radius: 12px;
    padding: 20px;
    overflow-x: auto; /* برای نمایش بهتر در موبایل */
}
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th, .data-table td {
    padding: 12px 15px;
    text-align: right;
    border-bottom: 1px solid var(--dash-border);
}
.data-table thead th {
    background-color: var(--dash-bg);
    font-weight: 500;
    color: var(--dash-text-secondary);
}
.data-table tbody tr:hover {
    background-color: var(--dash-bg);
}
.actions a {
    margin-left: 10px;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    transition: opacity 0.2s;
}
.actions a:hover {
    opacity: 0.8;
}
.preview-btn { background: #1e88e5; color: white; }
.approve-btn { background: #2e7d32; color: white; }
.reject-btn { background: #c62828; color: white; }
.no-pending-message {
    text-align: center;
    padding: 40px;
    color: var(--dash-text-secondary);
}
</style>

<div class="page-header">
    <h2>چپترهای در انتظار تایید</h2>
    <p style="color: var(--dash-text-secondary);">در حال حاضر <b><?php echo count($pending_chapters); ?></b> چپتر منتظر بررسی شما هستند.</p>
</div>

<div class="table-container">
    <?php if (empty($pending_chapters)): ?>
        <div class="no-pending-message">
            <p>✅</p>
            <p>عالی! هیچ چپتری در حال حاضر منتظر تایید نیست.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>نام اثر</th>
                    <th>عنوان چپتر</th>
                    <th>نویسنده</th>
                    <th style="text-align: center;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_chapters as $chapter): ?>
                <tr>
                    <td>
                        <a href="../novel_detail.php?id=<?php echo $chapter['novel_id']; ?>" target="_blank" title="مشاهده صفحه اثر">
                            <?php echo htmlspecialchars($chapter['novel_title']); ?>
                        </a>
                    </td>
                    <td>چپتر <?php echo htmlspecialchars($chapter['chapter_number']) . ': ' . htmlspecialchars($chapter['title']); ?></td>
                    <td><?php echo htmlspecialchars($chapter['author_name']); ?></td>
                    <td class="actions" style="text-align: center;">
                        <a href="../read_chapter.php?id=<?php echo $chapter['id']; ?>&preview=true" class="preview-btn" target="_blank" title="مشاهده محتوای چپتر در تب جدید">پیش‌نمایش</a>
                        <a href="chapter_action.php?action=approve&id=<?php echo $chapter['id']; ?>" class="approve-btn">تایید و انتشار</a>
                        <a href="chapter_action.php?action=reject&id=<?php echo $chapter['id']; ?>" class="reject-btn" onclick="return confirm('آیا از رد کردن این چپتر مطمئن هستید؟');">رد کردن</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php 
// --- گام ۴: فراخوانی فوتر پنل مدیریت ---
require_once 'footer.php'; 
?>
