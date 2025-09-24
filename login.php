<?php
// login.php (نسخه نهایی با تنظیمات صحیح کوکی)

/*
=====================================================
    NovelWorld - Login Page
    Version: 3.1 (Final - Patched Cookie Settings)
=====================================================
    - این نسخه شامل تنظیمات بهینه شده کوکی ('domain' و 'samesite')
      برای اطمینان از ارسال صحیح آن در تمام مرورگرها و پلتفرم‌ها است.
*/

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
require_once 'db_connect.php';

// --- گام ۲: آماده‌سازی متغیرها ---
$errors = [];
$username_input = '';

// --- گام ۳: پردازش فرم ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username']);
    $password = $_POST['password'];

    // اعتبارسنجی اولیه
    if (empty($username_input) || empty($password)) {
        $errors[] = "نام کاربری/ایمیل و رمز عبور الزامی است.";
    } else {
        try {
            // جستجوی کاربر در دیتابیس
            $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_input, $username_input]);
            $user = $stmt->fetch();

            // بررسی صحت رمز عبور
            if ($user && password_verify($password, $user['password_hash'])) {
                // --- بخش ایجاد سشن در دیتابیس ---

                // ۱. ایجاد یک شناسه سشن امن و تصادفی
                $session_id = bin2hex(random_bytes(32)); 
                
                // ۲. تعیین تاریخ انقضا برای ۷ روز آینده
                $expires_at_timestamp = time() + (3600 * 24 * 7); // 7 days
                $expires_at_db_format = date('Y-m-d H:i:s', $expires_at_timestamp);

                // ۳. ذخیره سشن جدید در دیتابیس
                $stmt_session = $conn->prepare("INSERT INTO sessions (session_id, user_id, expires_at) VALUES (?, ?, ?)");
                $stmt_session->execute([$session_id, $user['id'], $expires_at_db_format]);
                
                // --- ۴. تنظیم کوکی با پارامترهای اصلاح شده و بهینه ---
                $cookie_options = [
                    'expires' => $expires_at_timestamp,
                    'path' => '/',
                    //'domain' => $_SERVER['HTTP_HOST'], // خالی گذاشتن domain معمولاً سازگارتر است
                    'secure' => true,   // ضروری برای HTTPS
                    'httponly' => true, // جلوگیری از دسترسی جاوااسکریپت
                    'samesite' => 'Lax' // استاندارد مدرن برای سشن‌ها
                ];
                setcookie("user_session", $session_id, $cookie_options);
                
                // ۵. هدایت کاربر به صفحه پروفایل
                header("Location: profile.php");
                exit();

            } else {
                $errors[] = "نام کاربری یا رمز عبور اشتباه است.";
            }
        } catch (PDOException $e) {
            error_log("Login DB Error: ". $e->getMessage());
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
