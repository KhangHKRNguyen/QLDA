<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Giao dịch thanh toán phí nhận lớp của Gia sư. */
class PaymentTransaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_LATE_SUCCESS = 'late_success_pending_refund';

    protected $table = 'payment_transactions';
    protected $primaryKey = 'payment_transaction_id';
    public $timestamps = false;
    protected $guarded = ['payment_transaction_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_at' => 'datetime',
            'reservation_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function tutorClass(): BelongsTo
    {
        return $this->belongsTo(TutorClass::class, 'class_id', 'class_id');
    }

    /** Gia sư đã thanh toán. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'payment_transaction_id', 'payment_transaction_id');
    }
}
