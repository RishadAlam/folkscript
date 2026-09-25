<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\PublicMarkdown;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ReaderIndexController extends Controller
{
    public function __invoke(PublicMarkdown $markdown): Response
    {
        $html = '<h1>Folkscript</h1>'
            .'<blockquote>Written by the people, read by everyone. Folkscript is a free, open-source, nonprofit publishing project for independent writing and curious readers.</blockquote>'
            .'<p>Every published story is freely readable. This index links to public Markdown pages with complete story text, author attribution, source links, and paginated discovery.</p>'
            .'<h2>Explore</h2><ul>'
            .'<li>'.$markdown->link('All published stories', route('reader.explore')).': Browse the full public collection; follow the next-page links to continue.</li>'
            .'<li>'.$markdown->link('Writers', route('reader.explore', ['type' => 'people'])).': Discover writers with published stories.</li>'
            .'<li>'.$markdown->link('Trending stories', route('reader.explore', ['sort' => 'trending'])).': Browse the most-read published stories.</li>'
            .'</ul>';

        $categories = Category::whereHas('posts', fn ($query) => $query->published())
            ->orderBy('name')->orderBy('id')->limit(24)->get(['name', 'slug', 'description']);
        if ($categories->isNotEmpty()) {
            $html .= '<h2>Topics</h2><p>A selection of up to 24 categories with public stories.</p><ul>';
            foreach ($categories as $category) {
                $html .= '<li>'.$markdown->link($category->name, route('reader.topic', $category->slug));
                if ($category->description) {
                    $html .= ': '.e(Str::limit($category->description, 200));
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $stories = Post::published()->with('author:id,name,username')
            ->latest('published_at')->orderByDesc('id')->limit(12)
            ->get(['id', 'author_id', 'title', 'excerpt', 'published_at']);
        $html .= '<h2>Latest stories</h2><p>This is a selection of the 12 most recently published stories, not a complete archive. '
            .$markdown->link('Explore all published stories', route('reader.explore')).' for the full paginated collection.</p>';
        if ($stories->isEmpty()) {
            $html .= '<p>No stories have been published yet.</p>';
        } else {
            $html .= '<ul>';
            foreach ($stories as $story) {
                $html .= '<li>'.$markdown->link($story->title, route('reader.story', $story))
                    .' — By '.e($story->author->name).'; published '.$story->published_at->format('Y-m-d').'.';
                if ($story->excerpt) {
                    $html .= ' '.e(Str::limit($story->excerpt, 300));
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<h2>Reading and attribution</h2>'
            .'<p>Credit the named author and link to each story’s source URL. Drafts, scheduled stories, archived stories, suspended writers, and private account data are excluded from these exports.</p>'
            .'<p>Authors retain the rights to their writing. The software’s MIT license does not license published stories or third-party assets. This index is a navigation aid, not a content license or a guarantee of search inclusion.</p>'
            .'<h2>Other public formats</h2><ul>'
            .'<li>'.$markdown->link('RSS feed', route('seo.feed')).': Recently published story summaries.</li>'
            .'<li>'.$markdown->link('Sitemap', route('seo.sitemap')).': Public stories, author profiles, and topic archives.</li>'
            .'</ul>';

        return response($markdown->convert($html, route('reader.index')), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="llms.txt"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
