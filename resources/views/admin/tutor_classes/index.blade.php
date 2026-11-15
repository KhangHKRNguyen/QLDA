@extends('layouts.admin')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Danh sách Lớp học Đang hoạt động</h5>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã Lớp</th>
                    <th>Mã YC Gốc</th>
                    <th>Môn học</th>
                    <th>Phí nhận lớp</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="class-table-body">
                <!-- Dữ liệu render từ JS -->
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const token = localStorage.getItem('api_token') || 'YOUR_TOKEN_HERE';

    function loadClasses() {
        fetch('/api/lop-hoc', {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('class-table-body');
            tbody.innerHTML = '';
            
            data.data.forEach(cls => {
                // Logic phân quyền nút: Chỉ cho sửa/xóa/ẩn nếu trạng thái là pending (Đang tuyển) hoặc hidden
                const canEdit = ['pending', 'hidden'].includes(cls.status);
                const statusBadge = cls.status === 'pending' ? 'bg-primary' : (cls.status === 'processing' ? 'bg-warning text-dark' : 'bg-success');

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold">CLS-${String(cls.class_id).padStart(5, '0')}</td>
                        <td>#${cls.tutor_request_id}</td>
                        <td>${cls.subject}</td>
                        <td>${Number(cls.enrollment_fee).toLocaleString('vi-VN')} đ</td>
                        <td><span class="badge ${statusBadge}">${cls.status.toUpperCase()}</span></td>
                        <td>
                            <!-- Toggle Ẩn/Hiện: Bị mờ (disabled) nếu không thể edit -->
                            <div class="form-check form-switch d-inline-block me-2">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                    ${cls.status !== 'hidden' ? 'checked' : ''} 
                                    ${!canEdit ? 'disabled' : ''} 
                                    title="Ẩn/Hiện lớp">
                            </div>
                            
                            <!-- Nút Sửa/Xóa: Ẩn hoặc disable nếu đã có Gia sư nhận -->
                            <button class="btn btn-sm btn-outline-primary ${!canEdit ? 'disabled' : ''}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger ${!canEdit ? 'disabled' : ''}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
    }

    loadClasses();
</script>
@endpush