<section class="settings-pane" aria-labelledby="settings-profile-heading">
    <div class="settings-pane-heading">
        <h2 id="settings-profile-heading">{{ __('Your public profile') }}</h2>
        <p class="muted">{{ __('Choose how readers see you. Only your name and username are required.') }}</p>
    </div>
    <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="stack" data-settings-form>
        @csrf @method('PATCH')
        <div class="profile-photo-editor">
            <x-avatar :user="$user" class="account-avatar" />
            <label class="field">{{ __('Profile photo') }}
                <input aria-invalid="{{ $errors->has('avatar') ? 'true' : 'false' }}" aria-describedby="settings1-help settings1-error" class="form-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                <span class="field-help" id="settings1-help">{{ __('A square photo works best. JPG, PNG, or WebP, up to 4 MB.') }}</span>
                <x-field-error name="avatar" id="settings1-error" />
            </label>
        </div>
        @if($user->avatar)<label class="check-label"><input type="checkbox" name="remove_avatar" value="1" @checked(old('remove_avatar'))>{{ __('Remove current photo and use my initials') }}</label>@endif
        <div class="form-grid">
            <label class="field">{{ __('Display name') }}
                <input aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="settings2-error" class="form-input" autocomplete="name" name="name" value="{{ old('name', $user->name) }}" maxlength="80" required>
                <x-field-error name="name" id="settings2-error" />
            </label>
            <label class="field">{{ __('Username') }}
                <input aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" aria-describedby="settings3-help settings3-error" class="form-input" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="30" name="username" value="{{ old('username', $user->username) }}" pattern="[a-z0-9_]{3,30}" required>
                <span class="field-help" id="settings3-help">{{ __('3–30 lowercase letters, numbers, or underscores. Old profile and story links redirect if you change it.') }}</span>
                <x-field-error name="username" id="settings3-error" />
            </label>
        </div>
        <label class="field">{{ __('About you') }}
            <textarea aria-invalid="{{ $errors->has('bio') ? 'true' : 'false' }}" aria-describedby="settings4-help settings4-error" class="form-input" name="bio" rows="3" maxlength="500" placeholder="{{ __('A few words about you and what interests you.') }}">{{ old('bio', $user->bio) }}</textarea>
            <span class="field-help" id="settings4-help">{{ __('Up to 500 characters. Visible on your public profile.') }}</span>
            <x-field-error name="bio" id="settings4-error" />
        </label>
        <div class="form-grid">
            <label class="field">{{ __('Location') }}
                <input aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="settings5-error" class="form-input" name="location" value="{{ old('location', $user->location) }}" maxlength="100" placeholder="{{ __('City, country') }}">
                <x-field-error name="location" id="settings5-error" />
            </label>
            <label class="field">{{ __('Website') }}
                <input aria-invalid="{{ $errors->has('website') ? 'true' : 'false' }}" aria-describedby="settings6-error" class="form-input" type="url" name="website" maxlength="255" value="{{ old('website', $user->social_links['website'] ?? '') }}" placeholder="https://">
                <x-field-error name="website" id="settings6-error" />
            </label>
        </div>
        <details class="settings-disclosure settings-profile-extras" @if($errors->hasAny(['github', 'linkedin', 'cover_image'])) open @endif>
            <summary>{{ __('Social links & profile cover') }}</summary>
            <div class="stack">
                <div class="form-grid">
                    <label class="field">{{ __('GitHub profile') }}
                        <input aria-invalid="{{ $errors->has('github') ? 'true' : 'false' }}" aria-describedby="settings7-error" class="form-input" type="url" name="github" maxlength="255" value="{{ old('github', $user->social_links['github'] ?? '') }}" placeholder="https://github.com/yourname">
                        <x-field-error name="github" id="settings7-error" />
                    </label>
                    <label class="field">{{ __('LinkedIn profile') }}
                        <input aria-invalid="{{ $errors->has('linkedin') ? 'true' : 'false' }}" aria-describedby="settings8-error" class="form-input" type="url" name="linkedin" maxlength="255" value="{{ old('linkedin', $user->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/in/yourname">
                        <x-field-error name="linkedin" id="settings8-error" />
                    </label>
                </div>
                @if($user->cover_image)<img class="settings-cover-preview" src="{{ $user->cover_image }}" alt="{{ __('Your current profile cover') }}" loading="lazy">@endif
                <label class="field">{{ __('Profile cover') }}
                    <input aria-invalid="{{ $errors->has('cover_image') ? 'true' : 'false' }}" aria-describedby="settings9-help settings9-error" class="form-input" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                    <span class="field-help" id="settings9-help">{{ __('Recommended: 1400 × 400 px (3.5:1). JPG, PNG, or WebP, up to 6 MB. This is the banner on your profile, separate from story covers.') }}</span>
                    <x-field-error name="cover_image" id="settings9-error" />
                </label>
                @if($user->cover_image)<label class="check-label"><input type="checkbox" name="remove_cover_image" value="1" @checked(old('remove_cover_image'))>{{ __('Remove current profile cover') }}</label>@endif
            </div>
        </details>
        <div class="settings-actions settings-save-bar"><button class="btn btn-primary" type="submit">{{ __('Save profile') }}</button><span class="field-help" data-settings-unsaved hidden role="status">{{ __('Unsaved changes') }}</span></div>
    </form>
</section>
