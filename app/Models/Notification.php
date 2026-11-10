<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Thông báo gửi tới người dùng (bảng notifications của dự án) */
class Notification extends Model
{
    protected $primaryKey = 'notification_id';

    public $timestamps = false;

    protected $fillable = ['content', 'delivery_channel', 'is_read', 'account_id'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
