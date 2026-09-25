// Public interactions progressively enhance ordinary, CSRF-protected forms.
let feedbackTimer;
const pendingActions = new Set();
window.addEventListener('folkscript:feedback', () => {
    clearTimeout(feedbackTimer);
    const region = document.getElementById('action-feedback');
    if (region) region.hidden = true;
});
function feedback(message, failed = false) {
    const region = document.getElementById('action-feedback');
    if (!region) return;
    clearTimeout(feedbackTimer);
    window.dispatchEvent(new CustomEvent('folkscript:feedback'));
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
    if (pendingActions.has(form.action)) return;
    pendingActions.add(form.action);
    const hadFocus = form.contains(document.activeElement);
    const matchingForms = [...document.querySelectorAll('[data-toggle-action]')].filter(other => other.action === form.action);
    const controls = matchingForms.map(other => other.querySelector('button')).filter(Boolean);
    const disabledBefore = new Map(controls.map(control => [control, control.disabled]));
    controls.forEach(control => { control.disabled = true; });
    matchingForms.forEach(other => other.setAttribute('aria-busy', 'true'));
    try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) {
            const failure = await response.json().catch(() => ({}));
            if (response.status === 403 && failure?.message === 'Your email address is not verified.') {
                throw new Error('Verify your email from Settings, then try this action again.');
            }
            const messages = { 401: 'Sign in to continue.', 403: 'This action is unavailable for your account. Check your account access in Settings.', 404: 'This story or profile is no longer available. Refresh the page to see the latest content.', 419: 'Your session expired. Refresh this page, then try again.', 429: 'Please wait a moment before trying again.' };
            throw new Error(messages[response.status] || 'This change could not be saved. Please try again.');
        }
        const data = await response.json().catch(() => { throw new Error('This change could not be confirmed. Refresh the page and try again.'); });
        if (typeof data?.[stateKey] !== 'boolean') throw new Error('This change could not be confirmed. Please refresh the page.');
        const enabled = data[stateKey];
        document.querySelectorAll('[data-toggle-action]').forEach(other => {
            if (other.action !== form.action) return;
            const control = other.querySelector('button');
            if (!control) return;
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
                    for (const attribute of ['style', 'width', 'height']) {
                        const value = old.getAttribute(attribute);
                        if (value !== null) icon.setAttribute(attribute, value);
                    }
                    old.replaceWith(icon);
                }
            }
            const count = control.querySelector('[data-action-count]');
            if (count && Number.isFinite(data.count)) count.textContent = data.count.toLocaleString();
        });
        document.dispatchEvent(new CustomEvent('folkscript:icons'));
        if (kind === 'follow' && Number.isFinite(data.count)) {
            document.querySelectorAll('[data-follower-count]').forEach(counter => {
                if (counter.dataset.followUrl !== form.action) return;
                counter.textContent = `${data.count.toLocaleString()} ${data.count === 1 ? counter.dataset.singular : counter.dataset.plural}`;
            });
        }
        const messages = { bookmark: ['Removed from saved stories.', 'Story saved. Find it in Saved stories.'], follow: ['Removed from your following feed.', 'Following. New stories will appear in your feed.'], react: ['Appreciation removed.', 'Appreciation added.'] };
        feedback(messages[kind][Number(enabled)]);
        if (!enabled && form.hasAttribute('data-remove-on-off')) {
            const card = form.closest('.story-card');
            const restoreFocus = hadFocus && (document.activeElement === document.body || card?.contains(document.activeElement));
            const neighbour = card?.nextElementSibling || card?.previousElementSibling;
            card?.remove();
            const count = document.querySelector('[data-saved-count]');
            if (count) count.textContent = String(Math.max(0, Number(count.textContent) - 1));
            const removalHint = document.querySelector('[data-saved-removal-hint]');
            if (removalHint && Number(count?.textContent) === 0) removalHint.hidden = true;
            let nextFocus = neighbour?.querySelector('[data-toggle-action="bookmark"] button');
            const pagination = document.querySelector('[data-saved-pagination]');
            if (pagination) {
                // A removed item changes the page's range; keep navigation but omit its stale summary.
                const summary = pagination.querySelector('nav p');
                if (summary) summary.hidden = true;
            }
            if (!document.querySelector('.saved-list-items .story-card')) {
                const empty = document.querySelector('[data-saved-empty]');
                if (empty) {
                    empty.hidden = false;
                    if (Number(count?.textContent) === 0) {
                        const title = empty.querySelector('[data-saved-empty-title]');
                        const description = empty.querySelector('[data-saved-empty-description]');
                        if (title) title.textContent = title.dataset.emptyTitle;
                        if (description) description.textContent = description.dataset.emptyDescription;
                        empty.querySelector('[data-saved-remaining]')?.setAttribute('hidden', '');
                    }
                    nextFocus = empty.querySelector('a:not([hidden])');
                }
                if (pagination) pagination.hidden = true;
            }
            if (restoreFocus) nextFocus?.focus();
        }
    } catch (error) {
        feedback(error instanceof TypeError ? 'You seem to be offline. Reconnect and try again.' : error.message, true);
    } finally {
        pendingActions.delete(form.action);
        controls.forEach(control => { control.disabled = disabledBefore.get(control); });
        matchingForms.forEach(other => other.removeAttribute('aria-busy'));
        if (hadFocus && button.isConnected && document.activeElement === document.body) button.focus({ preventScroll: true });
    }
});
