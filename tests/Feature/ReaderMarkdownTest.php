<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class ReaderMarkdownTest extends SecurityTestCase
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
            'author_id' => $author->id, 'title' => 'Public writing', 'slug' => 'story-'.Str::random(12),
            'excerpt' => 'The public introduction.', 'body_html' => '<p>Words for everyone.</p>',
            'status' => 'published', 'published_at' => now()->subMinute(), 'reading_time' => 1,
            ...$attributes,
        ]);
    }

    public function test_ai_reader_links_include_the_exact_absolute_markdown_url_prompt_without_manual_tools(): void
    {
        $query = '?q=caf%C3%A9%20%26%20%E0%A6%AC%E0%A6%A8%20%2B%20%23&topic=nature&page=2';
        $html = Blade::render('<x-reader-tools :url="$exportUrl" />', [
            'exportUrl' => '/read/explore.md'.$query,
        ]);
        $prompt = 'Read from '.url('/read/explore.md').$query.' so I can ask questions about it.';
        $providers = [
            'Claude' => 'https://claude.ai/new?q=',
            'ChatGPT' => 'https://chatgpt.com/?q=',
            'Perplexity' => 'https://www.perplexity.ai/search?q=',
            'Copilot' => 'https://copilot.microsoft.com/?q=',
            'Grok' => 'https://grok.com/?q=',
            'Google AI Mode' => 'https://www.google.com/search?udm=50&q=',
        ];
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $links = (new \DOMXPath($document))->query('//a[contains(concat(" ", normalize-space(@class), " "), " reader-provider ")]');
        $this->assertCount(count($providers), $links);
        $destinations = [];
        foreach ($links as $link) {
            $destinations[] = $link->getAttribute('href');
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $parameters);
            $this->assertSame($prompt, $parameters['q']);
            $this->assertSame('_blank', $link->getAttribute('target'));
        }
        $this->assertEqualsCanonicalizing(array_map(fn ($prefix) => $prefix.rawurlencode($prompt), array_values($providers)), $destinations);
        foreach (['Gemini', 'DeepSeek', 'Copy &amp; paste instead', 'Prompt copied', 'Full-page prompt copied'] as $removed) {
            $this->assertStringNotContainsString($removed, $html);
        }
    }

    public function test_guest_markdown_preserves_story_structure_and_absolute_attribution_without_page_chrome(): void
    {
        $author = $this->account('author', ['name' => 'Élodie Reader', 'email' => 'private@example.test']);
        $post = $this->story($author, [
            'cover_image' => '/images/cover.jpg',
            'body_html' => '<h1>Public writing</h1><h1>Paying attention</h1><p>A <strong>clear</strong> story with <a href="/about">a link</a> and café.</p>'
                .'<ul><li>First observation</li><li>Second observation</li></ul><blockquote>A remembered line.</blockquote>'
                .'<figure><img src="/images/forest.jpg" alt="Green forest"></figure><h2>Another observation</h2><pre><code class="language-markdown">'."# Literal heading\n\nRead the example unchanged.".'</code></pre>'
                .'<figure><img src="/images/lake.jpg" alt="Blue lake"><figcaption>A still morning.</figcaption></figure>'
                .'<table><tr><th>Season</th><th>Color</th></tr><tr><td>Spring</td><td>Green</td></tr></table>'
                .'<p><a href="javascript:alert(1)">Unsafe link label</a></p><img src="data:image/svg+xml,bad" alt="Unsafe image">'
                .'<iframe src="https://www.youtube-nocookie.com/embed/abc123"></iframe><script>secretScript()</script>',
        ]);

        $response = $this->get(route('reader.story', $post))->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $content = $response->getContent();
        $this->assertStringStartsWith('> ## Content index', $content);
        $this->assertStringContainsString(url('/llms.txt'), $content);
        $this->assertSame(1, substr_count($content, '# Public writing'));
        $this->assertStringContainsString('> The public introduction.', $content);
        $this->assertStringContainsString(route('reader.story', $post), $content);
        $this->assertStringContainsString($post->published_at->toIso8601String(), $content);
        // View-count updates also touch updated_at; it is not an editorial revision date.
        $this->assertStringNotContainsString('Updated:', $content);
        $this->assertStringContainsString('![Green forest]('.url('/images/forest.jpg').")\n\n### Another observation", $content);
        $this->assertStringContainsString('![Blue lake]('.url('/images/lake.jpg').")\n\nA still morning.", $content);
        $this->assertStringContainsString("```markdown\n# Literal heading\n\nRead the example unchanged.\n```", $content);
        foreach (['# Public writing', 'Élodie Reader', url($post->url), '## Paying attention', '**clear**', '[a link]('.url('/about').')', 'café', '- First observation', '> A remembered line.', '![Green forest]('.url('/images/forest.jpg').')', '```', 'Season', 'Watch video'] as $expected) {
            $this->assertStringContainsString($expected, $content);
        }
        foreach (['private@example.test', '<h2>', '<iframe', 'secretScript', 'Sign in', 'Administration', 'javascript:', 'data:image'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $content);
        }
        $this->assertSame(0, $post->fresh()->views);
        $plain = $this->get(route('reader.story', ['post' => $post, 'view' => 'plain']))->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame($content, $plain->getContent());

    }

    public function test_reader_endpoints_cannot_export_private_stories_even_for_their_author(): void
    {
        $author = $this->account('author');
        $stories = [];
        foreach ([['status' => 'draft'], ['status' => 'scheduled'], ['status' => 'archived'], ['published_at' => now()->addDay()]] as $state) {
            $stories[] = $this->story($author, $state);
        }
        $suspended = $this->account('author', ['suspended_at' => now()]);
        $stories[] = $this->story($suspended);
        foreach ($stories as $post) {
            $this->get(route('reader.story', $post))->assertNotFound();
        }
        $this->actingAs($author);
        foreach ($stories as $post) {
            $this->get(route('reader.story', $post))->assertNotFound();
        }
        $this->get(route('reader.author', $suspended))->assertNotFound();
        $collection = Series::create(['author_id' => $suspended->id, 'title' => 'Suspended collection', 'slug' => 'suspended']);
        $this->get(route('reader.collection', $collection))->assertNotFound();
    }

    public function test_public_indexes_preserve_pagination_filters_and_collection_ownership(): void
    {
        $author = $this->account('author', ['name' => 'Public Author', 'email' => 'private@example.test', 'bio' => 'Notes about forests.']);
        $category = Category::create(['name' => 'Nature', 'slug' => 'nature']);
        $tag = Tag::create(['name' => 'Forests', 'slug' => 'forests']);
        $collection = Series::create(['author_id' => $author->id, 'title' => 'Public collection', 'slug' => 'public-collection']);
        for ($index = 1; $index <= 13; $index++) {
            $post = $this->story($author, ['title' => 'Forest story '.sprintf('%02d', $index), 'published_at' => now()->subMinutes($index), 'views' => $index]);
            $post->categories()->attach($category);
            $post->tags()->attach($tag);
            $collection->posts()->attach($post, ['order' => $index]);
        }
        $draft = $this->story($author, ['title' => 'Secret draft', 'status' => 'draft']);
        $draft->categories()->attach($category);
        $collection->posts()->attach($draft, ['order' => 0]);
        $foreign = $this->story($this->account('author'), ['title' => 'Foreign collection story']);
        $collection->posts()->attach($foreign, ['order' => 0]);

        foreach ([route('reader.collection', $collection), route('reader.topic', $category->slug), route('reader.topic', $tag->slug), route('reader.explore', ['topic' => 'nature'])] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('Forest story 01')->assertSee('Next page')->assertDontSee('Forest story 13')->assertDontSee('Secret draft')->assertDontSee('Foreign collection story');
            $this->assertStringStartsWith('> ## Content index', $response->getContent());
            $this->assertStringContainsString($url, $response->getContent());
            $this->assertStringContainsString('## Stories', $response->getContent());
            $this->assertStringContainsString('### [Forest story 01]', $response->getContent());
            $separator = str_contains($url, '?') ? '&' : '?';
            $pageTwo = $url.$separator.'page=2';
            $pageResponse = $this->get($pageTwo.'&view=plain')->assertOk()->assertSee('Forest story 13')->assertDontSee('Forest story 01')->assertSee('Previous page');
            $this->assertStringContainsString($pageTwo, $pageResponse->getContent());
            $this->assertStringNotContainsString('view=plain', $pageResponse->getContent());
        }
        $this->get(route('reader.author', $author))->assertOk()->assertSee('Forest story 08')->assertDontSee('Forest story 09')->assertDontSee('private@example.test')->assertDontSee('Secret draft');
        $this->get(route('reader.author', ['user' => $author, 'page' => 2]))->assertOk()->assertSee('Forest story 09')->assertDontSee('Forest story 01');
        $this->get(route('reader.explore', ['q' => 'story 13']))->assertOk()->assertSee('Forest story 13')->assertDontSee('Forest story 01');
        $this->get(route('reader.explore', ['q' => 'forests', 'type' => 'people']))->assertOk()->assertSee('## Writers')->assertSee('### [Public Author]')->assertDontSee('private@example.test');
        $trendingUrl = route('reader.explore', ['topic' => 'nature', 'sort' => 'trending']);
        $trending = $this->get($trendingUrl.'&view=plain')->assertOk()->assertSeeInOrder(['Trending stories', 'Forest story 13', 'Forest story 12']);
        $this->assertStringContainsString($trendingUrl, $trending->getContent());
        $this->assertStringNotContainsString('view=plain', $trending->getContent());

        foreach (['q[]=bad', 'topic[]=bad', 'type[]=bad', 'sort[]=bad', 'page=0', 'page[]=2', 'view[]=plain', 'view=html'] as $query) {
            $this->get(route('reader.explore').'?'.$query)->assertStatus(400);
        }
    }

    public function test_content_index_links_only_to_public_content_and_limits_recent_stories(): void
    {
        $author = $this->account('author', ['email' => 'private@example.test']);
        $category = Category::create(['name' => 'Public category', 'slug' => 'public-category']);
        $privateCategory = Category::create(['name' => 'Private category', 'slug' => 'private-category']);
        $stories = [];
        for ($index = 1; $index <= 13; $index++) {
            $stories[] = $this->story($author, ['title' => 'Indexed story '.sprintf('%02d', $index), 'published_at' => now()->subMinutes($index)]);
        }
        $stories[0]->categories()->attach($category);
        $draft = $this->story($author, ['title' => 'Private draft', 'status' => 'draft']);
        $draft->categories()->attach($privateCategory);
        $this->story($author, ['title' => 'Future story', 'published_at' => now()->addDay()]);
        $this->story($this->account('author', ['suspended_at' => now()]), ['title' => 'Suspended author story']);

        $response = $this->get(route('reader.index'))->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $content = $response->getContent();
        foreach ([route('reader.explore'), route('reader.explore', ['type' => 'people']), route('reader.topic', $category->slug), route('reader.story', $stories[0]), route('reader.story', $stories[11])] as $url) {
            $this->assertStringContainsString($url, $content);
        }
        foreach (['Indexed story 13', 'Private draft', 'Future story', 'Suspended author story', 'Private category', 'private@example.test'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $content);
        }
        $this->assertSame(0, $stories[0]->fresh()->views);
    }
}
