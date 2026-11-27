<?php

namespace App\Http\Controllers;

use App\Models\TutorClass;
use App\Models\TutorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorClassController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorClass::with('tutorRequest');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', "%{$keyword}%")
                  ->orWhere('grade_level', 'like', "%{$keyword}%")
                  ->orWhere('class_id', $keyword);
            });
        }

        $tutorClasses = $query->orderByDesc('class_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tutor_classes.index', compact('tutorClasses'));
    }

    public function show($id)
    {
        $tutorClass = TutorClass::with('tutorRequest')->findOrFail($id);

        return view('admin.tutor_classes.index', [
            'tutorClasses'  => TutorClass::with('tutorRequest')->orderByDesc('class_id')->paginate(10),
            'selectedClass' => $tutorClass,
        ]);
    }

    public function storeFromRequest(Request $request, $requestId)
    {
        $tutorRequest = TutorRequest::with('tutorClass')->findOrFail($requestId);

        if ($tutorRequest->status !== 'confirmed') {
            return redirect()
                ->back()
                ->with('error', 'Chỉ được tạo lớp học từ yêu cầu ở trạng thái Đã xác nhận.');
        }

        if ($tutorRequest->tutorClass) {
            return redirect()
                ->back()
                ->with('error', 'Yêu cầu này đã được tạo lớp học trước đó.');
        }

        $validated = $request->validate([
            'fee_per_session' => 'required|numeric|min:0',
            'enrollment_fee'  => 'required|numeric|min:0',
            'location'        => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($tutorRequest, $validated) {
            TutorClass::create([
                'tutor_request_id'  => $tutorRequest->tutor_request_id,
                'person_id'         => $tutorRequest->person_id,
                'account_id'        => auth()->id(),
                'subject'           => $tutorRequest->subject,
                'grade_level'       => $tutorRequest->grade_level,
                'learning_format'   => $tutorRequest->learning_format,
                'schedule'          => $tutorRequest->schedule,
                'sessions_per_week' => $tutorRequest->sessions_per_week,
                'fee_per_session'   => $validated['fee_per_session'],
                'enrollment_fee'    => $validated['enrollment_fee'],
                'location'          => $validated['location'] ?? null,
                'status'            => 'pending', // Đang tuyển gia sư
            ]);
        });

        return redirect()
            ->route('admin.tutor-classes.index')
            ->with('success', 'Tạo lớp học mới thành công và đã đưa lên danh sách tuyển gia sư.');
    }

    public function update(Request $request, $id)
    {
        $tutorClass = TutorClass::findOrFail($id);

        if (!$this->canModify($tutorClass)) {
            return redirect()
                ->back()
                ->with('error', 'Lớp học đã có gia sư nhận hoặc đang xử lý, không thể chỉnh sửa.');
        }

        $validated = $request->validate([
            'subject'           => 'required|string|max:100',
            'grade_level'       => 'required|string|max:50',
            'learning_format'   => 'required|string|max:50',
            'schedule'          => 'nullable|string',
            'sessions_per_week' => 'required|integer|min:1|max:14',
            'fee_per_session'   => 'required|numeric|min:0',
            'enrollment_fee'    => 'required|numeric|min:0',
            'location'          => 'nullable|string|max:500',
        ]);

        $tutorClass->update($validated);

        return redirect()
            ->route('admin.tutor-classes.index')
            ->with('success', 'Cập nhật thông tin lớp học thành công.');
    }

    public function toggleVisibility($id)
    {
        $tutorClass = TutorClass::findOrFail($id);

        if (!$this->canModify($tutorClass)) {
            return redirect()
                ->back()
                ->with('error', 'Không thể ẩn/hiện lớp khi đã có gia sư nhận.');
        }

        $newStatus = $tutorClass->status === 'hidden' ? 'pending' : 'hidden';
        $tutorClass->update(['status' => $newStatus]);

        return redirect()
            ->route('admin.tutor-classes.index')
            ->with('success', $newStatus === 'hidden' ? 'Đã ẩn lớp học khỏi danh sách tuyển.' : 'Đã hiển thị lại lớp học.');
    }

    public function destroy($id)
    {
        $tutorClass = TutorClass::findOrFail($id);

        if (!$this->canModify($tutorClass)) {
            return redirect()
                ->back()
                ->with('error', 'Không thể xóa lớp đã có gia sư nhận.');
        }

        $tutorClass->update(['status' => 'canceled']);

        return redirect()
            ->route('admin.tutor-classes.index')
            ->with('success', 'Đã hủy lớp học thành công.');
    }

    private function canModify(TutorClass $tutorClass): bool
    {
        return in_array($tutorClass->status, ['pending', 'hidden'], true);
    }
}