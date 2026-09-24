<section id="topics" class="admin-section" aria-labelledby="topics-heading">
<h2 id="topics-heading" class="sr-only">{{ __('Categories and tags') }}</h2>
        <div class="taxonomy-grid">
            @foreach(['categories' => $categories, 'tags' => $tags] as $type => $entries)
                @php
                    $taxonomyErrors = $errors->getBag($type);
                @endphp
                <div class="admin-taxonomy-panel"><h3>{{ $type === 'categories' ? __('Categories') : __('Tags') }} <span class="muted">{{ $entries->count() }}</span></h3><p class="admin-taxonomy-help">{{ $type === 'categories' ? __('Broad subjects for the main story navigation.') : __('Specific ideas that connect related stories.') }}</p>                    <details class="admin-taxonomy-create" @if($taxonomyErrors->any()) open @endif><summary><x-icon name="plus" size="17" />{{ $type === 'categories' ? __('Add category') : __('Add tag') }}</summary>
                    <form method="post" action="{{ route('admin.taxonomy.store', array_merge([$type], $returnQuery)) }}" class="admin-taxonomy-form" data-error-bag="{{ $type }}">@csrf<input type="hidden" name="taxonomy_type" value="{{ $type }}">
                        <label class="field">{{ $type === 'categories' ? __('New category') : __('New tag') }}<input class="form-input" name="name" value="{{ old('taxonomy_type') === $type ? old('name') : '' }}" maxlength="60" aria-invalid="{{ $taxonomyErrors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $type }}-name-error" required><x-field-error name="name" :bag="$type" :id="$type.'-name-error'" /></label>
                        <label class="field">{{ __('Description (optional)') }}<textarea class="form-input" name="description" rows="2" maxlength="250" aria-invalid="{{ $taxonomyErrors->has('description') ? 'true' : 'false' }}" aria-describedby="{{ $type }}-description-error">{{ old('taxonomy_type') === $type ? old('description') : '' }}</textarea><x-field-error name="description" :bag="$type" :id="$type.'-description-error'" /></label>
                        <button class="btn btn-outline" type="submit">{{ $type === 'categories' ? __('Add category') : __('Add tag') }}</button>
                    </form></details>
<div class="taxonomy-labels">@forelse($entries as $entry)<a href="{{ $entry->url }}"><span><strong>{{ $entry->name }}</strong>@if($entry->description)<small>{{ $entry->description }}</small>@endif</span><x-icon name="arrow-up-right" size="14" /></a>@empty<p class="muted">{{ $type === 'categories' ? __('No categories yet.') : __('No tags yet.') }}</p>@endforelse</div>

                </div>
            @endforeach
        </div>
    </section>
