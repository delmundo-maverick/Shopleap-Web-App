function initCarousel(root) {
    const track = root.querySelector('[data-carousel-track]');
    const slides = Array.from(root.querySelectorAll('[data-carousel-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-carousel-dot]'));
    const toggle = root.querySelector('[data-carousel-toggle]');

    if (!track || slides.length < 2) return;

    const interval = Number(root.dataset.autoplay) || 0;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let index = 0;
    let timer = null;
    let userPaused = prefersReducedMotion; // never auto-advance for people who asked for less motion
    let hovering = false;

    function schedule() {
        clearTimeout(timer);

        if (!interval || userPaused || hovering || document.hidden) return;

        timer = setTimeout(() => go(index + 1), interval);
    }

    function render() {
        track.style.transform = `translateX(-${index * 100}%)`;

        slides.forEach((slide, i) => {
            const active = i === index;
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            slide.inert = !active; // keeps hidden slides' links out of the tab order
        });

        dots.forEach((dot, i) => {
            const active = i === index;
            dot.setAttribute('aria-current', active ? 'true' : 'false');
            dot.classList.toggle('w-5', active);
            dot.classList.toggle('bg-white', active);
            dot.classList.toggle('w-2', !active);
            dot.classList.toggle('bg-white/50', !active);
        });
    }

    function renderToggle() {
        if (!toggle) return;

        toggle.setAttribute('aria-label', userPaused ? 'Play slideshow' : 'Pause slideshow');
        toggle.querySelector('[data-icon-pause]')?.classList.toggle('hidden', userPaused);
        toggle.querySelector('[data-icon-play]')?.classList.toggle('hidden', !userPaused);
    }

    function go(i) {
        index = (i + slides.length) % slides.length;
        render();
        schedule();
    }

    root.querySelector('[data-carousel-prev]')?.addEventListener('click', () => go(index - 1));
    root.querySelector('[data-carousel-next]')?.addEventListener('click', () => go(index + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));

    toggle?.addEventListener('click', () => {
        userPaused = !userPaused;
        renderToggle();
        schedule();
    });

    // Pause while the pointer or keyboard focus is inside the carousel.
    root.addEventListener('mouseenter', () => {
        hovering = true;
        schedule();
    });
    root.addEventListener('mouseleave', () => {
        hovering = false;
        schedule();
    });
    root.addEventListener('focusin', () => {
        hovering = true;
        schedule();
    });
    root.addEventListener('focusout', () => {
        hovering = false;
        schedule();
    });

    document.addEventListener('visibilitychange', schedule);

    // Swipe on touch screens.
    let startX = null;
    root.addEventListener(
        'touchstart',
        (e) => {
            startX = e.touches[0].clientX;
        },
        { passive: true }
    );
    root.addEventListener('touchend', (e) => {
        if (startX === null) return;

        const dx = e.changedTouches[0].clientX - startX;
        startX = null;

        if (Math.abs(dx) > 40) {
            go(index + (dx < 0 ? 1 : -1));
        }
    });

    // Left / right arrow keys while a control inside the carousel has focus.
    root.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') go(index - 1);
        if (e.key === 'ArrowRight') go(index + 1);
    });

    render();
    renderToggle();
    schedule();
}

document.querySelectorAll('[data-carousel]').forEach(initCarousel);
