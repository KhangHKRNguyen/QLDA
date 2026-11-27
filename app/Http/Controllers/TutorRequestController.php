<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\TutorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TutorRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorRequest::with(['tutorClass']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', "%{$keyword}%")
                  ->orWhere('grade_level', 'like', "%{$keyword}%")
                  ->orWhere('tutor_request_id', $keyword);
            });
        }

        $tutorRequests = $query->orderByDesc('tutor_request_id')
            ->paginate(10)
            ->withQueryString();

        $people = Person::orderByDesc('person_id')->get();

        return view('admin.tutor_requests.index', compact('tutorRequests', 'people'));
    }

    public function show($id)
    {
        $tutorRequest = TutorRequest::with('tutorClass')->findOrFail($id);

        return view('admin.tutor_requests.index', [
            'tutorRequests' => TutorRequest::with('tutorClass')->orderByDesc('tutor_request_id')->paginate(10),
            'people'        => Person::orderByDesc('person_id')->get(),
            'selectedRequest' => $tutorRequest,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id'           => 'nullable|exists:people,person_id',
            'full_name'           => 'required_without:person_id|nullable|string|max:150',
            'phone'               => 'required_without:person_id|nullable|string|max:20',
            'email'               => 'nullable|email|max:150',
            'address'             => 'nullable|string|max:255',
            'subject'             => 'required|string|max:100',
            'grade_level'         => 'required|string|max:50',
            'learning_format'     => 'required|string|max:50',
            'schedule'            => 'nullable|string',
            'sessions_per_week'   => 'required|integer|min:1|max:14',
            'desired_tuition_fee' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ], [
            'full_name.required_without' => 'Vui lòng chọn khách hàng có sẵn hoặc nhập họ tên phụ huynh mới.',
            'phone.required_without'     => 'Vui lòng nhập số điện thoại liên hệ của phụ huynh.',
        ]);

        DB::transaction(function () use ($validated) {
            $personId = $validated['person_id'] ?? null;

            // Nếu phụ huynh gọi tổng đài chưa có trong bảng people thì tạo mới
            if (!$personId) {
                $personData = ['full_name' => $validated['full_name']];
                if (Schema::hasColumn('people', 'phone')) {
                    $personData['phone'] = $validated['phone'];
                } elseif (Schema::hasColumn('people', 'phone_number')) {
                    $personData['phone_number'] = $validated['phone'];
                }
                if (Schema::hasColumn('people', 'email') && !empty($validated['email'])) {
                    $personData['email'] = $validated['email'];
                }
                if (Schema::hasColumn('people', 'address') && !empty($validated['address'])) {
                    $personData['address'] = $validated['address'];
                }

                $person = Person::create($personData);
                $personId = $person->person_id;
            }

            $requestData = [
                'person_id'           => $personId,
                'subject'             => $validated['subject'],
                'grade_level'         => $validated['grade_level'],
                'learning_format'     => $validated['learning_format'],
                'schedule'            => $validated['schedule'] ?? null,
                'sessions_per_week'   => $validated['sessions_per_week'],
                'desired_tuition_fee' => $validated['desired_tuition_fee'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'status'              => 'pending',
            ];

            if (Schema::hasColumn('tutor_requests', 'sla_deadline')) {
                $requestData['sla_deadline'] = now()->addHours(4);
            }

            TutorRequest::create($requestData);
        });

        return redirect()
            ->route('admin.tutor-requests.index')
            ->with('success', 'Tiếp nhận yêu cầu tìm gia sư thành công.');
    }

    public function update(Request $request, $id)
    {
        $tutorRequest = TutorRequest::findOrFail($id);

        $validated = $request->validate([
            'subject'             => 'required|string|max:100',
            'grade_level'         => 'required|string|max:50',
            'learning_format'     => 'required|string|max:50',
            'schedule'            => 'nullable|string',
            'sessions_per_week'   => 'required|integer|min:1|max:14',
            'desired_tuition_fee' => 'nullable|numeric|min:0',
            'status'              => 'required|in:pending,confirmed,canceled',
            'notes'               => 'nullable|string',
        ]);

        // Nếu đã tạo lớp thì không cho đổi ngược trạng thái về canceled
        if ($tutorRequest->tutorClass && $validated['status'] === 'canceled') {
            return redirect()
                ->back()
                ->with('error', 'Yêu cầu này đã tạo lớp học, không thể chuyển sang trạng thái Đã hủy.');
        }

        $tutorRequest->update($validated);

        return redirect()
            ->route('admin.tutor-requests.index')
            ->with('success', 'Cập nhật thông tin yêu cầu thành công.');
    }

    public function destroy(Request $request, $id)
    {
        $tutorRequest = TutorRequest::with('tutorClass')->findOrFail($id);

        if ($tutorRequest->tutorClass) {
            return redirect()
                ->back()
                ->with('error', 'Không thể hủy yêu cầu vì đã được tạo thành lớp học.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ], [
            'cancellation_reason.required' => 'Bắt buộc nhập lý do hủy yêu cầu.',
            'cancellation_reason.min'      => 'Lý do hủy cần tối thiểu 5 ký tự.',
        ]);

        $tutorRequest->update([
            'status'              => 'canceled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return redirect()
            ->route('admin.tutor-requests.index')
            ->with('success', 'Đã hủy yêu cầu tìm gia sư.');
    }
}