document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('reviewModalOverlay');
    const productName = document.getElementById('reviewModalProductName');
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('reviewRatingInput');
    const commentInput = document.getElementById('reviewComment');
    const mediaInput = document.getElementById('reviewMediaInput');
    const mediaPreview = document.getElementById('reviewMediaPreview');
    const errorBox = document.getElementById('reviewError');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    const MAX_MEDIA = 5;

    let currentRating = 0;
    let currentAction = null;

    function paintStars(rating) {
        starButtons.forEach((btn, i) => {
            const svg = btn.querySelector('svg');
            const filled = i < rating;
            svg.setAttribute('fill', filled ? '#1A5FB4' : 'none');
            svg.setAttribute('stroke', filled ? '#1A5FB4' : '#94A3B8');
        });
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function closeModal() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    }

    starButtons.forEach((btn, i) => {
        btn.addEventListener('click', () => {
            currentRating = i + 1;
            ratingInput.value = currentRating;
            paintStars(currentRating);
        });
        btn.addEventListener('mouseenter', () => paintStars(i + 1));
        btn.addEventListener('mouseleave', () => paintStars(currentRating));
    });

    document.querySelectorAll('.write-review-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentAction = btn.dataset.action;
            productName.textContent = btn.dataset.productName;

            currentRating = 0;
            ratingInput.value = '';
            paintStars(0);
            commentInput.value = '';
            mediaInput.value = '';
            mediaPreview.innerHTML = '';
            clearError();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';

            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        });
    });

    document.getElementById('reviewModalCloseBtn')?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });

    mediaInput?.addEventListener('change', function () {
        mediaPreview.innerHTML = '';
        Array.from(this.files).slice(0, MAX_MEDIA).forEach(file => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative aspect-square overflow-hidden rounded-lg border border-light-gray bg-ice-blue/60';

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    wrapper.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover">`;
                };
                reader.readAsDataURL(file);
            } else {
                wrapper.innerHTML = `<div class="flex h-full w-full items-center justify-center text-[10px] font-semibold text-charcoal/50">VIDEO</div>`;
            }

            mediaPreview.appendChild(wrapper);
        });
    });

    submitBtn?.addEventListener('click', async () => {
        clearError();

        if (!currentRating) {
            showError('Please select a star rating.');
            return;
        }

        const fd = new FormData();
        fd.append('rating', currentRating);
        fd.append('comment', commentInput.value.trim());
        Array.from(mediaInput.files).slice(0, MAX_MEDIA).forEach(f => fd.append('media[]', f));

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        try {
            const res = await fetch(currentAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                showError(firstError || data.message || 'Something went wrong. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
                return;
            }

            window.location.reload(); // the flashed status banner shows "Thanks for your review!"
        } catch {
            showError('Network error. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    });
});
