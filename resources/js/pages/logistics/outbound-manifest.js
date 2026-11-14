const button = document.getElementById('manifest-print-button');
const status = document.getElementById('manifest-print-status');

if (button && status) {
    button.addEventListener('click', async () => {
        if (button.disabled) return;
        button.disabled = true;
        status.textContent = 'Recording manifest print...';
        status.classList.remove('text-sale-red');

        try {
            const response = await fetch(button.dataset.recordUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ order_ids: JSON.parse(button.dataset.orderIds) }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(response.status >= 500 ? 'Unable to record the print. Please try again.' : data.message);
            }

            status.textContent = 'Manifest print recorded. Return to the bag to put it in transit.';
            try {
                window.localStorage.setItem(`logistics.manifest.printed.${button.dataset.bagId}`, `${data.printed_at}:${Date.now()}`);
            } catch {
                // Back to bag still reloads its server-confirmed print state.
            }
            window.print();
        } catch (error) {
            status.textContent = error.message || 'Unable to record the print. Please try again.';
            status.classList.add('text-sale-red');
        } finally {
            button.disabled = false;
        }
    });
}
