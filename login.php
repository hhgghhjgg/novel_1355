// login.php

<?php
/*
=====================================================
    NovelWorld - Login Page
    Version: 2.0 (Serverless Ready - JWT Auth)
=====================================================
    - این فایل منطق ورود کاربر را با استفاده از JWT مدیریت می‌کند.
    - پس از تایید موفقیت‌آمیز نام کاربری و رمز عبور، یک توکن JWT ساخته شده
      و در یک کوکی امن (HttpOnly, Secure, SameSite) ذخیره می‌شود.
    - این روش جایگزین کامل سیستم session-based سنتی است.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---

// اتصال به دیتابیس (که اکنون با PDO کار می‌کند)
require_once 'db_connect.php'; 

// فراخوانی autoload.php برای استفاده از کتابخانه JWT
// این فایل توسط Composer ساخته می‌شود.
require_once 'vendor/autoload.php'; 

// استفاده از کلاس‌های کتابخانه firebase/php-jwt
use Firebase\JWT\JWT;

// --- گام ۲: آماده‌سازی متغیرها ---

// آرایه‌ای برای نگهداری و نمایش خطاها
$errors = [];
// متغیری برای نگهداری ورودی کاربر (برای نمایش مجدد در فرم در صورت خطا)
$username_input = '';

// بررسی می‌کنیم که آیا کاربر از قبل لاگین کرده است یا نه (با بررسی کوکی)
// (این منطق را در header.php قرار خواهیم داد، اما اینجا هم می‌توان چک کرد)
if (isset($_COOKIE['auth_token'])) {
    // اگر توکن وجود داشت، می‌توانیم او را به صفحه پروفایل هدایت کنیم
    // برای سادگی، این بخش را به header.php واگذار می‌کنیم.
}


// --- گام ۳: پردازش فرم در صورت ارسال با متد POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // دریافت و پاکسازی ورودی‌ها
    $username_input = trim($_POST['username']); // کاربر می‌تواند نام کاربری یا ایمیل را وارد کند
    $password = $_POST['password'];

    // اعتبارسنجی اولیه
    if (empty($username_input) || empty($password)) {
        $errors[] = "نام کاربری/ایمیل و رمز عبور الزامی است.";
    } else {
        try {
            // جستجوی کاربر در دیتابیس بر اساس نام کاربری یا ایمیل با استفاده از PDO
            $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_input, $username_input]);
            
            // واکشی اطلاعات کاربر
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // --- کاربر تایید شد! حالا توکن JWT را می‌سازیم ---

                // ۱. کلید محرمانه را از متغیرهای محیطی می‌خوانیم. **هرگز این کلید را در کد ننویسید!**
                $secret_key = getenv('JWT_SECRET_KEY');
                if (!$secret_key) {
                    die("خطای امنیتی: کلید JWT در سرور تنظیم نشده است.");
                }

                // ۲. اطلاعات payload توکن را تعریف می‌کنیم
                $issuer_claim = "novelworld.com"; // آدرس سایت شما
                $audience_claim = "novelworld.com";
                $issuedat_claim = time();
                $notbefore_claim = $issuedat_claim; 
                $expire_claim = $issuedat_claim + (3600 * 24 * 7); // توکن برای ۷ روز معتبر است

                $payload = [
                    "iss" => $issuer_claim,
                    "aud" => $audience_claim,
                    "iat" => $issuedat_claim,
                    "nbf" => $notbefore_claim,
                    "exp" => $expire_claim,
                    "data" => [ // اطلاعاتی که می‌خواهیم همراه توکن باشد
                        "user_id" => $user['id'],
                        "username" => $user['username']
                    ]
                ];

                // ۳. انکود کردن توکن
                $jwt = JWT::encode($payload, $secret_key, 'HS256');

                // ۴. ذخیره توکن در یک کوکی امن
                setcookie("auth_token", $jwt, [
                    'expires' => $expire_claim,
                    'path' => '/',
                    'domain' => '', // برای کار کردن روی localhost و دامین اصلی خالی بگذارید
                    'secure' => true,   // فقط از طریق HTTPS ارسال شود (Render این را فراهم می‌کند)
                    'httponly' => true, // جلوگیری از دسترسی جاوااسکریپت (بسیار مهم برای امنیت)
                    'samesite' => 'Strict' // جلوگیری از حملات CSRF
                ]);
                
                // ۵. هدایت کاربر به صفحه پروفایل
                header("Location: profile.php");
                exit();

            } else {
                // اگر کاربر یافت نشد یا رمز عبور اشتباه بود، یک پیام خطای عمومی نمایش می‌دهیم
                // این کار از حملات شمارش نام کاربری (username enumeration) جلوگیری می‌کند.
                $errors[] = "نام کاربری یا رمز عبور اشتباه است.";
            }

        } catch (PDOException $e) {
            $errors[] = "خطای دیتابیس. لطفاً بعداً تلاش کنید.";
            // برای دیباگ: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به حساب کاربری - NovelWorld</title>
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
            <form action="login.php" method="POST" class="auth-form">
                <h2>ورود به حساب</h2>

                <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                    <div class="success-box">ثبت‌نام شما با موفقیت انجام شد. اکنون می‌توانید وارد شوید.</div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">نام کاربری یا ایمیل:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_input); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">رمز عبور:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-auth">ورود</button>
                <p class="switch-form">حساب کاربری ندارید؟ <a href="register.php">ثبت‌نام کنید</a></p>
            </form>
        </div>
    </div>
</body>
</html>
