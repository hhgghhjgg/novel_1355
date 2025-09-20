// register.php

<?php
/*
=====================================================
    NovelWorld - Registration Page
    Version: 2.0 (Serverless Ready - PDO)
=====================================================
    - این فایل منطق ثبت‌نام کاربر جدید را مدیریت می‌کند.
    - از PDO برای تعامل با دیتابیس PostgreSQL (Neon) استفاده می‌کند.
    - رمز عبور کاربر را به صورت امن با استفاده از password_hash هش می‌کند.
    - پس از ثبت‌نام موفق، کاربر را به صفحه ورود با یک پیام موفقیت‌آمیز هدایت می‌کند.
*/

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
require_once 'db_connect.php'; // این فایل اکنون از PDO استفاده می‌کند

// --- گام ۲: آماده‌سازی متغیرها ---

$errors = []; // آرایه‌ای برای نگهداری و نمایش خطاها
// متغیرهایی برای نگهداری ورودی‌های کاربر (برای پر کردن مجدد فرم در صورت خطا)
$username = '';
$email = '';

// اگر کاربر از قبل لاگین کرده بود (با چک کردن کوکی)، او را به صفحه پروفایل هدایت کن.
// این منطق بهتر است در یک فایل جداگانه قرار گیرد و در ابتدای تمام صفحات فراخوانی شود.
// فرض می‌کنیم این کار در header.php انجام می‌شود.

// --- گام ۳: پردازش فرم در صورت ارسال با متد POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت و پاکسازی ورودی‌ها
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- گام ۳.۱: اعتبارسنجی ورودی‌ها ---
    
    // اعتبارسنجی نام کاربری
    if (empty($username)) {
        $errors[] = "نام کاربری الزامی است.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "نام کاربری باید بین ۳ تا ۲۰ کاراکتر و فقط شامل حروف انگلیسی، اعداد و _ باشد.";
    }

    // اعتبارسنجی ایمیل
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "ایمیل وارد شده معتبر نیست.";
    }

    // اعتبارسنجی رمز عبور
    if (strlen($password) < 8) {
        $errors[] = "رمز عبور باید حداقل ۸ کاراکتر باشد.";
    }

    // --- گام ۳.۲: بررسی تکراری نبودن نام کاربری و ایمیل ---
    // اگر خطایی در اعتبارسنجی‌های اولیه وجود نداشت، ادامه بده
    if (empty($errors)) {
        try {
            // کوئری برای بررسی وجود نام کاربری یا ایمیل در دیتابیس
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                // اگر رکوردی پیدا شد، یعنی نام کاربری یا ایمیل قبلاً ثبت شده است.
                $errors[] = "نام کاربری یا ایمیل قبلاً در سیستم ثبت شده است.";
            } else {
                // --- گام ۳.۳: هش کردن رمز عبور و ذخیره کاربر جدید ---
                
                // هش کردن رمز عبور با استفاده از الگوریتم BCRYPT (استاندارد و امن)
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // ذخیره کاربر جدید در دیتابیس
                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                
                if ($insert_stmt->execute([$username, $email, $password_hash])) {
                    // اگر ثبت‌نام موفق بود، کاربر را به صفحه ورود هدایت کن و یک پیام موفقیت نشان بده
                    header("Location: login.php?status=success");
                    exit();
                } else {
                    $errors[] = "خطایی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره تلاش کنید.";
                }
            }

        } catch (PDOException $e) {
            $errors[] = "خطای دیتابیس. لطفاً بعداً تلاش کنید.";
            // برای دیباگ کردن: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام - NovelWorld</title>
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-showcase">
            <div>
                <h1 class="showcase-logo">Novel<span>World</span></h1>
                <p class="showcase-text">دنیای خود را بنویس، داستان خود را به اشتراک بگذار.</p>
            </div>
        </div>
        
        <div class="auth-container">
            <form action="register.php" method="POST" class="auth-form">
                <h2>ایجاد حساب کاربری</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">نام کاربری:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">ایمیل:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">رمز عبور:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-auth">ثبت‌نام</button>
                <p class="switch-form">حساب کاربری دارید؟ <a href="login.php">وارد شوید</a></p>
            </form>
        </div>
    </div>
</body>
</html>
