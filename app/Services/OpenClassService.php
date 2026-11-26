<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\TutorClass;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OpenClassService
{
    public const RESERVATION_MINUTES = 5;

    /** Danh sách lớp đang tuyển và bộ lọc HĐ1. */
    public function browse(array $filters = []): LengthAwarePaginator
    {
        $this->releaseExpiredReservation();

        return TutorClass::query()
            ->with('tutorRequest')
            ->where('status', TutorClass::STATUS_OPEN)
            ->when($filters['subject'] ?? null, fn ($q, $value) => $q->where('subject', 'like', '%' . trim($value) . '%'))
            ->when($filters['grade_level'] ?? null, fn ($q, $value) => $q->where('grade_level', 'like', '%' . trim($value) . '%'))
            ->when($filters['learning_format'] ?? null, fn ($q, $value) => $q->where('learning_format', $value))
            ->when($filters['area'] ?? null, function ($q, $value) {
                $keyword = '%' . trim($value) . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('location', 'like', $keyword)
                        ->orWhereHas('customer', fn ($person) => $person->where('district', 'like', $keyword));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();
    }

    /** Xem chi tiết lớp công khai; không trả thông tin liên hệ khách hàng. */
    public function findForTutor(int $classId): TutorClass
    {
        $this->releaseExpiredReservation();

        return TutorClass::query()
            ->with('tutorRequest')
            ->whereIn('status', [TutorClass::STATUS_OPEN, TutorClass::STATUS_RESERVED])
            ->findOrFail($classId);
    }

    /**
     * HĐ3-HĐ4: khóa bản ghi trong transaction để chỉ một Gia sư giữ được lớp.
     * PaymentTransaction được tạo ngay trong cùng transaction với thao tác giữ chỗ.
     */
    public function claim(int $classId, int $tutorAccountId): PaymentTransaction
    {
        $this->releaseExpiredReservation();

        return DB::transaction(function () use ($classId, $tutorAccountId) {
            /** @var TutorClass $class */
            $class = TutorClass::query()->lockForUpdate()->findOrFail($classId);

            $this->normalizeLegacyReservation($class);

            if ($class->status === TutorClass::STATUS_RESERVED && $class->lock_expires_at && now()->greaterThanOrEqualTo($class->lock_expires_at)) {
                $this->releaseLockedClass($class);
                $class->refresh();
            }

            // Cho phép cùng Gia sư tiếp tục lại màn hình thanh toán đang còn hiệu lực.
            if ($class->status === TutorClass::STATUS_RESERVED
                && (int) $class->locked_by_account_id === $tutorAccountId
                && $class->lock_expires_at
                && now()->lt($class->lock_expires_at)) {
                $existing = PaymentTransaction::query()
                    ->where('class_id', $class->class_id)
                    ->where('account_id', $tutorAccountId)
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->where('reservation_expires_at', '>', now())
                    ->latest('payment_transaction_id')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            if ($class->status === TutorClass::STATUS_RESERVED && $class->isLocked()) {
                throw new DomainException('Lớp đang được Gia sư khác giữ chỗ, vui lòng thử lại sau.');
            }

            if ($class->status !== TutorClass::STATUS_OPEN) {
                throw new DomainException('Lớp này không còn khả dụng.');
            }

            if ((float) $class->enrollment_fee <= 0) {
                throw new DomainException('Lớp chưa được thiết lập phí nhận lớp hợp lệ. Vui lòng liên hệ Trung tâm.');
            }

            $now = now();
            $expiresAt = $now->copy()->addMinutes(self::RESERVATION_MINUTES);

            $class->update([
                'status' => TutorClass::STATUS_RESERVED,
                'locked_at' => $now,
                'locked_by_account_id' => $tutorAccountId,
                'lock_expires_at' => $expiresAt,
            ]);

            return PaymentTransaction::create([
                'class_id' => $class->class_id,
                'account_id' => $tutorAccountId,
                'amount' => $class->enrollment_fee,
                'transaction_at' => $now,
                'reservation_expires_at' => $expiresAt,
                'status' => PaymentTransaction::STATUS_PENDING,
                'payment_provider' => config('class_payment.driver', 'mock'),
            ]);
        }, 3);
    }

    /** Mở khóa các lớp đã hết hạn; giao dịch thanh toán vẫn được giữ để nhận diện callback đến muộn. */
    public function releaseExpiredReservation(): int
    {
        $this->normalizeLegacyReservation();

        return TutorClass::query()
            ->where('status', TutorClass::STATUS_RESERVED)
            ->whereNotNull('lock_expires_at')
            ->where('lock_expires_at', '<=', now())
            ->update([
                'status' => TutorClass::STATUS_OPEN,
                'locked_at' => null,
                'locked_by_account_id' => null,
                'lock_expires_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function normalizeLegacyReservation(?TutorClass $class = null): void
    {
        // Dữ liệu cũ chỉ có locked_at; suy ra hạn khóa 5 phút để không làm kẹt lớp đang processing.
        if ($class) {
            if ($class->status === TutorClass::STATUS_RESERVED && ! $class->lock_expires_at && $class->locked_at) {
                $class->update([
                    'locked_by_account_id' => $class->locked_by_account_id ?: $class->account_id,
                    'lock_expires_at' => $class->locked_at->copy()->addMinutes(self::RESERVATION_MINUTES),
                ]);
            }
            return;
        }

        TutorClass::query()
            ->where('status', TutorClass::STATUS_RESERVED)
            ->whereNull('lock_expires_at')
            ->whereNotNull('locked_at')
            ->chunkById(100, function ($classes) {
                foreach ($classes as $row) {
                    $row->update([
                        'locked_by_account_id' => $row->locked_by_account_id ?: $row->account_id,
                        'lock_expires_at' => $row->locked_at->copy()->addMinutes(self::RESERVATION_MINUTES),
                    ]);
                }
            }, 'class_id');
    }

    private function releaseLockedClass(TutorClass $class): void
    {
        $class->update([
            'status' => TutorClass::STATUS_OPEN,
            'locked_at' => null,
            'locked_by_account_id' => null,
            'lock_expires_at' => null,
        ]);
    }
}
