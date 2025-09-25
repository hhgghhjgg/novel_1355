<?php
// setup_admin.php

/*
=====================================================
    NovelWorld - One-Time Admin Setup Script
=====================================================
    - این اسکریپت برای ایجاد یا بازنشانی کاربر ادمین به صورت خودکار است.
    - این فایل را فقط یک بار در مرورگر اجرا کنید.
    - پس از استفاده، برای امنیت، حتماً آن را از سرور خود حذف کنید.
*/

// نمایش تمام خطاها برای دیباگ کامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><title>ساخت ادمین</title><style>body{font-family: sans-serif; background: #222; color: #eee; padding: 20px;} .success{color: lime;} .error{color: red;}</style></head><body>";
echo "<h1>شروع فرآیند ساخت ادمین...</h1>";

// ۱. فراخوانی اتصال به دیتابیس
require_once 'db_connect.php';
if ($conn) {
    echo "<p class='success'>✅ اتصال به دیتابیس موفقیت‌آمیز بود.</p>";
} else {
    echo "<p class='error'>❌ خطا: اتصال به دیتابیس برقرار نشد. لطفاً متغیرهای محیطی را چک کنید.</p>";
    echo "</body></html>";
    die();
}

// ۲. تعریف اطلاعات ادمین
$admin_username = 'armin11';
$admin_password = 'armin1129';
$admin_email = 'armin11@novelworld.test';

try {
    // ۳. حذف کاربر ادمین قبلی (در صورت وجود) برای اطمینان از یک شروع تمیز
    echo "<p>در حال تلاش برای حذف کاربر قبلی '{$admin_username}' (در صورت وجود)...</p>";
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt_delete->execute([$admin_username]);
    if ($stmt_delete->rowCount() > 0) {
        echo "<p class='success'>- کاربر قبلی با موفقیت حذف شد.</p>";
    } else {
        echo "<p>- کاربری با این نام برای حذف وجود نداشت. ادامه می‌دهیم...</p>";
    }

    // ۴. هش کردن رمز عبور با استفاده از تابع داخلی PHP
    echo "<p>در حال هش کردن رمز عبور...</p>";
    $password_hash = password_hash($admin_password, PASSWORD_BCRYPT);
    echo "<p class='success'>- رمز عبور با موفقیت هش شد.</p>";

    // ۵. ایجاد کاربر ادمین جدید با هش صحیح
    echo "<p>در حال ایجاد کاربر ادمین جدید در دیتابیس...</p>";
    $stmt_insert = $conn->prepare(
        "INSERT INTO users (username, email, password_hash, role) 
         VALUES (?, ?, ?, 'admin')"
    );
    $stmt_insert->execute([$admin_username, $admin_email, $password_hash]);
    
    echo "<p class='success'>✅ کاربر ادمین <b>'{$admin_username}'</b> با موفقیت ایجاد شد.</p>";
    echo "<hr>";
    echo "<h2>عملیات با موفقیت انجام شد!</h2>";
    echo "<p>شما اکنون می‌توانید به <a href='admin/login.php'>صفحه لاگین ادمین</a> بروید و با اطلاعات زیر وارد شوید:</p>";
    echo "<ul>";
    echo "<li><b>نام کاربری:</b> {$admin_username}</li>";
    echo "<li><b>رمز عبور:</b> {$admin_password}</li>";
    echo "</ul>";
    echo "<p class='error' style='font-weight: bold;'>مهم: پس از ورود موفق، لطفاً فایل setup_admin.php را از پروژه خود حذف کنید.</p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ یک خطای دیتابیس رخ داد: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
