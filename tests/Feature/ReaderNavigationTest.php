<?php

namespace Tests\Feature;

class ReaderNavigationTest extends SecurityTestCase
{
    public function test_reader_navigation_does_not_offer_unavailable_publishing_actions(): void
    {
        $reader = $this->account();

        $this->actingAs($reader)->get('/settings')
            ->assertOk()
            ->assertDontSee('href="/write"', false)
            ->assertDontSee('Writing studio')
            ->assertSee('Saved stories');

        $this->get('/dashboard')->assertRedirect('/bookmarks');
        $this->get('/write')->assertForbidden();
    }

    public function test_writer_keeps_publishing_navigation_and_studio(): void
    {
        $writer = $this->account('author');

        $this->actingAs($writer)->get('/settings')
            ->assertOk()->assertSee('Writing studio')->assertSee('href="/write"', false);
        $this->get('/dashboard')->assertOk()->assertSee('New story');
    }
}
