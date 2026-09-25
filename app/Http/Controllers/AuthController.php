<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function loginPage(Request $request)
    {
        $returnTo = $request->query('return_to');
        if (is_string($returnTo) && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')
            && ! preg_match('/[\\\\\x00-\x20]/', $returnTo)) {
            $request->session()->put('url.intended', $returnTo);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', Str::lower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->suspended_at) {
            throw ValidationException::withMessages(['email' => 'These details do not match an active account.']);
        }
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->id, 'login.remember' => $request->boolean('remember'), 'login.expires' => now()->addMinutes(5)->timestamp, 'login.auth_hash' => $this->authenticationHash($user)]);
            return redirect()->route('two-factor.login');
        }
        return $this->authenticate($request, $user, $request->boolean('remember'));
    }

    public function register(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', 'unique:users,username', 'not_in:admin,api,settings,login,register,folkscript'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
            'website' => ['nullable', 'string', 'max:0'],
        ]);
        $data['email'] = Str::lower($data['email']);
        $user = User::create(collect($data)->except('website')->all());
        $user->assignRole('reader');
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('verification.notice')->with('status', $this->emailDeliveryAvailable()
            ? 'Your account is ready. Check your email to start writing.'
            : 'Your account is ready. Email delivery is unavailable on this installation. Contact the site administrator to verify your account.');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $request->input('redirect_to') === 'password-reset'
            ? redirect()->route('password.request')
            : redirect('/');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        if (! $this->emailDeliveryAvailable()) {
            return back()->with('status', 'Password reset email is unavailable on this installation. Contact the site administrator for help signing in.');
        }
        Password::sendResetLink(['email' => Str::lower($request->string('email')->toString())]);
        return back()->with('status', 'If an account uses that email, a password reset link is on its way.');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            event(new PasswordReset($user));
        });
        return $status === Password::PasswordReset ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect('/dashboard')->with('status', 'Email verified. Your next story starts here.');
    }

    public function sendVerification(Request $request)
    {
        if (! $this->emailDeliveryAvailable()) {
            return back()->with('status', 'Verification email is unavailable on this installation. Contact the site administrator to verify your account.');
        }
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }
        return back()->with('status', 'A new verification link has been sent to your email.');
    }

    public function confirmPassword(Request $request)
    {
        $request->validate(['password' => ['bail', 'required', 'string', 'current_password']]);
        $request->session()->passwordConfirmed();
        return redirect()->intended('/settings')->with('status', 'Password confirmed. You can now continue with your changes.');
    }

    public function twoFactor(Request $request)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:10'], 'recovery_code' => ['nullable', 'string', 'max:100']]);
        $user = User::find($request->session()->get('login.id'));
        if (! $user || $user->suspended_at || ! $user->two_factor_confirmed_at || $request->session()->get('login.expires', 0) < now()->timestamp
            || ! hash_equals($this->authenticationHash($user), (string) $request->session()->get('login.auth_hash', ''))) {
            $request->session()->forget(['login.id', 'login.remember', 'login.expires', 'login.auth_hash']);
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session expired. Please sign in again.']);
        }
        $valid = false;
        if (! empty($data['recovery_code'])) {
            $matched = collect($user->recoveryCodes())->first(fn ($code) => hash_equals($code, $data['recovery_code']));
            if ($matched) { $user->replaceRecoveryCode($matched); $valid = true; }
        } elseif (! empty($data['code'])) {
            $valid = app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $data['code']);
        }
        if (! $valid) {
            $field = ! empty($data['recovery_code']) ? 'recovery_code' : 'code';
            $message = $field === 'recovery_code'
                ? 'That recovery code is invalid or has already been used. Try another saved code.'
                : 'That code is not valid. Try the current six-digit code from your authenticator.';
            throw ValidationException::withMessages([$field => $message]);
        }
        $remember = (bool) $request->session()->get('login.remember');
        $request->session()->forget(['login.id', 'login.remember', 'login.expires', 'login.auth_hash']);
        return $this->authenticate($request, $user, $remember);
    }

    public function socialRedirect(string $provider)
    {
        $this->ensureProvider($provider);
        if (! config("services.$provider.client_id") || ! config("services.$provider.client_secret")) {
            return redirect()->route('login')->with('status', ucfirst($provider).' sign-in is not connected yet. You can use your email to continue.');
        }
        return Socialite::driver($provider)->redirect();
    }

    public function socialCallback(Request $request, string $provider)
    {
        $this->ensureProvider($provider);
        try {
            $identity = Socialite::driver($provider)->user();
            $email = $identity->getEmail();
            $verified = $provider === 'google' && ($identity->user['email_verified'] ?? $identity->user['verified_email'] ?? false);
            if ($provider === 'github') {
                $emails = Http::withToken($identity->token)->acceptJson()->timeout(10)->get('https://api.github.com/user/emails')->throw()->json();
                $verified = collect($emails)->contains(fn ($item) => ($item['email'] ?? null) === $email && ($item['verified'] ?? false));
            }
            if (! $email || ! $verified) { throw new \RuntimeException('Verified provider email required.'); }
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors(['email' => 'We could not verify your social sign-in. Please use email or try again.']);
        }
        $user = User::where('oauth_provider', $provider)->where('oauth_id', $identity->getId())->first();
        if (! $user) {
            if (User::where('email', Str::lower($email))->exists()) {
                return redirect()->route('login')->withErrors(['email' => 'An account already uses this email. Sign in with your password to keep it secure.']);
            }
            $username = Str::limit(Str::slug($identity->getNickname() ?: $identity->getName(), '_'), 20, '').'_'.Str::lower(Str::random(6));
            $user = User::create(['name' => $identity->getName() ?: $username, 'username' => $username, 'email' => Str::lower($email), 'password' => Str::random(64)]);
            $user->forceFill(['oauth_provider' => $provider, 'oauth_id' => $identity->getId(), 'email_verified_at' => now()])->save();
            $user->assignRole(['reader', 'author']);
        }
        if ($user->suspended_at) { abort(403, 'This account is suspended.'); }
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->id, 'login.remember' => false, 'login.expires' => now()->addMinutes(5)->timestamp, 'login.auth_hash' => $this->authenticationHash($user)]);
            return redirect()->route('two-factor.login');
        }
        return $this->authenticate($request, $user, false);
    }

    private function ensureProvider(string $provider): void { abort_unless(in_array($provider, ['google', 'github'], true), 404); }

    private function authenticationHash(User $user): string
    {
        return hash_hmac('sha256', $user->getAuthPassword(), config('app.key'));
    }

    private function emailDeliveryAvailable(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    private function authenticate(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->passwordConfirmed();
        return redirect()->intended('/dashboard');
    }
}
