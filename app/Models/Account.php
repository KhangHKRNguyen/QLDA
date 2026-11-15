<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Account extends Model
{
    protected $primaryKey = 'account_id';

    public $timestamps = false;

    protected $fillable = [
        'username', 'password', 'status', 'lock_reason', 'lock_expires_at',
        'status_updated_at', 'role_id', 'person_id',
    ];

    protected $hidden = ['password'];

    /** Giá trị cột accounts.status => [nhãn hiển thị, tone của badge] */
    public const STATUSES = [
        'active'               => ['Hoạt động', 'success'],
        'locked'               => ['Tạm khóa', 'warning'],
        'suspended'            => ['Đình chỉ vĩnh viễn', 'danger'],
        'pending_verification' => ['Chờ xác minh', 'info'],
        'supplement_required'  => ['Yêu cầu bổ sung', 'warning'],
        'incomplete'           => ['Chưa hoàn tất', 'neutral'],
        'rejected'             => ['Đã từ chối', 'danger'],
        'inactive'             => ['Ngừng hoạt động', 'neutral'],
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'lock_expires_at'   => 'datetime',
            'status_updated_at' => 'datetime',
            'date_of_birth'     => 'date', // cột của people, có khi dùng scopeWithProfile()
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'account_id', 'account_id');
    }

    /** Gộp sẵn thông tin people + roles vào từng dòng (full_name, email, role_name, ...) */
    public function scopeWithProfile(Builder $query): Builder
    {
        return $query
            ->join('people', 'people.person_id', '=', 'accounts.person_id')
            ->join('roles', 'roles.role_id', '=', 'accounts.role_id')
            ->select('accounts.*', 'roles.role_name',
                'people.full_name', 'people.phone_number', 'people.email', 'people.date_of_birth',
                'people.gender', 'people.address', 'people.district');
    }

    /** Chữ cái đầu của tên (để làm avatar) */
    protected function initial(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(mb_substr(Str::afterLast((string) $this->full_name, ' '), 0, 1)));
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
