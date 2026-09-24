<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Payout;
use App\Models\Post;
use App\Models\Report;
use App\Models\Revision;
use App\Models\Series;
use App\Models\User;
use App\Notifications\CommunityNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

/** Safe, repeatable fixtures for exercising local reading and administration. */
class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep direct invocation subject to the same guard as DatabaseSeeder.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO_CONTENT', false), FILTER_VALIDATE_BOOL)) { return; }

        $accounts = [
            ['reader', 'Maya Patel', 'reader', true, false, 'A reader with an interest in cities, books, and everyday design.'],
            ['premium', 'Theo Brooks', 'premium-reader', true, false, 'A demo member exploring long-form writing and independent perspectives.'],
            ['editor', 'Nora Williams', 'editor', true, false, 'Working with writers on clear, useful stories and considerate conversations.'],
            ['owner', 'Morgan Ellis', 'super-admin', true, false, 'Local demonstration account for platform administration.'],
            ['unverified', 'Leo Santos', 'reader', false, false, 'Local demo account awaiting email verification.'],
            ['suspended', 'Casey Miller', 'reader', true, true, 'Local demo account for reviewing suspension and restoration.'],
            ['sofia', 'Sofia Ahmed', 'reader', true, false, 'Reading about food, culture, and the places we call home.'],
            ['ben', 'Ben Walker', 'reader', true, false, 'A notebook, a bicycle, and something good to read.'],
            ['iris', 'Iris Tan', 'reader', true, false, 'Following thoughtful writing about technology and society.'],
            ['sam', 'Sam Rivera', 'reader', true, false, 'Interested in art, public spaces, and creative practice.'],
            ['lina', 'Lina Rahman', 'reader', true, false, 'Collecting stories about travel and ordinary life.'],
        ];
        $users = [];
        foreach ($accounts as [$key, $name, $role, $verified, $suspended, $bio]) {
            $user = User::firstOrCreate(['email' => $key.'@folkscript.test'], [
                'username' => $key, 'name' => $name, 'bio' => $bio,
                'password' => Hash::make('Folkscript2026!'),
                'email_verified_at' => $verified ? now() : null,
                'suspended_at' => $suspended ? now() : null,
                'newsletter_enabled' => false,
            ]);
            // Re-seeding must not undo an administrator's access changes.
            if ($user->wasRecentlyCreated) { $user->assignRole($role); }
            $users[$key] = $user;
        }

        $writer = User::where('email', 'writer@folkscript.test')->firstOrFail();
        $admin = User::where('email', 'admin@folkscript.test')->firstOrFail();
        $life = Category::where('slug', 'life')->firstOrFail();
        $design = Category::where('slug', 'design')->firstOrFail();
        $firstStory = Post::where('slug', 'the-quiet-art-of-paying-attention')->firstOrFail();

        $published = $this->story($writer, 'a-neighbourhood-library-built-on-trust', 'A neighbourhood library built on trust', 'published', [
            'A small shelf appeared outside the corner café last spring. By autumn, it had become a regular stop on my walk home.',
            'Neighbours leave books they have finished and take something they would never have chosen in a shop. There is no catalogue and nobody keeps score.',
            'This demonstration story is available for trying editorial review, reports, and publishing actions. Its details are fictional.',
        ], $life, ['cover_image' => '/images/story-book.jpg', 'published_at' => now()->subDays(3), 'views' => 184]);
        $second = $this->story($writer, 'the-case-for-a-proper-lunch-break', 'The case for a proper lunch break', 'published', [
            'At half past twelve I close the laptop and make something to eat. The work is still there when I return; the afternoon usually feels more manageable.',
            'Taking a break does not require an elaborate routine. A short walk, a meal away from the desk, or a conversation with a neighbour can make the day feel less compressed.',
            'I am learning to treat that interval as part of a working day, rather than a reward for finishing it.',
        ], $life, ['cover_image' => '/images/story-studio.jpg', 'published_at' => now()->subDays(4), 'views' => 96]);
        $draft = $this->story($writer, 'what-makes-a-street-feel-like-home', 'What makes a street feel like home', 'draft', [
            'I have lived on this street for four years, but I only recently learned the name of the man who opens the bakery before sunrise.',
            'Draft notes: return to the bakery, ask about the old shopfront, and speak with neighbours about the places they miss.',
        ], $design, ['cover_image' => '/images/story-city.jpg']);
        $this->story($writer, 'a-notebook-for-the-next-season', 'A notebook for the next season', 'scheduled', [
            'At the turn of each season, I leave a few pages in my notebook for things I would like to try. The list usually starts with something small.',
            'This time it is learning the names of the trees in the park and writing a letter to a friend I have not seen for a while.',
            'There is no deadline for becoming more attentive. There is only the next useful thing to notice.',
        ], $life, ['cover_image' => '/images/story-nature.jpg', 'published_at' => now()->addDays(7)->startOfHour()]);
        $this->story($writer, 'an-earlier-draft-about-working-from-home', 'An earlier draft about working from home', 'archived', [
            'This piece needs more reporting before it is ready to publish. I have kept it in the archive so that I can return to the useful parts.',
            'A better version will include conversations with people whose work cannot happen at a kitchen table.',
        ], $life);

        $collection = Series::firstOrCreate(['slug' => 'notes-from-the-neighbourhood'], [
            'author_id' => $writer->id, 'title' => 'Notes from the neighbourhood',
            'description' => 'Stories about the shared spaces, routines, and people that make a place feel like home.',
        ]);
        if ($collection->wasRecentlyCreated) {
            $collection->posts()->attach([$published->id => ['order' => 1], $second->id => ['order' => 2]]);
        }
        Revision::firstOrCreate(['post_id' => $draft->id, 'title' => 'A walk around the block'], [
            'edited_by' => $writer->id,
            'body_html' => '<p>A walk around the block is enough to remind me how much of a place I still do not know.</p>',
            'body_json' => $this->document(['A walk around the block is enough to remind me how much of a place I still do not know.']),
            'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
        ]);
        Revision::firstOrCreate(['post_id' => $draft->id, 'title' => 'The places we pass every day'], [
            'edited_by' => $writer->id,
            'body_html' => '<p>The places we pass every day have stories of their own. This is an earlier version of the opening.</p>',
            'body_json' => $this->document(['The places we pass every day have stories of their own. This is an earlier version of the opening.']),
            'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);

        $response = Comment::firstOrCreate(['post_id' => $published->id, 'user_id' => $users['reader']->id, 'body' => 'There is a shelf like this at our station. I found my favourite book of the year there.'], ['status' => 'visible']);
        Comment::firstOrCreate(['post_id' => $published->id, 'user_id' => $writer->id, 'body' => 'That is exactly the sort of unexpected discovery that makes these shelves worth keeping.'], ['parent_id' => $response->id, 'status' => 'visible']);
        Comment::firstOrCreate(['post_id' => $published->id, 'user_id' => $users['sam']->id, 'body' => 'Demo moderation example: this response needs an editor to check its tone before it appears in the discussion.'], ['status' => 'flagged']);
        $reportedComment = Comment::firstOrCreate(['post_id' => $firstStory->id, 'user_id' => $users['iris']->id, 'body' => 'Could the author add a source for the quotation in this section? I would like to read the original.'], ['status' => 'visible']);
        $hidden = Comment::firstOrCreate(['post_id' => $published->id, 'user_id' => $users['suspended']->id, 'body' => 'Demo moderation example: a response hidden after editorial review.'], ['status' => 'hidden']);
        $this->report($users['reader'], $published, 'Demo report: please check the attribution and fictional details in this sample story.');
        $this->report($users['premium'], $reportedComment, 'Demo report: review whether this response needs an attribution note.');
        $this->report($users['reader'], $hidden, 'Demo report: this sample response has already been reviewed.', 'resolved', $admin);

        foreach ([$published, $second, $firstStory] as $post) {
            Bookmark::firstOrCreate(['user_id' => $users['reader']->id, 'post_id' => $post->id]);
        }
        foreach ([$users['reader'], $users['premium']] as $reader) {
            Follow::firstOrCreate(['follower_id' => $reader->id, 'followable_type' => User::class, 'followable_id' => $writer->id]);
            Follow::firstOrCreate(['follower_id' => $reader->id, 'followable_type' => Category::class, 'followable_id' => $life->id]);
        }

        $this->notification($writer, 'demo-reader-follow', 'Maya Patel started following your writing.', '/@reader', 'follow');
        $this->notification($writer, 'demo-story-response', 'Maya Patel responded to “A neighbourhood library built on trust”.', $published->url.'#responses', 'comment');
        $this->notification($writer, 'demo-read-notification', 'Theo Brooks started following your writing.', '/@premium', 'follow', true);
        $this->notification($users['reader'], 'demo-writer-response', 'Alex Morgan replied to your response.', $published->url.'#responses', 'reply');

        // Demonstration ledger entries never contain a destination, transfer, or paid date.
        Payout::firstOrCreate(['reference' => 'DEMO-NOT-REAL-ALLOCATION-001'], [
            'author_id' => $writer->id, 'author_name' => $writer->name, 'approved_by' => $admin->id,
            'amount_cents' => 12500, 'currency' => 'usd', 'status' => 'pending',
            'idempotency_key' => '61eac1c7-3db1-471a-9fbd-422491904aae',
        ]);
        Activity::firstOrCreate(['log_name' => 'demo', 'description' => 'Loaded local demonstration content'], [
            'causer_type' => User::class, 'causer_id' => $admin->id,
            'properties' => ['demo' => true, 'note' => 'Fictional accounts and content for local feature review; no payments were made.'],
        ]);

        $this->command?->info('Local demo fixtures ready: reader, premium, editor, owner, unverified, and suspended accounts at @folkscript.test.');
        $this->command?->info('Includes moderation reports, a flagged response, writer story states, collections, revisions, notifications, and one explicitly labelled pending demo allocation. No funds transferred.');
    }

    private function story(User $author, string $slug, string $title, string $status, array $paragraphs, Category $category, array $extra = []): Post
    {
        $post = Post::firstOrCreate(['author_id' => $author->id, 'slug' => $slug], [
            'title' => $title, 'status' => $status, 'excerpt' => $paragraphs[0],
            'body_html' => collect($paragraphs)->map(fn ($text) => '<p>'.e($text).'</p>')->implode("\n"),
            'body_json' => $this->document($paragraphs), 'reading_time' => 1,
            ...$extra,
        ]);
        if ($post->wasRecentlyCreated) { $post->categories()->attach($category->id); }
        return $post;
    }

    private function document(array $paragraphs): array
    {
        return ['type' => 'doc', 'content' => array_map(fn ($text) => ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]], $paragraphs)];
    }

    private function report(User $reporter, Post|Comment $content, string $reason, string $status = 'open', ?User $resolver = null): void
    {
        Report::firstOrCreate(['user_id' => $reporter->id, 'reportable_type' => $content::class, 'reportable_id' => $content->id, 'reason' => $reason], [
            'status' => $status, 'resolved_by' => $resolver?->id,
        ]);
    }

    private function notification(User $user, string $key, string $message, string $url, string $type, bool $read = false): void
    {
        // A stable UUID keeps reruns from duplicating or marking a read message unread.
        $hash = md5('folkscript-local-demo:'.$key.':'.$user->email);
        $id = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-4'.substr($hash, 13, 3).'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);
        $user->notifications()->firstOrCreate(['id' => $id], [
            'type' => CommunityNotification::class,
            'data' => compact('message', 'url', 'type') + ['title' => $message],
            'read_at' => $read ? now()->subHours(2) : null,
        ]);
    }
}
