<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $pinnablePosts = $user->posts()->published()->latest('published_at')->get(['id', 'title']);
        $activeVerified = ! $user->suspended_at && $user->hasVerifiedEmail();
        $canFeatureStory = $activeVerified && ($user->canWrite() || $pinnablePosts->isNotEmpty() || $user->pinned_post_id);
        $section = $request->query('section', 'profile');
        if (! is_string($section) || ! in_array($section, ['profile', 'preferences', 'publishing', 'security', 'developer', 'account'], true)) {
            $section = 'profile';
        }

        // Validation failures must return to the form that needs attention.
        $errors = $request->session()->get('errors', new \Illuminate\Support\ViewErrorBag);
        if ($errors->getBag('passwordChange')->any() || $errors->getBag('confirmTwoFactorAuthentication')->any()) {
            $section = 'security';
        } elseif ($errors->getBag('accountDeletion')->any()) {
            $section = 'account';
        } elseif ($errors->has('post_id')) {
            $section = 'publishing';
        } elseif ($errors->has('token_name') || $request->session()->has('token')) {
            $section = 'developer';
        } elseif ($errors->has('newsletter_enabled')) {
            $section = 'preferences';
        } elseif ($errors->any()) {
            $section = 'profile';
        }
        if ($section === 'publishing' && ! $canFeatureStory) {
            $section = 'profile';
        }

        $accountRoleLabel = 'Reader';
        foreach (['super-admin' => 'Owner', 'admin' => 'Administrator', 'editor' => 'Editor', 'author' => 'Writer'] as $role => $label) {
            if ($user->hasRole($role)) {
                $accountRoleLabel = $label;
                break;
            }
        }

        return view('settings', [
            'user' => $user,
            'tokens' => $user->tokens()->latest()->get(),
            'pinnablePosts' => $pinnablePosts,
            'section' => $section,
            'canFeatureStory' => $canFeatureStory,
            'canModerate' => $activeVerified && $user->hasAnyRole(['editor', 'admin', 'super-admin']),
            'canManagePublication' => $activeVerified && $user->hasAnyRole(['admin', 'super-admin']) && $user->can('settings.manage'),
            'accountRoleLabel' => $accountRoleLabel,
            'hasRecentPasswordConfirmation' => time() - $request->session()->get('auth.password_confirmed_at', 0) <= config('auth.password_timeout', 10800),
            'emailDeliveryAvailable' => ! in_array(config('mail.default'), ['log', 'array'], true),
        ]);
    }

    public function preferences(Request $request)
    {
        $request->validate(['newsletter_enabled' => ['required', 'boolean']]);
        $request->user()->update(['newsletter_enabled' => $request->boolean('newsletter_enabled')]);

        return redirect()->route('settings', ['section' => 'preferences'])->with('status', 'Your email preferences have been saved.');
    }

    public function confirmAccess(Request $request)
    {
        $section = $request->query('section');
        $section = in_array($section, ['security', 'developer'], true) ? $section : 'security';
        $request->session()->put('url.intended', route('settings', ['section' => $section]));

        return redirect()->route('password.confirm');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', Rule::unique('users')->ignore($user->id), Rule::notIn(array_diff(['admin', 'api', 'settings', 'login', 'register', 'folkscript'], [$user->username]))],
            'bio' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'github' => ['nullable', 'url:http,https', 'max:255'],
            'linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'links_present' => ['nullable', 'boolean'],
            'links' => ['nullable', 'array', 'max:10'],
            'links.*' => ['array:label,url'],
            'links.*.label' => ['nullable', 'required_with:links.*.url', 'string', 'max:40', 'not_regex:/[\x00-\x1F\x7F]/'],
            'links.*.url' => ['bail', 'nullable', 'required_with:links.*.label', 'url:http,https', 'max:500', function ($attribute, $value, $fail) {
                $parts = is_string($value) ? parse_url($value) : false;
                if (is_array($parts) && (array_key_exists('user', $parts) || array_key_exists('pass', $parts))) {
                    $fail('Use a public link without a username or password in the address.');
                }
            }],
            'newsletter_enabled' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:6144', 'dimensions:max_width=8000,max_height=8000'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ], [
            'username.not_in' => 'This username is reserved. Choose a different username.',
            'links.max' => 'Add up to 10 links. Remove a link before adding another.',
            'links.*.label.required_with' => 'Give this link a label so readers know where it leads.',
            'links.*.label.not_regex' => 'Use a label without line breaks or control characters.',
            'links.*.url.required_with' => 'Add the full address for this link, starting with https://.',
            'links.*.url.url' => 'Enter a valid public web address starting with https:// or http://.',
        ]);
        if ($request->boolean('links_present') || $request->has('links')) {
            $data['social_links'] = collect($data['links'] ?? [])
                ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
                ->map(fn ($link) => ['label' => $link['label'], 'url' => $link['url']])->values()->all();
        } elseif ($request->hasAny(['website', 'github', 'linkedin'])) {
            // Older clients can still update their fixed fields without erasing other links.
            $links = $user->publicProfileLinks();
            foreach (['website' => 'Website', 'github' => 'GitHub', 'linkedin' => 'LinkedIn'] as $field => $label) {
                if (! $request->has($field)) { continue; }
                $links = array_values(array_filter($links, fn ($link) => mb_strtolower($link['label']) !== $field));
                if (filled($data[$field] ?? null)) { $links[] = ['label' => $label, 'url' => $data[$field]]; }
            }
            $data['social_links'] = $links;
        }
        if ($request->has('newsletter_enabled')) {
            $data['newsletter_enabled'] = $request->boolean('newsletter_enabled');
        }
        unset($data['website'], $data['github'], $data['linkedin'], $data['links'], $data['links_present']);
        foreach (['avatar', 'cover_image'] as $field) {
            unset($data[$field], $data['remove_'.$field]);
            if ($request->hasFile($field)) {
                $uploads = app(\App\Services\MediaUpload::class);
                $media = $uploads->store($user, $request->file($field), $field);
                $data[$field] = $uploads->url($media);
            } elseif ($request->boolean('remove_'.$field)) {
                $user->clearMediaCollection($field);
                $data[$field] = null;
            }
        }
        DB::transaction(function () use ($user, $data) {
            if ($user->username && $user->username !== $data['username']) {
                $paths = [['/@'.$user->username, '/@'.$data['username']]];
                foreach ($user->posts()->get(['slug']) as $post) {
                    $paths[] = ['/@'.$user->username.'/'.$post->slug, '/@'.$data['username'].'/'.$post->slug];
                }
                foreach (\App\Models\Series::where('author_id', $user->id)->get(['slug']) as $series) {
                    $paths[] = ['/@'.$user->username.'/series/'.$series->slug, '/@'.$data['username'].'/series/'.$series->slug];
                }
                foreach ($paths as [$old, $new]) {
                    Redirect::where('from_path', $new)->delete();
                    Redirect::where('to_path', $old)->update(['to_path' => $new]);
                    Redirect::updateOrCreate(['from_path' => $old], ['to_path' => $new, 'status_code' => 301]);
                }
            }
            $user->update($data);
        });
        return back()->with('status', 'Your profile has been updated.');
    }

    public function pinStory(Request $request)
    {
        $data = $request->validate(['post_id' => ['nullable', 'integer', Rule::exists('posts', 'id')->where(fn ($query) => $query->where('author_id', $request->user()->id)->where('status', 'published')->where('published_at', '<=', now()))]]);
        $request->user()->forceFill(['pinned_post_id' => $data['post_id'] ?? null])->save();
        return back()->with('status', empty($data['post_id']) ? 'Pinned story removed from your profile.' : 'Your featured story is pinned to your profile.');
    }

    public function password(Request $request)
    {
        $data = $request->validateWithBag('passwordChange', ['current_password' => ['bail', 'required', 'string', 'current_password'], 'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        Auth::logoutOtherDevices($data['password']);
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        $request->user()->tokens()->delete();
        return back()->with('status', 'Password changed. Other sessions and API tokens have been signed out.');
    }

    public function enableTwoFactor(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user());
        return back()->with('status', 'Scan the QR code with your authenticator, then enter its six-digit code.');
    }

    public function confirmTwoFactor(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $data = $request->validateWithBag('confirmTwoFactorAuthentication', ['code' => ['required', 'digits:6']]);
        $confirm($request->user(), $data['code']);
        return back()->with('status', 'Two-factor authentication is enabled. Save your recovery codes somewhere private.');
    }

    public function disableTwoFactor(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable($request->user());
        return back()->with('status', 'Two-factor authentication has been disabled.');
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        abort_unless($request->user()->two_factor_confirmed_at, 403);
        $generate($request->user());
        return back()->with('status', 'New recovery codes generated. Your old codes no longer work.');
    }

    public function createToken(Request $request)
    {
        $data = $request->validate(['token_name' => ['required', 'string', 'max:60']]);
        if ($request->user()->tokens()->count() >= 10) {
            return back()->withInput()->withErrors(['token_name' => 'You have reached the limit of 10 tokens. Revoke an unused token before creating another.']);
        }
        $token = $request->user()->createToken($data['token_name'], ['profile:read', 'posts:read'], now()->addDays(90));
        return back()->with('token', $token->plainTextToken)->with('status', 'Your read-only API token is ready. Copy it now; it will only be shown once. It expires in 90 days.');
    }

    public function revokeToken(Request $request, string $token)
    {
        $request->user()->tokens()->findOrFail($token)->delete();
        return back()->with('status', 'API token revoked.');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('accountDeletion', ['password' => ['bail', 'required', 'string', 'current_password'], 'confirmation' => ['required', 'in:DELETE']]);
        $user = $request->user();
        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['confirmation' => 'Transfer platform ownership before deleting this account.'], 'accountDeletion');
        }
        $user->tokens()->delete();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('status', 'Your account has been deleted.');
    }
}
