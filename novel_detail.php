// novel_detail.php

<?php
/*
=====================================================
    NovelWorld - Novel Detail Page
    Version: 2.1 (Final - With Library & Chapters)
=====================================================
    - این صفحه جزئیات کامل یک ناول، لیست چپترها و نظرات را نمایش می‌دهد.
    - دکمه "افزودن به کتابخانه" را به صورت داینامیک مدیریت می‌کند.
    - دکمه‌های مدیریت چپتر را فقط به نویسنده اثر نمایش می‌دهد.
*/

// --- گام ۱: فراخوانی هدر اصلی سایت ---
require_once 'header.php';


// --- گام ۲: دریافت و اعتبارسنجی ID اثر از URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا: شناسه اثر نامعتبر است.</div>");
}
$novel_id = intval($_GET['id']);


// --- گام ۳: واکشی جامع اطلاعات از دیتابیس ---
try {
    // ۱. واکشی اطلاعات اصلی ناول
    $stmt_novel = $conn->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt_novel->execute([$novel_id]);
    $novel = $stmt_novel->fetch();

    if (!$novel) {
        die("<div style='text-align:center; padding: 50px; color: white;'>خطا: اثری با این شناسه یافت نشد.</div>");
    }

    // ۲. واکشی لیست چپترها
    $stmt_chapters = $conn->prepare("SELECT id, chapter_number, title, created_at FROM chapters WHERE novel_id = ? ORDER BY chapter_number ASC");
    $stmt_chapters->execute([$novel_id]);
    $chapters_list = $stmt_chapters->fetchAll();
    
    // ۳. واکشی تمام نظرات و پاسخ‌ها
    $stmt_comments = $conn->prepare("SELECT * FROM comments WHERE novel_id = ? AND chapter_id IS NULL ORDER BY created_at ASC");
    $stmt_comments->execute([$novel_id]);
    $all_comments_results = $stmt_comments->fetchAll();

} catch (PDOException $e) {
    error_log("Novel Detail Fetch Error: " . $e->getMessage());
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا در بارگذاری اطلاعات.</div>");
}


// --- گام ۴: پردازش و مرتب‌سازی نظرات ---
$comments = [];
$replies = [];
foreach ($all_comments_results as $row) {
    if ($row['parent_id'] === null) {
        $comments[] = $row;
    } else {
        $replies[$row['parent_id']][] = $row;
    }
}


// --- گام ۵: بررسی‌های شرطی برای نمایش دکمه‌ها ---

// آیا کاربر فعلی، نویسنده این اثر است؟
$is_author = ($is_logged_in && $user_id == $novel['author_id']);

// آیا این ناول در کتابخانه کاربر فعلی وجود دارد؟
$is_in_library = false;
if ($is_logged_in) {
    try {
        $stmt_check = $conn->prepare("SELECT id FROM library_items WHERE user_id = ? AND novel_id = ?");
        $stmt_check->execute([$user_id, $novel_id]);
        if ($stmt_check->fetch()) {
            $is_in_library = true;
        }
    } catch (PDOException $e) {
        // خطا در بررسی کتابخانه نباید باعث از کار افتادن کل صفحه شود
        error_log("Library check failed: " . $e->getMessage());
    }
}

// --- گام ۶: رندر کردن بخش HTML ---
?>
<title><?php echo htmlspecialchars($novel['title']); ?> - NovelWorld</title>
<link rel="stylesheet" href="detail-style.css">

<style>
/* استایل سفارشی برای دکمه حذف از کتابخانه */
.btn-danger {
    background-color: #d32f2f;
    color: white;
}
.btn-danger:hover {
    background-color: #c62828;
}
</style>

<div class="detail-container">
    <section class="hero-section" style="background-image: url('<?php echo htmlspecialchars($novel['cover_url']); ?>');">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="کاور <?php echo htmlspecialchars($novel['title']); ?>" class="hero-cover-img">
            <div class="hero-title-box">
                <h1 class="hero-title"><?php echo htmlspecialchars($novel['title']); ?></h1>
            </div>
        </div>
    </section>

    <section class="info-panel">
        <div class="info-grid">
            <div class="info-item"><span>امتیاز</span><strong><?php echo htmlspecialchars($novel['rating']); ?> ★</strong></div>
            <div class="info-item"><span>وضعیت</span><strong><?php echo htmlspecialchars($novel['status'] ?? 'نامشخص'); ?></strong></div>
            <div class="info-item"><span>نویسنده</span><strong><?php echo htmlspecialchars($novel['author'] ?? 'نامشخص'); ?></strong></div>
            <div class="info-item"><span>آرتیست</span><strong><?php echo htmlspecialchars($novel['artist'] ?? 'نامشخص'); ?></strong></div>
        </div>
        <div class="genres-box">
            <?php 
                $genres = explode(',', $novel['genres']);
                foreach ($genres as $genre) {
                    echo '<span class="genre-tag">' . htmlspecialchars(trim($genre)) . '</span>';
                }
            ?>
        </div>
        <div class="action-buttons">
            <?php if (!empty($chapters_list)): ?>
                <a href="read_chapter.php?id=<?php echo $chapters_list[0]['id']; ?>" class="btn btn-primary">شروع خواندن</a>
            <?php endif; ?>
            
            <?php if ($is_logged_in && !$is_author): // فقط به کاربر عادی (نه نویسنده) نمایش بده ?>
                <button id="library-toggle-btn" class="btn <?php echo $is_in_library ? 'btn-danger' : 'btn-secondary'; ?>" data-novel-id="<?php echo $novel['id']; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px; vertical-align: middle;"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                    <span><?php echo $is_in_library ? 'حذف از کتابخانه' : 'افزودن به کتابخانه'; ?></span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <section class="tab-system">
        <div class="tab-links">
            <button class="tab-link active" data-tab="summary">خلاصه</button>
            <button class="tab-link" data-tab="chapters">لیست چپترها (<?php echo count($chapters_list); ?>)</button>
            <button class="tab-link" data-tab="comments">نظرات (<?php echo count($comments); ?>)</button>
        </div>

        <div id="summary" class="tab-content active">
            <p><?php echo nl2br(htmlspecialchars($novel['summary'])); ?></p>
        </div>

        <div id="chapters" class="tab-content">
            <?php if ($is_author): ?>
                <div class="author-actions-header">
                    <a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel['id']; ?>" class="btn-add-chapter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>افزودن چپتر جدید</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($chapters_list)): ?>
                <p style="text-align: center; margin-top: 20px;">هنوز چپتری برای این ناول منتشر نشده است.</p>
            <?php else: ?>
                <ul class="chapter-list">
                    <?php foreach ($chapters_list as $chapter): ?>
                        <li class="chapter-item">
                            <a href="read_chapter.php?id=<?php echo $chapter['id']; ?>">
                                چپتر <?php echo htmlspecialchars($chapter['chapter_number']); ?>: <?php echo htmlspecialchars($chapter['title']); ?>
                                <span style="font-size: 0.8em; color: var(--text-secondary-color); margin-right: 10px;">- <?php echo date("Y/m/d", strtotime($chapter['created_at'])); ?></span>
                            </a>
                            <?php if ($is_author): ?>
                                <div class="chapter-author-tools">
                                    <a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel['id']; ?>&chapter_id=<?php echo $chapter['id']; ?>" class="tool-btn edit-btn" title="ویرایش">✏️</a>
                                    <a href="dashboard/delete_chapter.php?novel_id=<?php echo $novel['id']; ?>&chapter_id=<?php echo $chapter['id']; ?>" class="tool-btn delete-btn" title="حذف" onclick="return confirm('آیا از حذف این چپتر مطمئن هستید؟ این عمل غیرقابل بازگشت است.');">🗑️</a>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div id="comments" class="tab-content">
            <?php // ... بخش نظرات شما بدون تغییر باقی می‌ماند ... ?>
        </div>
    </section>
</div>

<!-- اسکریپت‌های مورد نیاز -->
<script src="detail-script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('library-toggle-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            toggleBtn.disabled = true; // جلوگیری از کلیک‌های متعدد
            const novelId = toggleBtn.dataset.novelId;
            const btnSpan = toggleBtn.querySelector('span');

            try {
                const response = await fetch('toggle_library.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ novel_id: novelId })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'خطای سرور');
                }

                const data = await response.json();

                if (data.success) {
                    if (data.action === 'added') {
                        if (btnSpan) btnSpan.textContent = 'حذف از کتابخانه';
                        toggleBtn.classList.remove('btn-secondary');
                        toggleBtn.classList.add('btn-danger');
                    } else {
                        if (btnSpan) btnSpan.textContent = 'افزودن به کتابخانه';
                        toggleBtn.classList.remove('btn-danger');
                        toggleBtn.classList.add('btn-secondary');
                    }
                } else {
                    alert(data.message || 'خطایی رخ داد.');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                toggleBtn.disabled = false; // فعال کردن مجدد دکمه
            }
        });
    }
});
</script>

<?php 
require_once 'footer.php'; 
?>
