<?php
// edit_profile.php

/*
=====================================================
    NovelWorld - Edit Profile Page
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این صفحه به کاربران اجازه می‌دهد اطلاعات پروفایل خود را ویرایش کنند.
    - شامل قابلیت آپلود عکس پروفایل/هدر، تغییر بیو و لینک دونیت است.
    - قابلیت تغییر رمز عبور را نیز فراهم می‌کند.
*/

require_once 'core.php';
require_once 'vendor/autoload.php';

use Cloudinary\Cloudinary;

// اگر کاربر لاگین نکرده بود، اجازه دسترسی نمی‌دهیم.
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

// واکشی اطلاعات فعلی کاربر برای پر کردن فرم
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
} catch (PDOException $e) {
    die("خطا در بارگذاری اطلاعات کاربر.");
}

// پردازش فرم در صورت ارسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت داده‌ها
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $donation_link = trim($_POST['donation_link']);
    
    // اعتبارسنجی اولیه
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "ایمیل وارد شده معتبر نیست.";
    }
    // (می‌توانید اعتبارسنجی برای لینک دونیت هم اضافه کنید)

    $profile_pic_url = $user_data['profile_picture_url'];
    $header_pic_url = $user_data['header_image_url'];

    // پردازش آپلود عکس پروفایل
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        try {
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            $uploadResult = $cloudinary->uploadApi()->upload($_FILES['profile_picture']['tmp_name'], [
                'folder' => "profile_pictures/{$user_id}",
                'transformation' => [['width' => 300, 'height' => 300, 'crop' => 'fill', 'gravity' => 'face']]
            ]);
            $profile_pic_url = $uploadResult['secure_url'];
        } catch (Exception $e) { $errors[] = "خطا در آپلود عکس پروفایل."; }
    }

    // پردازش آپلود عکس هدر
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $cloudinary = new Cloudinary(getenv('CLOUDINARY_URL'));
            $uploadResult = $cloudinary->uploadApi()->upload($_FILES['header_image']['tmp_name'], [
                'folder' => "header_images/{$user_id}",
                'transformation' => [['width' => 1200, 'height' => 400, 'crop' => 'fill']]
            ]);
            $header_pic_url = $uploadResult['secure_url'];
        } catch (Exception $e) { $errors[] = "خطا در آپلود عکس هدر."; }
    }

    // پردازش تغییر رمز عبور
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "رمز عبور جدید باید حداقل ۸ کاراکتر باشد.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "رمز عبور جدید و تکرار آن مطابقت ندارند.";
        }
    }

    // اگر خطایی وجود نداشت، دیتابیس را آپدیت کن
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // آپدیت همه چیز به همراه رمز عبور
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET email = ?, bio = ?, donation_link = ?, profile_picture_url = ?, header_image_url = ?, password_hash = ? WHERE id = ?";
                $params = [$email, $bio, $donation_link, $profile_pic_url, $header_pic_url, $password_hash, $user_id];
            } else {
                // آپدیت همه چیز به جز رمز عبور
                $sql = "UPDATE users SET email = ?, bio = ?, donation_link = ?, profile_picture_url = ?, header_image_url = ? WHERE id = ?";
                $params = [$email, $bio, $donation_link, $profile_pic_url, $header_pic_url, $user_id];
            }
            
            $stmt_update = $conn->prepare($sql);
            $stmt_update->execute($params);

            $success_message = "پروفایل شما با موفقیت به‌روزرسانی شد.";
            // واکشی مجدد اطلاعات برای نمایش در فرم
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();

        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // خطای ایمیل تکراری
                $errors[] = "این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.";
            } else {
                $errors[] = "خطایی در دیتابیس رخ داد.";
                error_log("Edit Profile DB Error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش پروفایل - NovelWorld</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css">
    <link rel="stylesheet" href="form-style.css"> <!-- یک فایل CSS جدید برای فرم‌ها -->
</head>
<body>
    <?php require_once 'header.php'; ?>

    <main class="form-page-container">
        <div class="form-wrapper">
            <h2>ویرایش پروفایل</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-box">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>نام کاربری (غیرقابل تغییر)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="email">ایمیل</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="bio">بیوگرافی</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="donation_link">لینک دونیت (اختیاری)</label>
                    <input type="url" id="donation_link" name="donation_link" value="<?php echo htmlspecialchars($user_data['donation_link'] ?? ''); ?>" placeholder="https:// ...">
                </div>

                <hr class="form-divider">

                <div class="form-group">
                    <label for="profile_picture">تغییر عکس پروفایل</label>
                    <div class="image-preview-box">
                        <img src="<?php echo htmlspecialchars($user_data['profile_picture_url'] ?? 'default_avatar.png'); ?>" alt="پیش‌نمایش پروفایل" class="image-preview">
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label for="header_image">تغییر عکس هدر</label>
                    <div class="image-preview-box">
                        <img src="<?php echo htmlspecialchars($user_data['header_image_url'] ?? 'default_header.jpg'); ?>" alt="پیش‌نمایش هدر" class="image-preview header-preview">
                        <input type="file" id="header_image" name="header_image" accept="image/*">
                    </div>
                </div>

                <hr class="form-divider">

                <div class="form-group">
                    <label for="new_password">رمز عبور جدید (برای تغییر، پر کنید)</label>
                    <input type="password" id="new_password" name="new_password" placeholder="حداقل ۸ کاراکتر">
                </div>
                <div class="form-group">
                    <label for="confirm_password">تکرار رمز عبور جدید</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="profile.php" class="btn btn-secondary">بازگشت به پروفایل</a>
                </div>
            </form>
        </div>
    </main>
    
    <?php require_once 'footer.php'; ?>
</body>
</html>
