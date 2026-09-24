// Public interactions progressively enhance ordinary, CSRF-protected forms.
let feedbackTimer;
function feedback(message, failed = false) {
    const region = document.getElementById('action-feedback');
    if (!region) return;
    clearTimeout(feedbackTimer);
    region.textContent = message;
    region.classList.toggle('is-error', failed);
    region.hidden = false;
    feedbackTimer = setTimeout(() => { region.hidden = true; }, failed ? 10000 : 5000);
}

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-toggle-action]')) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (!button || button.disabled) return;
    const kind = form.dataset.toggleAction;
    const stateKey = { bookmark: 'bookmarked', follow: 'following', react: 'reacted' }[kind];
    if (!stateKey) return;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) {
            const messages = { 401: 'Sign in to continue.', 419: 'Your session expired. Refresh this page, then try again.', 429: 'Please wait a moment before trying again.' };
            throw new Error(messages[response.status] || 'This change could not be saved. Please try again.');
        }
        const data = await response.json();
        if (typeof data[stateKey] !== 'boolean') throw new Error('This change could not be confirmed. Please refresh the page.');
        const enabled = data[stateKey];
        document.querySelectorAll('[data-toggle-action]').forEach(other => {
            if (other.action !== form.action) return;
            const control = other.querySelector('button');
            control.setAttribute('aria-pressed', String(enabled));
            control.classList.toggle('engaged', enabled);
            if (kind === 'follow' && control.classList.contains('btn')) { control.classList.toggle('btn-outline', enabled); control.classList.toggle('btn-primary', !enabled); }
            if (other.dataset.labelOn && other.dataset.labelOff) {
                const label = enabled ? other.dataset.labelOn : other.dataset.labelOff;
                control.setAttribute('aria-label', label);
                if (control.hasAttribute('title')) control.title = label;
            }
            const text = control.querySelector('[data-action-label]');
            if (text) text.textContent = enabled ? other.dataset.textOn : other.dataset.textOff;
            const icons = { bookmark: ['bookmark', 'bookmark-check'], follow: ['plus', 'check'] };
            if (icons[kind]) {
                const old = control.querySelector('svg, [data-lucide]');
                if (old) {
                    const icon = document.createElement('i');
                    icon.dataset.lucide = icons[kind][Number(enabled)];
                    icon.className = 'icon';
                    icon.setAttribute('aria-hidden', 'true');
                    old.replaceWith(icon);
                }
            }
            const count = control.querySelector('[data-action-count]');
            if (count && Number.isFinite(data.count)) count.textContent = data.count.toLocaleString();
        });
        document.dispatchEvent(new CustomEvent('folkscript:icons'));
        const messages = { bookmark: ['Removed from saved stories.', 'Story saved. Find it in Saved stories.'], follow: ['Removed from your following feed.', 'Following. New stories will appear in your feed.'], react: ['Appreciation removed.', 'Appreciation added.'] };
        feedback(messages[kind][Number(enabled)]);
        if (!enabled && form.hasAttribute('data-remove-on-off')) {
            form.closest('.story-card')?.remove();
            const count = document.querySelector('[data-saved-count]');
            if (count) count.textContent = String(Math.max(0, Number(count.textContent) - 1));
            if (!document.querySelector('.saved-list-items .story-card')) {
                const empty = document.querySelector('[data-saved-empty]');
                if (empty) empty.hidden = false;
            }
        }
    } catch (error) {
        feedback(error instanceof TypeError ? 'You seem to be offline. Reconnect and try again.' : error.message, true);
    } finally {
        button.disabled = false;
        form.removeAttribute('aria-busy');
    }
});
