<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;
    public int $uniqueFor = 86400;

    public function __construct(public int $userId, public string $period)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string { return $this->userId.':'.$this->period; }

    public function handle(): void
    {
        if (! config('folkscript.digests_enabled', false)) { return; }
        $user = User::find($this->userId);
        if (! $user || ! $user->newsletter_enabled || ! $user->hasVerifiedEmail() || $user->suspended_at) { return; }
        $key = 'digest:'.$this->period.':'.$user->id;
        if (Cache::has($key)) { return; }
        $stories = Post::published()->with('author')->where('published_at', '>=', now()->subWeek())->latest('published_at')->limit(5)->get();
        if ($stories->isEmpty()) { return; }
        $body = "Hello {$user->name},\n\nHere are a few ideas worth making time for this week.\n\n";
        foreach ($stories as $story) {
            $body .= $story->title.' — '.$story->author->name."\n".$story->excerpt."\n".url($story->url)."\n\n";
        }
        $body .= "Written by the people, read by everyone.\n\nManage your email preferences: ".url('/settings');
        Mail::raw($body, fn ($message) => $message->to($user->email)->subject('A little perspective — your Folkscript reading list'));
        Cache::put($key, true, now()->addDays(8));
    }
}
