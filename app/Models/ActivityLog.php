<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $primaryKey = 'activity_log_id';

    public $timestamps = false;

    protected $fillable = ['action', 'occurred_at', 'affected_object', 'account_id'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    /** Tài khoản thực hiện thao tác */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
