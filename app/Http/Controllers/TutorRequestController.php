<?php

namespace App\Http\Controllers;

use App\Models\TutorRequest;
use Illuminate\Http\Request;

class TutorRequestController extends Controller
{
    // Cài đặt Phân quyền
    public function __construct()
    {
        $this->middleware('auth:api'); // Bắt buộc đăng nhập
        // Chỉ Nhân viên QLKH được Thêm, Sửa, Xóa
        $this->middleware('role:nhan_vien_qlkh')->only(['store', 'update', 'destroy']);
        // QLKH và Quản lý/Giám đốc đều được phép Xem
        $this->middleware('role:nhan_vien_qlkh|quan_ly|giam_doc')->only(['index', 'show']);
    }

    // 1. Xem danh sách Yêu cầu (Có phân trang và Lọc)
    public function index(Request $request)
    {
        $query = TutorRequest::query();

        // Lọc theo trạng thái nếu giao diện truyền lên (VD: ?status=pending)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Lấy danh sách, sắp xếp mới nhất lên đầu, mỗi trang 10 bản ghi
        $tutorRequests = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json($tutorRequests);
    }

    // 2. Xem chi tiết 1 Yêu cầu
    public function show($id)
    {
        // Kèm theo thông tin người dùng (Khách hàng) đã gửi yêu cầu
        $tutorRequest = TutorRequest::findOrFail($id);
        return response()->json($tutorRequest);
    }

    // 3. Tạo mới Yêu cầu
    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id'           => 'required|exists:people,person_id',
            'subject'             => 'required|string|max:100',
            'grade_level'         => 'required|string|max:50',
            'learning_format'     => 'required|string|max:50',
            'schedule'            => 'nullable|string',
            'sessions_per_week'   => 'required|integer|min:1',
            'desired_tuition_fee' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        // Cài đặt SLA 4 tiếng làm việc
        $validated['sla_deadline'] = now()->addHours(4);
        $validated['status'] = 'pending'; // Mặc định là chờ xử lý

        $tutorReq = TutorRequest::create($validated);

        return response()->json([
            'message' => 'Gửi yêu cầu thành công', 
            'data' => $tutorReq
        ], 201);
    }

    // 4. Sửa thông tin Yêu cầu (Cập nhật)
    public function update(Request $request, $id)
    {
        $tutorRequest = TutorRequest::findOrFail($id);

        $validated = $request->validate([
            'subject'             => 'sometimes|required|string|max:100',
            'grade_level'         => 'sometimes|required|string|max:50',
            'learning_format'     => 'sometimes|required|string|max:50',
            'schedule'            => 'nullable|string',
            'sessions_per_week'   => 'sometimes|required|integer|min:1',
            'desired_tuition_fee' => 'nullable|numeric|min:0',
            'status'              => 'sometimes|required|in:pending,confirmed,canceled',
            'notes'               => 'nullable|string',
        ]);

        $tutorRequest->update($validated);

        return response()->json([
            'message' => 'Cập nhật yêu cầu thành công',
            'data' => $tutorRequest
        ]);
    }

    // 5. Xóa / Hủy Yêu cầu
    public function destroy(Request $request, $id)
    {
        $tutorReq = TutorRequest::with('tutorClass')->findOrFail($id);
        
        if ($tutorReq->tutorClass) {
            return response()->json(['message' => 'Không thể hủy vì đã tạo lớp học.'], 422);
        }
        
        $request->validate(['cancellation_reason' => 'required|string|min:10']);
        
        $tutorReq->update([
            'status' => 'canceled',
            'cancellation_reason' => $request->cancellation_reason
        ]);
        
        return response()->json(['message' => 'Hủy yêu cầu thành công']);
    }
}