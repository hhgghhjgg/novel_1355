<?php
/*
=====================================================
    NovelWorld - Claim Chapter Reward (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged, Secure)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای اعطای پاداش "مانا" پس از
      خواندن کامل یک چپتر از آثار تالیفی (ایرانی) عمل می‌کند.
    - دارای منطق ضد تقلب قوی برای جلوگیری از دریافت چندباره پاداش است.
    - تمام عملیات در یک تراکنش دیتابیس انجام می‌شود.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---
// core.php شامل اتصال دیتابیس ($conn) و اطلاعات کاربر ($is_logged_in, $user_id) است.
require_once 'core.php';

// تنظیم هدر خروجی به application/json
header('Content-Type: application/json');


// --- گام ۲: بررسی‌های امنیتی و اولیه ---

// بررسی می‌کنیم که آیا اتصال به دیتابیس برقرار است یا نه
if ($conn === null) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'سرویس در حال حاضر در دسترس نیست.']);
    exit();
}

// بررسی لاگین بودن کاربر
if (!$is_logged_in) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'برای دریافت پاداش، لطفاً ابتدا وارد شوید.']);
    exit();
}


// --- گام ۳: دریافت و اعتبارسنجی ورودی ---

// داده‌ها به صورت JSON خام از بدنه درخواست ارسال می‌شوند.
$data = json_decode(file_get_contents('php://input'), true);
$chapter_id = isset($data['chapter_id']) ? intval($data['chapter_id']) : 0;

if ($chapter_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'شناسه چپتر نامعتبر است.']);
    exit();
}

// مقدار پاداش برای خواندن هر چپتر ایرانی
$reward_amount = 1;


// --- گام ۴: منطق اصلی (ضد تقلب و اعطای پاداش) ---

try {
    // شروع یک تراکنش (Transaction)
    // این تضمین می‌کند که تمام عملیات دیتابیس یا با هم موفق می‌شوند یا با هم لغو.
    $conn->beginTransaction();

    // ۱. (ضد تقلب) بررسی می‌کنیم که آیا کاربر قبلاً پاداش این چپتر را گرفته است یا نه.
    // ما جدول chapter_reads را برای این کار قفل می‌کنیم (FOR UPDATE) تا از race condition جلوگیری شود.
    $stmt_check = $conn->prepare("SELECT id FROM chapter_reads WHERE user_id = ? AND chapter_id = ? FOR UPDATE");
    $stmt_check->execute([$user_id, $chapter_id]);
    if ($stmt_check->fetch()) {
        $conn->rollBack(); // لغو تراکنش
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'شما قبلاً پاداش این چپتر را دریافت کرده‌اید.']);
        exit();
    }
    
    // ۲. (ضد تقلب) بررسی می‌کنیم که چپتر واقعاً متعلق به یک اثر تالیفی (ایرانی) باشد.
    $stmt_origin = $conn->prepare("SELECT n.origin FROM novels n JOIN chapters c ON n.id = c.novel_id WHERE c.id = ?");
    $stmt_origin->execute([$chapter_id]);
    $origin = $stmt_origin->fetchColumn();
    if ($origin !== 'original') {
        $conn->rollBack(); // لغو تراکنش
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'پاداش فقط برای خواندن آثار تالیفی (ایرانی) در نظر گرفته شده است.']);
        exit();
    }

    // ۳. ثبت خوانده شدن چپتر در جدول `chapter_reads` برای جلوگیری از تقلب در آینده
    $stmt_log = $conn->prepare("INSERT INTO chapter_reads (user_id, chapter_id) VALUES (?, ?)");
    $stmt_log->execute([$user_id, $chapter_id]);
    
    // ۴. افزایش موجودی مانای کاربر
    $stmt_update = $conn->prepare("UPDATE users SET mana_balance = mana_balance + ? WHERE id = ?");
    $stmt_update->execute([$reward_amount, $user_id]);
    
    // ۵. ثبت تراکنش در تاریخچه مالی کاربر برای شفافیت
    $description = "پاداش خواندن چپتر #" . $chapter_id;
    $stmt_trans = $conn->prepare("INSERT INTO mana_transactions (user_id, amount, description, related_chapter_id) VALUES (?, ?, ?, ?)");
    $stmt_trans->execute([$user_id, $reward_amount, $description, $chapter_id]);
    
    // اگر تمام مراحل موفقیت‌آمیز بود، تراکنش را تایید نهایی (commit) می‌کنیم.
    $conn->commit();

    // ارسال پاسخ موفقیت‌آمیز
    echo json_encode([
        'success' => true, 
        'message' => "تبریک! شما {$reward_amount} مانا برای خواندن این چپتر کسب کردید."
    ]);

} catch (PDOException $e) {
    // در صورت بروز هرگونه خطای دیتابیس، تراکنش را لغو می‌کنیم.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Claim Reward Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'خطایی در سرور رخ داد. لطفاً دوباره تلاش کنید.']);
}
?>
