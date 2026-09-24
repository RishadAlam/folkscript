<?php

namespace App\Http\Controllers;

use App\Domain\Seo\SeoData;
use App\Domain\Seo\SitemapBuilder;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(SitemapBuilder $builder): Response
    {
        return response($builder->index(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300']);
    }

    public function sitemapSection(SitemapBuilder $builder, string $type, int $page = 1): Response
    {
        return response($builder->section($type, $page), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300']);
    }

    public function feed(?string $username = null, ?string $slug = null): Response
    {
        $query = Post::query()->published()->with('author')->latest('published_at');
        $title = 'Folkscript';
        $link = url('/');
        if ($username) {
            $author = User::query()->where('username', $username)->firstOrFail();
            $query->where('author_id', $author->id);
            $title = $author->name.' on Folkscript';
            $link = url('/@'.$username);
        }
        if ($slug) {
            $topic = Category::query()->where('slug', $slug)->first();
            $relation = 'categories';
            if (! $topic) {
                $topic = Tag::query()->where('slug', $slug)->firstOrFail();
                $relation = 'tags';
            }
            $query->whereHas($relation, fn ($q) => $q->where('slug', $slug));
            $title = $topic->name.' on Folkscript';
            $link = url('/topic/'.$slug);
        }
        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel><title>'.$escape($title).'</title><link>'.$escape($link).'</link><description>Written by the people, read by everyone.</description><language>en</language><atom:link href="'.$escape(request()->url()).'" rel="self" type="application/rss+xml"/>';
        foreach ($query->limit(40)->get() as $post) {
            $xml .= '<item><title>'.$escape($post->title).'</title><link>'.$escape(SeoData::postUrl($post)).'</link><guid isPermaLink="true">'.$escape(SeoData::postUrl($post)).'</guid><description>'.$escape(strip_tags($post->excerpt ?? '')).'</description><dc:creator>'.$escape($post->author->name).'</dc:creator><pubDate>'.$post->published_at->toRssString().'</pubDate></item>';
        }
        $xml .= '</channel></rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function topicFeed(string $slug): Response
    {
        return $this->feed(slug: $slug);
    }

    public function robots(): Response
    {
        $text = "User-agent: *\nAllow: /\nDisallow: /dashboard\nDisallow: /write\nDisallow: /admin\nDisallow: /settings\nDisallow: /api/\nDisallow: /search\n\n";
        if (config('seo.block_training_bots', false)) {
            foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot'] as $bot) {
                $text .= "User-agent: {$bot}\nDisallow: /\n\n";
            }
        }
        $text .= 'Sitemap: '.url('/sitemap.xml')."\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function indexNowKey(): Response
    {
        $key = (string) config('seo.indexnow_key');
        abort_unless(preg_match('/^[a-zA-Z0-9-]{8,128}$/', $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
