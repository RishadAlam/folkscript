<section class="settings-pane" aria-labelledby="settings-publishing-heading">
    <div class="settings-pane-heading">
        <h2 id="settings-publishing-heading">{{ __('Featured story') }}</h2>
        <p class="muted">{{ __('Pin one of your published stories to the top of your public profile.') }}</p>
    </div>
    @if($pinnablePosts->isNotEmpty() || $user->pinned_post_id)
        <form method="post" action="{{ route('settings.pinned-story') }}" class="stack" data-settings-form>
            @csrf
            <label class="field">{{ __('Story to feature') }}
                <select aria-invalid="{{ $errors->has('post_id') ? 'true' : 'false' }}" aria-describedby="settings10-help settings10-error" class="form-input" name="post_id">
                    <option value="">{{ __('No featured story') }}</option>
                    @foreach($pinnablePosts as $pinnable)<option value="{{ $pinnable->id }}" @selected((int) old('post_id', $user->pinned_post_id) === $pinnable->id)>{{ $pinnable->title }}</option>@endforeach
                </select>
                <span class="field-help" id="settings10-help">{{ __('Only published stories are listed. Choose “No featured story” to remove the pin.') }}</span>
                <x-field-error name="post_id" id="settings10-error" />
            </label>
            <div class="settings-actions"><button class="btn btn-primary" type="submit">{{ __('Save featured story') }}</button><span class="field-help" data-settings-unsaved hidden role="status">{{ __('Unsaved changes') }}</span></div>
        </form>
    @else
        <div class="settings-empty">
            <h3>{{ __('Your first published story goes here') }}</h3>
            <p class="muted">{{ __('Once you publish a story, you can choose it here to introduce readers to your work.') }}</p>
            @if($user->canWrite())<a href="{{ url('/write') }}" class="btn btn-primary">{{ __('Write a story') }}</a>@endif
        </div>
    @endif
</section>
