@php
    $profileLinks = old('links_present') ? old('links', []) : old('links', $user->publicProfileLinks());
    $profileLinks = is_array($profileLinks) ? array_slice($profileLinks, 0, 10, true) : [];
    if (! $profileLinks) { $profileLinks = [['label' => '', 'url' => '']]; }
@endphp
<section class="settings-profile-block" aria-labelledby="profile-links-heading" data-profile-links>
    <h3 id="profile-links-heading">{{ __('Links on your profile') }}</h3>
    <p class="muted" id="profile-links-help">{{ __('Help readers find you elsewhere. Add any social profile, website, newsletter, or portfolio. You choose the labels readers see.') }}</p>
    <input type="hidden" name="links_present" value="1">
    <x-field-error name="links" id="profile-links-error" />
    <div class="settings-link-list" data-profile-link-list>
        @foreach($profileLinks as $index => $link)
            @include('settings.link-row', ['index' => $index, 'link' => is_array($link) ? $link : []])
        @endforeach
    </div>
    <template data-profile-link-template>@include('settings.link-row', ['index' => '__INDEX__', 'link' => []])</template>
    <div class="settings-link-actions">
        <button type="button" class="btn btn-outline" data-add-profile-link hidden><x-icon name="plus" :size="16" />{{ __('Add another link') }}</button>
        <p class="field-help" data-profile-link-count>{{ __('Up to 10 links. Leave both fields empty to skip a link.') }}</p>
    </div>
    <noscript><p class="field-help">{{ __('To remove a link, clear both fields. Enable JavaScript to add more links in one edit.') }}</p></noscript>
</section>
