<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gift extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'gift_occasion_id',
        'gift_state_id',
        'name',
        'description',
        'url',
        'estimated_price',
        'currency_id',
        'given_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'given_at' => 'date',
    ];

    /**
     * Get the vault associated with the gift.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the gift occasion associated with the gift.
     *
     * @return BelongsTo<GiftOccasion, $this>
     */
    public function giftOccasion(): BelongsTo
    {
        return $this->belongsTo(GiftOccasion::class);
    }

    /**
     * Get the gift state associated with the gift.
     *
     * @return BelongsTo<GiftState, $this>
     */
    public function giftState(): BelongsTo
    {
        return $this->belongsTo(GiftState::class);
    }

    /**
     * Get the currency associated with the gift.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the contacts the gift is for.
     *
     * @return BelongsToMany<Contact, $this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_gift')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include gifts that have not been given yet.
     *
     * @param  Builder<Gift>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('given_at');
    }

    /**
     * Scope a query to only include gifts that have been given.
     *
     * @param  Builder<Gift>  $query
     */
    public function scopeGiven(Builder $query): void
    {
        $query->whereNotNull('given_at');
    }
}
