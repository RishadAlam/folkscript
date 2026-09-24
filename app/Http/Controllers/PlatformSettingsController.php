<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeAdmin($request);
        $settings = collect(SiteSetting::CONFIG_KEYS)->mapWithKeys(fn ($config, $key) => [$key => config($config)])->all();

        return view('platform-settings', [
            'settings' => $settings,
            'trainingPolicySaved' => array_key_exists('block_training_bots', SiteSetting::values()),
            'mailTransport' => config('mail.default'),
            'analyticsReady' => $this->analyticsReady(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'site_tagline' => ['nullable', 'string', 'max:160'],
            'training_bot_policy' => ['required', 'in:allow,block'],
            'google_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'bing_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'render_og' => ['nullable', 'boolean'],
            'digests_enabled' => ['nullable', 'boolean'],
            'mail_notifications' => ['nullable', 'boolean'],
            'mail_from_name' => ['required', 'string', 'max:80', 'not_regex:/[\r\n]/'],
            'mail_from_address' => ['required', 'email:rfc', 'max:254'],
            'analytics_enabled' => ['nullable', 'boolean'],
            'plausible_domain' => ['nullable', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/i'],
        ]);
        $data['block_training_bots'] = $data['training_bot_policy'] === 'block';
        unset($data['training_bot_policy']);
        foreach (['render_og', 'digests_enabled', 'mail_notifications', 'analytics_enabled'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        if ($data['analytics_enabled'] && ! $this->analyticsReady()) {
            throw ValidationException::withMessages(['analytics_enabled' => 'Configure a valid HTTPS Plausible script URL and endpoint in the deployment environment before enabling analytics.']);
        }

        DB::transaction(function () use ($data, $request) {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            activity()->causedBy($request->user())->withProperties(['changed_settings' => array_keys($data)])->log('Updated platform settings');
        });
        SiteSetting::invalidate();

        return back()->with('status', 'Platform settings saved. Background workers pick them up on their next job.');
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->suspended_at && $user->hasVerifiedEmail()
            && $user->hasAnyRole(['admin', 'super-admin']) && $user->can('settings.manage'), 403);
    }

    private function analyticsReady(): bool
    {
        foreach (['analytics.plausible_script_url', 'analytics.plausible_endpoint'] as $key) {
            $url = config($key);
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                return false;
            }
        }

        return true;
    }
}
