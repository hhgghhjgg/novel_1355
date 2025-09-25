// admin/admin-script.js
document.addEventListener('DOMContentLoaded', function() {
    
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebarMenu = document.getElementById('sidebar-menu');

    if (hamburgerBtn && sidebarMenu) {
        // تابعی برای باز و بسته کردن سایدبار
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    // بستن سایدبار به صورت پیش‌فرض در دستگاه‌های موبایل
    if (window.innerWidth <= 768) {
        document.body.classList.add('sidebar-collapsed');
    }
});
