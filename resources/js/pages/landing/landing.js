let registered = false;

export function registerLandingEffects(Alpine) {
    if (registered || !document.body.classList.contains('landing-page')) { return; }
    registered = true;

    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const pending = new Set();
    const timers = new Map();
    let observer;

    function finish(element) {
        pending.delete(element);
        observer?.unobserve(element);
        clearTimeout(timers.get(element));
        timers.delete(element);
        element.classList.remove('reveal-pending', 'reveal-animating');
        element.style.removeProperty('--reveal-delay');
    }

    function show(element) {
        element.classList.add('reveal-animating');
        element.classList.remove('reveal-pending');
        observer?.unobserve(element);
        timers.set(element, setTimeout(() => finish(element), 650));
    }

    try {
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) { show(entry.target); }
                });
            }, { threshold: 0.05 });
            document.documentElement.classList.add('js-ready');
        }
    } catch {
        // Progressive enhancement: unsupported observers leave all content visible.
    }

    Alpine.directive('reveal', (element, { expression }, { cleanup }) => {
        if (!observer || motion.matches) { return; }
        const bounds = element.getBoundingClientRect();
        if (bounds.top < window.innerHeight && bounds.bottom > 0) { return; }

        const columns = getComputedStyle(element.parentElement).gridTemplateColumns.split(' ').length;
        const delay = (Number(expression) || 0) % Math.max(1, columns) * 60;
        element.style.setProperty('--reveal-delay', `${delay}ms`);
        observer.observe(element);
        pending.add(element);
        element.classList.add('reveal-pending');
        const focus = () => finish(element);
        element.addEventListener('focusin', focus, { once: true });
        cleanup(() => {
            finish(element);
            element.removeEventListener('focusin', focus);
        });
    });

    motion.addEventListener('change', () => {
        if (motion.matches) { [...pending].forEach(finish); }
    });

    const header = document.querySelector('.landing-navigation');
    if (header) {
        let scheduled = false;
        const updateHeader = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
            scheduled = false;
        };
        updateHeader();
        window.addEventListener('scroll', () => {
            if (!scheduled) { scheduled = true; requestAnimationFrame(updateHeader); }
        }, { passive: true });
    }
}
