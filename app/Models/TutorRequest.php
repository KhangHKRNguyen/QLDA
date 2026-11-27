<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TutorRequest extends Model
{
    public const UPDATED_AT = null;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELED = 'canceled';

    protected $table = 'tutor_requests';
    protected $primaryKey = 'tutor_request_id';
    protected $guarded = ['tutor_request_id'];

    protected function casts(): array
    {
        return [
            'created_at'          => 'datetime',
            'sla_deadline'        => 'datetime',
            'desired_tuition_fee' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }

    public function tutorClass(): HasOne
    {
        return $this->hasOne(TutorClass::class, 'tutor_request_id', 'tutor_request_id');
    }

    public function getEffectiveSlaDeadlineAttribute()
    {
        return $this->sla_deadline ?? $this->created_at?->copy()->addHours(4);
    }

    public function getIsSlaViolatedAttribute(): bool
    {
        $deadline = $this->effective_sla_deadline;

        return $this->status === self::STATUS_PENDING
            && $deadline !== null
            && now()->greaterThan($deadline);
    }
}