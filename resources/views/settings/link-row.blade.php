<fieldset class="settings-link-row" data-profile-link>
    <legend class="sr-only">{{ __('Profile link :number', ['number' => is_numeric($index) ? $index + 1 : '']) }}</legend>
    <div class="settings-link-fields">
        <label class="field" for="profile-link-{{ $index }}-label">{{ __('Link label') }}
            <input id="profile-link-{{ $index }}-label" class="form-input" name="links[{{ $index }}][label]" value="{{ is_string($link['label'] ?? null) ? $link['label'] : '' }}" maxlength="40" placeholder="{{ __('e.g. Instagram or My portfolio') }}" aria-invalid="{{ $errors->has('links.'.$index.'.label') ? 'true' : 'false' }}" aria-describedby="profile-link-{{ $index }}-label-error" data-link-label>
            <x-field-error :name="'links.'.$index.'.label'" :id="'profile-link-'.$index.'-label-error'" />
        </label>
        <label class="field" for="profile-link-{{ $index }}-url">{{ __('Web address') }}
            <input id="profile-link-{{ $index }}-url" class="form-input" type="url" name="links[{{ $index }}][url]" value="{{ is_string($link['url'] ?? null) ? $link['url'] : '' }}" maxlength="500" placeholder="https://" autocapitalize="none" spellcheck="false" aria-invalid="{{ $errors->has('links.'.$index.'.url') ? 'true' : 'false' }}" aria-describedby="profile-link-{{ $index }}-url-error" data-link-url>
            <x-field-error :name="'links.'.$index.'.url'" :id="'profile-link-'.$index.'-url-error'" />
        </label>
    </div>
    <button type="button" class="btn btn-outline settings-remove-link" data-remove-profile-link hidden aria-label="{{ __('Remove profile link :number', ['number' => is_numeric($index) ? $index + 1 : '']) }}"><x-icon name="x" :size="16" />{{ __('Remove') }}</button>
</fieldset>
