// detail-script.js

/*
=====================================================
    NovelWorld - Detail Page Script (Final, Unabridged)
    Version: 2.0 (Fully AJAX)
=====================================================
    - این اسکریپت تمام تعاملات صفحه جزئیات ناول را مدیریت می‌کند.
    - از Event Delegation برای بهینه‌سازی عملکرد استفاده می‌کند.
    - تمام عملیات (لایک، دیسلایک، ارسال نظر) به صورت AJAX و بدون
      رفرش صفحه انجام می‌شوند.
*/

document.addEventListener('DOMContentLoaded', () => {

    // --- ماژول ۱: سیستم تب‌بندی ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabLinks.length > 0 && tabContents.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tabId = link.dataset.tab;

                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => item.classList.remove('active'));

                link.classList.add('active');
                const activeTabContent = document.getElementById(tabId);
                if (activeTabContent) {
                    activeTabContent.classList.add('active');
                }
            });
        });
    }


    // --- ماژول ۲: استفاده از Event Delegation برای تمام تعاملات دیگر ---
    // یک event listener به یک والد بزرگ (detail-container) اضافه می‌کنیم تا برای
    // تمام عناصر داخلی (حتی آنهایی که بعداً اضافه می‌شوند) کار کند.
    const detailContainer = document.querySelector('.detail-container');
    if (detailContainer) {

        // --- مدیریت رویدادهای کلیک ---
        detailContainer.addEventListener('click', async (e) => {
            
            // --- منطق نمایش اسپویلر ---
            const spoiler = e.target.closest('.spoiler:not(.revealed)');
            if (spoiler) {
                spoiler.classList.add('revealed');
            }


            // --- منطق فرم داینامیک ریپلای ---
            const replyButton = e.target.closest('.reply-btn');
            if (replyButton) {
                const commentBox = replyButton.closest('.comment-box');
                // ابتدا تمام فرم‌های ریپلای دیگر را حذف می‌کنیم
                document.querySelectorAll('.reply-form-box').forEach(form => form.remove());
                
                // اگر فرمی از قبل در همین کامنت باکس بود، آن را حذف کن و تمام
                const existingForm = commentBox.querySelector('.reply-form-box');
                if (existingForm) {
                    existingForm.remove();
                    return;
                }

                // گرفتن اطلاعات لازم برای ساخت فرم
                const parentId = commentBox.id.split('-')[1];
                const novelIdInput = document.querySelector('input[name="novel_id"]');
                if (!novelIdInput) return;
                const novelId = novelIdInput.value;
                const username = commentBox.querySelector('.username').textContent.trim();

                // ساختن HTML فرم ریپلای
                const replyFormBox = document.createElement('div');
                replyFormBox.className = 'comment-form-box reply-form-box';
                replyFormBox.innerHTML = `
                    <h5>پاسخ به ${username}</h5>
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="${novelId}">
                        <input type="hidden" name="parent_id" value="${parentId}">
                        <textarea name="content" placeholder="پاسخ شما..." rows="3" required></textarea>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">ارسال پاسخ</button>
                        </div>
                    </form>
                `;
                // اضافه کردن فرم به انتهای کامنت باکس و فوکوس روی آن
                commentBox.appendChild(replyFormBox);
                replyFormBox.querySelector('textarea').focus();
            }


            // --- منطق لایک و دیسلایک با AJAX ---
            const actionButton = e.target.closest('.like-btn, .dislike-btn');
            if (actionButton) {
                e.preventDefault();
                const action = actionButton.dataset.action;
                const commentId = actionButton.dataset.commentId;
                const countSpan = actionButton.querySelector('span');
                
                // جلوگیری از کلیک‌های متعدد تا زمان دریافت پاسخ
                if (actionButton.classList.contains('processing')) return;
                actionButton.classList.add('processing');

                try {
                    const response = await fetch('comment_actions.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: action, comment_id: commentId })
                    });
                    const data = await response.json();

                    if (data.success) {
                        if (countSpan) countSpan.textContent = data.data.new_count;
                        actionButton.classList.add('action-success');
                        setTimeout(() => actionButton.classList.remove('action-success'), 500);
                    } else {
                        alert(data.message || 'خطایی رخ داد.');
                    }
                } catch (error) {
                    alert('ارتباط با سرور برقرار نشد.');
                } finally {
                    actionButton.classList.remove('processing');
                }
            }
        });


        // --- مدیریت رویدادهای ارسال فرم ---
        detailContainer.addEventListener('submit', async (e) => {
            const form = e.target;

            // فقط فرم‌های ارسال نظر را مدیریت می‌کنیم
            if (form.matches('.comment-form-box form')) {
                e.preventDefault();
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.textContent = 'در حال ارسال...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('submit_comment.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        // در یک اپلیکیشن پیشرفته‌تر، می‌توان بخش نظرات را به صورت داینامیک
                        // با fetch مجدد آپدیت کرد. برای سادگی، فعلاً صفحه را رفرش می‌کنیم.
                        window.location.reload();
                    } else {
                        const errorText = await response.text();
                        throw new Error(errorText || 'خطا در ارسال نظر.');
                    }
                } catch (error) {
                    alert(error.message);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            }
        });
    }
});
