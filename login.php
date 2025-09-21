// login.php

<?php
/*
=====================================================
    NovelWorld - Login Page
    Version: 2.1 (Final, Correct JWT Payload)
=====================================================
    - این فایل منطق ورود کاربر را با استفاده از JWT مدیریت می‌کند.
    - این نسخه تضمین می‌کند که payload توکن در بخش 'data' به صورت یک
      آرایه انجمنی (که در JSON به آبجکت تبدیل می‌شود) ساخته می‌شود.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز ---

// اتصال به دیتابیس (PDO)
require_once 'db_connect.php'; 

// Autoloader کامپوزر برای کتابخانه JWT
require_once 'vendor/autoload.php'; 

// استفاده از کلاس‌های کتابخانه firebase/php-jwt
use Firebase\JWT\JWT;

// --- گام ۲: آماده‌سازی متغیرها ---
$errors = [];
$username_input = '';

// --- گام ۳: پردازش فرم ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username_input = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username_input) || empty($password)) {
        $errors[] = "نام کاربری/ایمیل و رمز عبور الزامی است.";
    } else {
        try {
            // جستجوی کاربر در دیتابیس
            $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_input, $username_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // --- کاربر تایید شد! حالا توکن JWT را می‌سازیم ---

                // ۱. خواندن کلید محرمانه از متغیرهای محیطی
                $secret_key = getenv('JWT_SECRET_KEY');
                if (!$secret_key) {
                    // در صورت عدم وجود کلید، عملیات متوقف می‌شود
                    // این یک خطای سیستمی است و نباید برای کاربر عادی رخ دهد.
                    error_log("FATAL: JWT_SECRET_KEY is not set.");
                    die("خطای پیکربندی سرور.");
                }

                // ۲. تعریف اطلاعات payload توکن
                $issuer_claim = $_SERVER['HTTP_HOST']; // استفاده از دامین فعلی
                $audience_claim = $_SERVER['HTTP_HOST'];
                $issuedat_claim = time();
                $expire_claim = $issuedat_claim + (3600 * 24 * 7); // توکن برای ۷ روز معتبر است

                // *** نکته کلیدی و مهم برای رفع خطای قبلی ***
                // بخش 'data' باید یک آرایه انجمنی (associative array) باشد.
                $payload = [
                    "iss" => $issuer_claim,
                    "aud" => $audience_claim,
                    "iat" => $issuedat_claim,
                    "exp" => $expire_claim,
                    "data" => [ 
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
                    'domain' => '', 
                    'secure' => true,   // ضروری برای Render
                    'httponly' => true, // بسیار مهم برای امنیت
                    'samesite' => 'Strict'
                ]);
                
                // ۵. هدایت کاربر به صفحه پروفایل
                header("Location: profile.php");
                exit();

            } else {
                $errors[] = "نام کاربری یا رمز عبور اشتباه است.";
            }

        } catch (PDOException $e) {
            error_log("Login DB Error: " . $e->getMessage());
            $errors[] = "خطای دیتابیس. لطفاً بعداً تلاش کنید.";
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
