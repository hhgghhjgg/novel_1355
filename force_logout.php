<?php
// force_logout.php
// این فایل فقط برای پاک کردن اجباری کوکی استفاده می‌شود.

setcookie('auth_token', '', [
    'expires' => time() - 86400, // یک روز در گذشته
    'path' => '/',
]);

echo "Cookie has been forcefully cleared. Please go back to the main page.";
// ما از header() استفاده نمی‌کنیم تا مطمئن شویم هیچ خطایی رخ نمی‌دهد.
?>
