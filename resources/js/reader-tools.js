async function writeClipboard(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(typeof text === 'string' ? text : await text);
    } else if (navigator.clipboard?.write && window.ClipboardItem) {
        await navigator.clipboard.write([new ClipboardItem({ 'text/plain': Promise.resolve(text).then(value => new Blob([value], { type: 'text/plain' })) })]);
    } else throw new Error('clipboard');
}

export default function readerTools(url) {
    let pending, clearMessage, resizeObserver;
    return {
        open: false, busy: false, copied: false, failed: false, manual: false, markdown: '', message: '', panelLeft: 0, panelTop: 0, panelMaxHeight: 600,
        init() {
            // Prepare the separate Copy page action so its clipboard write retains the click gesture.
            this.load().catch(() => {});
            if (typeof ResizeObserver === 'undefined') return;
            resizeObserver = new ResizeObserver(() => { if (this.open) this.place(); });
            resizeObserver.observe(this.$refs.root);
        },
        get googleSearchUrl() {
            const title = document.querySelector('main h1')?.textContent.trim() || document.title;
            return 'https://www.google.com/search?q=' + encodeURIComponent(title);
        },
        show(focus = false) {
            this.open = true;
            this.$nextTick(() => {
                this.place();
                if (focus) this.$refs.root.querySelector('.reader-option')?.focus();
            });
        },
        place() {
            const root = this.$refs.root.getBoundingClientRect();
            const bounds = this.$refs.buttons.getBoundingClientRect();
            if (bounds.bottom <= 0 || bounds.top >= window.innerHeight || bounds.right <= 0 || bounds.left >= window.innerWidth) {
                this.close();
                return;
            }
            const width = this.$refs.panel.offsetWidth;
            this.panelLeft = Math.max(22, Math.min(bounds.right - width, window.innerWidth - width - 22)) - root.left;
            const above = Math.max(0, Math.min(bounds.top - 30, window.innerHeight - 44));
            const below = Math.max(0, Math.min(window.innerHeight - bounds.bottom - 30, window.innerHeight - 44));
            const upwards = below < Math.min(this.$refs.panel.scrollHeight, 320) && above > below;
            this.panelMaxHeight = Math.max(0, Math.min(600, window.innerHeight * .75, upwards ? above : below));
            const height = Math.min(this.$refs.panel.scrollHeight + 2, this.panelMaxHeight);
            const top = upwards ? bounds.top - height - 8 : bounds.bottom + 8;
            this.panelTop = Math.max(22, Math.min(top, window.innerHeight - height - 22)) - root.top;
        },
        toggle() { this.open ? this.close() : this.show(); },
        close(restoreFocus = false) {
            this.open = false;
            if (restoreFocus) this.$refs.toggle.focus();
        },
        async load() {
            if (this.markdown) return this.markdown;
            if (!pending) {
                pending = (async () => {
                    const controller = new AbortController();
                    const timeout = setTimeout(() => controller.abort(), 15000);
                    try {
                        const response = await fetch(url, { headers: { Accept: 'text/markdown' }, credentials: 'same-origin', signal: controller.signal });
                        if (!response.ok || !response.headers.get('content-type')?.includes('text/markdown')) throw new Error('unavailable');
                        const text = await response.text();
                        if (!text.trim()) throw new Error('empty');
                        this.markdown = text;
                        return text;
                    } finally { clearTimeout(timeout); }
                })().finally(() => { pending = null; });
            }
            return pending;
        },
        async copy() {
            if (this.busy) return;
            window.dispatchEvent(new CustomEvent('folkscript:feedback'));
            const fromMenu = this.open;
            clearTimeout(clearMessage);
            this.busy = true; this.copied = false; this.failed = false; this.message = ''; this.manual = false;
            const text = this.markdown || this.load();
            Promise.resolve(text).catch(() => {});
            try {
                await writeClipboard(text);
                this.copied = true;
                this.message = 'Page copied as Markdown.';
                this.close(fromMenu);
                clearMessage = setTimeout(() => { this.copied = false; this.message = ''; }, 3500);
            } catch {
                this.failed = true;
                try {
                    await text;
                    this.manual = true; this.show();
                    this.message = 'Automatic copying is unavailable. Select the text below and copy it, or open the Markdown version.';
                    this.$nextTick(() => { this.$refs.manualText.focus(); this.$refs.manualText.select(); });
                } catch {
                    this.message = 'This page could not be copied. Check your connection and try again, or open the Markdown version.';
                    this.show();
                }
            } finally { this.busy = false; }
        },
        destroy() { clearTimeout(clearMessage); resizeObserver?.disconnect(); },
    };
}
