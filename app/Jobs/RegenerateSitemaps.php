<?php

namespace App\Jobs;

use App\Domain\Seo\SitemapBuilder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegenerateSitemaps implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public int $tries = 3;

    public function handle(SitemapBuilder $builder): void
    {
        $builder->index();
        foreach (['posts', 'authors', 'topics'] as $type) {
            $builder->section($type);
        }
    }
}
