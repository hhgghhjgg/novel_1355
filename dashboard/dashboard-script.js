/*
=====================================================
    NovelWorld - Dashboard Script
    Version: 1.0
=====================================================
*/

document.addEventListener('DOMContentLoaded', function() {
    
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebarMenu = document.getElementById('sidebar-menu');
    const mainContainer = document.querySelector('.main-container');

    // بررسی می‌کنیم که آیا تمام عناصر مورد نیاز در صفحه وجود دارند
    if (hamburgerBtn && sidebarMenu && mainContainer) {

        // تابعی برای باز و بسته کردن سایدبار
        function toggleSidebar() {
            // یک کلاس به body اضافه یا کم می‌کنیم تا بتوانیم استایل‌ها را کنترل کنیم
            document.body.classList.toggle('sidebar-collapsed');
        }

        // اختصاص دادن رویداد کلیک به دکمه همبرگری
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    // بررسی اندازه صفحه هنگام بارگذاری برای وضعیت اولیه سایدبار
    // اگر صفحه در حالت موبایل باشد، سایدبار را به صورت پیش‌فرض ببند
    if (window.innerWidth <= 768) {
        document.body.classList.add('sidebar-collapsed');
    }

});
