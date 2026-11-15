<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Person;
use App\Models\Role;
use App\Models\VerificationProfile;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Quy trình 2.1.5 — Quản lý tài khoản gia sư:
 * đăng ký + nộp hồ sơ → QLGS duyệt / yêu cầu bổ sung (15 ngày) / từ chối → gia sư bổ sung
 * → tự nhắc trước 3 ngày, tự hủy khi quá hạn, tự xóa vĩnh viễn sau 30 ngày.
 *
 * Trạng thái dùng lại từ Account::STATUSES:
 *   pending_verification → supplement_required → (pending_verification | cancelled)
 *   pending_verification → active | rejected
 * Xóa tài khoản do gia sư chủ động: dùng AccountService::deactivate() (đã kiểm tra điều kiện).
 */
class TutorRegistrationService
{
    public const TUTOR_ROLE = 'Gia sư';
    public const STAFF_ROLE = 'Nhân viên Quản lý Gia sư';

    public const SUPPLEMENT_DAYS = 15;
    public const REMIND_BEFORE_DAYS = 3;
    public const PURGE_AFTER_DAYS = 30;

    /** Disk private (storage/app/private) — không truy cập được qua URL, phải qua controller có kiểm tra quyền */
    public const DISK = 'local';

    /** Gia sư đăng ký. $files: [document_type => UploadedFile]. Username = email. */
    public function register(array $data, array $files): Account
    {
        $roleId = Role::where('role_name', self::TUTOR_ROLE)->value('role_id');
        if (! $roleId) {
            throw new DomainException('Chưa có vai trò "Gia sư" trong hệ thống. Hãy chạy RoleSeeder.');
        }

        return DB::transaction(function () use ($data, $files, $roleId) {
            // Đăng ký lại sau khi bị từ chối / hủy: xóa hồ sơ cũ trước
            Account::where('username', $data['email'])
                ->whereIn('status', ['cancelled', 'rejected'])
                ->get()
                ->each(fn (Account $old) => $this->purgeAccount($old));

            $person = Person::create(Arr::only($data, [
                'full_name', 'phone_number', 'email', 'date_of_birth', 'gender', 'address', 'district',
            ]));

            $account = Account::create([
                'username'          => $data['email'],
                'password'          => $data['password'], // model tự hash
                'role_id'           => $roleId,
                'person_id'         => $person->person_id,
                'status'            => 'pending_verification',
                'status_updated_at' => now(),
            ]);

            foreach ($files as $type => $file) {
                if ($file instanceof UploadedFile && isset(VerificationProfile::TYPES[$type])) {
                    VerificationProfile::create([
                        'document_type'   => $type,
                        'attachment_file' => $file->store("verification/{$account->account_id}", self::DISK),
                        'review_status'   => 'pending',
                        'person_id'       => $person->person_id,
                    ]);
                }
            }

            $this->notifyStaff("Có hồ sơ gia sư mới chờ xác minh: {$person->full_name}.");
            $this->log($account, 'Đăng ký tài khoản gia sư', $account->account_id);

            return $account;
        });
    }

    /** QLGS: hồ sơ đạt → kích hoạt tài khoản */
    public function approve(Account $account, ?int $actorId): void
    {
        $this->assertStatus($account, ['pending_verification'], 'Chỉ duyệt được hồ sơ đang chờ xác minh.');

        DB::transaction(function () use ($account, $actorId) {
            VerificationProfile::where('person_id', $account->person_id)->update([
                'review_status' => 'approved', 'processing_reason' => null, 'supplement_deadline' => null,
            ]);
            $this->changeStatus($account, 'active');
            $this->notify($account, 'Hồ sơ của bạn đã được duyệt. Tài khoản đã được kích hoạt, bạn có thể xem và nhận lớp.');
            $this->log($account, 'Duyệt hồ sơ gia sư', $actorId);
        });
    }

    /** QLGS: yêu cầu bổ sung/chỉnh sửa trong 15 ngày. $documentIds = các giấy tờ chưa đạt. */
    public function requestSupplement(Account $account, array $documentIds, string $reason, ?int $actorId): void
    {
        $this->assertStatus($account, ['pending_verification'], 'Chỉ yêu cầu bổ sung với hồ sơ đang chờ xác minh.');

        $flagged = VerificationProfile::where('person_id', $account->person_id)
            ->whereIn('verification_profile_id', $documentIds)
            ->pluck('verification_profile_id');
        if ($flagged->isEmpty()) {
            throw new DomainException('Hãy chọn ít nhất một giấy tờ cần bổ sung.');
        }

        $deadline = now()->addDays(self::SUPPLEMENT_DAYS);

        DB::transaction(function () use ($account, $flagged, $reason, $deadline, $actorId) {
            // Giấy tờ không bị đánh dấu coi như đạt
            VerificationProfile::where('person_id', $account->person_id)
                ->whereNotIn('verification_profile_id', $flagged)
                ->update(['review_status' => 'approved', 'processing_reason' => null, 'supplement_deadline' => null]);
            VerificationProfile::whereIn('verification_profile_id', $flagged)->update([
                'review_status' => 'needs_supplement', 'processing_reason' => $reason,
                'supplement_deadline' => $deadline->toDateString(),
            ]);

            $this->changeStatus($account, 'supplement_required');
            $this->notify($account, "Hồ sơ của bạn cần bổ sung/chỉnh sửa trước ngày {$deadline->format('d/m/Y')}. Lý do: {$reason}");
            $this->log($account, "Yêu cầu bổ sung hồ sơ – {$reason}", $actorId);
        });
    }

    /** QLGS: từ chối hồ sơ */
    public function reject(Account $account, string $reason, ?int $actorId): void
    {
        $this->assertStatus($account, ['pending_verification', 'supplement_required'], 'Không thể từ chối hồ sơ ở trạng thái hiện tại.');

        DB::transaction(function () use ($account, $reason, $actorId) {
            VerificationProfile::where('person_id', $account->person_id)->update([
                'review_status' => 'rejected', 'processing_reason' => $reason, 'supplement_deadline' => null,
            ]);
            $this->changeStatus($account, 'rejected');
            $this->notify($account, "Hồ sơ đăng ký của bạn không được duyệt. Lý do: {$reason}");
            $this->log($account, "Từ chối hồ sơ gia sư – {$reason}", $actorId);
        });
    }

    /** Gia sư gửi bổ sung. $files: [verification_profile_id => UploadedFile] — phải đủ mọi giấy tờ được yêu cầu. */
    public function submitSupplement(Account $account, array $files): void
    {
        $this->assertStatus($account, ['supplement_required'], 'Hồ sơ của bạn hiện không có yêu cầu bổ sung.');

        $docs = VerificationProfile::where('person_id', $account->person_id)
            ->where('review_status', 'needs_supplement')->get();
        if ($docs->isEmpty()) {
            throw new DomainException('Không có giấy tờ nào cần bổ sung.');
        }
        if ($docs->contains(fn (VerificationProfile $d) => $d->isOverdue())) {
            throw new DomainException('Đã quá hạn bổ sung hồ sơ.');
        }
        foreach ($docs as $doc) {
            if (! ($files[$doc->verification_profile_id] ?? null) instanceof UploadedFile) {
                throw new DomainException('Vui lòng tải lên đủ các giấy tờ được yêu cầu bổ sung.');
            }
        }

        DB::transaction(function () use ($account, $docs, $files) {
            foreach ($docs as $doc) {
                if ($doc->attachment_file) {
                    Storage::disk(self::DISK)->delete($doc->attachment_file);
                }
                $doc->update([
                    'attachment_file'     => $files[$doc->verification_profile_id]->store("verification/{$account->account_id}", self::DISK),
                    'review_status'       => 'pending',
                    'processing_reason'   => null,
                    'supplement_deadline' => null,
                ]);
            }
            $this->changeStatus($account, 'pending_verification');
            $this->notifyStaff("Gia sư {$account->full_name} đã bổ sung hồ sơ, chờ xác minh lại.");
            $this->log($account, 'Bổ sung hồ sơ xác minh', $account->account_id);
        });
    }

    /**
     * Chạy hằng ngày (php artisan tutor:process-registrations):
     * nhắc trước 3 ngày → hủy khi quá hạn 15 ngày → xóa vĩnh viễn sau 30 ngày kể từ khi hủy.
     */
    public function processScheduled(): array
    {
        // 1. Nhắc trước 3 ngày
        $remindDate = today()->addDays(self::REMIND_BEFORE_DAYS)->toDateString();
        $remindIds = VerificationProfile::where('review_status', 'needs_supplement')
            ->whereDate('supplement_deadline', $remindDate)->pluck('person_id')->unique();
        $reminded = Account::whereIn('person_id', $remindIds)->where('status', 'supplement_required')->get();
        $reminded->each(fn (Account $a) => $this->notify($a,
            'Hồ sơ của bạn còn ' . self::REMIND_BEFORE_DAYS . ' ngày để bổ sung (hạn ' . today()->addDays(self::REMIND_BEFORE_DAYS)->format('d/m/Y') . '). Quá hạn đăng ký sẽ bị hủy.'));

        // 2. Quá hạn → Đã hủy đăng ký
        $expiredIds = VerificationProfile::where('review_status', 'needs_supplement')
            ->whereDate('supplement_deadline', '<', today()->toDateString())->pluck('person_id')->unique();
        $cancelled = Account::whereIn('person_id', $expiredIds)->where('status', 'supplement_required')->get();
        $cancelled->each(function (Account $a) {
            $this->changeStatus($a, 'cancelled');
            $this->notify($a, 'Đăng ký của bạn đã bị hủy do quá hạn bổ sung hồ sơ. Bạn có thể đăng ký lại.');
            $this->log($a, 'Tự động hủy đăng ký (quá hạn bổ sung hồ sơ)', null);
        });

        // 3. Sau 30 ngày kể từ khi hủy mà không đăng ký lại → xóa vĩnh viễn hồ sơ
        $purged = Account::where('status', 'cancelled')
            ->where('status_updated_at', '<=', now()->subDays(self::PURGE_AFTER_DAYS))->get();
        $purged->each(fn (Account $a) => $this->purgeAccount($a));

        return ['reminded' => $reminded->count(), 'cancelled' => $cancelled->count(), 'purged' => $purged->count()];
    }

    /** Xóa vĩnh viễn một đăng ký chưa được kích hoạt: giấy tờ (file + dòng), thông báo, nhật ký, tài khoản, thông tin cá nhân */
    public function purgeAccount(Account $account): void
    {
        DB::transaction(function () use ($account) {
            $personId = $account->person_id;

            VerificationProfile::where('person_id', $personId)->get()->each(function (VerificationProfile $d) {
                if ($d->attachment_file) {
                    Storage::disk(self::DISK)->delete($d->attachment_file);
                }
            });
            VerificationProfile::where('person_id', $personId)->delete();
            Notification::where('account_id', $account->account_id)->delete();
            ActivityLog::where('account_id', $account->account_id)->delete();
            Account::whereKey($account->account_id)->delete();

            if (! Account::where('person_id', $personId)->exists()) {
                Person::whereKey($personId)->delete();
            }
        });
    }

    // ------------------------------------------------------------------ helpers

    private function assertStatus(Account $account, array $allowed, string $message): void
    {
        if (! in_array($account->status, $allowed, true)) {
            throw new DomainException($message);
        }
    }

    private function changeStatus(Account $account, string $status): void
    {
        // Query trực tiếp vì $account có thể mang cột join (full_name, role_name...)
        Account::whereKey($account->account_id)->update(['status' => $status, 'status_updated_at' => now()]);
        $account->status = $status;
    }

    private function notify(Account $account, string $content): void
    {
        Notification::create([
            'content'          => $content,
            'delivery_channel' => 'system',
            'account_id'       => $account->account_id,
        ]);
    }

    /** Thông báo cho mọi Nhân viên Quản lý Gia sư đang hoạt động */
    private function notifyStaff(string $content): void
    {
        Account::whereHas('role', fn ($q) => $q->where('role_name', self::STAFF_ROLE))
            ->where('status', 'active')
            ->pluck('account_id')
            ->each(fn ($id) => Notification::create([
                'content' => $content, 'delivery_channel' => 'system', 'account_id' => $id,
            ]));
    }

    /** $actorId = người thực hiện; null thì ghi theo chính tài khoản bị tác động (giống AccountService) */
    private function log(Account $account, string $action, ?int $actorId): void
    {
        ActivityLog::create([
            'action'          => mb_substr($action, 0, 100),
            'occurred_at'     => now(),
            'affected_object' => AccountService::objectRef($account),
            'account_id'      => $actorId ?? $account->account_id,
        ]);
    }
}
