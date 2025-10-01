<?php
/*
=====================================================
    NovelWorld - Lock Latest Chapters (Cron Job Script)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت توسط یک سرویس Cron Job خارجی (مانند cron-job.org)
      به صورت دوره‌ای (مثلاً هر ساعت) اجرا می‌شود.
    - وظیفه آن، به‌روزرسانی خودکار وضعیت قفل بودن چپترهای آخر هر اثر است.
    - برای امنیت، در ابتدای اجرا یک کلید مخفی را بررسی می‌کند.
*/

// --- گام ۱: تنظیمات اولیه ---
// نادیده گرفتن محدودیت زمان اجرای اسکریپت (مهم برای پردازش‌های طولانی)
set_time_limit(0);
// نادیده گرفتن قطع ارتباط کاربر (چون این اسکریپت توسط ربات اجرا می‌شود)
ignore_user_abort(true);

// --- گام ۲: بررسی امنیتی کلید مخفی ---
$cron_secret_key = getenv('CRON_SECRET_KEY');
if ($cron_secret_key === false || !isset($_GET['secret']) || $_GET['secret'] !== $cron_secret_key) {
    header('HTTP/1.0 403 Forbidden');
    die('ACCESS DENIED: Invalid or missing secret key.');
}

// --- گام ۳: فراخوانی فایل‌های مورد نیاز ---
require_once 'db_connect.php';

// --- گام ۴: نمایش پیام شروع (برای لاگ‌های Cron Job) ---
// استفاده از تگ <pre> باعث می‌شود خروجی در لاگ‌های Cron-Job.org خواناتر باشد.
echo "<pre>";
echo "=============================================\n";
echo " Chapter Locking Cron Job Started\n";
echo " Time: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================\n\n";

// --- گام ۵: تعریف تنظیمات قفل‌گذاری ---
$novels_lock_count = 50; // تعداد چپترهای آخر ناول که باید قفل شوند
$comics_lock_count = 10; // تعداد چپترهای آخر مانهوا/مانگا که باید قفل شوند
$default_mana_price = 5; // قیمت پیش‌فرض برای هر چپتر قفل شده

// --- گام ۶: منطق اصلی و تعامل با دیتابیس ---
try {
    // ۱. تمام آثار (novels) را از دیتابیس واکشی کن
    $all_works_stmt = $conn->query("SELECT id, type, title FROM novels");
    $all_works = $all_works_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($all_works)) {
        echo "No works found to process. Exiting.\n";
        exit;
    }

    echo "Found " . count($all_works) . " work(s) to process...\n\n";

    // ۲. برای هر اثر، فرآیند قفل‌گذاری را اجرا کن
    foreach ($all_works as $work) {
        $novel_id = $work['id'];
        $lock_count = ($work['type'] === 'novel') ? $novels_lock_count : $comics_lock_count;

        echo "Processing: '" . htmlspecialchars($work['title']) . "' (ID: $novel_id) with lock count: $lock_count\n";

        // ۳. تمام چپترهای منتشر شده یک اثر را به ترتیب شماره (از آخر به اول) پیدا کن
        $chapters_stmt = $conn->prepare(
            "SELECT id FROM chapters 
             WHERE novel_id = ? AND status IN ('approved', 'published') 
             ORDER BY chapter_number DESC"
        );
        $chapters_stmt->execute([$novel_id]);
        $all_chapter_ids = $chapters_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (count($all_chapter_ids) < $lock_count) {
            echo " -> Not enough chapters to lock. Skipping.\n";
            continue; // اگر تعداد چپترها کمتر از حد نصاب قفل بود، به سراغ اثر بعدی برو
        }

        // ۴. چپترها را به دو دسته "قفل" و "رایگان" تقسیم کن
        $chapters_to_lock = array_slice($all_chapter_ids, 0, $lock_count);
        $chapters_to_unlock = array_slice($all_chapter_ids, $lock_count);

        // ۵. وضعیت را در دیتابیس آپدیت کن
        // شروع تراکنش برای اطمینان از صحت عملیات
        $conn->beginTransaction();

        // قفل کردن چپترهای آخر
        if (!empty($chapters_to_lock)) {
            $placeholders_lock = implode(',', array_fill(0, count($chapters_to_lock), '?'));
            $stmt_lock = $conn->prepare("UPDATE chapters SET mana_price = ? WHERE id IN ($placeholders_lock)");
            $params_lock = array_merge([$default_mana_price], $chapters_to_lock);
            $stmt_lock->execute($params_lock);
            echo " -> Locked " . $stmt_lock->rowCount() . " chapter(s).\n";
        }
        
        // رایگان کردن بقیه چپترها
        if (!empty($chapters_to_unlock)) {
            $placeholders_unlock = implode(',', array_fill(0, count($chapters_to_unlock), '?'));
            $stmt_unlock = $conn->prepare("UPDATE chapters SET mana_price = NULL WHERE id IN ($placeholders_unlock)");
            $stmt_unlock->execute($chapters_to_unlock);
            echo " -> Unlocked " . $stmt_unlock->rowCount() . " chapter(s).\n";
        }

        // تایید نهایی تراکنش
        $conn->commit();
        echo " -> Done.\n\n";
    }

} catch (PDOException $e) {
    // در صورت بروز خطا، تراکنش را لغو کرده و خطا را نمایش می‌دهیم
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
}

// --- گام ۷: نمایش پیام پایان ---
echo "=============================================\n";
echo " Cron Job Finished\n";
echo " Time: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================\n";
echo "</pre>";
?>
