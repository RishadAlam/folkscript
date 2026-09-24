<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $operator = $request->user();
        abort_unless($operator->hasRole('super-admin') && $operator->hasVerifiedEmail() && !$operator->suspended_at, 403);
        abort_if($request->session()->has('impersonator_id') || $user->hasAnyRole(['admin','super-admin']) || $user->suspended_at || $user->id === $operator->id, 403);
        activity()->causedBy($operator)->performedOn($user)->log('Read-only support session started');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'impersonator_id' => $operator->id,
            'impersonation_expires_at' => now()->addMinutes(30)->timestamp,
            'impersonator_auth_hash' => hash_hmac('sha256', $operator->getAuthPassword(), config('app.key')),
        ]);
        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        $id = $request->session()->get('impersonator_id');
        abort_unless($id, 403);
        $operator = User::findOrFail($id);
        abort_unless($operator->hasRole('super-admin') && $operator->hasVerifiedEmail() && !$operator->suspended_at, 403);
        activity()->causedBy($operator)->performedOn($request->user())->log('Read-only support session ended');
        $request->session()->forget(['impersonator_id', 'impersonation_expires_at', 'impersonator_auth_hash']);
        Auth::login($operator);
        $request->session()->regenerate();
        return redirect('/admin')->with('success', 'Returned to your administrator account.');
    }
}
