import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('sidebar', {
    open: false,
    collapsed: false,
});

Alpine.start();
