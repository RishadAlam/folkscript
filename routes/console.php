<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Post;
use App\Models\User;
use App\Jobs\SendWeeklyDigest;

Artisan::command('posts:publish-scheduled', function () {
    $count = 0;
    Post::where('status', 'scheduled')->where('published_at', '<=', now())->chunkById(100, function ($posts) use (&$count) {
        foreach ($posts as $post) {
            if (! $post->author->canWrite() || ! $post->author->can('posts.publish')) { continue; }
            $post->update(['status' => 'published']);
            event('post.saved', [$post]);
            $count++;
        }
    });
    $this->info("Published {$count} scheduled stories.");
})->purpose('Publish stories whose scheduled time has arrived');

Schedule::command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();

Artisan::command('folkscript:send-digest', function () {
    if (! config('folkscript.digests_enabled', false)) { $this->info('Weekly digests are disabled. Enable them in the Folkscript configuration when mail is ready.'); return; }
    $queued = 0;
    User::where('newsletter_enabled', true)->whereNotNull('email_verified_at')->whereNull('suspended_at')->chunkById(100, function ($users) use (&$queued) {
        foreach ($users as $user) {
            SendWeeklyDigest::dispatch($user->id, now()->format('o-W'));
            $queued++;
        }
    });
    $this->info("Queued {$queued} weekly digests for opted-in readers.");
})->purpose('Send the weekly reading list to readers who opted in');

Schedule::command('folkscript:send-digest')->weeklyOn(1, '08:00')->withoutOverlapping();

if (config('queue.default') === 'redis') {
    Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();
}
