const settings = document.querySelector('[data-settings-page]');
if (settings) {
    // Keep previously shared links useful after splitting the long settings form.
    const legacySections = { profile: 'profile', preferences: 'preferences', 'featured-story': 'publishing', security: 'security', api: 'developer' };
    const legacySection = legacySections[window.location.hash.slice(1)];
    if (legacySection && !new URLSearchParams(window.location.search).has('section')) {
        window.location.replace(`${window.location.pathname}?section=${legacySection}`);
    }

    const invalid = settings.querySelector('[aria-invalid="true"]');
    if (invalid) {
        let parent = invalid.parentElement;
        while (parent && parent !== settings) {
            if (parent.tagName === 'DETAILS') parent.open = true;
            parent = parent.parentElement;
        }
        invalid.focus();
    }

    const forms = [...settings.querySelectorAll('[data-settings-form]')];
    const values = form => JSON.stringify([...new FormData(form)].map(([name, value]) => [name, value instanceof File ? (value.name ? [value.name, value.size, value.lastModified] : null) : value]));
    for (const form of forms) {
        const initial = values(form);
        const update = () => {
            form.dataset.dirty = String(values(form) !== initial);
            const notice = form.querySelector('[data-settings-unsaved]');
            if (notice) notice.hidden = form.dataset.dirty !== 'true';
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('submit', event => {
            queueMicrotask(() => { if (!event.defaultPrevented) form.dataset.dirty = 'false'; });
        });
    }
    window.addEventListener('beforeunload', event => {
        if (forms.some(form => form.dataset.dirty === 'true')) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}
