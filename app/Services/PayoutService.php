<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Cashier;
use Stripe\Exception\InvalidRequestException;
use Stripe\Transfer;

class PayoutService
{
    public function ready(): bool
    {
        return (bool) config('cashier.secret') && (bool) config('services.stripe.payouts_enabled', false);
    }

    public function process(Payout $payout, User $actor): Payout
    {
        $this->authorize($actor);
        $this->assertReady();

        $payout = DB::transaction(function () use ($payout, $actor) {
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->id);
            if (! in_array($locked->status, ['pending', 'failed'], true) || $locked->stripe_transfer_id) {
                throw ValidationException::withMessages(['payout' => 'This allocation is already transferred or awaiting reconciliation. It cannot be sent again.']);
            }
            $author = $locked->author;
            if (! $author || ! $author->canWrite() || ! $author->stripe_connect_id) {
                throw ValidationException::withMessages(['payout' => 'The writer must have an active, verified account and complete Stripe Connect onboarding first.']);
            }
            if ($locked->stripe_destination && $locked->stripe_destination !== $author->stripe_connect_id) {
                throw ValidationException::withMessages(['payout' => 'The writer’s connected account changed after a transfer attempt. Review this allocation with platform support before moving funds.']);
            }
            // Keep the destination stable for all attempts with this idempotency key.
            $locked->forceFill([
                'status' => 'processing',
                'stripe_destination' => $locked->stripe_destination ?: $author->stripe_connect_id,
                'processing_at' => now(),
                'failure_message' => null,
            ])->save();
            activity()->causedBy($actor)->performedOn($locked)->log('Started approved earnings transfer');

            return $locked;
        });

        $transferAttempted = false;
        try {
            $stripe = Cashier::stripe();
            $account = $stripe->accounts->retrieve($payout->stripe_destination);
            if (! $account->payouts_enabled || ($account->capabilities->transfers ?? null) !== 'active') {
                throw ValidationException::withMessages(['payout' => 'Stripe has not enabled transfers and payouts for this connected account. Ask the writer to complete onboarding.']);
            }
            $balance = $stripe->balance->retrieve();
            $available = collect($balance->available)->where('currency', $payout->currency)->sum('amount');
            if ($available < $payout->amount_cents) {
                throw ValidationException::withMessages(['payout' => 'The available platform Stripe balance does not cover this transfer. Fund the balance or wait for existing funds to settle.']);
            }
            $transferAttempted = true;
            $transfer = $stripe->transfers->create([
                'amount' => $payout->amount_cents,
                'currency' => $payout->currency,
                'destination' => $payout->stripe_destination,
                'description' => 'Approved earnings: '.$payout->reference,
                'transfer_group' => 'folkscript-payout-'.$payout->id,
                'metadata' => ['folkscript_payout_id' => (string) $payout->id],
            ], ['idempotency_key' => $payout->idempotency_key]);

            return $this->markPaid($payout, $transfer, $actor);
        } catch (\Throwable $exception) {
            // A timeout or server error can mean Stripe sent funds even though no response
            // reached us. Never retry that state: keys may expire after 24 hours.
            $definitelyNotSent = ! $transferAttempted || $exception instanceof InvalidRequestException;
            $message = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : ($definitelyNotSent
                    ? 'Stripe could not create this transfer. Review the connected account and platform balance before retrying.'
                    : 'The transfer outcome is uncertain. Check Stripe and reconcile the matching transfer before taking further action.');
            Payout::query()->whereKey($payout->id)->where('status', 'processing')->update([
                'status' => $definitelyNotSent ? 'failed' : 'processing',
                'failure_message' => $message,
                'updated_at' => now(),
            ]);
            if (! $exception instanceof ValidationException) {
                report($exception);
            }
            throw ValidationException::withMessages(['payout' => $message]);
        }
    }

    public function reconcile(Payout $payout, string $transferId, User $actor): Payout
    {
        $this->authorize($actor);
        $this->assertReady();
        if ($payout->status !== 'processing') {
            throw ValidationException::withMessages(['payout' => 'Only an unresolved transfer can be reconciled.']);
        }

        try {
            $transfer = Cashier::stripe()->transfers->retrieve($transferId);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['payout' => 'That transfer could not be retrieved from the platform Stripe account.']);
        }

        return $this->markPaid($payout, $transfer, $actor);
    }

    private function markPaid(Payout $payout, Transfer $transfer, User $actor): Payout
    {
        if ((string) ($transfer->metadata->folkscript_payout_id ?? '') !== (string) $payout->id
            || $transfer->amount !== $payout->amount_cents
            || $transfer->currency !== $payout->currency
            || $transfer->destination !== $payout->stripe_destination
            || $transfer->reversed || $transfer->amount_reversed > 0) {
            throw ValidationException::withMessages(['payout' => 'This Stripe transfer does not match the allocation, or it has been reversed. The ledger remains unresolved.']);
        }

        return DB::transaction(function () use ($payout, $transfer, $actor) {
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->id);
            if ($locked->status === 'paid' && $locked->stripe_transfer_id === $transfer->id) {
                return $locked;
            }
            if ($locked->status !== 'processing') {
                throw ValidationException::withMessages(['payout' => 'This allocation is no longer awaiting a transfer result.']);
            }
            $locked->forceFill(['status' => 'paid', 'stripe_transfer_id' => $transfer->id, 'paid_at' => now(), 'failure_message' => null])->save();
            activity()->causedBy($actor)->performedOn($locked)->withProperties(['stripe_transfer_id' => $transfer->id])->log('Confirmed approved earnings transfer');

            return $locked;
        });
    }

    private function assertReady(): void
    {
        if (! $this->ready()) {
            throw ValidationException::withMessages(['payout' => 'Transfers are disabled. Configure Stripe and explicitly enable platform payouts in the deployment environment.']);
        }
    }

    private function authorize(User $actor): void
    {
        abort_unless(! $actor->suspended_at && $actor->hasVerifiedEmail()
            && $actor->hasAnyRole(['admin', 'super-admin']) && $actor->can('payouts.process'), 403);
    }
}
