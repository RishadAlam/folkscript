import './community';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { createIcons, Search, Moon, Sun, Bell, PenLine, ArrowUpRight, ArrowDown, ArrowRight, ArrowLeft, Menu, Plus, X, CheckCircle, Check, CheckCheck, Bookmark, BookmarkCheck, BookOpen, Feather, Sparkles, Heart, MessageCircle, Share2, Rss, ChevronRight, Archive, Bold, Italic, Strikethrough, Heading2, Heading3, Quote, List, ListOrdered, Code, Link, Image as ImageIcon, ImagePlus, Minus, Undo2, Redo2, Calendar, History, Circle, UserPlus, Shield, Lock, Mail, Settings, LogOut, Eye, Download, ExternalLink, FileText, Video } from 'lucide';

const iconSet = { Search, Moon, Sun, Bell, PenLine, ArrowUpRight, ArrowDown, ArrowRight, ArrowLeft, Menu, Plus, X, CheckCircle, Check, CheckCheck, Bookmark, BookmarkCheck, BookOpen, Feather, Sparkles, Heart, MessageCircle, Share2, Rss, ChevronRight, Archive, Bold, Italic, Strikethrough, Heading2, Heading3, Quote, List, ListOrdered, Code, Link, Image: ImageIcon, ImagePlus, Minus, Undo2, Redo2, Calendar, History, Circle, UserPlus, Shield, Lock, Mail, Settings, LogOut, Eye, Download, ExternalLink, FileText, Video };
let iconFrame;
function refreshIcons() { cancelAnimationFrame(iconFrame); iconFrame = requestAnimationFrame(() => createIcons({ icons: iconSet, attrs: { 'stroke-width': 1.65 } })); }

document.addEventListener('folkscript:icons', refreshIcons);

Alpine.data('storyEditor', wire => {
    let editor, saveTimer, changeVersion = 0, savedVersion = 0, trigger, confirmationTrigger;
    let beforeLeave, onlineHandler, offlineHandler;
    return {
        ready: false, wordCount: 0, busy: false, pendingAction: '', dirty: false, imageCount: 0, describedImages: 0, hasInternalLink: false,
        savedTime: '', saveError: '', online: navigator.onLine,
        uploading: false, uploadError: '', tool: '', toolValue: '', toolError: '', imageAlt: '', imageFile: null,
        pendingMarkdown: '', confirmation: null, active: {}, canUndo: false, canRedo: false,
        get statusText() {
            if (!this.online) return this.dirty ? 'You’re offline. Keep this page open to save your changes.' : 'You’re offline.';
            if (this.busy) return this.pendingAction === 'restoreRevision' ? 'Restoring version…' : this.pendingAction === 'publish' ? 'Publishing…' : this.pendingAction === 'schedule' ? 'Scheduling…' : 'Saving your story…';
            if (this.saveError) return 'Changes haven’t saved. Please try again.';
            if (this.dirty) {
                if (wire.status === 'published') return 'Unpublished changes · update when you’re ready';
                if (wire.status === 'scheduled') return 'Unsaved changes · update your schedule to save';
                if (wire.status === 'archived') return 'Unsaved changes · move to drafts to save';
                if (wire.title.trim().length < 3) return 'Add a title of at least 3 characters to save';
                return 'Unsaved changes · saving shortly';
            }
            if (this.savedTime) return `Saved at ${this.savedTime}`;
            return wire.status === 'published' ? 'Published · changes are saved when you update' : wire.status === 'scheduled' ? 'Scheduled · changes need an explicit update' : wire.status === 'archived' ? 'Archived · move to drafts to keep editing' : 'Draft · only you can see this story';
        },
        async init() {
            beforeLeave = event => { if (this.dirty || this.uploading) { event.preventDefault(); event.returnValue = ''; } };
            onlineHandler = () => { this.online = true; if (this.dirty) this.queueSave(); };
            offlineHandler = () => { this.online = false; };
            window.addEventListener('beforeunload', beforeLeave);
            window.addEventListener('online', onlineHandler);
            window.addEventListener('offline', offlineHandler);
            try {
                const [{ Editor }, { default: StarterKit }, { default: Image }, { default: Placeholder }, { default: Youtube }, { Markdown }] = await Promise.all([import('@tiptap/core'), import('@tiptap/starter-kit'), import('@tiptap/extension-image'), import('@tiptap/extension-placeholder'), import('@tiptap/extension-youtube'), import('@tiptap/markdown')]);
                editor = new Editor({
                    element: this.$refs.editor,
                    extensions: [StarterKit.configure({ link: { openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } }, heading: { levels: [2, 3] } }), Image.configure({ allowBase64: false }), Youtube.configure({ nocookie: true, width: 640, height: 360 }), Markdown, Placeholder.configure({ placeholder: 'Start writing your story…' })],
                    content: wire.bodyHtml || '',
                    editorProps: { attributes: { 'aria-label': 'Story body', role: 'textbox', 'aria-multiline': 'true' } },
                    onUpdate: () => { this.syncBody(); this.markChanged(); },
                    onSelectionUpdate: () => this.updateToolbar(),
                    onTransaction: () => this.updateToolbar(),
                });
                this.syncBody();
                this.ready = true;
                this.updateToolbar();
            } catch { this.saveError = 'The writing tools couldn’t load. Reload this page before you start writing.'; }
        },
        destroy() {
            clearTimeout(saveTimer);
            window.removeEventListener('beforeunload', beforeLeave);
            window.removeEventListener('online', onlineHandler);
            window.removeEventListener('offline', offlineHandler);
            editor?.destroy();
        },
        countWords(text) {
            return text.trim().split(/\s+/u).filter(Boolean).length;
        },
        syncBody() {
            if (!editor) return;
            this.wordCount = this.countWords(editor.getText());
            wire.$set('bodyHtml', editor.getHTML(), false);
            wire.$set('bodyJson', editor.getJSON(), false);
            let images = 0, described = 0, internal = false;
            editor.state.doc.descendants(node => {
                if (node.type.name === 'image') { images++; if (node.attrs.alt?.trim()) described++; }
                for (const mark of node.marks || []) {
                    if (mark.type.name !== 'link') continue;
                    try { const url = new URL(mark.attrs.href, window.location.origin); if (url.origin === window.location.origin && url.pathname.startsWith('/@')) internal = true; } catch {}
                }
            });
            this.imageCount = images; this.describedImages = described; this.hasInternalLink = internal;
        },
        markChanged() {
            changeVersion++;
            this.dirty = true;
            this.saveError = '';
            this.queueSave();
        },
        queueSave() {
            clearTimeout(saveTimer);
            if (wire.status !== 'draft' || !this.online) return;
            saveTimer = setTimeout(() => {
                if (!this.busy && this.dirty && wire.title.trim().length >= 3) this.submit('autosave');
                else if (this.busy) this.queueSave();
            }, 3500);
        },
        saveShortcut() { this.submit(wire.status === 'published' ? 'publish' : wire.status === 'scheduled' ? 'schedule' : 'saveDraft'); },
        async submit(action, revisionId = null) {
            if (this.busy || this.uploading || !this.ready) return;
            clearTimeout(saveTimer);
            if (!navigator.onLine) { this.online = false; this.saveError = 'Reconnect to save your changes. Keep this page open until they’re saved.'; return; }
            this.syncBody();
            const version = changeVersion;
            this.busy = true;
            this.pendingAction = action;
            this.saveError = '';
            try {
                const result = revisionId === null ? await wire.$call(action) : await wire.$call(action, revisionId);
                if (action === 'restoreRevision' && result?.html !== undefined) {
                    editor.commands.setContent(result.html, { emitUpdate: false });
                    this.syncBody();
                    this.markChanged();
                    this.closeConfirmation();
                    return;
                }
                if (!result?.saved) {
                    this.saveError = action === 'autosave' && wire.status !== 'draft' ? 'This story is no longer a draft. Reload it before making more changes.' : 'Check the fields below, then save your story again.';
                    this.$nextTick(() => this.$refs.validationErrors?.focus());
                    return;
                }
                savedVersion = version;
                this.dirty = changeVersion !== savedVersion;
                this.savedTime = result.savedAt ? new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(new Date(result.savedAt)) : result.time;
                this.closeConfirmation();
                if (result.editUrl && window.location.pathname === '/write') window.history.replaceState(window.history.state, '', result.editUrl);
                if (result.redirect) { this.dirty = false; window.location.assign(result.redirect); return; }
            } catch {
                this.saveError = 'Your changes haven’t saved. Keep this page open and try again.';
            } finally {
                this.busy = false;
                this.pendingAction = '';
                if (this.dirty && !this.saveError) this.queueSave();
            }
        },
        askForDraft() {
            confirmationTrigger = document.activeElement;
            if (wire.status === 'draft') { this.submit('saveDraft'); return; }
            this.confirmation = { action: 'saveDraft', id: null, title: 'Move this story to drafts?', message: wire.status === 'published' ? 'Readers will no longer be able to open it. You can publish it again whenever you’re ready.' : wire.status === 'scheduled' ? 'This cancels the scheduled publication. Your story and changes will be saved as a draft.' : 'Your story will return to your drafts with the changes you’ve made.', label: 'Move to drafts' };
            this.$nextTick(() => this.$refs.confirmationPanel?.focus());
        },
        askToRestore(id, label) {
            confirmationTrigger = document.activeElement;
            clearTimeout(saveTimer);
            this.confirmation = { action: 'restoreRevision', id, title: `Restore the version from ${label}?`, message: 'This replaces your title and story text. Your current text will be kept in revision history. Other story settings stay as they are.', label: 'Restore version' };
            this.$nextTick(() => this.$refs.confirmationPanel?.focus());
        },
        closeConfirmation() {
            const wasOpen = !!this.confirmation; this.confirmation = null;
            if (this.dirty) this.queueSave();
            if (wasOpen) this.$nextTick(() => { if (confirmationTrigger?.isConnected) confirmationTrigger.focus(); else editor?.commands.focus(); });
        },
        updateToolbar() {
            if (!editor) return;
            this.active = { bold: editor.isActive('bold'), italic: editor.isActive('italic'), strike: editor.isActive('strike'), h2: editor.isActive('heading', { level: 2 }), h3: editor.isActive('heading', { level: 3 }), quote: editor.isActive('blockquote'), bullet: editor.isActive('bulletList'), ordered: editor.isActive('orderedList'), code: editor.isActive('codeBlock'), link: editor.isActive('link') };
            this.canUndo = editor.can().undo(); this.canRedo = editor.can().redo();
        },
        format(command) {
            if (!editor) return;
            const chain = editor.chain().focus();
            const commands = { bold: () => chain.toggleBold(), italic: () => chain.toggleItalic(), strike: () => chain.toggleStrike(), h2: () => chain.toggleHeading({ level: 2 }), h3: () => chain.toggleHeading({ level: 3 }), quote: () => chain.toggleBlockquote(), bullet: () => chain.toggleBulletList(), ordered: () => chain.toggleOrderedList(), code: () => chain.toggleCodeBlock(), rule: () => chain.setHorizontalRule(), undo: () => chain.undo(), redo: () => chain.redo() };
            commands[command]?.().run();
        },
        openTool(name) {
            trigger = document.activeElement;
            this.tool = name; this.toolError = '';
            this.toolValue = name === 'link' ? editor.getAttributes('link').href || '' : '';
            this.$nextTick(() => { if (name === 'image') this.$refs.imageDescription?.focus(); else this.$refs.toolUrl?.focus(); });
        },
        closeTool() { this.tool = ''; this.toolError = ''; this.pendingMarkdown = ''; this.imageFile = null; trigger?.focus(); },
        applyTool() {
            const value = this.toolValue.trim();
            this.toolError = '';
            if (this.tool === 'link') {
                if (!value) { editor.chain().focus().extendMarkRange('link').unsetLink().run(); this.closeTool(); return; }
                if (!/^(https?:\/\/|\/[^/]|mailto:)/i.test(value)) { this.toolError = 'Use an https:// address, an email link, or a link such as /@writer/story.'; return; }
                const chain = editor.chain().focus().extendMarkRange('link');
                if (editor.state.selection.empty && !editor.isActive('link')) chain.insertContent({ type: 'text', text: value, marks: [{ type: 'link', attrs: { href: value } }] }).run();
                else chain.setLink({ href: value }).run();
            } else if (this.tool === 'video') {
                let parsed;
                try { parsed = new URL(value); } catch {}
                const videoId = parsed?.hostname === 'youtu.be' ? parsed.pathname.slice(1) : parsed?.pathname === '/watch' ? parsed.searchParams.get('v') : '';
                const validVideo = parsed?.protocol === 'https:' && ['youtube.com', 'www.youtube.com', 'youtu.be'].includes(parsed.hostname) && /^[a-zA-Z0-9_-]{11}$/.test(videoId || '');
                if (!validVideo || !editor.commands.setYoutubeVideo({ src: value })) { this.toolError = 'Paste a full YouTube watch link or a youtu.be link.'; return; }
            } else if (this.tool === 'markdown') {
                editor.commands.setContent(this.pendingMarkdown, { contentType: 'markdown' });
            }
            this.closeTool();
        },
        async importMarkdown(event) {
            const file = event.target.files[0]; event.target.value = '';
            if (!file) return;
            this.uploadError = '';
            if (file.size > 500000) { this.uploadError = 'Choose a Markdown file smaller than 500 KB.'; return; }
            try { this.pendingMarkdown = await file.text(); this.openTool('markdown'); }
            catch { this.uploadError = 'This file couldn’t be read. Choose another Markdown file.'; }
        },
        exportMarkdown() {
            if (!editor) return;
            const blob = new Blob([editor.getMarkdown()], { type: 'text/markdown;charset=utf-8' });
            const url = URL.createObjectURL(blob); const anchor = document.createElement('a'); anchor.href = url; anchor.download = (wire.slug || 'my-story') + '.md'; anchor.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
        },
        selectBodyImage(event) {
            const file = event.target.files[0]; event.target.value = '';
            if (!file) return;
            this.uploadError = '';
            if (file.size > 5 * 1024 * 1024) { this.uploadError = 'Choose an image smaller than 5 MB.'; return; }
            this.imageFile = file; this.imageAlt = ''; this.openTool('image');
        },
        async uploadCover(event) {
            const file = event.target.files[0]; event.target.value = '';
            if (file) await this.uploadImage(file, 'cover');
        },
        async uploadImage(file, destination) {
            if (!file || this.uploading) return;
            if (file.size > 5 * 1024 * 1024) { this.uploadError = 'Choose an image smaller than 5 MB.'; return; }
            this.uploading = true; this.uploadError = ''; this.toolError = '';
            try {
                const body = new FormData(); body.append('image', file);
                const response = await fetch('/media', { method: 'POST', body, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' } });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.errors?.image?.[0] || (response.status === 419 ? 'Your session expired. Save a Markdown copy before reloading this page.' : 'The image couldn’t upload. Check your connection and try again.'));
                if (destination === 'cover') { wire.$set('coverImage', data.url, false); this.markChanged(); }
                else { editor.chain().focus().setImage({ src: data.url, alt: this.imageAlt.trim() }).run(); this.closeTool(); }
            } catch (error) { this.uploadError = error.message; if (destination === 'body') this.toolError = error.message; }
            finally { this.uploading = false; }
        },
        removeCover() { wire.$set('coverImage', '', false); this.markChanged(); },
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

// Native POST forms keep their original submitter value while preventing repeats.
document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.matches('[data-toggle-action], [data-download-form]') || [...form.attributes].some(attribute => attribute.name.startsWith('wire:submit')) || event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
    if (form.dataset.submitting) { event.preventDefault(); return; }
    const submitter = event.submitter;
    queueMicrotask(() => {
        if (event.defaultPrevented) return;
        form.dataset.submitting = 'true'; form.setAttribute('aria-busy', 'true');
        if (submitter?.name) {
            const value = document.createElement('input'); value.type = 'hidden'; value.name = submitter.name; value.value = submitter.value; value.dataset.submitterCopy = 'true'; form.append(value);
        }
        for (const button of form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])')) {
            button.dataset.wasDisabled = String(button.disabled); button.disabled = true;
        }
        const status = document.createElement('span'); status.className = 'sr-only'; status.setAttribute('role', 'status'); status.dataset.submitStatus = 'true'; status.textContent = 'Please wait. Your request is being sent.'; form.append(status);
    });
});
window.addEventListener('pageshow', () => {
    for (const form of document.querySelectorAll('form[data-submitting]')) {
        delete form.dataset.submitting; form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-was-disabled]').forEach(button => { button.disabled = button.dataset.wasDisabled === 'true'; delete button.dataset.wasDisabled; });
        form.querySelectorAll('[data-submitter-copy], [data-submit-status]').forEach(element => element.remove());
    }
});
