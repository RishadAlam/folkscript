<section class="settings-pane" aria-labelledby="settings-profile-heading">
    <div class="settings-pane-heading">
        <h2 id="settings-profile-heading">{{ __('Your public profile') }}</h2>
        <p class="muted">{{ __('Choose how readers see you. Only your name and username are required.') }}</p>
    </div>
    <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="stack" data-settings-form>
        @csrf @method('PATCH')
        <div class="profile-photo-editor" data-profile-photo>
            <div class="settings-photo-preview" data-avatar-frame>
                <x-avatar :user="$user" class="account-avatar" />
                <!-- impeccable-disable-next-line broken-image: A selected local image supplies this hidden preview's source. -->
                <img class="settings-photo-selection" alt="{{ __('Selected profile photo preview') }}" data-avatar-preview hidden>
            </div>
            <label class="field">{{ __('Profile photo') }}
                <input aria-invalid="{{ $errors->has('avatar') ? 'true' : 'false' }}" aria-describedby="settings1-help settings1-error avatar-preview-feedback" class="form-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-file>
                <span class="field-help" id="settings1-help">{{ __('A square photo works best. It appears in a circle. JPG, PNG, or WebP, up to 4 MB and 6000 px per side.') }}</span>
                <x-field-error name="avatar" id="settings1-error" />
                <span id="avatar-preview-feedback" class="field-help" data-avatar-feedback hidden role="status"></span>
            </label>
        </div>
        <button type="button" class="btn btn-outline settings-photo-reset" data-avatar-reset hidden>{{ __('Cancel photo selection') }}</button>
        @if($user->avatar)<label class="check-label"><input type="checkbox" name="remove_avatar" value="1" @checked(old('remove_avatar')) data-avatar-remove>{{ __('Remove current photo and use my initials') }}</label>@endif
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
        <label class="field">{{ __('Location') }}
            <input aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" aria-describedby="settings5-error" class="form-input" name="location" value="{{ old('location', $user->location) }}" maxlength="100" placeholder="{{ __('City, country') }}">
            <x-field-error name="location" id="settings5-error" />
        </label>
        @include('settings.profile-links')
        @include('settings.profile-cover')
        <div class="settings-actions settings-save-bar"><button class="btn btn-primary" type="submit">{{ __('Save profile') }}</button><span class="field-help" data-settings-unsaved hidden role="status">{{ __('Unsaved changes') }}</span></div>
    </form>
</section>
