<?php
// create_post.php

/*
=====================================================
    NovelWorld - Create Post (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای ایجاد یک پست جدید
      توسط کاربر لاگین کرده عمل می‌کند.
    - ورودی را به صورت FormData (شامل متن و تصویر احتمالی) دریافت می‌کند.
    - خروجی آن در فرمت JSON است.
*/

// --- گام ۱: فراخوانی فایل هسته و نیازمندی‌ها ---
require_once 'core.php';
require_once 'vendor/autoload.php';

use Cloudinary\Cloudinary;

header('Content-Type: application/json');


// --- گام ۲: بررسی‌های امنیتی و اولیه ---

if ($conn === null) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'سرویس در حال حاضر در دسترس نیست.']);
    exit();
}

if (!$is_logged_in) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'برای ایجاد پست، لطفاً ابتدا وارد شوید.']);
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit();
}

$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (empty($content)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'محتوای پست نمی‌تواند خالی باشد.']);
    exit();
}

// --- گام ۴: منطق آپلود تصویر (در صورت وجود) ---
$image_url_for_db = null;

if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['post_image'];
    
    // اعتبارسنجی نوع و حجم فایل
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'فرمت فایل تصویر مجاز نیست (JPG, PNG, WebP, GIF).']);
        exit();
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.']);
        exit();
    }

    try {
        // آپلود تصویر به Cloudinary
        $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
        $uploadResult = $cloudinary->uploadApi()->upload($file['tmp_name'], [
            'folder' => "user_posts/{$user_id}",
            'transformation' => [
                ['width' => 1200, 'crop' => 'limit'], // تغییر اندازه برای بهینه‌سازی
                ['quality' => 'auto', 'fetch_format' => 'auto']
            ]
        ]);
        $image_url_for_db = $uploadResult['secure_url'];
    } catch (Exception $e) {
        error_log("Post Image Upload Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطایی در هنگام آپلود تصویر رخ داد.']);
        exit();
    }
}


// --- گام ۵: ذخیره پست در دیتابیس ---
try {
    $sql = "INSERT INTO posts (user_id, content, image_url, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $content, $image_url_for_db]);

    $new_post_id = $conn->lastInsertId();

    // ارسال پاسخ موفقیت‌آمیز
    echo json_encode([
        'success' => true,
        'post_id' => $new_post_id,
        'message' => 'پست شما با موفقیت منتشر شد.'
    ]);

} catch (PDOException $e) {
    error_log("Create Post DB Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'خطایی در ذخیره پست رخ داد.']);
}
?>
```
