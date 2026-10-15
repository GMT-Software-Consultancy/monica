<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gift extends Model
{
    use HasFactory;

    protected $table = 'gifts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'gift_occasion_id',
        'gift_state_id',
        'currency_id',
        'name',
        'description',
        'estimated_price',
        'url',
        'given_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_price' => 'integer',
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
     * Contacts that have been deleted are not returned.
     *
     * @return BelongsToMany<Contact, $this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}
