<?php
// admin/login.php

require_once __DIR__ . '/../db_connect.php'; // اتصال به دیتابیس

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash']) && $user['role'] === 'admin') {
            // کاربر ادمین است و رمز عبور صحیح است
            $session_id = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // سشن ادمین برای ۱ ساعت معتبر است

            $stmt_session = $conn->prepare("INSERT INTO sessions (session_id, user_id, expires_at) VALUES (?, ?, ?)");
            $stmt_session->execute([$session_id, $user['id'], $expires_at]);

            // تنظیم کوکی
            setcookie("user_session", $session_id, [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            header("Location: index.php"); // هدایت به داشبورد ادمین
            exit();
        } else {
            $errors[] = "نام کاربری یا رمز عبور نامعتبر است، یا شما دسترسی مدیریت ندارید.";
        }
    } catch (PDOException $e) {
        $errors[] = "خطای دیتابیس.";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <title>ورود به پنل مدیریت</title>
    <link rel="stylesheet" href="../auth-style.css">
    <style>
        .auth-showcase { display: none; } /* مخفی کردن بخش تصویر */
        .auth-container { flex-basis: 100%; }
        .auth-form h2 { color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-container">
            <form action="login.php" method="POST" class="auth-form">
                <h2>ورود به پنل مدیریت</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="form-group"><label for="username">نام کاربری:</label><input type="text" id="username" name="username" required></div>
                <div class="form-group"><label for="password">رمز عبور:</label><input type="password" id="password" name="password" required></div>
                <button type="submit" class="btn-auth">ورود</button>
            </form>
        </div>
    </div>
</body>
</html>
