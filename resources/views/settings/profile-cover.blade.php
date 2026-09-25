<section class="settings-profile-block" aria-labelledby="profile-cover-heading" data-profile-cover>
    <h3 id="profile-cover-heading">{{ __('Profile cover') }}</h3>
    <p class="muted">{{ __('An optional banner above your name on your public profile. It is separate from the covers on your stories.') }}</p>
    <div class="settings-cover-frame">
        <!-- impeccable-disable-next-line broken-image: This preview is hidden until a saved cover or a selected local image supplies its source. -->
        <img class="settings-cover-preview" @if($user->cover_image) src="{{ $user->cover_image }}" @else hidden @endif alt="{{ __('Profile cover preview') }}" data-cover-preview>
        <div class="settings-cover-empty" data-cover-empty @if($user->cover_image) hidden @endif><x-icon name="image" :size="24" /><span>{{ __('Your cover preview will appear here') }}</span></div>
    </div>
    <label class="field">{{ __('Choose a cover image') }}
        <input aria-invalid="{{ $errors->has('cover_image') ? 'true' : 'false' }}" aria-describedby="settings9-help settings9-error cover-preview-feedback" class="form-input" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" data-cover-file>
        <span class="field-help" id="settings9-help">{{ __('Recommended: 1400 × 400 px (3.5:1). JPG, PNG, or WebP, up to 6 MB and 8000 px per side. The full image stays visible in the preview.') }}</span>
        <x-field-error name="cover_image" id="settings9-error" />
    </label>
    <p id="cover-preview-feedback" class="field-help" data-cover-feedback hidden role="status"></p>
    <button type="button" class="btn btn-outline" data-cover-reset hidden>{{ __('Cancel image selection') }}</button>
    @if($user->cover_image)<label class="check-label"><input type="checkbox" name="remove_cover_image" value="1" @checked(old('remove_cover_image')) data-cover-remove>{{ __('Remove current profile cover') }}</label>@endif
    <p class="field-help">{{ __('Changes to your links and cover are applied when you save your profile.') }}</p>
</section>
