<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PayoutController extends Controller
{
    public function index(Request $request, PayoutService $service)
    {
        $this->authorizeAdmin($request);

        return view('payouts', [
            'payouts' => Payout::with(['author', 'approvedBy'])->latest()->paginate(20),
            'authors' => User::role(['author', 'editor', 'admin', 'super-admin'])->whereNotNull('email_verified_at')->whereNull('suspended_at')->orderBy('name')->get(['id', 'name', 'username']),
            'ready' => $service->ready(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'author_id' => ['required', 'integer', 'exists:users,id'],
            'amount_cents' => ['required', 'integer', 'min:1', 'max:100000000'],
            'currency' => ['required', Rule::in(Payout::CURRENCIES)],
            'reference' => ['required', 'string', 'max:120', 'unique:payouts,reference'],
            'allocation_confirmed' => ['accepted'],
        ]);
        $author = User::findOrFail($data['author_id']);
        abort_unless($author->canWrite(), 422, 'Choose an active, verified writer.');
        unset($data['allocation_confirmed']);
        $data += ['author_name' => $author->name, 'approved_by' => $request->user()->id, 'idempotency_key' => (string) Str::uuid()];
        DB::transaction(function () use ($data, $request) {
            $payout = Payout::create($data);
            activity()->causedBy($request->user())->performedOn($payout)
                ->withProperties(['amount_cents' => $payout->amount_cents, 'currency' => $payout->currency, 'reference' => $payout->reference])
                ->log('Recorded approved earnings allocation');
        });

        return back()->with('status', 'Approved earnings recorded. No funds have been transferred.');
    }

    public function process(Request $request, Payout $payout, PayoutService $service)
    {
        $this->authorizeAdmin($request);
        $request->validate(['confirm_transfer' => ['accepted']]);
        $service->process($payout, $request->user());

        return back()->with('status', 'Earnings transferred to the writer’s connected Stripe account. Stripe manages the bank payout separately.');
    }

    public function reconcile(Request $request, Payout $payout, PayoutService $service)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['transfer_id' => ['required', 'string', 'max:255', 'regex:/^tr_[A-Za-z0-9]+$/']]);
        $service->reconcile($payout, $data['transfer_id'], $request->user());

        return back()->with('status', 'Stripe transfer matched and the earnings ledger updated. No new transfer was created.');
    }

    public function earnings(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ! $user->suspended_at && $user->hasVerifiedEmail(), 403);

        return view('earnings', [
            'user' => $user,
            'payouts' => Payout::where('author_id', $user->id)->latest()->paginate(20),
            'totals' => Payout::where('author_id', $user->id)->selectRaw('currency, status, SUM(amount_cents) AS total_cents')->groupBy('currency', 'status')->get()->groupBy('currency'),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->suspended_at && $user->hasVerifiedEmail()
            && $user->hasAnyRole(['admin', 'super-admin']) && $user->can('payouts.process'), 403);
    }
}
