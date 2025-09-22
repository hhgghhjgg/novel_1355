// reader-script.js

/*
=====================================================
    NovelWorld - Reader Page Script (Final, Unabridged)
    Version: 1.1
=====================================================
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
    
    if (!readerContainer || !topBar || !bottomBar || !settingsPanel) {
        console.error("Reader UI elements are missing. Script execution halted.");
        return;
    }


    // --- گام ۲: مدیریت نمایش/مخفی کردن نوارها ---
    readerContainer.addEventListener('click', (e) => {
        if (e.target.closest('a, button, .comment-box')) return;
        topBar.classList.toggle('visible');
        bottomBar.classList.toggle('visible');
    });


    // --- گام ۳: مدیریت نوار پیشرفت (Progress Bar) ---
    function updateProgressBar() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight;
        const winHeight = window.innerHeight;
        const scrollableHeight = docHeight - winHeight;
        if (scrollableHeight <= 0) {
            if(progressBar) progressBar.style.width = '100%';
            return;
        }
        const scrollPercent = (scrollTop / scrollableHeight) * 100;
        if(progressBar) progressBar.style.width = `${Math.min(100, Math.max(0, scrollPercent))}%`;
    }
    window.addEventListener('scroll', updateProgressBar);
    updateProgressBar();


    // --- گام ۴: مدیریت کامل منوی تنظیمات ---
    const openSettings = () => {
        if (settingsPanel && overlay) {
            settingsPanel.classList.add('open');
            overlay.classList.add('open');
        }
    };
    const closeSettings = () => {
        if (settingsPanel && overlay) {
            settingsPanel.classList.remove('open');
            overlay.classList.remove('open');
        }
    };

    if (settingsBtn) settingsBtn.addEventListener('click', openSettings);
    if (closeSettingsBtn) closeSettingsBtn.addEventListener('click', closeSettings);
    if (overlay) overlay.addEventListener('click', closeSettings);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && settingsPanel && settingsPanel.classList.contains('open')) {
            closeSettings();
        }
    });


    // --- گام ۵: منطق اعمال و ذخیره تنظیمات کاربر ---
    const fontSizes = ['font-size-xsmall', 'font-size-small', 'font-size-medium', 'font-size-large', 'font-size-xlarge'];
    let currentSizeIndex = 2;

    function applyFontSize(index) {
        currentSizeIndex = Math.max(0, Math.min(fontSizes.length - 1, index));
        if (content) {
            content.classList.remove(...fontSizes);
            content.classList.add(fontSizes[currentSizeIndex]);
        }
        localStorage.setItem('reader_font_size_index', currentSizeIndex);
    }

    function applyFont(fontClass) {
        if (fontClass) {
            body.className = body.className.replace(/font-\w+/g, '');
            body.classList.add(fontClass);
            localStorage.setItem('reader_font', fontClass);
            if (fontSelect) fontSelect.value = fontClass;
        }
    }

    function applyTheme(themeClass) {
        if (themeClass) {
            body.className = body.className.replace(/theme-\w+/g, '');
            body.classList.add(themeClass);
            localStorage.setItem('reader_theme', themeClass);
            if (themeSwatches) {
                themeSwatches.forEach(swatch => {
                    swatch.classList.toggle('active', swatch.dataset.theme === themeClass);
                });
            }
        }
    }

    if (decreaseFontBtn) decreaseFontBtn.addEventListener('click', () => applyFontSize(currentSizeIndex - 1));
    if (increaseFontBtn) increaseFontBtn.addEventListener('click', () => applyFontSize(currentSizeIndex + 1));
    if (fontSelect) fontSelect.addEventListener('change', (e) => applyFont(e.target.value));
    if (themeSwatches) {
        themeSwatches.forEach(swatch => {
            swatch.addEventListener('click', () => applyTheme(swatch.dataset.theme));
        });
    }

    function loadUserSettings() {
        const savedTheme = localStorage.getItem('reader_theme') || 'theme-dark';
        const savedFont = localStorage.getItem('reader_font') || 'font-vazirmatn';
        const savedSizeIndex = parseInt(localStorage.getItem('reader_font_size_index'), 10);
        
        applyTheme(savedTheme);
        applyFont(savedFont);
        applyFontSize(isNaN(savedSizeIndex) ? 2 : savedSizeIndex);
    }
    loadUserSettings();


    // --- گام ۶: پیاده‌سازی Lazy Loading برای تصاویر ---
    const lazyImages = document.querySelectorAll('img.lazy-load');
    if (lazyImages.length > 0) {
        const lazyImageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    img.classList.remove('lazy-load');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            lazyImageObserver.observe(img);
        });
    }


    // --- گام ۷: بارگذاری و مدیریت داینامیک نظرات ---
    async function loadComments() {
        if (!commentsContainer || !chapterId) return;
        
        commentsContainer.innerHTML = '<p>در حال بارگذاری نظرات...</p>';
        try {
            // شما باید یک فایل load_chapter_comments.php بسازید
            // const response = await fetch(`load_chapter_comments.php?chapter_id=${chapterId}`);
            // const html = await response.text();
            // commentsContainer.innerHTML = html;

            // --- کد نمونه برای نمایش (این بخش را بعدا با fetch واقعی جایگزین کنید) ---
            const mockHTML = `
                ${ (typeof USER_IS_LOGGED_IN !== 'undefined' && USER_IS_LOGGED_IN)
                    ? `<div class="comment-form-box" style="background-color: var(--reader-bg);">
                           <h3>نظر خود را به عنوان "${CURRENT_USERNAME}" بنویسید</h3>
                           <form id="new-comment-form">
                               <textarea name="content" placeholder="نظر شما درباره این چپتر..." rows="4" required style="background-color: var(--reader-surface); color: var(--reader-text);"></textarea>
                               <div class="form-footer" style="justify-content: flex-end;">
                                   <button type="submit" class="btn btn-primary" style="background-color: var(--reader-primary); color: var(--reader-bg);">ارسال نظر</button>
                               </div>
                           </form>
                       </div>`
                    : '<p class="login-prompt"><a href="login.php">برای ثبت نظر، لطفاً وارد شوید.</a></p>'
                }
                <div class="comment-box" style="background-color: var(--reader-bg);">
                    <div class="comment-header"><span class="username">کاربر نمونه</span><span class="timestamp">2025/09/22</span></div>
                    <div class="comment-body"><p>این یک کامنت آزمایشی برای چپتر است. طراحی بخش نظرات عالی شده!</p></div>
                </div>
            `;
            commentsContainer.innerHTML = mockHTML;
            // --- پایان کد نمونه ---
        } catch (error) {
            commentsContainer.innerHTML = `<p style="color: #ff8a8a;">خطا در بارگذاری نظرات.</p>`;
        }
    }
    
    if (commentsSection) {
        loadComments();

        commentsSection.addEventListener('submit', (e) => {
            if (e.target.id === 'new-comment-form') {
                e.preventDefault();
                // در آینده، اینجا منطق ارسال نظر با fetch قرار می‌گیرد
                alert('منطق ارسال نظر در آینده اینجا پیاده‌سازی خواهد شد.');
            }
        });
    }
});
