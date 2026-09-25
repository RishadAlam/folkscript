const toneNames = ['top', 'bottom', 'left', 'right'];
const properties = [...toneNames.map(side => `--image-tone-${side}`), '--image-tone-fill'];

// Transparent pixels contribute proportionally; empty edges inherit the image's overall color.
export function sampleImageTones({ data, width, height }) {
    if (!width || !height || data.length < width * height * 4) return null;
    const sums = Array.from({ length: 5 }, () => [0, 0, 0, 0]);
    const stripX = Math.max(1, Math.round(width / 8));
    const stripY = Math.max(1, Math.round(height / 8));
    const add = (sum, offset, alpha) => {
        for (let channel = 0; channel < 3; channel++) sum[channel] += data[offset + channel] * alpha;
        sum[3] += alpha;
    };
    for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        const offset = (y * width + x) * 4, alpha = data[offset + 3] / 255;
        if (!alpha) continue;
        add(sums[4], offset, alpha);
        if (y < stripY) add(sums[0], offset, alpha);
        if (y >= height - stripY) add(sums[1], offset, alpha);
        if (x < stripX) add(sums[2], offset, alpha);
        if (x >= width - stripX) add(sums[3], offset, alpha);
    }
    if (!sums[4][3]) return null;
    return Object.fromEntries(toneNames.map((side, index) => {
        const sum = sums[index][3] ? sums[index] : sums[4];
        return [side, `rgb(${sum.slice(0, 3).map(value => Math.round(value / sum[3])).join(' ')})`];
    }));
}

if (typeof document !== 'undefined') {
    const selector = 'img[data-image-tones]';
    const cache = new Map(), images = new WeakMap();
    const clear = image => {
        properties.forEach(property => image.style.removeProperty(property));
        image.removeAttribute('data-image-tones-ready');
    };
    const apply = image => {
        const tones = images.get(image)?.tones;
        const width = image.clientWidth, height = image.clientHeight;
        if (!tones || image.hidden || !image.isConnected || !width || !height) { clear(image); return; }
        toneNames.forEach(side => image.style.setProperty(`--image-tone-${side}`, tones[side]));
        const horizontalBars = image.naturalWidth / image.naturalHeight >= width / height;
        image.style.setProperty('--image-tone-fill', horizontalBars
            ? `linear-gradient(to bottom, ${tones.top}, ${tones.bottom})`
            : `linear-gradient(to right, ${tones.left}, ${tones.right})`);
        image.setAttribute('data-image-tones-ready', '');
    };
    const resize = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(entries => {
        entries.forEach(({ target }) => apply(target));
    });
    const process = image => {
        if (!image.matches(selector)) { resize?.unobserve(image); images.delete(image); clear(image); return; }
        if (!images.has(image)) { images.set(image, {}); resize?.observe(image); }
        const source = image.currentSrc || image.getAttribute('src');
        if ((!image.getAttribute('src') && !image.getAttribute('srcset')) || !image.complete || !image.naturalWidth || !source) {
            images.set(image, {}); clear(image); return;
        }
        if (images.get(image)?.source === source) { apply(image); return; }
        let tones = cache.get(source);
        if (!cache.has(source)) {
            try {
                const scale = Math.min(1, 32 / Math.max(image.naturalWidth, image.naturalHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                const context = canvas.getContext('2d', { willReadFrequently: true });
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                tones = sampleImageTones(context.getImageData(0, 0, canvas.width, canvas.height));
            } catch { tones = null; } // Cross-origin or unsupported images keep the theme's fallback.
            cache.set(source, tones);
            if (cache.size > 128) cache.delete(cache.keys().next().value);
        }
        images.set(image, { source, tones });
        apply(image);
    };
    const visit = (node, callback) => {
        if (!(node instanceof Element)) return;
        if (node.matches(selector)) callback(node);
        node.querySelectorAll(selector).forEach(callback);
    };
    document.addEventListener('load', event => {
        if (event.target instanceof HTMLImageElement && event.target.matches(selector)) process(event.target);
    }, true);
    document.addEventListener('error', event => {
        if (event.target instanceof HTMLImageElement && event.target.matches(selector)) {
            images.set(event.target, {}); clear(event.target);
        }
    }, true);
    new MutationObserver(records => {
        for (const record of records) {
            if (record.type === 'attributes') {
                if (record.target instanceof HTMLImageElement && (record.target.matches(selector) || images.has(record.target))) process(record.target);
                continue;
            }
            record.removedNodes.forEach(node => visit(node, image => {
                if (!image.isConnected) { resize?.unobserve(image); images.delete(image); }
            }));
            record.addedNodes.forEach(node => visit(node, process));
        }
    }).observe(document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['src', 'srcset', 'sizes', 'hidden', 'data-image-tones'] });
    document.querySelectorAll(selector).forEach(process);
    if (!resize) window.addEventListener('resize', () => document.querySelectorAll(selector).forEach(apply));
}
