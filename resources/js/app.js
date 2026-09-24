import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { createIcons, Search, Moon, Sun, Bell, PenLine, ArrowUpRight, ArrowDown, ArrowRight, ArrowLeft, Menu, Plus, X, CheckCircle, Check, CheckCheck, Bookmark, BookmarkCheck, BookOpen, Feather, Sparkles, Heart, MessageCircle, Share2, Rss, ChevronRight, Archive, Bold, Italic, Strikethrough, Heading2, Heading3, Quote, List, ListOrdered, Code, Link, Image as ImageIcon, ImagePlus, Minus, Undo2, Redo2, Calendar, History, Circle, UserPlus, Shield, Lock, Mail, Settings, LogOut, Eye, Download, ExternalLink, FileText, Video } from 'lucide';

const iconSet = { Search, Moon, Sun, Bell, PenLine, ArrowUpRight, ArrowDown, ArrowRight, ArrowLeft, Menu, Plus, X, CheckCircle, Check, CheckCheck, Bookmark, BookmarkCheck, BookOpen, Feather, Sparkles, Heart, MessageCircle, Share2, Rss, ChevronRight, Archive, Bold, Italic, Strikethrough, Heading2, Heading3, Quote, List, ListOrdered, Code, Link, Image: ImageIcon, ImagePlus, Minus, Undo2, Redo2, Calendar, History, Circle, UserPlus, Shield, Lock, Mail, Settings, LogOut, Eye, Download, ExternalLink, FileText, Video };
let iconFrame;
function refreshIcons() { cancelAnimationFrame(iconFrame); iconFrame = requestAnimationFrame(() => createIcons({ icons: iconSet, attrs: { 'stroke-width': 1.65 } })); }

Alpine.data('storyEditor', (wire, initial) => {
    let editor, saveTimer, dirty = false;
    const warnBeforeLeaving = event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } };
    const queueDraftSave = () => {
        dirty = true;
        clearTimeout(saveTimer);
        if (wire.status === 'published') return;
        saveTimer = setTimeout(() => { if (wire.title.trim().length >= 3 && dirty) wire.$call('autosave'); }, 18000);
    };
    return {
        wordCount: 0, uploading: false, uploadError: '',
        async init() {
            const [{ Editor }, { default: StarterKit }, { default: Image }, { default: Placeholder }, { default: Youtube }, { Markdown }] = await Promise.all([import('@tiptap/core'), import('@tiptap/starter-kit'), import('@tiptap/extension-image'), import('@tiptap/extension-placeholder'), import('@tiptap/extension-youtube'), import('@tiptap/markdown')]);
            editor = new Editor({
                element: this.$refs.editor,
                extensions: [StarterKit.configure({ link: { openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } }, heading: { levels: [2, 3] } }), Image.configure({ allowBase64: false }), Youtube.configure({ nocookie: true, width: 640, height: 360 }), Markdown, Placeholder.configure({ placeholder: 'Start with the thought you can’t stop thinking about…' })],
                content: initial || '',
                editorProps: { attributes: { 'aria-label': 'Story body', role: 'textbox', 'aria-multiline': 'true' } },
                onUpdate: ({ editor: instance }) => {
                    this.wordCount = instance.getText().trim().split(/\s+/).filter(Boolean).length;
                    wire.$set('bodyHtml', instance.getHTML(), false);
                    wire.$set('bodyJson', instance.getJSON(), false);
                    queueDraftSave();
                },
            });
            this.wordCount = editor.getText().trim().split(/\s+/).filter(Boolean).length;
            for (const property of ['title', 'excerpt', 'coverImage', 'isPremium', 'publishedAt', 'metaTitle', 'metaDescription', 'canonicalUrl', 'categoryIds', 'tagNames']) wire.$watch(property, queueDraftSave);
            window.addEventListener('beforeunload', warnBeforeLeaving);
            this.$wire.on('revision-restored', (event) => { editor.commands.setContent(event.html); queueDraftSave(); });
            this.$wire.on('story-saved', () => { dirty = false; });
        },
        destroy() { clearTimeout(saveTimer); window.removeEventListener('beforeunload', warnBeforeLeaving); editor?.destroy(); },
        format(command) {
            const chain = editor.chain().focus();
            const commands = { bold: () => chain.toggleBold(), italic: () => chain.toggleItalic(), strike: () => chain.toggleStrike(), h2: () => chain.toggleHeading({ level: 2 }), h3: () => chain.toggleHeading({ level: 3 }), quote: () => chain.toggleBlockquote(), bullet: () => chain.toggleBulletList(), ordered: () => chain.toggleOrderedList(), code: () => chain.toggleCodeBlock(), rule: () => chain.setHorizontalRule(), undo: () => chain.undo(), redo: () => chain.redo() };
            commands[command]?.().run();
        },
        addVideo() {
            const url = window.prompt('Paste a YouTube video URL:', '');
            if (!url) return;
            if (!/^https:\/\/(www\.)?(youtube\.com\/watch|youtu\.be\/)/i.test(url)) { this.uploadError = 'Use a YouTube video link beginning with https://.'; return; }
            editor.commands.setYoutubeVideo({ src: url });
        },
        async importMarkdown(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 500000) { this.uploadError = 'Choose a Markdown file smaller than 500 KB.'; return; }
            editor.commands.setContent(await file.text(), { contentType: 'markdown' });
            event.target.value = '';
        },
        exportMarkdown() {
            const blob = new Blob([editor.getMarkdown()], { type: 'text/markdown;charset=utf-8' });
            const url = URL.createObjectURL(blob); const anchor = document.createElement('a'); anchor.href = url; anchor.download = (wire.slug || 'my-story') + '.md'; anchor.click(); URL.revokeObjectURL(url);
        },
        setLink() {
            const previous = editor.getAttributes('link').href || '';
            const url = window.prompt('Paste a link (https://… or /@writer/story):', previous);
            if (url === null) return;
            if (!url.trim()) { editor.chain().focus().unsetLink().run(); return; }
            if (!/^(https?:\/\/|\/[^/]|mailto:)/i.test(url)) { this.uploadError = 'Use a full https:// URL or a link to a Folkscript story.'; return; }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },
        async uploadImage(event, destination) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { this.uploadError = 'Choose an image smaller than 5 MB.'; return; }
            this.uploading = true; this.uploadError = '';
            try {
                const body = new FormData(); body.append('image', file);
                const response = await fetch('/media', { method: 'POST', body, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors?.image?.[0] || data.message || 'The upload failed. Please try again.');
                if (destination === 'cover') { wire.$set('coverImage', data.url); }
                else { const alt = window.prompt('Describe this image for readers using assistive technology:', '') || ''; editor.chain().focus().setImage({ src: data.url, alt }).run(); }
            } catch (error) { this.uploadError = error.message; }
            finally { this.uploading = false; event.target.value = ''; }
        },
    };
});

Livewire.hook('morph.updated', refreshIcons);
Livewire.start();
refreshIcons();

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-url]');
    if (!button) return;
    const url = new URL(button.dataset.shareUrl);
    url.searchParams.set('utm_source', 'reader'); url.searchParams.set('utm_medium', 'share'); url.searchParams.set('utm_campaign', 'story');
    const label = button.querySelector('span');
    try {
        await navigator.clipboard.writeText(url.href);
        if (label) { label.textContent = 'Link copied'; setTimeout(() => label.textContent = 'Share', 2500); }
    } catch { window.prompt('Copy this story link:', url.href); }
});
const progress = document.querySelector('[data-reading-progress]');
const article = document.querySelector('[data-article-content]');
if (progress && article) {
    let ticking = false;
    const update = () => { const top = article.getBoundingClientRect().top + window.scrollY; const height = article.offsetHeight; progress.style.transform = `scaleX(${Math.min(1, Math.max(0, (window.scrollY - top + window.innerHeight * .4) / height))})`; ticking = false; };
    window.addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(update); ticking = true; } }, { passive: true }); update();
}
if ('serviceWorker' in navigator) window.addEventListener('load', () => { navigator.serviceWorker.register('/sw.js').catch(() => {}); });

const toc = document.querySelector('[data-article-toc]');
if (article && toc) {
    const headings = [...article.querySelectorAll('h2,h3')];
    if (headings.length > 1) {
        toc.hidden = false;
        headings.forEach((heading, index) => {
            heading.id = `story-section-${index + 1}`;
            const link = document.createElement('a'); link.href = `#${heading.id}`; link.textContent = heading.textContent; toc.querySelector('nav').append(link);
        });
    }
}
const quoteInput = document.querySelector('[data-quote-input]');
if (article && quoteInput) {
    document.addEventListener('selectionchange', () => {
        const selection = window.getSelection();
        if (selection && article.contains(selection.anchorNode) && article.contains(selection.focusNode)) {
            const quote = selection.toString().trim();
            if (quote.length >= 10 && quote.length <= 240) quoteInput.value = quote;
        }
    });
}
const realtime = document.querySelector('meta[name="folkscript-realtime"]');
if (realtime) {
    const options = JSON.parse(realtime.content);
    Promise.all([import('laravel-echo'), import('pusher-js')]).then(([{ default: Echo }, { default: Pusher }]) => {
        window.Pusher = Pusher;
        const echo = new Echo({ broadcaster: 'reverb', key: options.key, wsHost: options.host, wsPort: options.port, wssPort: options.port, forceTLS: options.scheme === 'https', enabledTransports: ['ws', 'wss'] });
        echo.private(`App.Models.User.${options.user}`).notification(() => {
            const link = document.querySelector('.notification-link');
            if (link && !link.querySelector('.notification-dot')) { const dot = document.createElement('span'); dot.className = 'notification-dot'; link.append(dot); }
        });
    }).catch(() => {});
}
