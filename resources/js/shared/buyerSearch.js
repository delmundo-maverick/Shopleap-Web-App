const RECENT_KEY = 'shopleap_recent_searches';
const MAX_RECENT = 5;

function getRecent() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY)) || [];
    } catch {
        return [];
    }
}

function saveRecent(term) {
    const current = getRecent().filter((t) => t.toLowerCase() !== term.toLowerCase());
    current.unshift(term);
    localStorage.setItem(RECENT_KEY, JSON.stringify(current.slice(0, MAX_RECENT)));
}

function clearRecent() {
    localStorage.removeItem(RECENT_KEY);
}

export function initBuyerSearch() {
    const form = document.getElementById('buyerSearchForm');
    const input = document.getElementById('buyerSearchInput');
    const recentSection = document.getElementById('recentSearchesSection');
    const recentContainer = document.getElementById('recentSearchesList');
    const clearBtn = document.getElementById('clearRecentSearchesBtn');

    if (!form || !input) return;

    function renderRecent() {
        const recent = getRecent();
        if (!recentContainer || !recentSection) return;

        recentContainer.innerHTML = '';

        if (recent.length === 0) {
            recentSection.classList.add('hidden');
            return;
        }

        recentSection.classList.remove('hidden');

        recent.forEach((term) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rounded-full bg-ice-blue px-3 py-1 text-xs text-charcoal/70 transition hover:bg-primary/10 hover:text-primary';
            btn.textContent = term;
            btn.addEventListener('click', () => {
                input.value = term;
                form.submit();
            });
            recentContainer.appendChild(btn);
        });
    }

    document.querySelectorAll('.trending-search-chip').forEach((btn) => {
        btn.addEventListener('click', () => {
            input.value = btn.textContent.trim();
            form.submit();
        });
    });

    clearBtn?.addEventListener('click', () => {
        clearRecent();
        renderRecent();
    });

    form.addEventListener('submit', () => {
        const term = input.value.trim();
        if (term) saveRecent(term);
    });

    renderRecent();
}
