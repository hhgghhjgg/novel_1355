<?php
// load_next_stories.php

/*
=====================================================
    NovelWorld - Load Next Stories (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت برای پیاده‌سازی قابلیت "مشاهده بی‌پایان استوری" استفاده می‌شود.
    - پس از اتمام استوری‌های یک کاربر، به صورت هوشمند استوری‌های یک کاربر
      تصادفی دیگر (که کاربر فعلی او را دنبال نمی‌کند) را پیدا کرده و برمی‌گرداند.
    - خروجی آن همیشه در فرمت JSON است.
*/

// --- گام ۱: فراخوانی فایل هسته ---
require_once 'core.php';
header('Content-Type: application/json');


// --- گام ۲: بررسی‌های امنیتی و اولیه ---

if ($conn === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'سرویس در حال حاضر در دسترس نیست.']);
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
// شناسه کاربری که استوری‌هایش تمام شده است
$current_user_profile_id = isset($_GET['current_user_id']) ? intval($_GET['current_user_id']) : 0;
if ($current_user_profile_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'شناسه کاربر فعلی نامعتبر است.']);
    exit();
}


// --- گام ۴: منطق اصلی پیدا کردن کاربر بعدی ---
try {
    // ما به دنبال یک کاربر تصادفی می‌گردیم که:
    // ۱. خودش، کاربر لاگین کرده نباشد.
    // ۲. خودش، کاربری که استوری‌هایش تمام شده، نباشد.
    // ۳. حداقل یک استوری فعال داشته باشد.
    // ۴. (اختیاری) کاربر لاگین کرده، او را دنبال نکند (برای کشف افراد جدید).

    $sql = "
        SELECT u.id
        FROM users u
        WHERE 
            u.id != :current_user_profile_id
            AND EXISTS (
                SELECT 1 FROM novel_stories ns 
                WHERE ns.user_id = u.id AND ns.expires_at > NOW()
            )
    ";
    
    $params = [':current_user_profile_id' => $current_user_profile_id];

    if ($is_logged_in) {
        $sql .= " AND u.id != :logged_in_user_id ";
        $params[':logged_in_user_id'] = $user_id;
    }

    // اضافه کردن بخش تصادفی‌سازی
    $sql .= " ORDER BY RANDOM() LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $next_user_id = $stmt->fetchColumn();

    if (!$next_user_id) {
        // اگر هیچ کاربر دیگری با استوری فعال یافت نشد
        echo json_encode(['success' => false, 'message' => 'استوری دیگری برای نمایش یافت نشد.']);
        exit();
    }

    // --- گام ۵: واکشی استوری‌های کاربر جدید ---
    $stmt_stories = $conn->prepare(
        "SELECT s.id, s.title, n.cover_url, n.id as novel_id
         FROM novel_stories s
         JOIN novels n ON s.novel_id = n.id
         WHERE s.user_id = ? AND s.expires_at > NOW()
         ORDER BY s.created_at ASC"
    );
    $stmt_stories->execute([$next_user_id]);
    $next_stories_data = $stmt_stories->fetchAll(PDO::FETCH_ASSOC);

    // ارسال پاسخ موفقیت‌آمیز
    echo json_encode([
        'success' => true,
        'stories' => $next_stories_data
    ]);

} catch (PDOException $e) {
    error_log("Load Next Stories Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطایی در سرور رخ داد.']);
}
?>
