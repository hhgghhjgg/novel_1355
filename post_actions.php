<?php
// post_actions.php

/*
=====================================================
    NovelWorld - Post Actions (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای لایک/دیسلایک کردن
      پست‌ها توسط کاربران عمل می‌کند.
    - برای جلوگیری از رای تکراری، از جدول `comment_votes` با یک نوع جدید
      ('post_like'/'post_dislike') استفاده می‌کند.
    - خروجی آن همیشه در فرمت JSON است.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---
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
    echo json_encode(['success' => false, 'message' => 'برای ثبت رای، لطفاً ابتدا وارد شوید.']);
    exit();
}

// --- گام ۳: دریافت و اعتبarsanjy ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? intval($data['post_id']) : 0;
$action = isset($data['action']) ? $data['action'] : '';

if ($post_id <= 0 || !in_array($action, ['like', 'dislike'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر است.']);
    exit();
}


// --- گام ۴: منطق اصلی (پردازش رای) ---

// ما از همان جدول comment_votes استفاده می‌کنیم، اما با یک شناسه متفاوت
// برای جلوگیری از تداخل، ID پست را منفی می‌کنیم یا از یک ستون type جدید استفاده می‌کنیم.
// روش ساده‌تر: استفاده از ID منفی برای پست‌ها.
// این یک ترفند است. روش اصولی‌تر، اضافه کردن ستون `item_type` به جدول `comment_votes` است.
$item_id_for_vote_table = -$post_id; 
$vote_type_for_db = 'post_' . $action;

try {
    // شروع تراکنش
    $conn->beginTransaction();

    // ۱. بررسی اینکه آیا کاربر قبلاً به این پست رأی داده است یا نه
    $stmt_check = $conn->prepare("SELECT id FROM comment_votes WHERE user_id = ? AND comment_id = ?");
    $stmt_check->execute([$user_id, $item_id_for_vote_table]);

    if ($stmt_check->fetch()) {
        $conn->rollBack();
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'شما قبلاً به این پست رأی داده‌اید.']);
        exit();
    }

    // ۲. ثبت رای جدید در جدول `comment_votes`
    // (توجه: ما از ستون comment_id برای ذخیره ID آیتم استفاده می‌کنیم)
    $stmt_insert_vote = $conn->prepare("INSERT INTO comment_votes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
    $stmt_insert_vote->execute([$user_id, $item_id_for_vote_table, $vote_type_for_db]);

    // ۳. آپدیت کردن ستون مربوطه (likes یا dislikes) در جدول `posts`
    $column_to_update = ($action === 'like') ? 'likes' : 'dislikes';
    
    // استفاده از کوئری UPDATE ... RETURNING برای دریافت مقدار جدید (ویژگی PostgreSQL)
    $stmt_update = $conn->prepare("UPDATE posts SET {$column_to_update} = {$column_to_update} + 1 WHERE id = ? RETURNING likes, dislikes");
    $stmt_update->execute([$post_id]);
    
    $new_counts = $stmt_update->fetch();
    if (!$new_counts) {
        throw new Exception('پست مورد نظر یافت نشد.');
    }

    // ۴. تایید نهایی تراکنش
    $conn->commit();

    // ۵. ارسال پاسخ موفقیت‌آمیز به کاربر
    echo json_encode([
        'success' => true, 
        'message' => 'رای شما با موفقیت ثبت شد.',
        'data' => [
            'new_likes' => (int)$new_counts['likes'],
            'new_dislikes' => (int)$new_counts['dislikes']
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Post Action Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
