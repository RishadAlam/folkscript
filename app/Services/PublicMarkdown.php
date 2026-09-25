<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Pagination\LengthAwarePaginator;
use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\HtmlConverter;

final class PublicMarkdown
{
    public function story(Post $post): string
    {
        $source = url($post->url);
        $metadata = [
            'Author' => $this->link($post->author->name, url('/@'.$post->author->username)),
            'Published' => e($post->published_at->toIso8601String()),
        ];
        if ($post->canonical_url && $post->canonical_url !== $source) {
            $metadata['Canonical source'] = $this->link($post->canonical_url, $post->canonical_url);
        }
        $html = $this->heading($post->title, $source, route('reader.story', $post), $post->excerpt ?? '', $metadata);
        if ($post->cover_image) {
            $html .= '<p><img src="'.e($post->cover_image).'" alt="'.e($post->title).'"></p>';
        }
        $html .= '<hr><div data-reader-body>'.$post->body_html.'</div>';
        if ($post->categories->isNotEmpty() || $post->tags->isNotEmpty()) {
            $html .= '<hr><h2>Topics</h2><ul>'.$post->categories->concat($post->tags)->unique('slug')
                ->map(fn ($topic) => '<li>'.$this->link($topic->name, route('reader.topic', $topic->slug)).'</li>')->implode('').'</ul>';
        }
        $html .= '<h2>More to read</h2><ul><li>'.$this->link('More by '.$post->author->name, route('reader.author', $post->author)).'</li>'
            .'<li>'.$this->link('Explore public stories', route('reader.explore')).'</li></ul>';

        return $this->convert($html, $source, $post->title);
    }

    public function listing(string $title, string $source, string $markdownUrl, string $description, LengthAwarePaginator $items, string $extraHtml = '', bool $people = false): string
    {
        $html = $this->heading($title, $source, $markdownUrl, $description).$extraHtml
            .'<h2>'.($people ? 'Writers' : 'Stories').'</h2>'
            .'<p>Page '.$items->currentPage().' of '.$items->lastPage().'. '.$items->total().' results.</p>';
        if (! $people && $items->isNotEmpty()) {
            $html .= '<p>These are story summaries. Each Markdown link opens the complete published story.</p>';
        }
        if ($items->isEmpty()) {
            $html .= '<p>No published results on this page.</p>';
        }
        foreach ($items as $item) {
            if ($item instanceof User) {
                $html .= '<h3>'.$this->link($item->name, url('/@'.$item->username)).'</h3><p>'.e($item->bio ?? '').'</p>'
                    .'<p>'.$this->link('Profile as Markdown', route('reader.author', $item)).'</p>';
            } else {
                $html .= '<h3>'.$this->link($item->title, url($item->url)).'</h3><p>By '.$this->link($item->author->name, route('reader.author', $item->author)).' · '.$item->published_at->toDateString().'</p><p>'.e($item->excerpt ?? '').'</p>'
                    .'<p>'.$this->link('Read story as Markdown', route('reader.story', $item)).'</p>';
            }
        }
        $pagination = array_filter([
            $items->previousPageUrl() ? $this->link('Previous page', $items->previousPageUrl()) : null,
            $items->nextPageUrl() ? $this->link('Next page', $items->nextPageUrl()) : null,
        ]);
        if ($pagination) {
            $html .= '<h2>Pagination</h2><p>'.implode(' · ', $pagination).'</p>';
        }

        return $this->convert($html, $source);
    }

    public function link(string $text, string $url): string
    {
        return '<a href="'.e($url).'">'.e($text).'</a>';
    }

    public function convert(string $html, string $source, ?string $bodyTitle = null): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($document);
            // The encoding hint belongs to the HTML parser, not to exported prose.
            foreach ($xpath->query('//processing-instruction()') as $instruction) {
                $instruction->parentNode->removeChild($instruction);
            }
            if ($bodyTitle !== null) {
                $this->normalizeBodyHeadings($document, $xpath, $bodyTitle);
            }
            foreach ($xpath->query('//a[@href] | //img[@src] | //iframe[@src]') as $node) {
                $attribute = $node->tagName === 'a' ? 'href' : 'src';
                $target = $this->absoluteUrl($node->getAttribute($attribute), $source);
                if ($target === null) {
                    $node->removeAttribute($attribute);

                    continue;
                }
                $node->setAttribute($attribute, $target);
                if ($node->tagName === 'iframe') {
                    $link = $document->createElement('a', 'Watch video');
                    $link->setAttribute('href', $target);
                    $paragraph = $document->createElement('p');
                    $paragraph->appendChild($link);
                    $node->parentNode->replaceChild($paragraph, $node);
                }
            }
            $converter = new HtmlConverter([
                'header_style' => 'atx',
                'strip_tags' => true,
                'strip_placeholder_links' => true,
                'remove_nodes' => 'script style noscript form input button iframe',
            ]);
            $converter->getEnvironment()->addConverter(new TableConverter);
            // Figure wrappers are block elements, even when their image is inline.
            $converter->getEnvironment()->addConverter(new class implements ConverterInterface
            {
                public function convert(ElementInterface $element): string
                {
                    return "\n\n".trim($element->getValue())."\n\n";
                }

                public function getSupportedTags(): array
                {
                    return ['figure', 'figcaption'];
                }
            });

            return trim($converter->convert($document->saveHTML()))."\n";
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function heading(string $title, string $source, string $markdownUrl, string $description, array $metadata = []): string
    {
        $html = '<blockquote><h2>Content index</h2><p>Discover public stories, writers, and topics: '
            .$this->link('Folkscript content index', url('/llms.txt')).'.</p></blockquote>'
            .'<h1>'.e($title).'</h1>';
        if ($description !== '') {
            $html .= '<blockquote><p>'.nl2br(e($description)).'</p></blockquote>';
        }
        $metadata = [
            'Source' => $this->link($source, $source),
            'Markdown' => $this->link($markdownUrl, $markdownUrl),
            ...$metadata,
        ];
        $html .= '<ul>';
        foreach ($metadata as $label => $value) {
            $html .= '<li>'.e($label).': '.$value.'</li>';
        }

        return $html.'</ul>';
    }

    private function normalizeBodyHeadings(\DOMDocument $document, \DOMXPath $xpath, string $title): void
    {
        $body = $xpath->query('//*[@data-reader-body]')->item(0);
        if (! $body) {
            return;
        }
        $first = $xpath->query('./*[1]', $body)->item(0);
        $normalize = fn (string $text) => trim(preg_replace('/\s+/u', ' ', $text));
        if ($first?->nodeName === 'h1' && $normalize($first->textContent) === $normalize($title)) {
            $body->removeChild($first);
        }
        // An article already has a page title; keep authored sections below it.
        $headings = iterator_to_array($xpath->query('.//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][not(ancestor::pre) and not(ancestor::code)]', $body));
        if (! in_array('h1', array_map(fn ($heading) => $heading->nodeName, $headings), true)) {
            return;
        }
        foreach ($headings as $heading) {
            $replacement = $document->createElement('h'.min(6, (int) substr($heading->nodeName, 1) + 1));
            while ($heading->firstChild) {
                $replacement->appendChild($heading->firstChild);
            }
            $heading->parentNode->replaceChild($replacement, $heading);
        }
    }

    private function absoluteUrl(string $target, string $source): ?string
    {
        if ($target === '' || preg_match('/[\x00-\x1f\x7f]/', $target)) {
            return null;
        }
        try {
            $uri = UriResolver::resolve(new Uri($source), new Uri($target));

            return in_array(strtolower($uri->getScheme()), ['http', 'https', 'mailto'], true) ? (string) $uri : null;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
