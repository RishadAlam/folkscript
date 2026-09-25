const settings = document.querySelector('[data-settings-page]');
if (settings) {
    // Keep previously shared links useful after splitting the long settings form.
    const legacySections = { profile: 'profile', preferences: 'preferences', 'featured-story': 'publishing', security: 'security', api: 'developer' };
    const legacySection = legacySections[window.location.hash.slice(1)];
    if (legacySection && !new URLSearchParams(window.location.search).has('section')) {
        window.location.replace(`${window.location.pathname}?section=${legacySection}`);
    }

    const links = settings.querySelector('[data-profile-links]');
    if (links) {
        const list = links.querySelector('[data-profile-link-list]');
        const template = links.querySelector('[data-profile-link-template]');
        const add = links.querySelector('[data-add-profile-link]');
        const count = links.querySelector('[data-profile-link-count]');
        let nextIndex = Math.max(-1, ...[...list.querySelectorAll('[data-link-label]')].map(input => Number(input.name.match(/\[(\d+)\]/)?.[1]) || 0)) + 1;
        add.hidden = false;
        const updateLinks = () => {
            const rows = [...list.querySelectorAll('[data-profile-link]')];
            rows.forEach((row, index) => {
                const remove = row.querySelector('[data-remove-profile-link]');
                remove.hidden = false;
                remove.setAttribute('aria-label', `Remove profile link ${index + 1}`);
                row.querySelector('legend').textContent = `Profile link ${index + 1}`;
            });
            add.disabled = rows.length >= 10;
            count.textContent = rows.length >= 10 ? 'All 10 links are in use. Remove one to add another.' : `${rows.length} of 10 links. Leave both fields empty to skip a link.`;
        };
        add.addEventListener('click', () => {
            if (list.children.length >= 10) return;
            const row = template.content.firstElementChild.cloneNode(true);
            for (const element of [row, ...row.querySelectorAll('*')]) {
                for (const attribute of [...element.attributes]) {
                    if (attribute.value.includes('__INDEX__')) element.setAttribute(attribute.name, attribute.value.replaceAll('__INDEX__', String(nextIndex)));
                }
            }
            nextIndex++;
            list.append(row);
            updateLinks();
            row.querySelector('[data-link-label]').focus();
            links.closest('form').dispatchEvent(new Event('input', { bubbles: true }));
            document.dispatchEvent(new Event('folkscript:icons'));
        });
        list.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-profile-link]');
            if (!remove) return;
            remove.closest('[data-profile-link]').remove();
            updateLinks();
            add.focus();
            links.closest('form').dispatchEvent(new Event('input', { bubbles: true }));
        });
        updateLinks();
    }

    const photo = settings.querySelector('[data-profile-photo]');
    if (photo) {
        const form = photo.closest('form');
        const input = photo.querySelector('[data-avatar-file]');
        const preview = photo.querySelector('[data-avatar-preview]');
        const frame = photo.querySelector('[data-avatar-frame]');
        const feedback = photo.querySelector('[data-avatar-feedback]');
        const reset = form.querySelector('[data-avatar-reset]');
        const remove = form.querySelector('[data-avatar-remove]');
        const serverInvalid = input.getAttribute('aria-invalid');
        let previewUrl;
        const restorePhoto = () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            preview.onload = null;
            preview.onerror = null;
            preview.hidden = true;
            preview.removeAttribute('src');
            frame.dataset.remove = String(!!remove?.checked);
            reset.hidden = true;
            feedback.hidden = true;
            input.setCustomValidity('');
            input.setAttribute('aria-invalid', serverInvalid);
        };
        input.addEventListener('change', () => {
            restorePhoto();
            const file = input.files[0];
            if (!file) return;
            reset.hidden = false;
            const showError = message => {
                input.setCustomValidity(message);
                input.setAttribute('aria-invalid', 'true');
                feedback.textContent = message;
                feedback.dataset.state = 'error';
                feedback.hidden = false;
                preview.hidden = true;
            };
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { showError('Choose a JPG, PNG, or WebP image.'); return; }
            if (file.size > 4 * 1024 * 1024) { showError('This photo is too large. Choose one no larger than 4 MB.'); return; }
            previewUrl = URL.createObjectURL(file);
            preview.onload = () => {
                if (preview.naturalWidth > 6000 || preview.naturalHeight > 6000) {
                    showError('Resize this photo to no more than 6000 pixels on either side.');
                    return;
                }
                if (remove) remove.checked = false;
                frame.dataset.remove = 'false';
                input.setAttribute('aria-invalid', 'false');
                preview.hidden = false;
                feedback.textContent = 'Preview only. Save profile to apply this photo.';
                delete feedback.dataset.state;
                feedback.hidden = false;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };
            preview.onerror = () => showError('This photo could not be read. Choose a different image.');
            preview.src = previewUrl;
        });
        reset.addEventListener('click', () => {
            input.value = '';
            restorePhoto();
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        remove?.addEventListener('change', () => {
            input.value = '';
            restorePhoto();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        restorePhoto();
        window.addEventListener('pagehide', event => { if (!event.persisted && previewUrl) URL.revokeObjectURL(previewUrl); });
    }

    const cover = settings.querySelector('[data-profile-cover]');
    if (cover) {
        const input = cover.querySelector('[data-cover-file]');
        const preview = cover.querySelector('[data-cover-preview]');
        const empty = cover.querySelector('[data-cover-empty]');
        const feedback = cover.querySelector('[data-cover-feedback]');
        const reset = cover.querySelector('[data-cover-reset]');
        const remove = cover.querySelector('[data-cover-remove]');
        const original = preview.getAttribute('src');
        const serverInvalid = input.getAttribute('aria-invalid');
        let previewUrl;
        const restorePreview = () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            preview.onload = null;
            preview.onerror = null;
            if (original) preview.src = original;
            else preview.removeAttribute('src');
            preview.hidden = !original || !!remove?.checked;
            empty.hidden = !preview.hidden;
            reset.hidden = true;
            input.setCustomValidity('');
            input.setAttribute('aria-invalid', serverInvalid);
            feedback.hidden = true;
        };
        input.addEventListener('change', () => {
            restorePreview();
            const file = input.files[0];
            if (!file) return;
            reset.hidden = false;
            const showError = message => {
                input.setCustomValidity(message);
                input.setAttribute('aria-invalid', 'true');
                feedback.textContent = message;
                feedback.dataset.state = 'error';
                feedback.hidden = false;
                preview.hidden = true;
                empty.hidden = false;
            };
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { showError('Choose a JPG, PNG, or WebP image.'); return; }
            if (file.size > 6 * 1024 * 1024) { showError('This image is too large. Choose one smaller than 6 MB.'); return; }
            if (remove) remove.checked = false;
            previewUrl = URL.createObjectURL(file);
            preview.onload = () => {
                if (preview.naturalWidth > 8000 || preview.naturalHeight > 8000) {
                    showError('Resize this image to no more than 8000 pixels on either side.');
                    return;
                }
                feedback.textContent = `${preview.naturalWidth} × ${preview.naturalHeight} px · Preview only. Save profile to apply.`;
                input.setAttribute('aria-invalid', 'false');
                delete feedback.dataset.state;
                feedback.hidden = false;
            };
            preview.onerror = () => showError('This image could not be read. Choose a different image.');
            preview.src = previewUrl;
            preview.hidden = false;
            empty.hidden = true;
        });
        reset.addEventListener('click', () => {
            input.value = '';
            if (remove) remove.checked = false;
            restorePreview();
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        remove?.addEventListener('change', () => {
            input.value = '';
            restorePreview();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        restorePreview();
        window.addEventListener('pagehide', event => { if (!event.persisted && previewUrl) URL.revokeObjectURL(previewUrl); });
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

// A native checkbox works with touch, keyboards, and password managers alike.
for (const form of document.querySelectorAll('.auth-shell form, .auth-single form, [data-settings-page] form')) {
    const passwords = [...form.querySelectorAll('input[type="password"]')];
    if (!passwords.length) continue;
    const label = document.createElement('label');
    label.className = 'check-label password-visibility';
    const toggle = document.createElement('input');
    toggle.type = 'checkbox';
    label.append(toggle, document.createTextNode(passwords.length > 1 ? 'Show passwords' : 'Show password'));
    const finalField = passwords.at(-1).closest('.form-grid') || passwords.at(-1).closest('.field');
    finalField.after(label);
    toggle.addEventListener('change', () => {
        passwords.forEach(input => { input.type = toggle.checked ? 'text' : 'password'; });
    });
}

const authInvalid = document.querySelector('.auth-shell [aria-invalid="true"], .auth-single [aria-invalid="true"]');
if (authInvalid) {
    const disclosure = authInvalid.closest('details');
    if (disclosure) disclosure.open = true;
    requestAnimationFrame(() => authInvalid.focus());
}
