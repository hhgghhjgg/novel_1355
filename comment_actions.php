// comment_actions.php

<?php
/*
=====================================================
    NovelWorld - Comment Actions (Like/Dislike)
    Version: 2.0 (Serverless Ready - JWT & DB Votes)
=====================================================
    - این فایل درخواست‌های AJAX برای لایک/دیسلایک کردن نظرات را مدیریت می‌کند.
    - هویت کاربر از طریق توکن JWT که در header.php پردازش شده، تایید می‌شود.
    - برای جلوگیری از رای تکراری، هر رای در جدول `comment_votes` دیتابیس ثبت می‌شود.
    - پاسخ‌ها همیشه در فرمت JSON هستند.
*/

// --- گام ۱: تنظیمات اولیه و فراخوانی فایل‌های مورد نیاز ---

// تنظیم هدر برای اطمینان از اینکه خروجی همیشه در فرمت JSON است
header('Content-Type: application/json');

// فراخوانی فایل هدر اصلی سایت
// این فایل شامل موارد زیر است:
// 1. اتصال به دیتابیس ($conn)
// 2. Autoloader کامپوزر
// 3. تابع get_current_user() و متغیرهای $is_logged_in و $user_id
require_once 'header.php'; 

// --- گام ۲: تعریف یک تابع برای ارسال پاسخ‌های JSON ---
// این کار به استانداردسازی خروجی‌ها کمک می‌کند.
function send_json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

// --- گام ۳: بررسی امنیت و مجوز دسترسی ---
if (!$is_logged_in) {
    send_json_response(false, 'برای ثبت رای، لطفاً ابتدا وارد شوید.');
}

// --- گام ۴: دریافت و پردازش درخواست ورودی ---
// داده‌ها به صورت JSON از طریق بدنه درخواست (body) ارسال می‌شوند.
$data = json_decode(file_get_contents('php://input'), true);

// اعتبارسنجی داده‌های ورودی
if (!isset($data['action']) || !isset($data['comment_id']) || !in_array($data['action'], ['like', 'dislike'])) {
    send_json_response(false, 'درخواست نامعتبر است.');
}

$action = $data['action'];
$comment_id = intval($data['comment_id']);

if ($comment_id <= 0) {
    send_json_response(false, 'شناسه کامنت نامعتبر است.');
}


// --- گام ۵: منطق اصلی و تعامل با دیتابیس ---
try {
    // شروع یک تراکنش (Transaction)
    // این تضمین می‌کند که تمام عملیات دیتابیس با هم موفق یا با هم ناموفق شوند.
    $conn->beginTransaction();

    // ۱. بررسی اینکه آیا کاربر قبلاً به این نظر رأی داده است یا نه
    $stmt_check = $conn->prepare("SELECT id FROM comment_votes WHERE user_id = ? AND comment_id = ?");
    $stmt_check->execute([$user_id, $comment_id]);

    if ($stmt_check->fetch()) {
        // اگر رکوردی پیدا شد، یعنی کاربر قبلاً رای داده است.
        $conn->rollBack(); // لغو تراکنش
        send_json_response(false, 'شما قبلاً به این نظر رأی داده‌اید.');
    }

    // ۲. ثبت رای جدید در جدول `comment_votes`
    $stmt_insert_vote = $conn->prepare("INSERT INTO comment_votes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
    $stmt_insert_vote->execute([$user_id, $comment_id, $action]);

    // ۳. آپدیت کردن ستون مربوطه (likes یا dislikes) در جدول `comments`
    $column_to_update = ($action === 'like') ? 'likes' : 'dislikes';
    
    // استفاده از کوئری UPDATE ... RETURNING برای دریافت مقدار جدید (ویژگی PostgreSQL)
    // این کار باعث می‌شود یک کوئری SELECT اضافی نزنیم.
    $stmt_update = $conn->prepare("UPDATE comments SET {$column_to_update} = {$column_to_update} + 1 WHERE id = ? RETURNING {$column_to_update}");
    $stmt_update->execute([$comment_id]);
    
    // دریافت تعداد جدید از نتیجه کوئری
    $result = $stmt_update->fetch();
    if (!$result) {
        // اگر کامنتی با این ID وجود نداشت، تراکنش را لغو کن
        throw new Exception('کامنت مورد نظر یافت نشد.');
    }
    $new_count = $result[$column_to_update];

    // ۴. اگر تمام مراحل موفقیت‌آمیز بود، تراکنش را تایید نهایی (commit) کن
    $conn->commit();

    // ۵. ارسال پاسخ موفقیت‌آمیز به کاربر
    send_json_response(true, 'رای شما با موفقیت ثبت شد.', ['new_count' => $new_count]);

} catch (PDOException $e) {
    // در صورت بروز هرگونه خطای دیتابیس، تراکنش را لغو کن
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // یک خطای عمومی به کاربر نشان بده و خطای واقعی را لاگ کن
    error_log("Comment Action Error: " . $e->getMessage());
    send_json_response(false, 'خطایی در سرور رخ داد. لطفاً دوباره تلاش کنید.');
} catch (Exception $e) {
    // مدیریت خطاهای دیگر (مانند کامنت یافت نشده)
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("General Error: " . $e->getMessage());
    send_json_response(false, $e->getMessage());
}
?>
