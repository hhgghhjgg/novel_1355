// reader-script.js

/*
=====================================================
    NovelWorld - Reader Page Script
    Version: 1.0
=====================================================
    - مدیریت نمایش/مخفی کردن نوارهای بالا و پایین.
    - محاسبه و نمایش نوار پیشرفت (Progress Bar).
    - مدیریت کامل منوی تنظیمات (باز/بسته شدن).
    - اعمال و ذخیره تنظیمات کاربر (تم، فونت، اندازه فونت) در Local Storage.
    - بارگذاری، نمایش و مدیریت تعاملات بخش نظرات به صورت داینامیک.
*/

document.addEventListener('DOMContentLoaded', () => {

    // --- گام ۱: انتخاب تمام عناصر DOM مورد نیاز ---
    const body = document.body;
    const readerContainer = document.getElementById('reader-container');
    const content = document.getElementById('reader-content');
    const topBar = document.querySelector('.top-bar');
    const bottomBar = document.querySelector('.bottom-bar');
    const progressBar = document.getElementById('progress-bar');
    
    // عناصر تنظیمات
    const settingsBtn = document.getElementById('settings-btn');
    const settingsPanel = document.getElementById('settings-panel');
    const overlay = document.getElementById('settings-overlay');
    const closeSettingsBtn = document.getElementById('close-settings-btn');
    const fontSelect = document.getElementById('font-select');
    const decreaseFontBtn = document.querySelector('[data-action="decrease-font"]');
    const increaseFontBtn = document.querySelector('[data-action="increase-font"]');
    const themeSwatches = document.querySelectorAll('.theme-swatch');

    // عناصر نظرات
    const commentsSection = document.querySelector('.chapter-comments-section');
    const commentsContainer = document.getElementById('comments-container');
    const chapterId = body.dataset.chapterId;


    // --- گام ۲: مدیریت نمایش/مخفی کردن نوارها ---
    readerContainer.addEventListener('click', (e) => {
        // اگر روی لینک یا دکمه‌ای داخل محتوا کلیک شد، نوارها ظاهر نشوند
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
        
        topBar.classList.toggle('visible');
        bottomBar.classList.toggle('visible');
    });


    // --- گام ۳: مدیریت نوار پیشرفت (Progress Bar) ---
    function updateProgressBar() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight;
        const winHeight = window.innerHeight;
        const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;
        
        // جلوگیری از مقادیر بیشتر از ۱۰۰ یا کمتر از ۰
        const clampedPercent = Math.min(100, Math.max(0, scrollPercent));
        progressBar.style.width = clampedPercent + '%';
    }
    window.addEventListener('scroll', updateProgressBar);
    updateProgressBar(); // اجرای اولیه برای بارگذاری صفحه


    // --- گام ۴: مدیریت کامل منوی تنظیمات ---
    const openSettings = () => {
        settingsPanel.classList.add('open');
        overlay.classList.add('open');
    };
    const closeSettings = () => {
        settingsPanel.classList.remove('open');
        overlay.classList.remove('open');
    };
    settingsBtn.addEventListener('click', openSettings);
    closeSettingsBtn.addEventListener('click', closeSettings);
    overlay.addEventListener('click', closeSettings);
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && settingsPanel.classList.contains('open')) {
            closeSettings();
        }
    });


    // --- گام ۵: منطق اعمال و ذخیره تنظیمات کاربر ---
    const fontSizes = ['font-size-xsmall', 'font-size-small', 'font-size-medium', 'font-size-large', 'font-size-xlarge'];
    let currentSizeIndex = 2; // Index for 'font-size-medium'

    // تابع برای اعمال اندازه فونت
    function applyFontSize(index) {
        content.classList.remove(...fontSizes);
        content.classList.add(fontSizes[index]);
        localStorage.setItem('reader_font_size_index', index);
    }

    // تابع برای اعمال فونت
    function applyFont(fontClass) {
        body.className = body.className.replace(/font-\w+/g, '');
        body.classList.add(fontClass);
        localStorage.setItem('reader_font', fontClass);
        fontSelect.value = fontClass;
    }

    // تابع برای اعمال تم
    function applyTheme(themeClass) {
        body.className = body.className.replace(/theme-\w+/g, '');
        body.classList.add(themeClass);
        localStorage.setItem('reader_theme', themeClass);
        themeSwatches.forEach(swatch => {
            swatch.classList.toggle('active', swatch.dataset.theme === themeClass);
        });
    }

    // رویدادهای کلیک برای دکمه‌های تنظیمات
    decreaseFontBtn.addEventListener('click', () => {
        if (currentSizeIndex > 0) {
            currentSizeIndex--;
            applyFontSize(currentSizeIndex);
        }
    });
    increaseFontBtn.addEventListener('click', () => {
        if (currentSizeIndex < fontSizes.length - 1) {
            currentSizeIndex++;
            applyFontSize(currentSizeIndex);
        }
    });
    fontSelect.addEventListener('change', (e) => applyFont(e.target.value));
    themeSwatches.forEach(swatch => {
        swatch.addEventListener('click', () => applyTheme(swatch.dataset.theme));
    });

    // بارگذاری تنظیمات ذخیره شده هنگام ورود به صفحه
    function loadUserSettings() {
        const savedTheme = localStorage.getItem('reader_theme') || 'theme-dark';
        const savedFont = localStorage.getItem('reader_font') || 'font-vazirmatn';
        const savedSizeIndex = parseInt(localStorage.getItem('reader_font_size_index'), 10) || 2;
        
        currentSizeIndex = savedSizeIndex;
        applyTheme(savedTheme);
        applyFont(savedFont);
        applyFontSize(savedSizeIndex);
    }
    loadUserSettings();


    // --- گام ۶: بارگذاری و مدیریت داینامیک نظرات ---
    
    // این تابع نظرات را از سرور واکشی کرده و در صفحه قرار می‌دهد
    async function loadComments() {
        try {
            // در آینده، شما یک فایل load_comments.php خواهید ساخت
            // const response = await fetch(`load_comments.php?chapter_id=${chapterId}`);
            // if (!response.ok) throw new Error('Network response was not ok');
            // const html = await response.text();
            // commentsContainer.innerHTML = html;
            
            // کد نمونه برای نمایش (این بخش را بعدا با fetch جایگزین کنید)
            commentsContainer.innerHTML = `
                <div class="comment-box">
                    <p>بخش نظرات به زودی فعال خواهد شد!</p>
                </div>
            `;
        } catch (error) {
            commentsContainer.innerHTML = `<p style="color: #ff8a8a;">خطا در بارگذاری نظرات.</p>`;
            console.error('Failed to load comments:', error);
        }
    }
    
    // اولین بار نظرات را بارگذاری می‌کنیم
    if (chapterId) {
        loadComments();
    }
    
    // استفاده از Event Delegation برای مدیریت رویدادهای کلیک در کل بخش نظرات
    commentsSection.addEventListener('click', (e) => {
        const target = e.target;

        // اگر روی دکمه لایک کلیک شد
        const likeBtn = target.closest('.like-btn');
        if (likeBtn) {
            console.log('Like button clicked for comment:', likeBtn.dataset.commentId);
            // در اینجا منطق ارسال درخواست fetch برای لایک کردن را اضافه کنید
        }

        // اگر روی دکمه دیسلایک کلیک شد
        const dislikeBtn = target.closest('.dislike-btn');
        if (dislikeBtn) {
            console.log('Dislike button clicked for comment:', dislikeBtn.dataset.commentId);
             // در اینجا منطق ارسال درخواست fetch برای دیسلایک کردن را اضافه کنید
        }

        // اگر روی دکمه ریپلای کلیک شد
        const replyBtn = target.closest('.reply-btn');
        if (replyBtn) {
            console.log('Reply button clicked for comment box');
            // در اینجا منطق نمایش فرم ریپلای را اضافه کنید
        }
    });
});
```
