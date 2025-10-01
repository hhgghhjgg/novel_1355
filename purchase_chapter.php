<?php
/*
=====================================================
    NovelWorld - Purchase Chapter (AJAX Endpoint)
    Version: 1.0 (Final, Unabridged, Secure)
=====================================================
    - این اسکریپت به صورت پشت صحنه (AJAX) برای پردازش خرید یک چپتر
      قفل شده با استفاده از "مانا" عمل می‌کند.
    - تمام عملیات (بررسی موجودی، کسر مانا، ثبت خرید) در یک تراکنش
      امن دیتابیس انجام می‌شود.
    - خروجی آن همیشه در فرمت JSON است.
*/

// --- گام ۱: فراخوانی فایل هسته و تنظیم هدر ---
require_once 'core.php';
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
    echo json_encode(['success' => false, 'message' => 'برای خرید چپتر، لطفاً ابتدا وارد شوید.']);
    exit();
}

// --- گام ۳: دریافت و اعتبارسنجی ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
$chapter_id = isset($data['chapter_id']) ? intval($data['chapter_id']) : 0;

if ($chapter_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'شناسه چپتر نامعتبر است.']);
    exit();
}

// --- گام ۴: منطق اصلی (پردازش خرید) ---
try {
    // ۱. شروع یک تراکنش برای اطمینان از صحت تمام عملیات
    $conn->beginTransaction();

    // ۲. واکشی اطلاعات چپتر (قیمت) و موجودی کاربر در یک کوئری قفل شده (FOR UPDATE)
    //    این کار از race condition (تلاش برای خرید همزمان) جلوگیری می‌کند.
    $stmt = $conn->prepare(
        "SELECT c.mana_price, u.mana_balance 
         FROM chapters c, users u 
         WHERE c.id = ? AND u.id = ? FOR UPDATE"
    );
    $stmt->execute([$chapter_id, $user_id]);
    $data = $stmt->fetch();

    // ۳. بررسی‌های منطقی قبل از خرید
    if (!$data) {
        throw new Exception("اطلاعات کاربر یا چپتر یافت نشد.");
    }
    if ($data['mana_price'] === null || $data['mana_price'] <= 0) {
        throw new Exception("این چپتر رایگان است یا برای فروش نیست.");
    }
    
    $price = (int)$data['mana_price'];
    $balance = (int)$data['mana_balance'];

    // بررسی اینکه آیا کاربر قبلاً این چپتر را خریده است یا نه
    $stmt_check_purchase = $conn->prepare("SELECT id FROM purchased_chapters WHERE user_id = ? AND chapter_id = ?");
    $stmt_check_purchase->execute([$user_id, $chapter_id]);
    if ($stmt_check_purchase->fetch()) {
        throw new Exception("شما قبلاً این چپتر را خریداری کرده‌اید.");
    }

    // بررسی موجودی کافی
    if ($balance < $price) {
        throw new Exception("مانای شما برای خرید این چپتر کافی نیست. موجودی شما: {$balance} مانا");
    }
    
    // ۴. اجرای تراکنش (عملیات اصلی)
    
    // کسر مانا از موجودی کاربر
    $stmt_debit = $conn->prepare("UPDATE users SET mana_balance = mana_balance - ? WHERE id = ?");
    $stmt_debit->execute([$price, $user_id]);
    
    // ثبت خرید در جدول `purchased_chapters`
    $stmt_purchase = $conn->prepare("INSERT INTO purchased_chapters (user_id, chapter_id) VALUES (?, ?)");
    $stmt_purchase->execute([$user_id, $chapter_id]);
    
    // ثبت تراکنش در تاریخچه مالی کاربر
    $description = "خرید چپتر #" . $chapter_id;
    $stmt_trans = $conn->prepare("INSERT INTO mana_transactions (user_id, amount, description, related_chapter_id) VALUES (?, ?, ?, ?)");
    $stmt_trans->execute([$user_id, -$price, $description, $chapter_id]);
    
    // ۵. تایید نهایی تراکنش
    $conn->commit();

    // ۶. ارسال پاسخ موفقیت‌آمیز
    echo json_encode(['success' => true, 'message' => 'چپتر با موفقیت خریداری شد! صفحه در حال بارگذاری مجدد است.']);

} catch (Exception $e) {
    // در صورت بروز هرگونه خطا، تمام تغییرات را لغو (rollback) می‌کنیم.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // ثبت خطا در لاگ سرور برای بررسی‌های بعدی
    error_log("Purchase Chapter Error: " . $e->getMessage());

    // ارسال یک پیام خطای مناسب به کاربر
    http_response_code(400); // Bad Request (چون معمولاً خطا از سمت کاربر است)
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
