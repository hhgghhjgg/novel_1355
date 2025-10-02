// public_profile_script.js

/*
=====================================================
    NovelWorld - Public Profile Page Script
    Version: 1.0 (Final, Unabridged)
=====================================================
    - این اسکریپت تمام تعاملات صفحه پروفایل عمومی را مدیریت می‌کند:
    - منطق تب‌بندی (آثار، پست‌ها).
    - منطق دکمه دنبال کردن/لغو دنبال کردن (AJAX).
    - مدیریت منوی سه نقطه و مودال‌های "ایجاد استوری" و "ایجاد پست".
    - بارگذاری داینامیک پست‌ها هنگام کلیک روی تب.
    - پیش‌نمایش تصویر هنگام آپلود در فرم ایجاد پست.
*/

document.addEventListener('DOMContentLoaded', () => {

    // --- ماژول ۱: منطق تب‌بندی ---
    const tabLinks = document.querySelectorAll('.profile-content-tabs .tab-link:not(.donate-link)');
    const tabContents = document.querySelectorAll('.profile-content-tabs .tab-content');

    if (tabLinks.length > 0 && tabContents.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tabId = link.dataset.tab;
                
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));
                
                link.classList.add('active');
                const activeTab = document.getElementById(tabId);
                if (activeTab) {
                    activeTab.classList.add('active');
                }
            });
        });
    }


    // --- ماژول ۲: منطق دکمه دنبال کردن/لغو دنبال کردن (AJAX) ---
    const followBtn = document.getElementById('follow-toggle-btn');
    if (followBtn) {
        followBtn.addEventListener('click', async () => {
            const profileId = followBtn.dataset.profileId;
            followBtn.disabled = true;

            try {
                const response = await fetch('toggle_follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ profile_id: profileId })
                });
                const data = await response.json();

                if (data.success) {
                    if (data.action === 'followed') {
                        followBtn.textContent = 'لغو دنبال';
                        followBtn.classList.remove('btn-primary');
                        followBtn.classList.add('btn-secondary');
                    } else {
                        followBtn.textContent = 'دنبال کردن';
                        followBtn.classList.remove('btn-secondary');
                        followBtn.classList.add('btn-primary');
                    }
                    // آپدیت تعداد دنبال‌کنندگان (در یک اپلیکیشن واقعی)
                    // window.location.reload(); // ساده‌ترین راه برای آپدیت عدد
                } else {
                    alert(data.message || 'خطایی رخ داد.');
                }
            } catch (error) {
                console.error('Follow toggle error:', error);
                alert('خطای ارتباط با سرور.');
            } finally {
                followBtn.disabled = false;
            }
        });
    }


    // --- ماژول ۳: منوی اقدامات پروفایل و مودال‌ها (برای صاحب پروفایل) ---
    const profileActionsToggle = document.getElementById('profile-actions-toggle');
    const profileActionsDropdown = document.getElementById('profile-actions-dropdown');
    
    // باز و بسته کردن منوی سه نقطه
    if (profileActionsToggle && profileActionsDropdown) {
        profileActionsToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // جلوگیری از بسته شدن فوری منو توسط event listener پایین
            profileActionsDropdown.classList.toggle('open');
        });
    }
    // بستن منو با کلیک در هر جای دیگر صفحه
    document.addEventListener('click', () => {
        if (profileActionsDropdown && profileActionsDropdown.classList.contains('open')) {
            profileActionsDropdown.classList.remove('open');
        }
    });


    // مدیریت مودال ایجاد استوری
    const createStoryLink = document.getElementById('create-story-link');
    const createStoryModal = document.getElementById('create-story-modal');
    if (createStoryLink && createStoryModal) {
        createStoryLink.addEventListener('click', (e) => {
            e.preventDefault();
            createStoryModal.classList.add('open');
        });

        const closeBtn = createStoryModal.querySelector('.close-modal-btn');
        if(closeBtn) closeBtn.addEventListener('click', () => createStoryModal.classList.remove('open'));
        createStoryModal.addEventListener('click', (e) => { if (e.target === createStoryModal) createStoryModal.classList.remove('open'); });

        const createStoryForm = document.getElementById('create-story-form');
        if (createStoryForm) {
            createStoryForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const novelId = createStoryForm.querySelector('#story-novel-select').value;
                const title = createStoryForm.querySelector('#story-title-input').value;

                if (!novelId) {
                    alert('لطفاً یک اثر را انتخاب کنید.');
                    return;
                }

                try {
                    const response = await fetch('create_story.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ novel_id: novelId, title: title })
                    });
                    const data = await response.json();
                    alert(data.message);
                    if (data.success) {
                        createStoryModal.classList.remove('open');
                        window.location.reload(); // رفرش برای نمایش استوری جدید
                    }
                } catch (error) {
                    alert('خطا در ایجاد استوری.');
                }
            });
        }
    }


    // --- ماژول ۴: فرم ایجاد پست و بارگذاری پست‌ها ---
    const createPostForm = document.getElementById('create-post-form');
    const postsContainer = document.getElementById('posts-container');
    const postsTab = document.querySelector('[data-tab="posts-tab"]');
    let postsLoaded = false;
    
    // تابع برای بارگذاری پست‌ها با AJAX
    async function loadPosts() {
        if (postsContainer) {
            postsContainer.innerHTML = '<p class="empty-tab-message">در حال بارگذاری پست‌ها...</p>';
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const username = urlParams.get('username'); // گرفتن نام کاربری از URL
                
                // شما باید user_id را در PHP به یک data-attribute در تگ body اضافه کنید
                // <body data-profile-userid="<?php echo $profile_user_id; ?>">
                const profileUserId = document.body.dataset.profileUserid;

                const response = await fetch(`load_posts.php?user_id=${profileUserId}`);
                if (!response.ok) throw new Error('Network response was not ok.');
                const html = await response.text();
                
                postsContainer.innerHTML = html || '<p class="empty-tab-message">این کاربر هنوز پستی منتشر نکرده است.</p>';
                postsLoaded = true;
            } catch (error) {
                console.error("Failed to load posts:", error);
                postsContainer.innerHTML = '<p class="empty-tab-message" style="color: var(--danger-color);">خطا در بارگذاری پست‌ها.</p>';
            }
        }
    }

    // بارگذاری پست‌ها فقط زمانی که کاربر روی تب "پست‌ها" کلیک می‌کند
    if (postsTab) {
        postsTab.addEventListener('click', () => {
            if (!postsLoaded) {
                loadPosts();
            }
        });
    }

    // منطق ارسال فرم ایجاد پست
    if (createPostForm) {
        const postImageInput = document.getElementById('post-image-input');
        const imagePreviewContainer = document.getElementById('post-image-preview');

        // پیش‌نمایش تصویر انتخابی
        if (postImageInput && imagePreviewContainer) {
            postImageInput.addEventListener('change', () => {
                const file = postImageInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreviewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                    reader.readAsDataURL(file);
                } else {
                    imagePreviewContainer.innerHTML = '';
                }
            });
        }

        createPostForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(createPostForm);
            const submitButton = createPostForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'در حال انتشار...';

            try {
                const response = await fetch('create_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    createPostForm.reset();
                    if (imagePreviewContainer) imagePreviewContainer.innerHTML = '';
                    loadPosts(); // بارگذاری مجدد پست‌ها برای نمایش پست جدید
                } else {
                    alert(data.message || 'خطا در انتشار پست.');
                }
            } catch (error) {
                console.error("Post creation error:", error);
                alert('خطا در ارتباط با سرور.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'انتشار پست';
            }
        });
    }
});
