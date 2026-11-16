document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-coverage-filters]');
    if (!form) return;
    const places = JSON.parse(form.dataset.places || '[]');
    const province = form.elements.province;
    const municipality = form.elements.municipality;
    province.addEventListener('change', () => {
        const options = [...new Set(places.filter(place => !province.value || place.province === province.value).map(place => place.municipality))].sort();
        municipality.replaceChildren(new Option('All municipalities', ''), ...options.map(name => new Option(name, name)));
    });
});
