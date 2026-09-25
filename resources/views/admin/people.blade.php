@php
    $roleDescriptions = [
        'reader' => __('Read, save stories, follow writers, and join conversations. No writing or administration access.'),
        'author' => __('Everything a reader can do, plus write, publish, schedule, and manage their own stories and series.'),
        'editor' => __('Everything a writer can do, plus edit any story, review reports, moderate comments, and manage categories and tags.'),
        'admin' => __('Everything an editor can do, plus manage reader, writer, and editor accounts, view activity, and configure the publication.'),
        'super-admin' => __('Everything an administrator can do, plus appoint administrators and open temporary read-only support sessions.'),
    ];
    $canAssignAdministrators = auth()->user()->hasRole('super-admin');
    $assignableRoles = ['reader', 'author', 'editor', ...($canAssignAdministrators ? ['admin'] : [])];
@endphp
<section id="people" class="admin-section" aria-labelledby="people-heading">
    <h2 id="people-heading" class="sr-only">{{ __('Users and access') }}</h2>
    <div class="admin-access-intro">
        <p>{{ __('Use Manage access beside an account to change its role or suspend it. Changes take effect when saved.') }}</p>
        <p>{{ $canAssignAdministrators ? __('As platform owner, you can also appoint and manage administrators.') : __('You can manage readers, writers, and editors. Only a platform owner can manage administrators.') }}</p>
        <details class="admin-role-guide">
            <summary>{{ __('Compare roles and permissions') }}<x-icon name="chevron-right" size="16" /></summary>
            <dl>@foreach($roleDescriptions as $role => $description)<div><dt>{{ __('ui.roles.'.$role) }}</dt><dd>{{ $description }}</dd></div>@endforeach</dl>
            <p>{{ __('Writing and administration require a verified email and an active account. Each role has a fixed set of capabilities; individual permissions are not customized here. Ownership is assigned through the server console.') }}</p>
        </details>
    </div>
    <form method="get" action="{{ route('admin') }}" class="admin-people-filters">
        <input type="hidden" name="view" value="people">
        <label class="field" for="people-search"><span>{{ __('Find a user') }}</span><input class="form-input" id="people-search" name="q" type="search" value="{{ $userQuery }}" placeholder="{{ __('Name, username, or email') }}" maxlength="200"></label>
        <label class="field" for="people-role"><span>{{ __('Role') }}</span><select class="form-input" id="people-role" name="user_role"><option value="all">{{ __('All roles') }}</option>@foreach($roleDescriptions as $value => $description)<option value="{{ $value }}" @selected($userRole === $value)>{{ __('ui.roles.'.$value) }}</option>@endforeach</select></label>
        <label class="field" for="people-status"><span>{{ __('Account status') }}</span><select class="form-input" id="people-status" name="user_status">@foreach(['all' => __('All accounts'), 'active' => __('Active & verified'), 'unverified' => __('Email unverified'), 'suspended' => __('Suspended')] as $value => $label)<option value="{{ $value }}" @selected($userStatus === $value)>{{ $label }}</option>@endforeach</select></label>
        <div class="admin-filter-actions"><button class="btn btn-outline">{{ __('Find users') }}</button>@if($userQuery !== '' || $userStatus !== 'all' || $userRole !== 'all')<a class="admin-row-action" href="{{ route('admin', ['view' => 'people']) }}">{{ __('Reset filters') }}</a>@endif</div>
    </form>
    <p class="admin-result-count">{{ trans_choice('{0} No accounts|{1} 1 account|[2,*] :count accounts', $users->total()) }}</p>
    <div class="admin-table-wrap">
        <table class="admin-table admin-responsive-table admin-people-table">
            <caption class="sr-only">{{ __('Community accounts and access controls') }}</caption>
            <thead><tr><th scope="col">{{ __('User') }}</th><th scope="col">{{ __('Current access') }}</th><th scope="col">{{ __('Manage') }}</th></tr></thead>
            <tbody>
                @forelse($users as $person)
                    @php
                        $canEditAccess = $person->id !== auth()->id() && ! $person->hasRole('super-admin') && ($canAssignAdministrators || ! $person->hasRole('admin'));
                        $accessBag = 'access-'.$person->id;
                        $accessErrors = $errors->getBag($accessBag);
                        $personHasOldInput = is_scalar(old('access_person')) && (string) old('access_person') === (string) $person->id;
                        $primaryRole = collect(['super-admin', 'admin', 'editor', 'author', 'reader'])->first(fn ($role) => $person->hasRole($role));
                        $selectedRole = $personHasOldInput && is_string(old('role')) && in_array(old('role'), $assignableRoles, true) ? old('role') : ($primaryRole ?? 'reader');
                    @endphp
                    <tr>
                        <td class="admin-person-cell"><div class="admin-person-identity"><x-avatar :user="$person" size="small" /><div><strong>{{ $person->name }} @if($person->id === auth()->id())<span class="admin-you">{{ __('You') }}</span>@endif</strong><span class="muted table-secondary">{{ $person->email }}</span><a class="admin-profile-link" href="{{ '/@'.$person->username }}">{{ __('View profile') }}</a></div></div></td>
                        <td class="admin-person-status"><span class="admin-role-name">{{ $primaryRole ? __('ui.roles.'.$primaryRole) : __('No role assigned') }}</span><span @class(['admin-status', 'is-danger' => (bool) $person->suspended_at, 'is-success' => !$person->suspended_at && $person->hasVerifiedEmail()])>{{ $person->suspended_at ? __('Suspended') : ($person->hasVerifiedEmail() ? __('Active') : __('Email unverified')) }}</span></td>
                        <td class="admin-person-access">
                            @if($canEditAccess)
                                <details class="admin-access-editor" @if($accessErrors->any()) open @endif>
                                    <summary aria-label="{{ __('Manage access for :name', ['name' => $person->name]) }}">{{ __('Manage access') }}<x-icon name="chevron-right" size="16" /></summary>
                                    <form method="post" action="{{ route('admin.users.update', array_merge([$person], $returnQuery)) }}" class="user-access-form" data-error-bag="{{ $accessBag }}" x-data="{ role: @js($selectedRole), descriptions: @js($roleDescriptions) }">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="access_person" value="{{ $person->id }}">
                                        <label class="field"><span>{{ __('Role') }}</span><select class="form-input" name="role" x-model="role" aria-label="{{ __('Role for :name', ['name' => $person->name]) }}" aria-invalid="{{ $accessErrors->has('role') ? 'true' : 'false' }}" aria-describedby="role-help-{{ $person->id }} role-error-{{ $person->id }}">@foreach($assignableRoles as $value)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ __('ui.roles.'.$value) }}</option>@endforeach</select><x-field-error name="role" :bag="$accessBag" :id="'role-error-'.$person->id" /></label>
                                        <p class="admin-access-help" id="role-help-{{ $person->id }}" x-text="descriptions[role]">{{ $roleDescriptions[$selectedRole] }}</p>
                                        <label class="field"><span>{{ __('Account access') }}</span><select class="form-input" name="suspended" aria-label="{{ __('Account access for :name', ['name' => $person->name]) }}" aria-describedby="access-help-{{ $person->id }} access-error-{{ $person->id }}" aria-invalid="{{ $accessErrors->has('suspended') ? 'true' : 'false' }}"><option value="0" @selected(! ($personHasOldInput ? old('suspended') : $person->suspended_at))>{{ __('Active') }}</option><option value="1" @selected((bool) ($personHasOldInput ? old('suspended') : $person->suspended_at))>{{ __('Suspended') }}</option></select><x-field-error name="suspended" :bag="$accessBag" :id="'access-error-'.$person->id" /></label>
                                        <p class="admin-access-help" id="access-help-{{ $person->id }}">{{ __('Suspension blocks sign-in, hides published stories, and revokes API tokens. Restoring access does not restore those tokens.') }}</p>
                                        @unless($person->hasVerifiedEmail())<p class="admin-access-help">{{ __('This user still needs to verify their email. Changing a role does not verify their account.') }}</p>@endunless
                                        <button class="btn btn-primary" type="submit" aria-label="{{ __('Save access for :name', ['name' => $person->name]) }}">{{ __('Save access') }}</button>
                                    </form>
                                    @if($canAssignAdministrators && ! $person->hasAnyRole(['admin','super-admin']) && ! $person->suspended_at)<form method="POST" action="{{ route('support.start', $person) }}" class="support-session-form">@csrf<button class="text-button">{{ __('Open read-only support session') }}</button></form>@endif
                                </details>
                            @else
                                <span class="admin-protected"><x-icon name="lock" size="14" />{{ __('Protected account') }}</span><p class="muted table-secondary">{{ $person->id === auth()->id() ? __('You cannot change your own role or suspend yourself.') : ($person->hasRole('super-admin') ? __('Ownership is managed through the server console.') : __('Only a platform owner can manage this administrator.')) }}</p>
                            @endif
                        </td>
                    </tr>
                @empty<tr><td colspan="3"><div class="admin-inline-empty"><x-icon name="search" size="20" /><p>{{ __('No users match these filters. Try another name, role, or account status.') }}</p></div></td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $users->withQueryString()->fragment('people')->links() }}</div>
</section>
