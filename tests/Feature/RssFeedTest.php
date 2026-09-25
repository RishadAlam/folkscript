<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class RssFeedTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['seo.indexnow_key' => null]);
    }

    private function story(User $author, array $attributes = []): Post
    {
        return Post::create([
            'author_id' => $author->id, 'title' => 'Public story', 'slug' => 'story-'.Str::random(12),
            'excerpt' => 'A freely readable introduction.', 'body_html' => '<p>A freely readable story.</p>',
            'status' => 'published', 'published_at' => now()->subMinute(), 'reading_time' => 1,
            ...$attributes,
        ]);
    }

    private function feed(string $url): \SimpleXMLElement
    {
        $response = $this->get($url)->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($response->getContent());
            $errors = array_map(fn ($error) => trim($error->message), libxml_get_errors());
            $this->assertNotFalse($xml, implode('; ', $errors));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $this->assertSame('2.0', (string) $xml['version']);
        $this->assertSame($url, (string) $xml->channel->children('http://www.w3.org/2005/Atom')->link->attributes()['href']);

        return $xml;
    }

    public function test_public_feeds_include_valid_metadata_and_filter_by_author_category_and_tag(): void
    {
        $author = $this->account('author', ['name' => 'Élodie Reader']);
        $otherAuthor = $this->account('author');
        $category = Category::create(['name' => 'Nature', 'slug' => 'nature']);
        $tag = Tag::create(['name' => 'Forests', 'slug' => 'forests']);
        $older = $this->story($author, ['title' => 'Older story', 'published_at' => now()->subHour()]);
        $newer = $this->story($author, ['title' => 'Newest story']);
        $other = $this->story($otherAuthor, ['title' => 'Other writer', 'published_at' => now()->subMinutes(5)]);
        $newer->categories()->attach($category);
        $other->categories()->attach($category);
        $older->tags()->attach($tag);

        $feeds = [
            route('seo.feed') => [$newer, $other, $older],
            route('seo.author-feed', $author->username) => [$newer, $older],
            route('seo.topic-feed', $category->slug) => [$newer, $other],
            route('seo.topic-feed', $tag->slug) => [$older],
        ];
        foreach ($feeds as $url => $expected) {
            $feed = $this->feed($url);
            $this->assertCount(count($expected), $feed->channel->item);
            foreach ($expected as $index => $post) {
                $item = $feed->channel->item[$index];
                $this->assertSame($post->title, (string) $item->title);
                $this->assertSame(url($post->url), (string) $item->link);
                $this->assertSame(url($post->url), (string) $item->guid);
                $this->assertSame('true', (string) $item->guid['isPermaLink']);
                $this->assertSame($post->excerpt, (string) $item->description);
                $this->assertSame($post->author->name, (string) $item->children('http://purl.org/dc/elements/1.1/')->creator);
                $this->assertSame($post->published_at->toRssString(), (string) $item->pubDate);
            }
        }
    }

    public function test_feeds_hide_non_public_stories_allow_empty_feeds_and_reject_missing_or_suspended_authors(): void
    {
        $author = $this->account('author');
        $emptyAuthor = $this->account('author');
        $suspended = $this->account('author', ['suspended_at' => now()]);
        $category = Category::create(['name' => 'Reading', 'slug' => 'reading']);
        $emptyTag = Tag::create(['name' => 'Empty topic', 'slug' => 'empty-topic']);
        $public = $this->story($author);
        $posts = [$public, $this->story($suspended)];
        foreach ([['status' => 'draft'], ['status' => 'scheduled'], ['status' => 'archived'], ['published_at' => now()->addDay()], ['published_at' => null]] as $state) {
            $posts[] = $this->story($author, $state);
        }
        foreach ($posts as $post) {
            $post->categories()->attach($category);
        }
        foreach ([route('seo.feed'), route('seo.author-feed', $author->username), route('seo.topic-feed', $category->slug)] as $url) {
            $feed = $this->feed($url);
            $this->assertCount(1, $feed->channel->item);
            $this->assertSame(url($public->url), (string) $feed->channel->item->link);
        }
        foreach ([route('seo.author-feed', $emptyAuthor->username), route('seo.topic-feed', $emptyTag->slug)] as $url) {
            $this->assertCount(0, $this->feed($url)->channel->item);
        }
        $this->get(route('seo.author-feed', 'missing-writer'))->assertNotFound();
        $this->get(route('seo.topic-feed', 'missing-topic'))->assertNotFound();
        $this->get(route('seo.author-feed', $suspended->username))->assertNotFound();
    }

    public function test_user_text_cannot_break_feed_xml_and_retains_unicode_and_special_characters(): void
    {
        $author = $this->account('author', ['name' => "Élodie & \"Reader\"\x01"]);
        $post = $this->story($author, [
            'title' => "A < B & \"café\"\x01",
            'excerpt' => "<b>Read</b> & \"remember\"\x01",
        ]);
        foreach ([route('seo.feed'), route('seo.author-feed', $author->username)] as $url) {
            $feed = $this->feed($url);
            $item = $feed->channel->item;
            $this->assertSame(str_replace("\x01", "\u{FFFD}", $post->title), (string) $item->title);
            $this->assertSame("Read & \"remember\"\u{FFFD}", (string) $item->description);
            $this->assertSame(str_replace("\x01", "\u{FFFD}", $author->name), (string) $item->children('http://purl.org/dc/elements/1.1/')->creator);
        }
    }
}
