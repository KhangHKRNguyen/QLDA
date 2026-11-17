<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một dòng = một giấy tờ xác minh của gia sư (CCCD, bằng cấp, chứng chỉ...). */
class VerificationProfile extends Model
{
    protected $table = 'verification_profiles';

    protected $primaryKey = 'verification_profile_id';

    public $timestamps = false;

    protected $fillable = [
        'document_type', 'attachment_file', 'review_status',
        'processing_reason', 'supplement_deadline', 'person_id',
    ];

    /** document_type => [nhãn, bắt buộc khi đăng ký?] */
    public const TYPES = [
        'id_card'     => ['CCCD / giấy tờ tùy thân', true],
        'degree'      => ['Bằng cấp / trình độ học vấn', true],
        'certificate' => ['Chứng chỉ (nếu có)', false],
        'experience'  => ['Minh chứng kinh nghiệm giảng dạy (nếu có)', false],
    ];

    /** review_status => [nhãn, tone badge] */
    public const STATUSES = [
        'pending'          => ['Chờ duyệt', 'info'],
        'approved'         => ['Đạt', 'success'],
        'needs_supplement' => ['Cần bổ sung', 'warning'],
        'rejected'         => ['Không đạt', 'danger'],
    ];

    protected function casts(): array
    {
        return ['supplement_deadline' => 'date'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type][0] ?? $this->document_type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->review_status][0] ?? $this->review_status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->review_status][1] ?? 'neutral';
    }

    /** Đã quá hạn bổ sung (tính hết ngày deadline) */
    public function isOverdue(): bool
    {
        return $this->supplement_deadline !== null
            && $this->supplement_deadline->copy()->endOfDay()->isPast();
    }
}
