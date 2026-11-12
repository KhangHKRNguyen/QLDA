<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorSearchRequestController extends Controller
{
    // Hiển thị trang chủ khách hàng.
    public function index()
    {
        return view('customer.home');
    }

    // Hiển thị form đăng ký tìm gia sư.
    public function create()
    {
        return view('customer.tutor-request.create');
    }

    // Xử lý gửi yêu cầu tìm gia sư.
    public function store(Request $request)
    {
        // Kiểm tra dữ liệu khách hàng gửi lên.
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'phone_number' => [
    'required',
    'string',
    'digits:10',
    'regex:/^(03|05|07|08|09)[0-9]{8}$/',
],
            'email' => 'nullable|email|max:255',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string',

            'subject' => 'required|string|max:100',
            'grade_level' => 'required|string|max:50',
           'learning_format' => 'required|in:Trực tiếp,Trực tuyến,Linh hoạt',
            'sessions_per_week' => 'required|integer|min:1',
            'desired_tuition_fee' => 'nullable|numeric|min:0',
            'schedule' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Lưu thông tin người liên hệ và yêu cầu tìm gia sư.
        DB::transaction(function () use ($validated) {

            // Tạo người liên hệ mới.
            $personId = DB::table('people')->insertGetId([
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'district' => $validated['district'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            // Tạo yêu cầu tìm gia sư mới.
            DB::table('tutor_requests')->insert([
                'person_id' => $personId,
                'subject' => $validated['subject'],
                'grade_level' => $validated['grade_level'],
                'learning_format' => $validated['learning_format'],
                'sessions_per_week' => $validated['sessions_per_week'],
                'desired_tuition_fee' => $validated['desired_tuition_fee'] ?? null,
                'schedule' => $validated['schedule'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        // Thông báo gửi yêu cầu thành công.
        return redirect()
            ->route('customer.tutor-requests.create')
            ->with('success', 'Gửi yêu cầu tìm gia sư thành công!');
    }
}
