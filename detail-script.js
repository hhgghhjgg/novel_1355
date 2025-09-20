/*
=====================================================
    NovelWorld - Detail Page Script (detail-script.js)
    Version: 1.2 (All Modules Included)
=====================================================
*/

// این رویداد مطمئن می‌شود که تمام کدهای جاوااسکریپت پس از بارگذاری کامل صفحه اجرا شوند.
document.addEventListener('DOMContentLoaded', () => {

    // --- ماژول ۱: سیستم تب‌بندی ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabLinks.length > 0 && tabContents.length > 0) {
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
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

    // --- ماژول ۲، ۳ و ۴ با استفاده از Event Delegation ---
    // ما یک event listener به یک والد بزرگ اضافه می‌کنیم تا برای تمام کامنت‌ها (حتی آنهایی که در آینده اضافه می‌شوند) کار کند.
    const detailContainer = document.querySelector('.detail-container');
    if (detailContainer) {
        detailContainer.addEventListener('click', function(e) {
            
            // --- منطق نمایش اسپویلر ---
            const spoiler = e.target.closest('.spoiler:not(.revealed)');
            if (spoiler) {
                spoiler.classList.add('revealed');
            }

            // --- منطق فرم داینامیک ریپلای ---
            const replyButton = e.target.closest('.reply-btn');
            if (replyButton) {
                const commentBox = replyButton.closest('.comment-box');
                const existingForm = commentBox.querySelector('.reply-form-box');
                
                if (existingForm) {
                    existingForm.remove();
                    return;
                }

                document.querySelectorAll('.reply-form-box').forEach(form => form.remove());

                const parentId = commentBox.id.split('-')[1];
                const novelIdInput = document.querySelector('input[name="novel_id"]');
                if (!novelIdInput) return;
                const novelId = novelIdInput.value;

                const replyFormBox = document.createElement('div');
                replyFormBox.className = 'comment-form-box reply-form-box';
                replyFormBox.innerHTML = `
                    <h5>پاسخ به ${commentBox.querySelector('.username').textContent.trim()}</h5>
                    <form action="submit_comment.php" method="POST">
                        <input type="hidden" name="novel_id" value="${novelId}">
                        <input type="hidden" name="parent_id" value="${parentId}">
                        <textarea name="content" placeholder="پاسخ شما..." rows="3" required></textarea>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary">ارسال پاسخ</button>
                        </div>
                    </form>
                `;
                commentBox.appendChild(replyFormBox);
                replyFormBox.querySelector('textarea').focus();
            }

            // --- منطق لایک و دیسلایک با AJAX ---
            const actionButton = e.target.closest('.like-btn, .dislike-btn');
            if (actionButton) {
                const action = actionButton.dataset.action;
                const commentId = actionButton.dataset.commentId;

                fetch('comment_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, comment_id: commentId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const countSpan = actionButton.querySelector('span');
                        if(countSpan) {
                           countSpan.textContent = data.new_count;
                        }
                        
                        actionButton.classList.add('action-success');
                        setTimeout(() => {
                            actionButton.classList.remove('action-success');
                        }, 500);

                    } else {
                        alert(data.message || 'خطایی رخ داد. لطفاً ابتدا وارد شوید.');
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('ارتباط با سرور برقرار نشد.');
                });
            }
        });
    }
});
