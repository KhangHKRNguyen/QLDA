@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Quản lý Danh sách Lớp học</h1>
            <p class="text-sm text-gray-500">Quản lý lớp đang tuyển, ẩn/hiện lớp và khóa chỉnh sửa khi lớp đã có gia sư</p>
        </div>
        <a href="{{ route('admin.tutor-requests.index') }}"
           class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            + Tạo lớp từ Yêu cầu đã xác nhận
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Bộ lọc --}}
    <form method="GET" action="{{ route('admin.tutor-classes.index') }}" class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Tìm kiếm (Mã lớp, Môn, Cấp lớp)</label>
            <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Nhập từ khóa..."
                   class="w-full border-gray-300 rounded-lg text-sm">
        </div>
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
            <select name="status" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" @selected(request('status') === 'pending')>Đang tuyển gia sư</option>
                <option value="hidden" @selected(request('status') === 'hidden')>Đã ẩn</option>
                <option value="processing" @selected(request('status') === 'processing')>Đang giữ chỗ / Chờ TT</option>
                <option value="assigned" @selected(request('status') === 'assigned')>Đã có gia sư</option>
                <option value="canceled" @selected(request('status') === 'canceled')>Đã hủy</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">Lọc</button>
        <a href="{{ route('admin.tutor-classes.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Đặt lại</a>
    </form>

    {{-- Bảng danh sách lớp --}}
    <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Mã lớp</th>
                    <th class="px-4 py-3 text-left">YC gốc</th>
                    <th class="px-4 py-3 text-left">Môn học / Cấp lớp</th>
                    <th class="px-4 py-3 text-left">Học phí / buổi</th>
                    <th class="px-4 py-3 text-left">Phí nhận lớp</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($tutorClasses as $cls)
                    @php
                        $canModify = in_array($cls->status, ['pending', 'hidden'], true);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-900">CLS-{{ str_pad($cls->class_id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 text-gray-600">#{{ $cls->tutor_request_id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $cls->subject }} - {{ $cls->grade_level }}</div>
                            <div class="text-xs text-gray-500">{{ $cls->learning_format }} • {{ $cls->sessions_per_week }} buổi/tuần</div>
                        </td>
                        <td class="px-4 py-3">{{ number_format($cls->fee_per_session) }} đ</td>
                        <td class="px-4 py-3">{{ number_format($cls->enrollment_fee) }} đ</td>
                        <td class="px-4 py-3">
                            @if($cls->status === 'pending')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Đang tuyển</span>
                            @elseif($cls->status === 'hidden')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700">Đã ẩn</span>
                            @elseif($cls->status === 'canceled')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Đã hủy</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ strtoupper($cls->status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                            @if($canModify)
                                <button type="button"
                                        onclick='openEditClassModal(@json($cls))'
                                        class="px-2.5 py-1 border border-gray-300 text-gray-700 rounded text-xs hover:bg-gray-100">
                                    Sửa
                                </button>

                                <form action="{{ route('admin.tutor-classes.toggle-visibility', $cls->class_id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">
                                        {{ $cls->status === 'hidden' ? 'Hiện lớp' : 'Ẩn lớp' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.tutor-classes.destroy', $cls->class_id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Xác nhận hủy lớp học này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                                        Xóa
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 italic">Đã khóa chỉnh sửa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có lớp học nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $tutorClasses->links() }}</div>
</div>

{{-- Modal Sửa thông tin lớp học --}}
<div id="modal-edit-class" class="hidden fixed inset-0 bg-black/50 z-50 p-4 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 space-y-4">
        <h3 class="text-lg font-bold text-gray-800">Chỉnh sửa Thông tin Lớp học</h3>
        <form id="form-edit-class" method="POST" class="space-y-3 text-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Môn học *</label>
                    <input type="text" name="subject" id="edit-cls-subject" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Cấp lớp *</label>
                    <input type="text" name="grade_level" id="edit-cls-grade" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Hình thức *</label>
                    <input type="text" name="learning_format" id="edit-cls-format" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Số buổi / tuần *</label>
                    <input type="number" name="sessions_per_week" id="edit-cls-sessions" min="1" max="14" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Học phí / buổi *</label>
                    <input type="number" name="fee_per_session" id="edit-cls-fee" min="0" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Phí nhận lớp *</label>
                    <input type="number" name="enrollment_fee" id="edit-cls-enrollment" min="0" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Địa điểm</label>
                <input type="text" name="location" id="edit-cls-location" class="w-full border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Lịch học</label>
                <textarea name="schedule" id="edit-cls-schedule" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('modal-edit-class').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Đóng</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditClassModal(cls) {
        const form = document.getElementById('form-edit-class');
        form.action = `/admin/lop-hoc/${cls.class_id}`;
        document.getElementById('edit-cls-subject').value = cls.subject ?? '';
        document.getElementById('edit-cls-grade').value = cls.grade_level ?? '';
        document.getElementById('edit-cls-format').value = cls.learning_format ?? '';
        document.getElementById('edit-cls-sessions').value = cls.sessions_per_week ?? 2;
        document.getElementById('edit-cls-fee').value = cls.fee_per_session ?? 0;
        document.getElementById('edit-cls-enrollment').value = cls.enrollment_fee ?? 0;
        document.getElementById('edit-cls-location').value = cls.location ?? '';
        document.getElementById('edit-cls-schedule').value = cls.schedule ?? '';
        document.getElementById('modal-edit-class').classList.remove('hidden');
    }
</script>
@endsection