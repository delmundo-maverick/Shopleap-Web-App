import { pollingRequest } from './polling-request.js';

export function notificationBell(cfg) {
            const fetchFeed = pollingRequest(15000);
            return {
                open: false,
                loading: false,
                failed: false,
                unread: cfg.unread,
                items: [],
                timer: null,
                onVisibility: null,
                markingAll: false,

                init() {
                    if (cfg.navDropdownId) {
                        this.$watch('activeDropdown', value => {
                            if (value === cfg.navDropdownId) {
                                this.loading = true;
                                this.refresh().finally(() => this.loading = false);
                            }
                        });
                    }
                    // Keep the count and list fresh while the tab is visible.
                    this.timer = setInterval(() => {
                        if (!document.hidden) this.refresh();
                    }, 45000);

                    this.onVisibility = () => {
                        if (!document.hidden) this.refresh();
                    };
                    document.addEventListener('visibilitychange', this.onVisibility);
                },

                destroy() {
                    clearInterval(this.timer);
                    document.removeEventListener('visibilitychange', this.onVisibility);
                },

                badge() {
                    return this.unread > 99 ? '99+' : this.unread;
                },

                async refresh() {
                    try {
                        const res = await fetchFeed(cfg.feedUrl, {
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                        });

                        if (!res) return;
                        if (!res.ok) throw new Error('feed ' + res.status);

                        const data = await res.json();
                        this.items = data.items;
                        this.unread = data.unread;
                        this.failed = false;
                    } catch (e) {
                        this.failed = true;
                    }
                },

                toggle() {
                    this.open = !this.open;

                    if (this.open) {
                        this.loading = true;
                        this.refresh().finally(() => this.loading = false);
                    }
                },

                // Mark it read, then go to the page it is about.
                async go(item) {
                    try {
                        await fetch(cfg.readUrl.replace('__ID__', item.id), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': cfg.csrf,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                        });
                    } catch (e) {
                        // Still navigate. It will be marked read the next time it is opened.
                    }

                    window.location.href = item.url || cfg.allUrl;
                },

                async markAll() {
                    if (this.markingAll) return;
                    this.markingAll = true;
                    try {
                        const res = await fetch(cfg.readAllUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': cfg.csrf,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin',
                        });

                        if (!res.ok) throw new Error('read-all ' + res.status);

                        this.items = this.items.map(i => ({
                            ...i,
                            read: true
                        }));
                        this.unread = 0;
                    } catch (e) {
                        this.failed = true;
                    } finally {
                        this.markingAll = false;
                    }
                },
            };

}
