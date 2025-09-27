<?php
// cron_processor.php

// این اسکریپت توسط سرویس Cron Job در Render فراخوانی خواهد شد.
// هیچ کاربری به صورت مستقیم به این فایل دسترسی ندارد.

require_once 'db_connect.php';
require_once 'telegram_notifier.php';

echo "Cron Job Started at " . date('Y-m-d H:i:s') . "\n";

try {
    // ۱. پیدا کردن چپترهایی که تایید شده، زمان انتشارشان فرا رسیده، اما هنوز نوتیفیکیشنشان ارسال نشده.
    // ما از ستون 'status' برای این کار استفاده می‌کنیم.
    // ابتدا وضعیت را به 'publishing' تغییر می‌دهیم تا دوباره انتخاب نشوند.
    $conn->exec("
        UPDATE chapters 
        SET status = 'publishing'
        WHERE status = 'approved' AND published_at <= NOW()
    ");

    $stmt = $conn->query(
        "SELECT c.id, c.novel_id, c.chapter_number, c.title,
                n.title as novel_title, n.cover_url, n.author
         FROM chapters c
         JOIN novels n ON c.novel_id = n.id
         WHERE c.status = 'publishing'"
    );
    $chapters_to_publish = $stmt->fetchAll();
    
    if (empty($chapters_to_publish)) {
        echo "No new chapters to publish.\n";
    } else {
        echo "Found " . count($chapters_to_publish) . " chapter(s) to publish.\n";
        
        foreach ($chapters_to_publish as $chapter) {
            // ۲. ارسال نوتیفیکیشن تلگرام برای هر چپتر
            $caption = "🔥 <b>چپتر جدید منتشر شد!</b> 🔥\n\n";
            $caption .= "<b>" . htmlspecialchars($chapter['novel_title']) . "</b>\n";
            $caption .= "<i>" . htmlspecialchars($chapter['author']) . "</i>";
            
            $success = sendTelegramNotification(
                $chapter['cover_url'],
                $caption,
                "📖 خواندن چپتر " . htmlspecialchars($chapter['chapter_number']),
                "read_chapter.php?id=" . $chapter['id']
            );

            if ($success) {
                // ۳. اگر نوتیفیکیشن موفق بود، وضعیت را به 'published' تغییر بده
                // (ما به یک وضعیت جدید نیاز داریم تا بدانیم این چپتر پردازش شده)
                $update_stmt = $conn->prepare("UPDATE chapters SET status = 'published' WHERE id = ?");
                $update_stmt->execute([$chapter['id']]);
                echo " - Published chapter ID: " . $chapter['id'] . "\n";
            } else {
                // اگر ناموفق بود، به وضعیت 'approved' برگردان تا در اجرای بعدی دوباره تلاش شود
                $update_stmt = $conn->prepare("UPDATE chapters SET status = 'approved' WHERE id = ?");
                $update_stmt->execute([$chapter['id']]);
                echo " - FAILED to publish chapter ID: " . $chapter['id'] . "\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
}

echo "Cron Job Finished at " . date('Y-m-d H:i:s') . "\n";
?>
