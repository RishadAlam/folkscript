<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Redirect;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeriesController extends Controller
{
    public function index(Request $request, ?Series $series = null)
    {
        Gate::authorize('create', Series::class);
        $collections = Series::where('author_id', $request->user()->id)->withCount('posts')->latest()->get();
        $selected = $series ?? $collections->first();
        $available = collect();
        if ($selected) {
            Gate::authorize('update', $selected);
            $selected->load(['author', 'posts']);
            $available = Post::where('author_id', $selected->author_id)->whereNotIn('id', $selected->posts->pluck('id'))->where('status', '!=', 'archived')->latest()->get(['id', 'title', 'status']);
        }
        return view('series.index', compact('collections', 'selected', 'available'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Series::class);
        $data = $request->validate(['title' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:1000']]);
        $data['author_id'] = $request->user()->id;
        $data['slug'] = Str::limit(Str::slug($data['title']), 100, '') ?: 'collection';
        if (Series::where('slug', $data['slug'])->exists()) { $data['slug'] .= '-'.Str::lower(Str::random(6)); }
        $series = Series::create($data);
        Redirect::where('from_path', '/@'.$request->user()->username.'/series/'.$series->slug)->delete();
        activity()->causedBy($request->user())->performedOn($series)->log('Created story collection');
        return redirect()->route('series.edit', $series)->with('status', 'Your collection is ready. Add the stories that belong together.');
    }

    public function update(Request $request, Series $series)
    {
        Gate::authorize('update', $series);
        $series->update($request->validate(['title' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:1000']]));
        return back()->with('status', 'Collection details saved.');
    }

    public function destroy(Request $request, Series $series)
    {
        Gate::authorize('delete', $series);
        $series->load('author');
        $path = '/@'.$series->author->username.'/series/'.$series->slug;
        Redirect::updateOrCreate(['from_path' => $path], ['to_path' => '/@'.$series->author->username, 'status_code' => 301]);
        activity()->causedBy($request->user())->performedOn($series)->log('Deleted story collection');
        $series->delete();
        return redirect()->route('series.index')->with('status', 'Collection deleted. Its stories are still in your writing studio.');
    }

    public function attach(Request $request, Series $series)
    {
        Gate::authorize('update', $series);
        $data = $request->validate(['post_id' => ['required', 'integer', Rule::exists('posts', 'id')->where('author_id', $series->author_id)]]);
        DB::transaction(function () use ($series, $data) {
            Series::whereKey($series->id)->lockForUpdate()->firstOrFail();
            $position = (int) $series->posts()->max('series_post.order') + 1;
            $series->posts()->syncWithoutDetaching([$data['post_id'] => ['order' => $position]]);
        });
        return back()->with('status', 'Story added. Drafts stay private until you publish them.');
    }

    public function detach(Request $request, Series $series, Post $post)
    {
        Gate::authorize('update', $series);
        abort_unless($post->author_id === $series->author_id && $series->posts()->where('posts.id', $post->id)->exists(), 404);
        $series->posts()->detach($post->id);
        return back()->with('status', 'Story removed from this collection.');
    }

    public function reorder(Request $request, Series $series)
    {
        Gate::authorize('update', $series);
        $data = $request->validate(['positions' => ['required', 'array', 'max:1000'], 'positions.*' => ['required', 'integer', 'min:1', 'max:1000']]);
        DB::transaction(function () use ($series, $data) {
            Series::whereKey($series->id)->lockForUpdate()->firstOrFail();
            $ids = $series->posts()->where('author_id', $series->author_id)->pluck('posts.id')->map(fn ($id) => (string) $id)->sort()->values()->all();
            $submitted = collect(array_keys($data['positions']))->map(fn ($id) => (string) $id)->sort()->values()->all();
            if ($ids !== $submitted) { throw ValidationException::withMessages(['positions' => 'Your collection changed. Refresh this page before saving the order.']); }
            $positions = collect($data['positions'])->sort();
            foreach ($positions->keys()->values() as $index => $id) { $series->posts()->updateExistingPivot($id, ['order' => $index + 1]); }
        });
        return back()->with('status', 'Reading order saved.');
    }

    public function show(string $username, string $slug)
    {
        $series = Series::with('author')->where('slug', $slug)->whereHas('author', fn ($query) => $query->where('username', $username)->whereNull('suspended_at'))->firstOrFail();
        $posts = $series->posts()->where('posts.author_id', $series->author_id)->published()->withCard()->paginate(12);
        return view('series.show', compact('series', 'posts'));
    }
}
