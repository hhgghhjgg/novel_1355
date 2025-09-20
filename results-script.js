/*
=====================================================
    NovelWorld - Results Page Script
    Version: 1.0 (Dropdown Logic)
=====================================================
*/

document.addEventListener('DOMContentLoaded', function() {
    
    const dropdownBtn = document.querySelector('.sort-dropdown-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    // بررسی می‌کنیم که آیا عناصر منوی کشویی در صفحه وجود دارند
    if (dropdownBtn && dropdownMenu) {
        
        // با کلیک روی دکمه، منو باز و بسته شود
        dropdownBtn.addEventListener('click', () => {
            dropdownMenu.classList.toggle('active');
            dropdownBtn.classList.toggle('open');
        });

        // اگر کاربر هر جای دیگری از صفحه کلیک کرد، منو بسته شود
        document.addEventListener('click', (e) => {
            // این شرط چک می‌کند که کلیک خارج از دکمه و خارج از خود منو بوده باشد
            if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('active');
                dropdownBtn.classList.remove('open');
            }
        });

        // بستن منو با کلید Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape" && dropdownMenu.classList.contains('active')) {
                dropdownMenu.classList.remove('active');
                dropdownBtn.classList.remove('open');
            }
        });
    }

});
