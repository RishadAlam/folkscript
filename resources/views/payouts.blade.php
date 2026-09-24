<x-layout title="{{ __('Approved earnings administration') }}">
<div class="page-shell platform-page">
    <header class="page-heading"><div><h1>{{ __('Approved earnings.') }}</h1><p class="muted">{{ __('Record allocations and manage transfers to writers.') }}</p></div><a href="{{ route('admin') }}" class="btn btn-outline">{{ __('Administration') }}</a></header>
    <p class="platform-intro">{{ __('This ledger records earnings approved by an administrator. Reads, reactions, and memberships do not automatically generate allocations. A transfer moves existing platform funds to a writer’s Stripe account; Stripe manages the later bank payout.') }}</p>
    @unless($ready)<div class="notice">{{ __('Transfers are disabled. Stripe credentials and the explicit platform payout switch must be configured in the deployment environment. You can record approved allocations now.') }}</div>@endunless
    <details class="platform-disclosure" @if($errors->has('author_id') || $errors->has('amount_cents') || $errors->has('reference') || $errors->has('allocation_confirmed')) open @endif>
        <summary>{{ __('Record an approved allocation') }}</summary>
        <form method="post" action="{{ route('payouts.store') }}" class="platform-allocation-form">
            @csrf
            <label class="field">{{ __('Writer') }}<select class="form-input" name="author_id" required><option value="">{{ __('Choose a writer') }}</option>@foreach($authors as $author)<option value="{{ $author->id }}" @selected((string) old('author_id') === (string) $author->id)>{{ $author->name }} (&#64;{{ $author->username }})</option>@endforeach</select></label>
            <div class="form-grid"><label class="field">{{ __('Amount in cents') }}<input class="form-input" name="amount_cents" type="number" min="1" max="100000000" step="1" value="{{ old('amount_cents') }}" required><span class="field-help">{{ __('For example, 2500 represents 25.00 in the selected currency.') }}</span></label><label class="field">{{ __('Currency') }}<select class="form-input" name="currency" required>@foreach(\App\Models\Payout::CURRENCIES as $currency)<option value="{{ $currency }}" @selected(old('currency', 'usd') === $currency)>{{ strtoupper($currency) }}</option>@endforeach</select></label></div>
            <label class="field">{{ __('Approval reference') }}<input class="form-input" name="reference" value="{{ old('reference') }}" maxlength="120" required><span class="field-help">{{ __('Use a unique invoice or allocation reference. Amounts and references cannot be edited after approval.') }}</span></label>
            <label class="platform-check"><input type="checkbox" name="allocation_confirmed" value="1" required><span>{{ __('I confirm this is an approved, payable allocation backed by the platform’s accounting records.') }}</span></label>
            <button class="btn btn-primary" type="submit">{{ __('Record allocation') }}</button>
        </form>
    </details>
    <section aria-labelledby="ledger-title"><h2 class="platform-ledger-heading" id="ledger-title">{{ __('Transfer ledger') }}</h2>
        @if($payouts->isNotEmpty())
        <div class="table-wrap" tabindex="0" aria-label="{{ __('Approved earnings ledger') }}"><table class="data-table platform-ledger"><thead><tr><th scope="col">{{ __('Writer & reference') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
            @foreach($payouts as $payout)
            <tr><td><strong>{{ $payout->author?->name ?? $payout->author_name }}</strong><p>{{ $payout->reference }}</p><p>{{ $payout->created_at->format('M j, Y') }} · {{ __('Approved by :name', ['name' => $payout->approvedBy?->name ?? __('Former administrator')]) }}</p></td><td class="platform-amount">{{ $payout->formattedAmount() }}</td><td><span class="status-badge">{{ __('ui.payout_status.'.$payout->status) }}</span>@if($payout->failure_message)<p class="platform-transfer-note">{{ $payout->failure_message }}</p>@endif @if($payout->stripe_transfer_id)<p class="platform-transfer-id">{{ $payout->stripe_transfer_id }}</p>@endif</td><td>
                @if(in_array($payout->status, ['pending', 'failed']))
                <form method="post" action="{{ route('payouts.process', $payout) }}" class="platform-transfer-form">@csrf<label class="platform-check"><input type="checkbox" name="confirm_transfer" value="1" required @disabled(!$ready)><span>{{ __('Send :amount', ['amount' => $payout->formattedAmount()]) }}</span></label><button class="btn btn-outline" type="submit" @disabled(!$ready)>{{ $payout->status === 'failed' ? __('Retry transfer') : __('Transfer funds') }}</button></form>
                @elseif($payout->status === 'processing')
                <details class="platform-reconcile"><summary>{{ __('Reconcile with Stripe') }}</summary><form method="post" action="{{ route('payouts.reconcile', $payout) }}">@csrf<label class="field">{{ __('Matching transfer ID') }}<input class="form-input" name="transfer_id" placeholder="tr_…" pattern="tr_[A-Za-z0-9]+" required></label><button class="btn btn-outline" type="submit" @disabled(!$ready)>{{ __('Verify transfer') }}</button><p class="field-help">{{ __('Reads the existing Stripe transfer. Never sends funds again.') }}</p></form></details>
                @else<p>{{ $payout->paid_at?->format('M j, Y') }}</p>@endif
            </td></tr>
            @endforeach
        </tbody></table></div><div class="pagination-wrap">{{ $payouts->links() }}</div>
        @else<div class="empty-state"><h3>{{ __('No approved earnings yet.') }}</h3><p>{{ __('When an allocation is approved in your accounting process, record it above. Nothing is calculated from demo readership or story engagement.') }}</p></div>@endif
    </section>
</div>
</x-layout>
