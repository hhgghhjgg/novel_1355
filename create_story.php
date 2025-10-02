<?php
// create_story.php

/*
=====================================================
    NovelWorld - Create Novel Story (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) به نویسندگان اجازه می‌دهد تا
      یکی از آثار خود را به عنوان "استوری ناول" اضافه کنند.
    - محدودیت تعداد استوری‌های فعال را بررسی می‌کند.
    - خروجی آن در فرمت JSON است.
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
if (!$is_logged_in) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'برای ایجاد استوری، لطفاً ابتدا وارد شوید.']);
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$novel_id = isset($data['novel_id']) ? intval($data['novel_id']) : 0;
$title = isset($data['title']) ? trim($data['title']) : null; // عنوان استوری اختیاری است

if ($novel_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'شناسه ناول نامعتبر است.']);
    exit();
}

// --- گام ۴: منطق اصلی ایجاد استوری ---
$max_stories = 10; // حداکثر تعداد استوری‌های فعال برای هر کاربر

try {
    // شروع تراکنش
    $conn->beginTransaction();

    // ۱. بررسی مالکیت ناول توسط نویسنده
    $stmt_owner = $conn->prepare("SELECT id FROM novels WHERE id = ? AND author_id = ?");
    $stmt_owner->execute([$novel_id, $user_id]);
    if (!$stmt_owner->fetch()) {
        throw new Exception("شما فقط می‌توانید آثار خودتان را به استوری اضافه کنید.");
    }

    // ۲. بررسی اینکه آیا این ناول از قبل در استوری‌های فعال کاربر وجود دارد یا نه
    $stmt_exists = $conn->prepare("SELECT id FROM novel_stories WHERE user_id = ? AND novel_id = ? AND expires_at > NOW()");
    $stmt_exists->execute([$user_id, $novel_id]);
    if ($stmt_exists->fetch()) {
        throw new Exception("این اثر از قبل در استوری‌های شما وجود دارد.");
    }

    // ۳. شمارش تعداد استوری‌های فعال فعلی کاربر
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM novel_stories WHERE user_id = ? AND expires_at > NOW()");
    $stmt_count->execute([$user_id]);
    $current_story_count = (int)$stmt_count->fetchColumn();

    if ($current_story_count >= $max_stories) {
        throw new Exception("شما به محدودیت {$max_stories} استوری فعال رسیده‌اید. لطفاً تا منقضی شدن استوری‌های قبلی صبر کنید.");
    }

    // ۴. ایجاد استوری جدید
    // استوری‌ها به مدت ۲۴ ساعت فعال خواهند بود
    $expires_at = date('Y-m-d H:i:s', time() + (24 * 3600));

    $sql = "INSERT INTO novel_stories (user_id, novel_id, title, expires_at) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql);
    $stmt_insert->execute([$user_id, $novel_id, $title, $expires_at]);

    $new_story_id = $conn->lastInsertId();

    // تایید نهایی تراکنش
    $conn->commit();

    // ارسال پاسخ موفقیت‌آمیز
    echo json_encode([
        'success' => true,
        'story_id' => $new_story_id,
        'message' => 'ناول با موفقیت به استوری‌های شما اضافه شد.'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Create Story Error: " . $e->getMessage());
    http_response_code(400); // Bad Request (معمولاً خطا از سمت کاربر است)
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
