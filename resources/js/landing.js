function initMobileMenu() {
    const button = document.getElementById('mobile-menu-button');
    const menu = document.getElementById('mobile-menu');
    if (!button || !menu) return;

    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    button.addEventListener('click', () => setOpen(menu.classList.contains('hidden')));
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
}

function initSearchSuggestions() {
    const wrapper = document.getElementById('searchWrapper');
    const input = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions');
    if (!wrapper || !input || !suggestions) return;

    input.addEventListener('focus', () => suggestions.classList.remove('hidden'));
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) suggestions.classList.add('hidden');
    });

    // Clicking a trending term fills the box and searches it
    suggestions.querySelectorAll('[data-trend]').forEach((chip) => {
        chip.addEventListener('click', () => {
            input.value = chip.dataset.trend;
            input.form?.submit();
        });
    });
}

function initHeroCarousel() {
    const root = document.getElementById('heroCarousel');
    if (!root) return;

    const slides = Array.from(root.querySelectorAll('.hero-slide'));
    const dots = Array.from(root.querySelectorAll('.hero-dot'));
    if (slides.length < 2) return;

    let index = 0;
    let timer;

    const show = (i) => {
        slides[index].classList.remove('is-active');
        dots[index].classList.remove('is-active');
        index = (i + slides.length) % slides.length;
        slides[index].classList.add('is-active');
        dots[index].classList.add('is-active');
    };

    const start = () => {
        clearInterval(timer);
        timer = setInterval(() => show(index + 1), 5000);
    };

    dots.forEach((dot, i) => dot.addEventListener('click', () => {
        show(i);
        start();
    }));

    root.addEventListener('mouseenter', () => clearInterval(timer));
    root.addEventListener('mouseleave', start);
    start();
}

function initScrollReveal() {
    const targets = document.querySelectorAll('.reveal, .reveal-section');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (!entry.isIntersecting) return;
            entry.target.style.transitionDelay = `${(i % 6) * 60}ms`;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0, rootMargin: '150px 0px 150px 0px' });

    targets.forEach((el) => observer.observe(el));
}

/**
 * Images marked data-fallback="hide" are removed if they fail to load,
 * so the coloured background behind them shows instead of a broken icon.
 * data-fallback="<url>" swaps in that image once.
 */
function initImageFallbacks() {
    const handle = (img) => {
        const fallback = img.dataset.fallback;
        if (!fallback || img.dataset.fallbackDone) return;
        img.dataset.fallbackDone = '1';

        if (fallback === 'hide') {
            img.style.display = 'none';
        } else {
            img.src = fallback;
        }
    };

    // "error" doesn't bubble, so listen in the capture phase
    document.addEventListener('error', (e) => {
        if (e.target instanceof HTMLImageElement) handle(e.target);
    }, true);

    // Catch images that failed before this script ran
    document.querySelectorAll('img[data-fallback]').forEach((img) => {
        if (img.complete && img.naturalWidth === 0) handle(img);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initSearchSuggestions();
    initHeroCarousel();
    initScrollReveal();
    initImageFallbacks();
});
