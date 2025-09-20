/*
=====================================================
    ManhwaWorld - Search Page Script (search-script.js)
    Version: 1.1 (with Modal Logic)
=====================================================
*/

// این رویداد مطمئن می‌شود که تمام کدهای جاوااسکریپت پس از بارگذاری کامل صفحه اجرا شوند.
document.addEventListener('DOMContentLoaded', function() {

    console.log("Search page script loaded successfully.");

    // --- ماژول ۱: مدیریت پاپ‌آپ (Modal) جستجوی پیشرفته ---
    
    const openModalBtn = document.getElementById('open-filters-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalOverlay = document.getElementById('filters-modal');

    // بررسی می‌کنیم که آیا تمام عناصر مورد نیاز در صفحه وجود دارند
    if (openModalBtn && closeModalBtn && modalOverlay) {
        
        // رویداد برای باز کردن پاپ‌آپ با کلیک روی دکمه فیلتر
        openModalBtn.addEventListener('click', () => {
            modalOverlay.classList.add('active');
        });

        // رویداد برای بستن پاپ‌آپ با کلیک روی دکمه "ضربدر"
        closeModalBtn.addEventListener('click', () => {
            modalOverlay.classList.remove('active');
        });

        // رویداد برای بستن پاپ‌آپ با کلیک روی پس‌زمینه تیره (خود overlay)
        modalOverlay.addEventListener('click', (e) => {
            // این شرط چک می‌کند که آیا کلیک مستقیماً روی خود پس‌زمینه بوده یا روی محتوای داخل آن
            if (e.target === modalOverlay) {
                modalOverlay.classList.remove('active');
            }
        });

        // رویداد برای بستن پاپ‌آپ با فشردن کلید Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape" && modalOverlay.classList.contains('active')) {
                modalOverlay.classList.remove('active');
            }
        });
    }

    // -------------------------------------------------------------------
    // --- آینده: کد مربوط به اسلایدر انتخاب محدوده امتیاز ---
    // -------------------------------------------------------------------
    // در این بخش، ما یک کتابخانه جاوااسکریپتی مانند noUiSlider را فراخوانی کرده
    // و input[type="range"] خود را به یک اسلایدر زیبا و کاربردی تبدیل خواهیم کرد.
    /*
    const ratingSlider = document.getElementById('rating_min_input');
    // ... کد پیاده‌سازی اسلایدر ...
    */
});
