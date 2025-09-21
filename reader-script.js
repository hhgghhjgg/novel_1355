// reader-script.js

/*
=====================================================
    NovelWorld - Reader Page Script
    Version: 1.1 (Final, Hardened)
=====================================================
*/

// اجرای تمام کدها پس از بارگذاری کامل ساختار HTML صفحه
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
    
    // بررسی می‌کنیم که آیا عناصر اصلی صفحه وجود دارند یا نه
    if (!readerContainer || !topBar || !bottomBar || !settingsPanel) {
        console.error("Reader UI elements not found. Aborting script.");
        return; // اگر عناصر اصلی نبودند، اجرای اسکریپت را متوقف کن
    }


    // --- گام ۲: مدیریت نمایش/مخفی کردن نوارها ---
    readerContainer.addEventListener('click', (e) => {
        if (e.target.closest('a, button')) return;
        topBar.classList.toggle('visible');
        bottomBar.classList.toggle('visible');
    });


    // --- گام ۳: مدیریت نوار پیشرفت (Progress Bar) ---
    function updateProgressBar() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight;
        const winHeight = window.innerHeight;
        // جلوگیری از تقسیم بر صفر اگر محتوا کوتاه باشد
        const scrollableHeight = docHeight - winHeight;
        if (scrollableHeight <= 0) {
            progressBar.style.width = '100%';
            return;
        }
        const scrollPercent = (scrollTop / scrollableHeight) * 100;
        progressBar.style.width = `${Math.min(100, Math.max(0, scrollPercent))}%`;
    }
    window.addEventListener('scroll', updateProgressBar);
    updateProgressBar();


    // --- گام ۴: مدیریت کامل منوی تنظیمات ---
    const openSettings = () => {
        settingsPanel.classList.add('open');
        overlay.classList.add('open');
    };
    const closeSettings = () => {
        settingsPanel.classList.remove('open');
        overlay.classList.remove('open');
    };

    if (settingsBtn) settingsBtn.addEventListener('click', openSettings);
    if (closeSettingsBtn) closeSettingsBtn.addEventListener('click', closeSettings);
    if (overlay) overlay.addEventListener('click', closeSettings);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && settingsPanel.classList.contains('open')) {
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


    // --- گام ۶: بارگذاری و مدیریت داینامیک نظرات ---
    
    // این تابع در آینده نظرات را از سرور واکشی می‌کند.
    async function loadComments() {
        if (!commentsContainer || !chapterId) return;
        
        commentsContainer.innerHTML = '<p>در حال بارگذاری نظرات...</p>';
        try {
            // شما باید یک فایل load_chapter_comments.php بسازید
            // const response = await fetch(`load_chapter_comments.php?chapter_id=${chapterId}`);
            // if (!response.ok) throw new Error('Failed to fetch comments');
            // const html = await response.text();
            // commentsContainer.innerHTML = html;

            // --- کد نمونه برای نمایش (این بخش را بعدا با fetch واقعی جایگزین کنید) ---
            const mockHTML = `
                ${
                    // USER_IS_LOGGED_IN و CURRENT_USERNAME از تگ <script> در PHP می‌آیند
                    (typeof USER_IS_LOGGED_IN !== 'undefined' && USER_IS_LOGGED_IN)
                    ? `<div class="comment-form-box">
                           <h3>نظر خود را به عنوان "${CURRENT_USERNAME}" بنویسید</h3>
                           <form id="new-comment-form">
                               <textarea name="content" placeholder="نظر شما..." rows="4" required></textarea>
                               <div class="form-footer">
                                   <button type="submit" class="btn btn-primary">ارسال نظر</button>
                               </div>
                           </form>
                       </div>`
                    : '<p class="login-prompt"><a href="login.php">برای ثبت نظر، لطفاً وارد شوید.</a></p>'
                }
                
                <div class="comment-box">
                    <div class="comment-header"><span class="username">تستر</span><span class="timestamp">2025/09/21</span></div>
                    <div class="comment-body"><p>این یک کامنت نمونه برای این چپتر است.</p></div>
                    <div class="comment-footer"><div class="actions">
                        <button class="action-btn reply-btn">پاسخ</button>
                        <button class="action-btn like-btn" data-comment-id="101">👍 <span>5</span></button>
                        <button class="action-btn dislike-btn" data-comment-id="101">👎 <span>1</span></button>
                    </div></div>
                </div>
            `;
            commentsContainer.innerHTML = mockHTML;
            // --- پایان کد نمونه ---

        } catch (error) {
            commentsContainer.innerHTML = `<p style="color: #ff8a8a;">خطا در بارگذاری نظرات.</p>`;
            console.error(error);
        }
    }
    
    if (commentsSection) {
        loadComments();

        commentsSection.addEventListener('submit', async (e) => {
            if (e.target.id === 'new-comment-form') {
                e.preventDefault();
                const form = e.target;
                const content = form.querySelector('textarea[name="content"]').value;
                
                // در اینجا منطق ارسال نظر جدید با fetch را اضافه کنید
                // const formData = new FormData();
                // formData.append('content', content);
                // formData.append('chapter_id', chapterId);
                // await fetch('submit_chapter_comment.php', { method: 'POST', body: formData });
                
                console.log("ارسال نظر:", content);
                alert("نظر شما (به صورت آزمایشی) ثبت شد!");
                form.reset();
                // loadComments(); // بارگذاری مجدد نظرات پس از ارسال
            }
        });
    }
});
