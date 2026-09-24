<section id="people" class="admin-section" aria-labelledby="people-heading">
    <h2 id="people-heading" class="sr-only">{{ __('People and access') }}</h2>
    <form method="get" action="{{ route('admin') }}" class="admin-list-toolbar"><input type="hidden" name="view" value="people"><div class="admin-search-field"><x-icon name="search" size="18" /><label class="sr-only" for="people-search">{{ __('Find a person by name or email') }}</label><input class="form-input" id="people-search" name="q" type="search" value="{{ $userQuery }}" placeholder="{{ __('Search name or email…') }}" maxlength="200"></div><label class="sr-only" for="people-status">{{ __('Account status') }}</label><select class="form-input admin-status-select" id="people-status" name="user_status">@foreach(['all' => __('All accounts'), 'active' => __('Active'), 'unverified' => __('Unverified'), 'suspended' => __('Suspended')] as $value => $label)<option value="{{ $value }}" @selected($userStatus === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline">{{ __('Search') }}</button>@if($userQuery !== '' || $userStatus !== 'all')<a class="admin-row-action" href="{{ route('admin', ['view' => 'people']) }}">{{ __('Reset') }}</a>@endif</form>
    <p class="admin-result-count">{{ trans_choice('{0} No accounts|{1} 1 account|[2,*] :count accounts', $users->total()) }}</p>
            <div class="admin-table-wrap"><table class="admin-table admin-responsive-table admin-people-table"><caption class="sr-only">{{ __('Community accounts and access controls') }}</caption><thead><tr><th scope="col">{{ __('Person') }}</th><th scope="col">{{ __('Account') }}</th><th scope="col">{{ __('Access') }}</th></tr></thead><tbody>
                @forelse($users as $person)
                    @php
                        $canEditAccess = $person->id !== auth()->id() && ! $person->hasRole('super-admin') && (auth()->user()->hasRole('super-admin') || ! $person->hasRole('admin'));
                        $accessBag = 'access-'.$person->id;
                        $accessErrors = $errors->getBag($accessBag);
                        $personHasOldInput = (string) old('access_person') === (string) $person->id;
                        $primaryRole = collect(['super-admin', 'admin', 'editor', 'author', 'premium-reader', 'reader'])->first(fn ($role) => $person->hasRole($role));
                    @endphp
                    <tr><td class="admin-person-cell"><strong>{{ $person->name }} @if($person->id === auth()->id())<span class="admin-you">{{ __('You') }}</span>@endif</strong><span class="muted table-secondary">{{ $person->email }}</span><a class="admin-profile-link" href="{{ '/@'.$person->username }}">{{ __('View profile') }}</a></td><td class="admin-person-status"><span class="admin-role-name">{{ __('ui.roles.'.$primaryRole) }}</span><span @class(['admin-status', 'is-danger' => (bool) $person->suspended_at, 'is-success' => !$person->suspended_at && $person->hasVerifiedEmail()])>{{ $person->suspended_at ? __('Suspended') : ($person->hasVerifiedEmail() ? __('Verified') : __('Unverified')) }}</span></td><td class="admin-person-access">
                        @if($canEditAccess)
                            <details class="admin-access-editor" @if($accessErrors->any()) open @endif><summary aria-label="{{ __('Manage access for :name', ['name' => $person->name]) }}">{{ __('Manage access') }}<x-icon name="chevron-right" size="16" /></summary>
                            <form method="post" action="{{ route('admin.users.update', array_merge([$person], $returnQuery)) }}" class="user-access-form" data-error-bag="{{ $accessBag }}">@csrf @method('PATCH')<input type="hidden" name="access_person" value="{{ $person->id }}">
                                <label class="field"><span>{{ __('Role') }}</span><select class="form-input" name="role" aria-label="{{ __('Role for :name', ['name' => $person->name]) }}" aria-invalid="{{ $accessErrors->has('role') ? 'true' : 'false' }}" aria-describedby="role-error-{{ $person->id }}">@foreach(['reader' => 'Reader', 'premium-reader' => 'Premium reader', 'author' => 'Author', 'editor' => 'Editor'] + (auth()->user()->hasRole('super-admin') ? ['admin' => 'Admin'] : []) as $value => $label)<option value="{{ $value }}" @selected(($personHasOldInput ? old('role') : $primaryRole) === $value)>{{ __($label) }}</option>@endforeach</select><x-field-error name="role" :bag="$accessBag" :id="'role-error-'.$person->id" /></label>
                                <input type="hidden" name="suspended" value="0"><label class="check-label"><input type="checkbox" name="suspended" value="1" @checked($personHasOldInput ? old('suspended') : $person->suspended_at)> {{ __('Suspend account') }}</label>
                                <button class="btn btn-outline" type="submit" aria-label="{{ __('Save access for :name', ['name' => $person->name]) }}">{{ __('Save access') }}</button>
                            </form>
                            @if(auth()->user()->hasRole('super-admin') && ! $person->hasAnyRole(['admin','super-admin']) && ! $person->suspended_at)<form method="POST" action="{{ route('support.start', $person) }}" class="support-session-form">@csrf<button class="text-button">{{ __('Open read-only support session') }}</button></form>@endif
                            </details>
                        @else
                            <span class="admin-protected"><x-icon name="lock" size="14" />{{ __('Protected account') }}</span><p class="muted table-secondary">{{ $person->id === auth()->id() ? __('Your own access is protected here.') : __('This account’s access is protected.') }}</p>
                        @endif
                    </td></tr>
                @empty<tr><td colspan="3"><div class="admin-inline-empty"><x-icon name="search" size="20" /><p>{{ __('No accounts match. Try another name or email.') }}</p></div></td></tr>@endforelse
            </tbody></table></div>
            <div class="admin-pagination">{{ $users->withQueryString()->fragment('people')->links() }}</div>
        </section>
