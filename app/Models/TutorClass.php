<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorClass extends Model
{
    public const STATUS_OPEN = 'pending';
    public const STATUS_RESERVED = 'processing';
    public const STATUS_TEACHING = 'teaching';

    protected $table = 'tutor_classes';
    protected $primaryKey = 'class_id';
    protected $guarded = ['class_id'];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'locked_at' => 'datetime',
            'lock_expires_at' => 'datetime',
            'trial_decided_at' => 'datetime',
            'completed_at' => 'datetime',
            'fee_per_session' => 'decimal:2',
            'enrollment_fee' => 'decimal:2',
        ];
    }

    public function tutorRequest(): BelongsTo
    {
        return $this->belongsTo(TutorRequest::class, 'tutor_request_id', 'tutor_request_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }

    public function reservationOwner(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'locked_by_account_id', 'account_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'class_id', 'class_id');
    }

    public function scopeOpenForClaim(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /** True only while a live five-minute reservation exists. */
    public function isLocked(): bool
    {
        // Fallback supports rows created before lock_expires_at was added.
        $expiresAt = $this->lock_expires_at;
        if (! $expiresAt && $this->locked_at) {
            $expiresAt = $this->locked_at->copy()->addMinutes(5);
        }

        return $this->status === self::STATUS_RESERVED
            && $expiresAt !== null
            && now()->lt($expiresAt);
    }
}
