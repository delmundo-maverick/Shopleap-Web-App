const value = document.querySelector('[data-otp-value]');
const boxes = [...document.querySelectorAll('[data-otp-digit]')];
if (value && boxes.length) {
    value.type = 'hidden';
    value.required = false;
    document.querySelector('[data-otp-boxes]').classList.replace('hidden', 'grid');
    const sync = () => { value.value = boxes.map(box => box.value).join(''); };
    const fill = (text, start) => {
        const digits = text.replace(/\D/g, '').slice(0, 6 - start);
        [...digits].forEach((digit, offset) => { boxes[start + offset].value = digit; });
        sync();
        boxes[Math.min(start + digits.length, 5)].focus();
    };
    boxes.forEach((box, index) => {
        box.required = true;
        box.pattern = '[0-9]';
        box.addEventListener('input', () => {
            const text = box.value;
            box.value = '';
            fill(text, text.length === 6 ? 0 : index);
        });
        box.addEventListener('paste', event => {
            event.preventDefault();
            const text = event.clipboardData.getData('text');
            fill(text, text.replace(/\D/g, '').length === 6 ? 0 : index);
        });
        box.addEventListener('keydown', event => {
            if (event.key === 'Backspace') {
                event.preventDefault();
                if (box.value) { box.value = ''; }
                else if (index > 0) { boxes[index - 1].value = ''; boxes[index - 1].focus(); }
                sync();
            }
        });
    });
}
const resend = document.querySelector('[data-resend]');
if (resend) {
    const until = Date.now() + Number(resend.dataset.cooldown) * 1000;
    const tick = () => {
        const seconds = Math.max(0, Math.ceil((until - Date.now()) / 1000));
        resend.disabled = seconds > 0;
        resend.textContent = seconds > 0 ? `Resend OTP (${seconds}s)` : 'Resend OTP';
        if (seconds > 0) { setTimeout(tick, 1000); }
    };
    tick();
}
document.querySelectorAll('[data-recovery-form]').forEach(form => {
    form.addEventListener('submit', event => {
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        const button = form.querySelector('button');
        button.disabled = true;
        button.textContent = button.dataset.loading;
        form.setAttribute('aria-busy', 'true');
    });
});
window.addEventListener('pageshow', event => { if (event.persisted) { window.location.reload(); } });
