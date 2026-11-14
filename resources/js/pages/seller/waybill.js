const preview = document.getElementById('waybill-preview');
const printButton = document.querySelector('[data-print-waybill]');

if (preview && printButton) {
    printButton.disabled = true;
    preview.addEventListener('load', () => { printButton.disabled = false; });
    printButton.addEventListener('click', () => {
        preview.contentWindow.focus();
        preview.contentWindow.print();
    });
}
