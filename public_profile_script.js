// public_profile_script.js
// نسخه کامل و اصلاح شده برای مدیریت تمام تعاملات صفحه پروفایل عمومی

document.addEventListener('DOMContentLoaded', () => {

    // --- بخش ۱: متغیرهای اصلی و خواندن شناسه کاربر از HTML ---

    const mainContainer = document.querySelector('.profile-page-container');
    // **راه حل کلیدی:** خواندن شناسه کاربر از data-attribute که در PHP قرار دادیم.
    const profileUserId = mainContainer ? mainContainer.dataset.profileUserid : null;

    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const postsContainer = document.getElementById('posts-container');
    let postsLoaded = false; // متغیری برای جلوگیری از بارگذاری مجدد پست‌ها


    // --- بخش ۲: مدیریت سیستم تب‌بندی (آثار و پست‌ها) ---

    tabs.forEach(tab => {
        // فقط به دکمه‌ها event listener اضافه می‌کنیم، نه لینک‌ها
        if (tab.tagName === 'BUTTON') {
            tab.addEventListener('click', () => {
                const targetTabId = tab.dataset.tab;

                // غیرفعال کردن همه تب‌ها و محتواها
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // فعال کردن تب و محتوای کلیک شده
                tab.classList.add('active');
                const targetContent = document.getElementById(targetTabId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }

                // اگر تب "پست‌ها" انتخاب شد و هنوز بارگذاری نشده، آن را بارگذاری کن
                if (targetTabId === 'posts-tab' && !postsLoaded) {
                    loadPosts();
                }
            });
        }
    });


    // --- بخش ۳: بارگذاری داینامیک پست‌ها (AJAX) ---

    async function loadPosts() {
        if (!profileUserId) {
            postsContainer.innerHTML = "<p class='empty-tab-message' style='color: var(--danger-color);'>خطا: شناسه کاربر برای بارگذاری پست‌ها یافت نشد.</p>";
            return;
        }

        postsContainer.innerHTML = "<p class='empty-tab-message'>در حال بارگذاری پست‌ها...</p>";

        try {
            // **اصلاح کلیدی:** ارسال شناسه کاربر صحیح در URL
            const response = await fetch(`load_posts.php?user_id=${profileUserId}`);
            if (!response.ok) {
                throw new Error('خطا در ارتباط با سرور.');
            }
            const html = await response.text();
            postsContainer.innerHTML = html;
            postsLoaded = true; // علامت‌گذاری به عنوان بارگذاری شده
        } catch (error) {
            console.error('Error loading posts:', error);
            postsContainer.innerHTML = "<p class='empty-tab-message' style='color: var(--danger-color);'>خطایی در بارگذاری پست‌ها رخ داد.</p>";
        }
    }


    // --- بخش ۴: مدیریت دکمه دنبال کردن / لغو دنبال کردن (Follow/Unfollow) ---

    const followToggleBtn = document.getElementById('follow-toggle-btn');
    if (followToggleBtn) {
        followToggleBtn.addEventListener('click', async () => {
            const profileId = followToggleBtn.dataset.profileId;
            followToggleBtn.disabled = true; // جلوگیری از کلیک‌های متعدد

            try {
                const response = await fetch('toggle_follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ profile_id: profileId })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.action === 'followed') {
                        followToggleBtn.textContent = 'لغو دنبال';
                        followToggleBtn.classList.remove('btn-primary');
                        followToggleBtn.classList.add('btn-secondary');
                    } else {
                        followToggleBtn.textContent = 'دنبال کردن';
                        followToggleBtn.classList.remove('btn-secondary');
                        followToggleBtn.classList.add('btn-primary');
                    }
                } else {
                    alert(data.message || 'خطایی رخ داد.');
                }
            } catch (error) {
                console.error('Follow toggle error:', error);
                alert('خطا در ارتباط با سرور.');
            } finally {
                followToggleBtn.disabled = false;
            }
        });
    }

    
    // --- بخش ۵: مدیریت فرم ایجاد پست (Create Post) ---

    const createPostForm = document.getElementById('create-post-form');
    const postImageInput = document.getElementById('post-image-input');
    const postImagePreview = document.getElementById('post-image-preview');

    if (createPostForm) {
        // نمایش پیش‌نمایش تصویر
        postImageInput.addEventListener('change', () => {
            const file = postImageInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    postImagePreview.innerHTML = `<img src="${e.target.result}" alt="پیش‌نمایش تصویر">`;
                };
                reader.readAsDataURL(file);
            } else {
                postImagePreview.innerHTML = '';
            }
        });

        // ارسال فرم
        createPostForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = createPostForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'در حال انتشار...';

            const formData = new FormData(createPostForm);

            try {
                const response = await fetch('create_post.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();

                if (response.ok && data.success) {
                    // موفقیت‌آمیز بود
                    createPostForm.reset();
                    postImagePreview.innerHTML = '';
                    postsLoaded = false; // برای بارگذاری مجدد پست‌ها با پست جدید
                    loadPosts(); // بارگذاری مجدد پست‌ها برای نمایش پست جدید
                } else {
                    // خطا از سمت سرور
                    alert(data.message || 'خطایی در انتشار پست رخ داد.');
                }
            } catch (error) {
                console.error('Create post error:', error);
                alert('خطا در ارتباط با سرور. لطفاً از اتصال اینترنت خود مطمئن شوید.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'انتشار پست';
            }
        });
    }


    // --- بخش ۶: مدیریت مودال‌ها (ایجاد استوری و منوی گزینه‌ها) ---
    
    // منوی گزینه‌های پروفایل (سه نقطه)
    const profileActionsToggle = document.getElementById('profile-actions-toggle');
    const profileActionsDropdown = document.getElementById('profile-actions-dropdown');

    if (profileActionsToggle) {
        profileActionsToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // جلوگیری از بسته شدن فوری توسط event listener بدنه
            profileActionsDropdown.classList.toggle('open');
        });
    }
    
    // بستن منو با کلیک بیرون از آن
    document.addEventListener('click', () => {
        if (profileActionsDropdown && profileActionsDropdown.classList.contains('open')) {
            profileActionsDropdown.classList.remove('open');
        }
    });

    // مودال ایجاد استوری
    const createStoryModal = document.getElementById('create-story-modal');
    const openStoryModalLink = document.getElementById('create-story-link');
    const closeStoryModalBtn = createStoryModal ? createStoryModal.querySelector('.close-modal-btn') : null;
    const createStoryForm = document.getElementById('create-story-form');

    function toggleStoryModal(show) {
        if (createStoryModal) {
            createStoryModal.classList.toggle('open', show);
        }
    }

    if (openStoryModalLink) {
        openStoryModalLink.addEventListener('click', (e) => {
            e.preventDefault();
            toggleStoryModal(true);
        });
    }
    if (closeStoryModalBtn) {
        closeStoryModalBtn.addEventListener('click', () => toggleStoryModal(false));
    }
    if (createStoryModal) {
        createStoryModal.addEventListener('click', (e) => {
            if (e.target === createStoryModal) { // فقط اگر روی پس‌زمینه کلیک شد
                toggleStoryModal(false);
            }
        });
    }

    // ارسال فرم ایجاد استوری
    if (createStoryForm) {
        createStoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const novelId = createStoryForm.querySelector('#story-novel-select').value;
            const title = createStoryForm.querySelector('#story-title-input').value;
            
            try {
                const response = await fetch('create_story.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ novel_id: novelId, title: title })
                });
                const data = await response.json();
                alert(data.message);
                if(data.success) {
                    toggleStoryModal(false);
                    // برای نمایش استوری جدید، صفحه باید رفرش شود
                    window.location.reload();
                }
            } catch (error) {
                alert('خطا در ارتباط با سرور.');
            }
        });
    }

    // --- بخش ۷: مدیریت رویدادهای داخل پست‌ها (لایک، دیسلایک) با Event Delegation ---

    if (postsContainer) {
        postsContainer.addEventListener('click', async (e) => {
            const likeBtn = e.target.closest('.like-btn');
            const dislikeBtn = e.target.closest('.dislike-btn');
            
            if (!likeBtn && !dislikeBtn) return; // اگر روی دکمه‌ای کلیک نشده بود، خارج شو

            const button = likeBtn || dislikeBtn;
            const postId = button.dataset.postId;
            const action = button.dataset.action;

            // جلوگیری از رای دادن کاربر لاگین نکرده
            // (این متغیرها از فایل read_chapter.php به صورت گلوبال تعریف شده‌اند،
            // باید مطمئن شویم در public_profile.php هم وجود دارند. بهتر است از is_logged_in که در PHP داریم استفاده کنیم)
            // if (!USER_IS_LOGGED_IN) { 
            //     window.location.href = 'login.php';
            //     return;
            // }
            
            try {
                const response = await fetch('post_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId, action: action })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const postCard = button.closest('.post-card');
                    if (postCard) {
                        postCard.querySelector('.like-btn span').textContent = data.data.new_likes;
                        postCard.querySelector('.dislike-btn span').textContent = data.data.new_dislikes;
                        button.classList.add('action-success'); // تغییر استایل دکمه
                        button.disabled = true; // غیرفعال کردن دکمه پس از رای
                        // غیرفعال کردن دکمه مخالف
                        const otherButton = action === 'like' ? postCard.querySelector('.dislike-btn') : postCard.querySelector('.like-btn');
                        if (otherButton) otherButton.disabled = true;
                    }
                } else {
                    alert(data.message || 'شما قبلاً رای داده‌اید یا خطایی رخ داده است.');
                    button.disabled = true;
                }
            } catch (error) {
                console.error('Post action error:', error);
                alert('خطا در ارتباط با سرور.');
            }
        });
    }

}); // End of DOMContentLoaded
