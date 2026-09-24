<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $billingReady = (bool) (config('cashier.secret') && config('services.stripe.monthly_price'));
        $connect = null;
        if ($user?->stripe_connect_id && config('cashier.secret')) {
            try {
                $account = Cashier::stripe()->accounts->retrieve($user->stripe_connect_id);
                $balance = Cashier::stripe()->balance->retrieve([], ['stripe_account' => $user->stripe_connect_id]);
                $connect = ['enabled' => $account->payouts_enabled, 'available' => $balance->available, 'pending' => $balance->pending];
            } catch (\Throwable $e) { report($e); }
        }
        return view('membership', compact('user', 'billingReady', 'connect'));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate(['plan' => ['required', 'in:monthly,yearly']]);
        $price = config('services.stripe.'.$data['plan'].'_price');
        if (! config('cashier.secret') || ! $price) {
            return back()->with('status', 'Paid memberships are not open yet. Your free account is ready to read, follow, and publish.');
        }
        if ($request->user()->subscribed('default')) { return redirect()->route('membership')->with('status', 'You already have an active membership. Manage it in your billing portal.'); }
        try {
            return $request->user()->newSubscription('default', $price)->checkout([
                'success_url' => route('membership').'?checkout=complete',
                'cancel_url' => route('membership'),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['billing' => 'Checkout is temporarily unavailable. Please try again later.']);
        }
    }

    public function portal(Request $request)
    {
        if (! config('cashier.secret') || ! $request->user()->stripe_id) { return back()->with('status', 'There is no billing account to manage yet.'); }
        try { return $request->user()->redirectToBillingPortal(route('membership')); }
        catch (\Throwable $e) { report($e); return back()->withErrors(['billing' => 'Your billing portal could not be opened. Please try again later.']); }
    }

    public function connect(Request $request)
    {
        abort_unless($request->user()->canWrite(), 403);
        if (! config('cashier.secret')) { return back()->with('status', 'Creator payouts will be available when Stripe is connected by the platform.'); }
        try {
            $user = $request->user();
            if (! $user->stripe_connect_id) {
                $account = Cashier::stripe()->accounts->create(['type' => 'express', 'email' => $user->email, 'capabilities' => ['transfers' => ['requested' => true]], 'metadata' => ['user_id' => (string) $user->id]], ['idempotency_key' => 'creator-account-'.$user->id]);
                $user->forceFill(['stripe_connect_id' => $account->id])->save();
            }
            $link = Cashier::stripe()->accountLinks->create(['account' => $user->stripe_connect_id, 'refresh_url' => route('membership'), 'return_url' => route('membership'), 'type' => 'account_onboarding']);
            return redirect()->away($link->url);
        } catch (\Throwable $e) { report($e); return back()->withErrors(['billing' => 'Creator onboarding is temporarily unavailable. Please try again later.']); }
    }
}
