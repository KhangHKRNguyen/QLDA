@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Quản lý Yêu cầu Tìm Gia sư</h1>
            <p class="text-sm text-gray-500">Tiếp nhận, xác nhận thông tin phụ huynh và khởi tạo lớp học mới</p>
        </div>
        <button type="button"
                onclick="document.getElementById('modal-create-request').classList.remove('hidden')"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            + Tiếp nhận yêu cầu mới
        </button>
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

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Bộ lọc tìm kiếm --}}
    <form method="GET" action="{{ route('admin.tutor-requests.index') }}" class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Tìm kiếm (Mã YC, Môn, Cấp lớp)</label>
            <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Nhập từ khóa..."
                   class="w-full border-gray-300 rounded-lg text-sm">
        </div>
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
            <select name="status" class="w-full border-gray-300 rounded-lg text-sm">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" @selected(request('status') === 'pending')>Chờ xử lý (Pending)</option>
                <option value="confirmed" @selected(request('status') === 'confirmed')>Đã xác nhận (Confirmed)</option>
                <option value="canceled" @selected(request('status') === 'canceled')>Đã hủy (Canceled)</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">Lọc</button>
        <a href="{{ route('admin.tutor-requests.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Đặt lại</a>
    </form>

    {{-- Bảng danh sách --}}
    <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Mã YC</th>
                    <th class="px-4 py-3 text-left">Môn học / Cấp lớp</th>
                    <th class="px-4 py-3 text-left">Hình thức / Lịch</th>
                    <th class="px-4 py-3 text-left">Học phí mong muốn</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-left">Hạn xử lý SLA</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($tutorRequests as $req)
                    @php
                        $deadline = $req->sla_deadline ?? $req->created_at?->copy()->addHours(4);
                        $isSlaViolated = $req->status === 'pending' && $deadline && now()->greaterThan($deadline);
                    @endphp
                    <tr class="{{ $isSlaViolated ? 'bg-red-50 border-l-4 border-red-600' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-3 font-semibold text-gray-900">#{{ $req->tutor_request_id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $req->subject }}</div>
                            <div class="text-xs text-gray-500">{{ $req->grade_level }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $req->learning_format }} ({{ $req->sessions_per_week }} buổi/tuần)</div>
                            <div class="text-xs text-gray-500">{{ $req->schedule ?: 'Chưa rõ lịch' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ number_format($req->desired_tuition_fee ?? 0) }} đ</td>
                        <td class="px-4 py-3">
                            @if($req->status === 'confirmed')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Đã xác nhận</span>
                            @elseif($req->status === 'canceled')
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Đã hủy</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Chưa xử lý</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 {{ $isSlaViolated ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                            {{ $deadline ? $deadline->format('d/m/Y H:i') : '—' }}
                            @if($isSlaViolated)
                                <div class="text-xs font-normal">Quá hạn SLA!</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                            @if($req->status === 'confirmed' && !$req->tutorClass)
                                <button type="button"
                                        onclick="openCreateClassModal({{ $req->tutor_request_id }}, {{ $req->desired_tuition_fee ?? 200000 }})"
                                        class="px-2.5 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                    Tạo lớp
                                </button>
                            @elseif($req->tutorClass)
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">Đã tạo lớp #{{ $req->tutorClass->class_id }}</span>
                            @endif

                            @if($req->status !== 'canceled')
                                <button type="button"
                                        onclick='openEditRequestModal(@json($req))'
                                        class="px-2.5 py-1 border border-gray-300 text-gray-700 rounded text-xs hover:bg-gray-100">
                                    Sửa / Xác nhận
                                </button>
                            @endif

                            @if(!$req->tutorClass && $req->status !== 'canceled')
                                <form action="{{ route('admin.tutor-requests.destroy', $req->tutor_request_id) }}" method="POST" class="inline"
                                      onsubmit="const reason = prompt('Nhập lý do hủy yêu cầu (tối thiểu 5 ký tự):'); if(!reason) return false; this.querySelector('[name=cancellation_reason]').value = reason;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="cancellation_reason" value="">
                                    <button type="submit" class="px-2.5 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                                        Hủy YC
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có yêu cầu tìm gia sư nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $tutorRequests->links() }}</div>
</div>

{{-- Modal 1: Tiếp nhận yêu cầu mới --}}
<div id="modal-create-request" class="hidden fixed inset-0 bg-black/50 z-50 p-4 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Tiếp nhận Yêu cầu Tìm Gia sư</h3>
            <form action="{{ route('admin.tutor-requests.store') }}" method="POST" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium text-gray-700 mb-1">Chọn phụ huynh có sẵn (hoặc để trống để nhập mới bên dưới)</label>
                <select name="person_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">-- Nhập thông tin phụ huynh mới --</option>
                    @foreach($people as $p)
                        <option value="{{ $p->person_id }}">#{{ $p->person_id }} - {{ $p->full_name ?? $p->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Họ tên phụ huynh mới</label>
                    <input type="text" name="full_name" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" name="phone" class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Môn học *</label>
                    <input type="text" name="subject" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Cấp lớp *</label>
                    <input type="text" name="grade_level" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Hình thức học *</label>
                    <select name="learning_format" class="w-full border-gray-300 rounded-lg">
                        <option value="Offline">Offline (Tại nhà)</option>
                        <option value="Online">Online</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Số buổi / tuần *</label>
                    <input type="number" name="sessions_per_week" value="2" min="1" max="14" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Học phí mong muốn / buổi (VNĐ)</label>
                <input type="number" name="desired_tuition_fee" value="200000" min="0" class="w-full border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Lịch học & Ghi chú</label>
                <textarea name="schedule" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('modal-create-request').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Lưu yêu cầu</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Cập nhật / Xác nhận yêu cầu --}}
<div id="modal-edit-request" class="hidden fixed inset-0 bg-black/50 z-50 p-4 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Cập nhật & Xác nhận Yêu cầu</h3>
            <form id="form-edit-request" method="POST" class="space-y-3 text-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Môn học *</label>
                    <input type="text" name="subject" id="edit-req-subject" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Cấp lớp *</label>
                    <input type="text" name="grade_level" id="edit-req-grade" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Hình thức học *</label>
                    <input type="text" name="learning_format" id="edit-req-format" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Số buổi / tuần *</label>
                    <input type="number" name="sessions_per_week" id="edit-req-sessions" min="1" max="14" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Học phí / buổi</label>
                    <input type="number" name="desired_tuition_fee" id="edit-req-fee" min="0" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Trạng thái xử lý *</label>
                    <select name="status" id="edit-req-status" class="w-full border-gray-300 rounded-lg">
                        <option value="pending">Chưa xử lý (Pending)</option>
                        <option value="confirmed">Đã xác nhận (Confirmed)</option>
                        <option value="canceled">Đã hủy (Canceled)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Lịch học</label>
                <textarea name="schedule" id="edit-req-schedule" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('modal-edit-request').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Đóng</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 3: Tạo lớp học từ yêu cầu đã xác nhận --}}
<div id="modal-create-class" class="hidden fixed inset-0 bg-black/50 z-50 p-4 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
        <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Khởi tạo Lớp học từ Yêu cầu</h3>
            <form id="form-create-class" method="POST" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block font-medium text-gray-700 mb-1">Học phí / buổi (VNĐ) *</label>
                <input type="number" name="fee_per_session" id="create-class-fee" required min="0" class="w-full border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Phí nhận lớp gia sư phải đóng (VNĐ) *</label>
                <input type="number" name="enrollment_fee" value="300000" required min="0" class="w-full border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Địa điểm học</label>
                <input type="text" name="location" placeholder="Quận/Huyện, Thành phố..." class="w-full border-gray-300 rounded-lg">
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('modal-create-class').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Tạo lớp & Đăng tuyển</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditRequestModal(req) {
        const form = document.getElementById('form-edit-request');
        form.action = `/admin/yeu-cau/${req.tutor_request_id}`;
        document.getElementById('edit-req-subject').value = req.subject ?? '';
        document.getElementById('edit-req-grade').value = req.grade_level ?? '';
        document.getElementById('edit-req-format').value = req.learning_format ?? '';
        document.getElementById('edit-req-sessions').value = req.sessions_per_week ?? 2;
        document.getElementById('edit-req-fee').value = req.desired_tuition_fee ?? 0;
        document.getElementById('edit-req-status').value = req.status ?? 'pending';
        document.getElementById('edit-req-schedule').value = req.schedule ?? '';
        document.getElementById('modal-edit-request').classList.remove('hidden');
    }

    function openCreateClassModal(requestId, defaultFee) {
        const form = document.getElementById('form-create-class');
        form.action = `/admin/lop-hoc/tao-tu-yeu-cau/${requestId}`;
        document.getElementById('create-class-fee').value = defaultFee;
        document.getElementById('modal-create-class').classList.remove('hidden');
    }
</script>
@endsection