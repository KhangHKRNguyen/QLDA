<?php

namespace App\Http\Controllers;

use App\Models\TutorClass;
use App\Models\TutorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorClassController extends Controller
{
    // Cài đặt Phân quyền
    public function __construct()
    {
        $this->middleware('auth:api');
        // Trừ hàm Xem và hàm Gia sư nhận lớp, còn lại (Thêm, Sửa, Xóa) chỉ có QLKH được làm
        $this->middleware('role:nhan_vien_qlkh')->except(['index', 'show', 'assignClass']);
        
        // Cấp quyền riêng cho Gia sư bấm nhận lớp
        $this->middleware('role:gia_su')->only(['assignClass']);
    }

    // 1. Xem danh sách Lớp học (Public cho Gia sư / Quản lý)
    public function index(Request $request)
    {
        $query = TutorClass::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($classes);
    }

    // 2. Xem chi tiết 1 Lớp học
    public function show($id)
    {
        $tutorClass = TutorClass::findOrFail($id);
        return response()->json($tutorClass);
    }

    // 3. Tạo Lớp học kế thừa từ Yêu cầu (QLKH thực hiện)
    public function storeFromRequest(Request $request, $requestId)
    {
        $tutorReq = TutorRequest::findOrFail($requestId);
        
        if ($tutorReq->status !== 'confirmed') {
            return response()->json(['message' => 'Chỉ tạo lớp từ yêu cầu đã xác nhận.'], 403);
        }

        $validated = $request->validate([
            'fee_per_session' => 'required|numeric|min:0',
            'enrollment_fee'  => 'required|numeric|min:0',
            'location'        => 'nullable|string'
        ]);

        $tutorClass = DB::transaction(function () use ($tutorReq, $validated) {
            return TutorClass::create([
                'tutor_request_id'  => $tutorReq->tutor_request_id,
                'person_id'         => $tutorReq->person_id,
                'account_id'        => auth()->id(), // ID của nhân viên tạo lớp
                'subject'           => $tutorReq->subject,
                'grade_level'       => $tutorReq->grade_level,
                'learning_format'   => $tutorReq->learning_format,
                'schedule'          => $tutorReq->schedule,
                'sessions_per_week' => $tutorReq->sessions_per_week,
                'fee_per_session'   => $validated['fee_per_session'],
                'enrollment_fee'    => $validated['enrollment_fee'],
                'location'          => $validated['location'],
                'status'            => 'pending' // pending tương đương trạng thái 'Đang tuyển'
            ]);
        });

        return response()->json(['message' => 'Tạo lớp thành công', 'data' => $tutorClass], 201);
    }

    // 4. Cập nhật thông tin Lớp học (Chỉ khi chưa có Gia sư nhận)
    public function update(Request $request, $id)
    {
        $tutorClass = TutorClass::findOrFail($id);

        if (!$tutorClass->canEditOrDelete()) {
            return response()->json(['message' => 'Không thể chỉnh sửa vì lớp đã có giao dịch.'], 403);
        }

        $validated = $request->validate([
            'subject'           => 'sometimes|required|string|max:100',
            'schedule'          => 'nullable|string',
            'sessions_per_week' => 'sometimes|required|integer|min:1',
            'fee_per_session'   => 'sometimes|required|numeric|min:0',
            'enrollment_fee'    => 'sometimes|required|numeric|min:0',
            'location'          => 'nullable|string',
            'status'            => 'sometimes|required|in:pending,hidden,canceled',
        ]);

        $tutorClass->update($validated);

        return response()->json([
            'message' => 'Cập nhật thông tin lớp thành công',
            'data' => $tutorClass
        ]);
    }

    // 5. Gia sư thao tác "Nhận lớp" (Pessimistic Locking - Chống giật lớp)
    public function assignClass(Request $request, $classId)
    {
        $tutorId = $request->user()->account_id;

        try {
            DB::beginTransaction();

            $tutorClass = TutorClass::where('class_id', $classId)->lockForUpdate()->firstOrFail();

            if ($tutorClass->status !== 'pending') {
                DB::rollBack();
                return response()->json(['message' => 'Lớp này không còn khả dụng.'], 400);
            }

            if ($tutorClass->isLocked()) {
                DB::rollBack();
                return response()->json(['message' => 'Lớp đang được gia sư khác giữ chỗ chờ thanh toán.'], 409);
            }

            $tutorClass->update([
                'status'     => 'processing',
                'account_id' => $tutorId, // Ghi danh tạm thời gia sư
                'locked_at'  => now() // Bắt đầu tính giờ đếm ngược 5 phút
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Giữ chỗ thành công. Vui lòng thanh toán trong 5 phút.',
                'locked_at' => $tutorClass->locked_at,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống, vui lòng thử lại sau.'], 500);
        }
    }
}