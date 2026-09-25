<?php

namespace Tests\Feature;

use App\Domain\Seo\SeoData;
use Illuminate\Support\Facades\Queue;

class PublicProfileLinksTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_public_consumers_preserve_custom_labels_and_repeated_platforms_without_exposing_unsafe_links(): void
    {
        $links = [
            ['label' => 'my portfolio', 'url' => 'https://portfolio.example/work'],
            ['label' => 'Mastodon', 'url' => 'https://social.example/@personal'],
            ['label' => 'Mastodon', 'url' => 'https://social.example/@studio'],
            ['label' => 'Unsafe script', 'url' => 'javascript:alert(1)'],
            ['label' => 'Private credentials', 'url' => 'https://private:secret@private.example/'],
            ['label' => 'FTP link', 'url' => 'ftp://files.example/'],
            ['label' => ['Malformed label'], 'url' => 'https://malformed.example/'],
            ['label' => 'Malformed URL', 'url' => ['https://malformed.example/']],
            ['label' => '', 'url' => 'https://empty-label.example/'],
        ];
        $author = $this->account('author', ['social_links' => $links]);

        $html = $this->get('/@'.$author->username)->assertOk()->getContent();
        $markdown = $this->get(route('reader.author', $author))->assertOk()->getContent();
        foreach (array_slice($links, 0, 3) as $link) {
            $this->assertStringContainsString('href="'.$link['url'].'"', $html);
            $this->assertStringContainsString('['.$link['label'].']('.$link['url'].')', $markdown);
        }
        foreach (['javascript:', 'private:secret', 'ftp://files.example/', 'malformed.example', 'empty-label.example'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
            $this->assertStringNotContainsString($unsafe, $markdown);
        }
        $schema = new SeoData(author: $author);
        $person = collect($schema->schema['@graph'])->firstWhere('@type', 'ProfilePage')['mainEntity'];
        $this->assertSame(array_column(array_slice($links, 0, 3), 'url'), $person['sameAs']);
    }

    public function test_legacy_profile_links_remain_readable_and_safe_in_every_consumer(): void
    {
        $author = $this->account('author', ['social_links' => [
            'website' => 'https://writer.example/',
            'github' => 'https://github.com/writer',
            'linkedin' => 'https://www.linkedin.com/in/writer',
            'my journal' => 'https://journal.example/',
            'nested' => ['unexpected' => 'https://nested.example/'],
            'credentials' => 'https://user@private.example/',
        ]]);
        $expected = [
            ['label' => 'Website', 'url' => 'https://writer.example/'],
            ['label' => 'GitHub', 'url' => 'https://github.com/writer'],
            ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/writer'],
            ['label' => 'my journal', 'url' => 'https://journal.example/'],
        ];

        $this->assertSame($expected, $author->publicProfileLinks());
        $this->get('/@'.$author->username)->assertOk()->assertSee('GitHub')->assertSee('LinkedIn')->assertDontSee('private.example');
        $markdown = $this->get(route('reader.author', $author))->assertOk()->getContent();
        foreach ($expected as $link) {
            $this->assertStringContainsString('['.$link['label'].']('.$link['url'].')', $markdown);
        }
        $schema = new SeoData(author: $author);
        $person = collect($schema->schema['@graph'])->firstWhere('@type', 'ProfilePage')['mainEntity'];
        $this->assertSame(array_column($expected, 'url'), $person['sameAs']);
    }
}
