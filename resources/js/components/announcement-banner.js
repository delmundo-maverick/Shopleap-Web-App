document.querySelectorAll('.announcement-banner').forEach((banner) => {
    const key = `dismissed_announcement_${banner.dataset.announcementId}`;
    try {
        if (localStorage.getItem(key)) {
            banner.remove();
            return;
        }
    } catch {
        // Storage can be unavailable in privacy-restricted browsers.
    }

    banner.querySelector('.announcement-dismiss-btn')?.addEventListener('click', () => {
        try {
            localStorage.setItem(key, '1');
        } catch {
            // Dismiss the banner even when the preference cannot be persisted.
        }
        banner.remove();
    });
});
