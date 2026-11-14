const hero = document.querySelector('.landing-hero, .portal-hero');
const header = document.querySelector('.landing-navigation, .portal-header');

if (hero && header) {
    const updateHeaderHeight = () => {
        hero.style.setProperty('--landing-header-height', `${header.getBoundingClientRect().height}px`);
    };

    updateHeaderHeight();

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(updateHeaderHeight);
        observer.observe(header);
    } else {
        window.addEventListener('resize', updateHeaderHeight, { passive: true });
    }
}
