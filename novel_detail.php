<?php
/*
=====================================================
    NovelWorld - Ultra Modern Detail Page (FINAL)
    Version: 5.1 (Full Webnovel Style Implementation)
=====================================================
    - Features: Immersive Header, Visual Stats, Modern Tabs,
      AJAX Library Integration, Dynamic Chapter List.
*/

// --- گام ۱: فراخوانی هدر و تنظیمات اولیه ---
require_once 'header.php';

// --- گام ۲: دریافت و اعتبارسنجی ID ---
$novel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// اگر ID نامعتبر بود، پیام خطا نمایش بده
if ($novel_id <= 0) {
    echo "<div style='color: white; text-align: center; padding: 100px;'>شناسه اثر نامعتبر است.</div>";
    require_once 'footer.php';
    exit();
}

// --- گام ۳: واکشی اطلاعات از دیتابیس ---
try {
    // ۳.۱: اطلاعات اصلی ناول
    $stmt = $conn->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt->execute([$novel_id]);
    $novel = $stmt->fetch();

    if (!$novel) {
        echo "<div style='color: white; text-align: center; padding: 100px;'>اثر مورد نظر یافت نشد.</div>";
        require_once 'footer.php';
        exit();
    }

    // ۳.۲: بررسی وضعیت در کتابخانه کاربر (اگر لاگین باشد)
    $is_in_library = false;
    if ($is_logged_in) {
        $stmt_lib = $conn->prepare("SELECT id FROM library_items WHERE user_id = ? AND novel_id = ?");
        $stmt_lib->execute([$user_id, $novel_id]);
        if ($stmt_lib->fetch()) {
            $is_in_library = true;
        }
    }

    // ۳.۳: واکشی چپترها (با منطق دسترسی نویسنده)
    $is_author = ($is_logged_in && $user_id == $novel['author_id']);
    
    $sql_chapters = "SELECT id, chapter_number, title, created_at, status, mana_price, published_at 
                     FROM chapters 
                     WHERE novel_id = ? ";
    
    // اگر کاربر نویسنده نیست، فقط چپترهای تایید شده و منتشر شده را ببیند
    if (!$is_author) {
        $sql_chapters .= "AND status = 'approved' AND published_at <= NOW() ";
    }
    
    $sql_chapters .= "ORDER BY chapter_number DESC"; // نمایش از جدیدترین به قدیمی‌ترین
    
    $stmt_chapters = $conn->prepare($sql_chapters);
    $stmt_chapters->execute([$novel_id]);
    $chapters = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);

    // ۳.۴: واکشی نظرات
    $stmt_comments = $conn->prepare("SELECT * FROM comments WHERE novel_id = ? AND chapter_id IS NULL ORDER BY created_at DESC");
    $stmt_comments->execute([$novel_id]);
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

    // ۳.۵: آمار (می‌توانید بعداً این‌ها را از دیتابیس واقعی بخوانید)
    // فعلاً تعداد بازدید را از دیتابیس نمی‌خوانیم (چون ستونش ممکن است نباشد)، یک عدد تصادفی برای زیبایی می‌گذاریم
    // یا اگر ستون views دارید، آن را اینجا جایگزین کنید.
    $view_count = isset($novel['views']) ? number_format($novel['views']) : number_format(rand(1000, 50000));
    $chapter_count = count($chapters);

} catch (PDOException $e) {
    error_log("Detail Page Error: " . $e->getMessage());
    echo "<div style='color: white; text-align: center; padding: 100px;'>خطای سیستمی رخ داد.</div>";
    require_once 'footer.php';
    exit();
}

// نگاشت وضعیت به فارسی
$status_map = [
    'ongoing' => 'در حال انتشار',
    'completed' => 'تکمیل شده',
    'hiatus' => 'متوقف شده'
];
$novel_status = $status_map[$novel['status']] ?? $novel['status'];
?>

<!-- لینک به فایل CSS اختصاصی (مطمئن شوید detail-style.css را ساخته‌اید) -->
<link rel="stylesheet" href="detail-style.css">

<main class="novel-detail-page">
    
    <!-- === بخش ۱: هدر ایمرسیو (Immersive Header) === -->
    <section class="immersive-header">
        <!-- پس‌زمینه بلور شده -->
        <div class="header-bg" style="background-image: url('<?php echo htmlspecialchars($novel['cover_url']); ?>');"></div>
        <div class="header-overlay"></div>
        
        <div class="header-content container">
            <!-- کاور کتاب -->
            <div class="book-cover-wrapper">
                <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>" class="book-cover">
                <!-- بج رتبه (نمایشی) -->
                <div class="rank-badge">
                    <span class="material-symbols-outlined">trophy</span>
                    <span>#<?php echo rand(1, 50); ?></span>
                </div>
            </div>
            
            <!-- اطلاعات کتاب -->
            <div class="book-info">
                <h1 class="book-title"><?php echo htmlspecialchars($novel['title']); ?></h1>
                
                <!-- اطلاعات متا (نویسنده، نوع، وضعیت) -->
                <div class="book-meta-row">
                    <div class="meta-item">
                        <span class="material-symbols-outlined icon">edit_square</span>
                        <span><?php echo htmlspecialchars($novel['author']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="material-symbols-outlined icon">category</span>
                        <span><?php echo htmlspecialchars($novel['type']); ?></span>
                    </div>
                    <div class="meta-item status-<?php echo $novel['status']; ?>">
                        <span class="material-symbols-outlined icon">update</span>
                        <span><?php echo $novel_status; ?></span>
                    </div>
                </div>

                <!-- باکس‌های آمار -->
                <div class="book-stats-grid">
                    <div class="stat-box">
                        <span class="stat-value">★ <?php echo htmlspecialchars($novel['rating']); ?></span>
                        <span class="stat-label">امتیاز</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value"><?php echo $chapter_count; ?></span>
                        <span class="stat-label">چپتر</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value"><?php echo $view_count; ?></span>
                        <span class="stat-label">بازدید</span>
                    </div>
                </div>

                <!-- تگ‌های ژانر -->
                <div class="book-tags">
                    <?php 
                    $genres = explode(',', $novel['genres']);
                    foreach($genres as $genre): 
                        $genre = trim($genre);
                        if(empty($genre)) continue;
                    ?>
                        <a href="search.php?genres[]=<?php echo urlencode($genre); ?>" class="tag-pill"><?php echo htmlspecialchars($genre); ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- دکمه‌های عملیات -->
                <div class="action-bar">
                    <?php if(!empty($chapters)): 
                        // پیدا کردن اولین چپتر برای دکمه "شروع خواندن"
                        // چون آرایه نزولی مرتب شده، آخرین عنصر، اولین چپتر است.
                        $first_chapter = end($chapters); 
                    ?>
                        <a href="read_chapter.php?id=<?php echo $first_chapter['id']; ?>" class="btn-read-main">
                            <span class="material-symbols-outlined">auto_stories</span>
                            شروع خواندن
                        </a>
                    <?php endif; ?>

                    <?php if($is_logged_in): ?>
                        <!-- دکمه افزودن به کتابخانه (AJAX) -->
                        <button id="library-btn" class="btn-icon <?php echo $is_in_library ? 'added' : ''; ?>" data-id="<?php echo $novel_id; ?>" title="<?php echo $is_in_library ? 'حذف از کتابخانه' : 'افزودن به کتابخانه'; ?>">
                            <span class="material-symbols-outlined"><?php echo $is_in_library ? 'bookmark_added' : 'bookmark_add'; ?></span>
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect_url=novel_detail.php?id=<?php echo $novel_id; ?>" class="btn-icon" title="برای افزودن به کتابخانه وارد شوید">
                            <span class="material-symbols-outlined">bookmark_add</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if($is_author): ?>
                        <a href="dashboard/edit_novel.php?id=<?php echo $novel_id; ?>" class="btn-icon settings" title="مدیریت اثر">
                            <span class="material-symbols-outlined">settings</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- === بخش ۲: محتوا و تب‌ها === -->
    <section class="content-body container">
        <!-- ناوبری تب‌ها -->
        <div class="tabs-nav">
            <button class="tab-btn active" data-target="about">درباره اثر</button>
            <button class="tab-btn" data-target="chapters">فهرست چپترها (<?php echo $chapter_count; ?>)</button>
            <button class="tab-btn" data-target="reviews">نظرات (<?php echo count($comments); ?>)</button>
        </div>

        <!-- تب ۱: درباره (خلاصه داستان) -->
        <div id="about" class="tab-content active">
            <div class="synopsis-card">
                <h3>خلاصه داستان</h3>
                <div class="synopsis-text">
                    <?php echo nl2br(htmlspecialchars($novel['summary'])); ?>
                </div>
            </div>
        </div>

        <!-- تب ۲: لیست چپترها -->
        <div id="chapters" class="tab-content">
            <?php if($is_author): ?>
                <div class="author-tools" style="margin-bottom: 20px; text-align: left;">
                    <a href="dashboard/manage_chapter.php?novel_id=<?php echo $novel_id; ?>" class="btn-read-main" style="display: inline-flex; font-size: 0.9rem; padding: 10px 20px;">
                        <span class="material-symbols-outlined">add</span> آپلود چپتر جدید
                    </a>
                </div>
            <?php endif; ?>

            <div class="chapter-list-grid">
                <?php if(empty($chapters)): ?>
                    <div class="empty-state" style="color: var(--text-muted); padding: 20px;">هنوز چپتری برای این اثر منتشر نشده است.</div>
                <?php else: ?>
                    <?php foreach($chapters as $ch): 
                        // بررسی وضعیت قفل بودن (ساده شده)
                        // در سیستم کامل، باید جدول purchased_chapters چک شود
                        $is_locked = ($ch['mana_price'] > 0 && !$is_author); 
                        $status_class = ($ch['status'] === 'approved') ? '' : 'pending-chapter';
                    ?>
                    <a href="read_chapter.php?id=<?php echo $ch['id']; ?>" class="chapter-row <?php echo $is_locked ? 'locked' : ''; ?> <?php echo $status_class; ?>">
                        <div class="ch-info">
                            <span class="ch-num">#<?php echo $ch['chapter_number']; ?></span>
                            <span class="ch-title"><?php echo htmlspecialchars($ch['title']); ?></span>
                            
                            <?php if($is_author && $ch['status'] != 'approved'): ?>
                                <span class="badge" style="background: orange; color: black; font-size: 0.7rem; margin-right: 5px; padding: 2px 5px; border-radius: 4px;">
                                    <?php echo $ch['status']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ch-meta">
                            <span class="ch-date"><?php echo date('Y/m/d', strtotime($ch['published_at'] ?? $ch['created_at'])); ?></span>
                            <?php if($is_locked): ?>
                                <span class="ch-lock">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">lock</span> 
                                    <?php echo $ch['mana_price']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- تب ۳: نظرات -->
        <div id="reviews" class="tab-content">
            <?php if($is_logged_in): ?>
                <div class="comment-input-box">
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="<?php echo $novel_id; ?>">
                        <textarea name="content" placeholder="نظر ارزشمند خود را بنویسید..." rows="3" required></textarea>
                        <button type="submit" class="btn-send-comment">ارسال نظر</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                    <p>برای ثبت نظر لطفاً <a href="login.php" style="color: var(--gold);">وارد شوید</a>.</p>
                </div>
            <?php endif; ?>

            <div class="comments-list" style="margin-top: 20px;">
                <?php if(empty($comments)): ?>
                    <p style="color: var(--text-muted);">هنوز نظری ثبت نشده است. اولین نفر باشید!</p>
                <?php else: ?>
                    <?php foreach($comments as $cm): ?>
                    <div class="comment-card">
                        <div class="user-avatar-placeholder">
                            <?php echo mb_substr($cm['user_name'], 0, 1, "UTF-8"); ?>
                        </div>
                        <div class="comment-content" style="flex: 1;">
                            <div class="comment-header">
                                <span class="c-user"><?php echo htmlspecialchars($cm['user_name']); ?></span>
                                <span class="c-date"><?php echo date('Y/m/d', strtotime($cm['created_at'])); ?></span>
                            </div>
                            <div class="c-text"><?php echo nl2br(htmlspecialchars($cm['content'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </section>
</main>

<!-- جاوااسکریپت برای تعاملات صفحه -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. مدیریت تب‌ها
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // غیرفعال کردن همه تب‌ها
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // فعال کردن تب کلیک شده
            tab.classList.add('active');
            const targetId = tab.dataset.target;
            const targetContent = document.getElementById(targetId);
            if(targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // 2. دکمه افزودن به کتابخانه (AJAX)
    const libBtn = document.getElementById('library-btn');
    if(libBtn) {
        libBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            libBtn.disabled = true; // جلوگیری از کلیک رگباری
            
            const novelId = libBtn.dataset.id;
            const iconSpan = libBtn.querySelector('span');

            try {
                const response = await fetch('toggle_library.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ novel_id: novelId })
                });

                if(!response.ok) throw new Error('Network error');

                const data = await response.json();

                if(data.success) {
                    if(data.action === 'added') {
                        libBtn.classList.add('added');
                        iconSpan.textContent = 'bookmark_added';
                        libBtn.title = 'حذف از کتابخانه';
                    } else {
                        libBtn.classList.remove('added');
                        iconSpan.textContent = 'bookmark_add';
                        libBtn.title = 'افزودن به کتابخانه';
                    }
                } else {
                    alert(data.message || 'خطایی رخ داد.');
                }
            } catch(error) {
                console.error('Library toggle error:', error);
                alert('خطا در ارتباط با سرور.');
            } finally {
                libBtn.disabled = false;
            }
        });
    }
});
</script>

<?php 
// --- گام ۴: فراخوانی فوتر ---
require_once 'footer.php'; 
?>
