<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Giao dịch thanh toán phí nhận lớp của Gia sư */
class PaymentTransaction extends Model
{
    protected $primaryKey = 'payment_transaction_id';

    public $timestamps = false;

    protected $guarded = ['payment_transaction_id'];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:0',
            'transaction_at' => 'datetime',
        ];
    }

    public function tutorClass(): BelongsTo
    {
        return $this->belongsTo(TutorClass::class, 'class_id', 'class_id');
    }

    /** Gia sư đã thanh toán */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'payment_transaction_id', 'payment_transaction_id');
    }
}
