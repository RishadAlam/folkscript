<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['posts.create', 'posts.publish', 'posts.edit-own', 'posts.edit-any', 'comments.create', 'comments.moderate', 'categories.manage', 'users.manage', 'payouts.process', 'settings.manage', 'analytics.view'];
        foreach ($permissions as $permission) { Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']); }
        $roles = [
            'reader' => ['comments.create'],
            'premium-reader' => ['comments.create'],
            'author' => ['comments.create', 'posts.create', 'posts.publish', 'posts.edit-own', 'analytics.view'],
            'editor' => ['comments.create', 'posts.create', 'posts.publish', 'posts.edit-own', 'posts.edit-any', 'comments.moderate', 'categories.manage', 'analytics.view'],
            'admin' => $permissions,
            'super-admin' => $permissions,
        ];
        foreach ($roles as $name => $abilities) { Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->syncPermissions($abilities); }

        // Demo content is intentionally opt-in outside a local environment.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO_CONTENT', false), FILTER_VALIDATE_BOOL)) { return; }

        $authors = [];
        foreach ([
            ['elena', 'Elena Rossi', 'elena@folkscript.test', 'Writer, observer, and collector of small moments. Exploring a more considered way to live.', 'Florence, Italy'],
            ['james', 'James Chen', 'james@folkscript.test', 'Thinking about the spaces we inhabit and the technology we bring into them. Designer by day.', 'San Francisco, USA'],
            ['amara', 'Amara Okafor', 'amara@folkscript.test', 'Stories at the intersection of culture, creativity, and everyday life. Always asking another question.', 'London, UK'],
            ['oliver', 'Oliver Park', 'oliver@folkscript.test', 'On the road, in the woods, or between the pages of a very good book.', 'Portland, USA'],
            ['alex', 'Alex Morgan', 'writer@folkscript.test', 'A curious mind with a notebook. Writing about what matters, one story at a time.', 'Brooklyn, New York'],
        ] as [$username, $name, $email, $bio, $location]) {
            $author = User::firstOrCreate(['email' => $email], ['username' => $username, 'name' => $name, 'password' => Hash::make('Folkscript2026!'), 'email_verified_at' => now(), 'bio' => $bio, 'location' => $location, 'avatar' => null, 'newsletter_enabled' => true]);
            if (! $author->hasVerifiedEmail()) { $author->forceFill(['email_verified_at' => now()])->save(); }
            $author->assignRole('author');
            $authors[$username] = $author;
        }
        $admin = User::firstOrCreate(['email' => 'admin@folkscript.test'], ['username' => 'admin', 'name' => 'Folkscript Editorial', 'password' => Hash::make('Folkscript2026!'), 'email_verified_at' => now(), 'bio' => 'The people behind Folkscript. A place for curious minds and independent voices.', 'avatar' => null]);
        if (! $admin->hasVerifiedEmail()) { $admin->forceFill(['email_verified_at' => now()])->save(); }
        $admin->assignRole('admin');

        $categories = [];
        foreach ([
            'Design' => 'Thoughtful objects, useful spaces, and ideas that make the everyday better.',
            'Culture' => 'The art, stories, and shared rituals that bring us together.',
            'Technology' => 'A human perspective on a world in constant motion.',
            'Life' => 'Big questions, small pleasures, and everything in between.',
            'Travel' => 'New perspectives from around the corner and across the world.',
        ] as $name => $description) {
            $categories[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], compact('name', 'description'));
        }

        $stories = [
            ['elena', 'The quiet art of paying attention', 'In a world that never stops asking for more, there is something quietly radical about noticing what is already here.', 'Life', 'attention', 'Mindful living, Creativity', 2, false],
            ['james', 'The cities we build, the lives we shape', 'A walk through the spaces between buildings reveals what our cities really value—and what we might do differently.', 'Design', 'city', 'Architecture, Urban life', 4, false],
            ['amara', 'Making room for a slower kind of creativity', 'The most meaningful work rarely arrives in a hurry. What happens when we give our ideas the space they need?', 'Culture', 'studio', 'Creativity, Work', 6, false],
            ['oliver', 'A little further from the beaten path', 'Beyond the itinerary and the postcard views, the best journeys leave room for the unexpected.', 'Travel', 'nature', 'Travel, Nature', 8, false],
            ['elena', 'The objects we choose to keep', 'On well-worn ceramics, handwritten notes, and the everyday things that quietly become a part of who we are.', 'Design', 'design', 'Design, Intentional living', 10, false],
            ['james', 'A more human future for technology', 'Progress is not only a question of what we can build. It is also a question of what deserves our attention.', 'Technology', 'book', 'Technology, Human connection', 14, false],
            ['amara', 'Why we still need independent voices', 'In a world of familiar opinions, a personal point of view can open a window we did not know was there.', 'Culture', 'book', 'Writing, Culture', 24, false],
            ['oliver', 'What the forest knows about beginning again', 'A season spent walking the same trail taught me that growth is often happening long before we can see it.', 'Life', 'nature', 'Nature, Personal growth', 32, true],
            ['alex', 'Finding a little wonder in the everyday', 'You do not need a plane ticket or a perfect morning to see things differently. Sometimes all it takes is a second look.', 'Life', 'attention', 'Life, Mindful living', 40, false],
            ['james', 'Good design leaves room for real life', 'The things we love to use are rarely the loudest in the room. They simply make a little more space for us.', 'Design', 'design', 'Design, Everyday life', 48, true],
        ];
        $seeded = [];
        foreach ($stories as $index => [$authorKey, $title, $excerpt, $category, $image, $tagNames, $hours, $premium]) {
            $author = $authors[$authorKey];
            $body = $this->storyBody($excerpt, $category, $image);
            $post = Post::firstOrCreate(['author_id' => $author->id, 'slug' => Str::slug($title)], ['title' => $title, 'excerpt' => $excerpt, 'body_html' => $body, 'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $excerpt]]]]], 'cover_image' => '/images/story-'.$image.'.jpg', 'status' => 'published', 'is_premium' => $premium, 'published_at' => now()->subHours($hours), 'reading_time' => 5 + $index % 4, 'meta_title' => Str::limit($title.' — Stories and ideas on Folkscript', 60, ''), 'meta_description' => Str::limit($excerpt.' Discover independent perspectives on Folkscript, written by the people and read by everyone.', 160, ''), 'views' => 1250 - $index * 87]);
            $post->categories()->syncWithoutDetaching([$categories[$category]->id]);
            foreach (explode(', ', $tagNames) as $tagName) {
                $tag = Tag::firstOrCreate(['slug' => Str::slug($tagName)], ['name' => $tagName]);
                $post->tags()->syncWithoutDetaching([$tag->id]);
            }
            $seeded[] = $post;
        }
        foreach ($authors as $author) {
            if ($author->id !== $authors['elena']->id) {
                Follow::firstOrCreate(['follower_id' => $author->id, 'followable_id' => $authors['elena']->id, 'followable_type' => User::class]);
            }
            foreach (array_slice($seeded, 0, 5) as $post) {
                Reaction::firstOrCreate(['user_id' => $author->id, 'post_id' => $post->id, 'type' => 'clap']);
            }
        }
        Comment::firstOrCreate(['post_id' => $seeded[0]->id, 'user_id' => $authors['amara']->id, 'body' => '“Attention is a kind of generosity.” This is exactly what I needed to read today. Thank you for putting it into words.'], ['status' => 'visible']);
        Comment::firstOrCreate(['post_id' => $seeded[0]->id, 'user_id' => $authors['oliver']->id, 'body' => 'I have been taking the same walk each morning for a month. It is surprising how much changes when you stop trying to get somewhere.'], ['status' => 'visible']);
        foreach ([1, 2, 3] as $index) { Bookmark::firstOrCreate(['user_id' => $authors['alex']->id, 'post_id' => $seeded[$index]->id]); }
        $series = Series::firstOrCreate(['slug' => 'a-more-considered-life'], ['title' => 'A more considered life', 'author_id' => $authors['elena']->id, 'description' => 'Notes on attention, intention, and the things that last.']);
        $series->posts()->syncWithoutDetaching([$seeded[0]->id => ['order' => 1], $seeded[4]->id => ['order' => 2]]);
        Post::firstOrCreate(['author_id' => $authors['alex']->id, 'slug' => 'notes-from-a-sunday-morning'], ['title' => 'Notes from a Sunday morning', 'excerpt' => 'A few things I have been thinking about lately.', 'body_html' => '<p>There is a particular kind of quiet on Sunday mornings. Before the week begins again, I like to sit by the window with a notebook and see what finds its way onto the page.</p>', 'status' => 'draft', 'reading_time' => 1]);
    }

    private function storyBody(string $excerpt, string $category, string $image): string
    {
        $opening = match ($category) {
            'Design' => 'Good design begins with attention to how people actually live. Before a drawing, a prototype, or a finished object, there is a quieter task: looking carefully at what is already there.',
            'Technology' => 'Human-centered technology gives people more agency over their time and choices. Its value is measured in the lives it improves, not simply in how often we return to a screen.',
            'Culture' => 'Creativity grows through a conversation between our inner lives and the world around us. Giving that conversation time is one of the most practical things we can do for our work.',
            'Travel' => 'Meaningful travel begins when we trade a little certainty for curiosity. The places we remember best are often the ones we did not know enough to put on a list.',
            default => 'Paying attention is the practice of being fully present with what is in front of you. It is a small, ordinary act that can change the texture of an entire day.',
        };
        return '<p>'.$opening.'</p>
<p>'.e($excerpt).'</p>
<p>It started with a walk I had taken a hundred times before. The route was familiar enough that I usually moved through it without really looking: the corner shop, the old tree, the stretch of pavement where the light fell differently in the afternoon. On this particular day, I left my phone at home.</p>
<p>Nothing remarkable happened. But I noticed the tiny things I normally walked past. Someone had repainted a door. A plant was pushing through a crack in the wall. A conversation drifted from an open window, followed by laughter. The street had not changed. The quality of my attention had.</p>
<h2>What changes when we slow down?</h2>
<p>We tend to think of attention as a resource to spend carefully, and there is truth in that. But it is also a relationship. What we attend to becomes part of the world we inhabit. A day filled with interruptions feels different from a day that contains even one unhurried hour.</p>
<p>This is not an argument for withdrawing from a busy life. Most of us have obligations that do not become smaller because we have decided to move more deliberately. The useful question is simpler: where is there already a little room? Perhaps in the first five minutes of the morning. Perhaps in the walk between one meeting and the next.</p>
<blockquote><p>Attention is a kind of generosity. What we choose to notice is what we allow to matter.</p></blockquote>
<p>I began with a modest experiment. For one week, I wrote down three things I had noticed each evening. Not things I had accomplished, or things I intended to do tomorrow. Just details from the day that would otherwise have disappeared. The exercise took less than two minutes. It made the days feel less interchangeable.</p>
<h2>Making space for the ordinary</h2>
<p>There is a temptation to turn every useful idea into another system. A new notebook, a complicated ritual, a perfect sequence of steps. I have found that the practices that last are usually much smaller. They fit into the life I already have, rather than requiring a different life first.</p>
<ul><li>Take a familiar route and look for one detail you have never noticed.</li><li>Finish one conversation before reaching for your phone.</li><li>Leave a small part of your day without a plan.</li><li>Write down something ordinary that you would like to remember.</li></ul>
<p>None of this is new. That is part of its value. Across cultures and generations, people have found ways to make a little space around their experience. A cup of tea, a walk after dinner, a few lines in a journal. These are not solutions to every problem. They are ways of remaining in conversation with our own lives.</p>
<figure><img src="/images/story-'.$image.'.jpg" alt="A quiet scene inviting a closer look at everyday life" width="1200" height="800"></figure>
<h2>A practice, not a destination</h2>
<p>Some days I still hurry past everything. Some evenings I cannot remember much beyond the tasks I completed. The point is not to become a person who never gets distracted. The point is to notice when you have drifted away, and to return without making a performance of it.</p>
<p>The world keeps offering more than we can take in. We cannot attend to all of it, and we do not need to. But we can choose, from time to time, to meet a small part of it with a little more care. That choice is available on an ordinary Tuesday, on a familiar street, in the middle of an imperfect life.</p>
<p>For more independent perspectives, explore our <a href="/topic/'.strtolower($category).'">'.e(strtolower($category)).' stories</a>. The conversation is better with you in it.</p>';
    }
}
