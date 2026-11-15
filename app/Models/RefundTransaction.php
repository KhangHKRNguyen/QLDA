<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Giao dịch hoàn tiền qua cổng thanh toán — chỉ tồn tại khi mức hoàn > 0 */
class RefundTransaction extends Model
{
    protected $primaryKey = 'refund_transaction_id';

    public $timestamps = false;

    protected $guarded = ['refund_transaction_id'];

    /** status => [nhãn, tone] */
    public const STATUSES = [
        'pending' => ['Chờ cổng thanh toán', 'info'],
        'success' => ['Thành công', 'success'],
        'failed'  => ['Thất bại', 'danger'],
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:0',
            'refunded_at'   => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class, 'refund_request_id', 'refund_request_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'neutral';
    }
}
