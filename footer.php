<?php
// footer.php

/*
=====================================================
    NovelWorld - Main Site Footer
    Version: 2.1 (Final, JWT Ready)
=====================================================
    - این فایل تگ‌های اصلی محتوا را که در header.php باز شده‌اند، می‌بندد.
    - نوار ناوبری پایین صفحه (مخصوص موبایل) را رندر می‌کند.
    - فوتر اصلی سایت را (که در دسکتاپ نمایش داده می‌شود) نشان می‌دهد.
    - فایل اصلی جاوااسکریپت سایت (script.js) را فراخوانی می‌کند.
    - منطق آن برای استفاده از متغیر سراسری $is_logged_in (از سیستم JWT)
      به جای $_SESSION به‌روزرسانی شده است.
*/

// --- گام ۱: بستن تگ div اصلی محتوا که در header.php باز شده است ---
?>
    </div> <!-- End of .main-content -->

<?php
// --- گام ۲: رندر کردن نوار ناوبری پایین صفحه (فقط برای موبایل) ---
?>
    <nav class="mobile-bottom-nav">
        <?php
            // این کد به صورت هوشمند تشخیص می‌دهد که کاربر در کدام صفحه است
            // و آن لینک را به عنوان فعال (active) مشخص می‌کند.
            // این بخش نیازی به تغییر نداشت و به درستی کار می‌کند.
            $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>خانه</span>
        </a>
        <a href="search.php" class="<?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <span>جستجو</span>
        </a>
        
        <?php // *** تغییر کلیدی: استفاده از $is_logged_in به جای $_SESSION *** ?>
        <?php if ($is_logged_in): ?>
            <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>پروفایل</span>
            </a>
        <?php else: ?>
             <a href="login.php" class="<?php echo (in_array($current_page, ['login.php', 'register.php'])) ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span>ورود</span>
            </a>
        <?php endif; ?>
    </nav>

<?php
// --- گام ۳: رندر کردن فوتر اصلی سایت (برای دسکتاپ) ---
?>
    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> NovelWorld. تمامی حقوق محفوظ است.</p>
    </footer>

<?php
// --- گام ۴: فراخوانی فایل JavaScript و بستن تگ‌های نهایی HTML ---
?>
    <script src="script.js"></script>
</body>
</html>
