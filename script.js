/*
=====================================================
    NovelWorld - Main JavaScript File (script.js)
    Version: 1.2 (All Modules Included)
=====================================================
*/

// این رویداد مطمئن می‌شود که تمام کدها پس از بارگذاری کامل صفحه اجرا شوند.
document.addEventListener('DOMContentLoaded', () => {

    // --- ماژول ۱: منطق Hero Slider صفحه اصلی ---
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    if (slides.length > 0 && dots.length > 0) {
        let currentSlide = 0;
        const slideInterval = 7000;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            currentSlide = index;
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetAutoPlay();
            });
        });

        let autoPlayInterval = setInterval(() => {
            let nextSlide = (currentSlide + 1) % slides.length;
            showSlide(nextSlide);
        }, slideInterval);

        function resetAutoPlay() {
            clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(() => {
                let nextSlide = (currentSlide + 1) % slides.length;
                showSlide(nextSlide);
            }, slideInterval);
        }
        
        showSlide(0);
    }


    // --- ماژول ۲: جستجوی زنده (Live Search) ---
    const searchInput = document.getElementById('live-search-input');
    const searchResults = document.getElementById('search-results');
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length > 1) {
                fetch(`live_search.php?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(manhwa => {
                                const item = document.createElement('a');
                                item.href = `detail.php?id=${manhwa.id}`;
                                item.className = 'search-result-item';
                                item.innerHTML = `<img src="${manhwa.cover_url}" alt=""><span>${manhwa.title_fa}</span>`;
                                searchResults.appendChild(item);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                searchResults.style.display = 'none';
            }
        });
        document.addEventListener('click', function(e) {
            if (searchInput.parentElement && !searchInput.parentElement.contains(e.target)) {
                 searchResults.style.display = 'none';
            }
        });
    }

    
    // --- ماژول ۳: منطق منوی کناری (Sidebar/Hamburger Menu) ---
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebarMenu = document.getElementById('sidebar-menu');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        if (sidebarMenu && sidebarOverlay) {
            sidebarMenu.classList.add('open');
            sidebarOverlay.classList.add('open');
        }
    }

    function closeSidebar() {
        if (sidebarMenu && sidebarOverlay) {
            sidebarMenu.classList.remove('open');
            sidebarOverlay.classList.remove('open');
        }
    }

    if (hamburgerBtn && sidebarMenu && sidebarOverlay) {
        hamburgerBtn.addEventListener('click', openSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        // بستن منو با کلید Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape" && sidebarMenu.classList.contains('open')) {
                closeSidebar();
            }
        });
    }

});
