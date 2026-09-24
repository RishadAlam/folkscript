<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    // Deliberately bounded to currencies with two decimal minor units.
    public const CURRENCIES = ['usd', 'eur', 'gbp', 'cad', 'aud'];

    protected $fillable = ['author_id', 'author_name', 'approved_by', 'amount_cents', 'currency', 'reference', 'idempotency_key'];
    protected $hidden = ['idempotency_key', 'stripe_destination'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'processing_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function formattedAmount(): string
    {
        return strtoupper($this->currency).' '.number_format($this->amount_cents / 100, 2);
    }
}
