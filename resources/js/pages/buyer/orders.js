document.addEventListener('DOMContentLoaded', () => {
    const reviewModalOverlay = document.getElementById('reviewModalOverlay');
    const reviewModalProductName = document.getElementById('reviewModalProductName');
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('reviewRatingInput');
    const mediaInput = document.getElementById('reviewMediaInput');
    const mediaPreview = document.getElementById('reviewMediaPreview');

    let currentRating = 0;

    function paintStars(rating) {
        starButtons.forEach((btn, i) => {
            const filled = i < rating;
            btn.querySelector('svg').setAttribute('fill', filled ? '#1A5FB4' : 'none');
            btn.querySelector('svg').setAttribute('stroke', filled ? '#1A5FB4' : '#94A3B8');
        });
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
            reviewModalProductName.textContent = btn.dataset.productName;
            document.getElementById('reviewProductId').value = btn.dataset.productId;
            document.getElementById('reviewOrderItemId').value = btn.dataset.orderItemId;

            currentRating = 0;
            ratingInput.value = '';
            paintStars(0);
            document.getElementById('reviewComment').value = '';
            mediaInput.value = '';
            mediaPreview.innerHTML = '';

            reviewModalOverlay.classList.remove('hidden');
            reviewModalOverlay.classList.add('flex');
        });
    });

    document.getElementById('reviewModalCloseBtn')?.addEventListener('click', closeReviewModal);
    reviewModalOverlay?.addEventListener('click', (e) => {
        if (e.target === reviewModalOverlay) closeReviewModal();
    });

    function closeReviewModal() {
        reviewModalOverlay.classList.add('hidden');
        reviewModalOverlay.classList.remove('flex');
    }

    mediaInput?.addEventListener('change', function () {
        mediaPreview.innerHTML = '';
        Array.from(this.files).slice(0, 5).forEach(file => {
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

    document.getElementById('reviewSubmitBtn')?.addEventListener('click', function () {
        if (!currentRating) {
            alert('Please select a star rating.');
            return;
        }

        // TODO: wire to a real endpoint once the reviews table/backend exists.
        // For now this only previews the UI — nothing is saved.
        alert('Review UI captured — backend saving isn\'t built yet.');
        closeReviewModal();
    });
});
