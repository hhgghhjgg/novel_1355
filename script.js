/*
=====================================================
    NovelWorld - Main JavaScript File
    Version: 3.0 (Unified & Modular)
=====================================================
    - این فایل شامل تمام اسکریپت‌های اصلی سایت است.
    - به صورت هوشمند تشخیص می‌دهد در کدام صفحه است و فقط کدهای مربوطه را اجرا می‌کند.
*/

document.addEventListener('DOMContentLoaded', () => {

    // ===================================
    // ۱. ماژول‌های سراسری (Global Modules)
    // ===================================

    /**
     * مدیریت سایدبار موبایل (منوی همبرگری)
     */
    const initSidebar = () => {
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sidebarMenu = document.getElementById('sidebar-menu');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (hamburgerBtn && sidebarMenu && sidebarOverlay) {
            hamburgerBtn.addEventListener('click', () => {
                sidebarMenu.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');
            });

            sidebarOverlay.addEventListener('click', () => {
                sidebarMenu.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }
    };

    /**
     * بارگذاری تنبل (Lazy Loading) برای تمام تصاویر سایت
     */
    const initLazyLoading = () => {
        const lazyImages = document.querySelectorAll('img.lazy-load, img[data-src]');
        if ('IntersectionObserver' in window) {
            let lazyImageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        let lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.add('loaded');
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });

            lazyImages.forEach((lazyImage) => {
                lazyImageObserver.observe(lazyImage);
            });
        } else {
            // Fallback برای مرورگرهای قدیمی
            lazyImages.forEach(img => img.src = img.dataset.src);
        }
    };

    // اجرای ماژول‌های سراسری
    initSidebar();
    initLazyLoading();


    // ===================================
    // ۲. اسکریپت‌های مخصوص هر صفحه
    // ===================================

    /**
     * منطق صفحه اصلی (index.php)
     */
    const initIndexPage = () => {
        // --- منطق هیرو اسلایدر ---
        const heroCarousel = document.querySelector('.hero-carousel');
        if (heroCarousel) {
            const heroCards = heroCarousel.querySelectorAll('.hero-card');
            const heroTitle = document.getElementById('hero-title');
            const heroSummary = document.getElementById('hero-summary');
            const heroLink = document.getElementById('hero-link');
            const heroBackground = document.querySelector('.hero-background');
            let currentIndex = 0;
            let slideInterval;

            const updateHeroContent = (index) => {
                const card = heroCards[index];
                if (!card) return;

                heroTitle.textContent = card.dataset.title;
                heroSummary.textContent = card.dataset.summary;
                heroLink.href = card.dataset.link;

                heroBackground.style.opacity = 0;
                setTimeout(() => {
                    heroBackground.style.backgroundImage = `url('${card.dataset.bg}')`;
                    heroBackground.style.opacity = 1;
                }, 300);

                heroCarousel.querySelector('.hero-card.active')?.classList.remove('active');
                card.classList.add('active');
                currentIndex = index;
            };

            const nextSlide = () => {
                const nextIndex = (currentIndex + 1) % heroCards.length;
                updateHeroContent(nextIndex);
            };

            const startAutoplay = () => {
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 5000);
            };

            heroCards.forEach((card, index) => {
                card.addEventListener('click', () => {
                    updateHeroContent(index);
                    startAutoplay();
                });
            });

            if (heroCards.length > 1) {
                startAutoplay();
            }
            if (heroBackground) {
                heroBackground.style.transition = 'opacity 0.3s ease-in-out';
            }
        }
    };

    /**
     * منطق صفحه جزئیات اثر (novel_detail.php)
     */
    const initDetailPage = () => {
        // --- سیستم تب‌بندی ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                link.classList.add('active');
                document.getElementById(link.dataset.tab).classList.add('active');
            });
        });

        // --- مدیریت اسپویلر ---
        document.body.addEventListener('click', (e) => {
            if (e.target.matches('.comment-body.spoiler')) {
                e.target.classList.add('revealed');
            }
        });
        
        // --- مدیریت دکمه افزودن/حذف از کتابخانه ---
        const toggleBtn = document.getElementById('library-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                toggleBtn.disabled = true;
                const novelId = toggleBtn.dataset.novelId;
                const btnSpan = toggleBtn.querySelector('span');
                try {
                    const response = await fetch('toggle_library.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ novel_id: novelId })
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'خطای سرور');

                    if (data.action === 'added') {
                        if (btnSpan) btnSpan.textContent = 'حذف از کتابخانه';
                        toggleBtn.classList.remove('btn-secondary');
                        toggleBtn.classList.add('btn-danger');
                    } else {
                        if (btnSpan) btnSpan.textContent = 'افزودن به کتابخانه';
                        toggleBtn.classList.remove('btn-danger');
                        toggleBtn.classList.add('btn-secondary');
                    }
                } catch (error) {
                    alert('خطا: ' + error.message);
                } finally {
                    toggleBtn.disabled = false;
                }
            });
        }
    };

    /**
     * منطق صفحه پروفایل عمومی (public_profile.php)
     */
    const initPublicProfilePage = () => {
        const container = document.querySelector('.profile-page-container');
        const profileUserId = container.dataset.profileUserid;

        // --- مدیریت تب‌ها ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        let postsLoaded = false;
        
        tabLinks.forEach(link => {
            link.addEventListener('click', async (e) => {
                if (link.classList.contains('donate-link')) return;
                e.preventDefault();
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                link.classList.add('active');
                const activeTab = document.getElementById(link.dataset.tab);
                activeTab.classList.add('active');

                // بارگذاری داینامیک پست‌ها فقط در اولین کلیک
                if (link.dataset.tab === 'posts-tab' && !postsLoaded) {
                    const postsContainer = document.getElementById('posts-container');
                    postsContainer.innerHTML = '<p class="empty-tab-message">در حال بارگذاری پست‌ها...</p>';
                    try {
                        const response = await fetch(`load_posts.php?user_id=${profileUserId}`);
                        postsContainer.innerHTML = await response.text();
                        postsLoaded = true;
                    } catch (error) {
                        postsContainer.innerHTML = '<p class="empty-tab-message" style="color:var(--danger-color);">خطا در بارگذاری پست‌ها.</p>';
                    }
                }
            });
        });

        // --- دکمه دنبال/لغو دنبال کردن ---
        const followBtn = document.getElementById('follow-toggle-btn');
        if(followBtn) {
            followBtn.addEventListener('click', async () => {
                followBtn.disabled = true;
                try {
                    const response = await fetch('toggle_follow.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ profile_id: profileUserId })
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'خطای سرور');

                    if(data.action === 'followed') {
                        followBtn.textContent = 'لغو دنبال';
                        followBtn.classList.remove('btn-primary');
                        followBtn.classList.add('btn-secondary');
                    } else {
                        followBtn.textContent = 'دنبال کردن';
                        followBtn.classList.remove('btn-secondary');
                        followBtn.classList.add('btn-primary');
                    }
                } catch (error) {
                    alert('خطا: ' + error.message);
                } finally {
                    followBtn.disabled = false;
                }
            });
        }
        
        // --- فرم ایجاد پست ---
        const createPostForm = document.getElementById('create-post-form');
        if (createPostForm) {
            createPostForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(createPostForm);
                const submitBtn = createPostForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'در حال انتشار...';

                try {
                    const response = await fetch('create_post.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if(!response.ok) throw new Error(data.message || 'خطا در ارسال پست.');
                    
                    // ریست کردن فرم و بارگذاری مجدد پست‌ها
                    createPostForm.reset();
                    document.getElementById('post-image-preview').innerHTML = '';
                    postsLoaded = false; // برای اجبار به بارگذاری مجدد
                    document.querySelector('.tab-link[data-tab="posts-tab"]').click();

                } catch(error) {
                    alert('خطا: ' + error.message);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'انتشار پست';
                }
            });
             // پیش‌نمایش تصویر پست
            const imageInput = document.getElementById('post-image-input');
            const imagePreview = document.getElementById('post-image-preview');
            imageInput.addEventListener('change', () => {
                const file = imageInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="پیش‌نمایش"><button type="button" class="remove-preview-btn">&times;</button>`;
                        imagePreview.querySelector('.remove-preview-btn').addEventListener('click', () => {
                            imageInput.value = '';
                            imagePreview.innerHTML = '';
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // --- مودال ایجاد استوری ---
        const createStoryLink = document.getElementById('create-story-link');
        const storyModal = document.getElementById('create-story-modal');
        if (createStoryLink && storyModal) {
            const closeModalBtn = storyModal.querySelector('.close-modal-btn');
            
            createStoryLink.addEventListener('click', e => { e.preventDefault(); storyModal.style.display = 'flex'; });
            closeModalBtn.addEventListener('click', () => { storyModal.style.display = 'none'; });
            storyModal.addEventListener('click', e => { if (e.target === storyModal) storyModal.style.display = 'none'; });

            const createStoryForm = document.getElementById('create-story-form');
            createStoryForm.addEventListener('submit', async (e) => {
                 e.preventDefault();
                 const novelId = createStoryForm.querySelector('select').value;
                 const title = createStoryForm.querySelector('input').value;
                 
                 try {
                     const response = await fetch('create_story.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ novel_id: novelId, title: title })
                    });
                    const data = await response.json();
                    if(!response.ok) throw new Error(data.message || 'خطا در ایجاد استوری.');
                    
                    alert('استوری با موفقیت ایجاد شد!');
                    window.location.reload(); // رفرش صفحه برای نمایش استوری جدید
                 } catch(error) {
                    alert('خطا: ' + error.message);
                 }
            });
        }
    };
    
    /**
     * منطق صفحه جستجو (search.php)
     */
    const initSearchPage = () => {
        const openBtn = document.getElementById('open-filters-btn');
        const closeBtn = document.getElementById('close-modal-btn');
        const modal = document.getElementById('filters-modal');

        if(openBtn && closeBtn && modal) {
            openBtn.addEventListener('click', () => modal.classList.add('active'));
            closeBtn.addEventListener('click', () => modal.classList.remove('active'));
            modal.addEventListener('click', (e) => {
                if(e.target === modal) modal.classList.remove('active');
            });
        }
    };


    // ===================================
    // ۳. راه‌انداز (Initializer)
    // ===================================
    
    // تشخیص صفحه فعلی و اجرای کدهای مربوطه
    if (document.querySelector('.cinematic-hero')) {
        initIndexPage();
    }
    if (document.querySelector('.detail-container')) {
        initDetailPage();
    }
    if (document.querySelector('.profile-page-container')) {
        initPublicProfilePage();
    }
    if (document.querySelector('.search-page-container')) {
        initSearchPage();
    }

});```
