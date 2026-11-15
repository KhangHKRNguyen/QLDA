@extends('layouts.admin')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Danh sách Yêu cầu tìm Gia sư</h5>
        <button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tạo yêu cầu mới</button>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã YC</th>
                    <th>Môn học</th>
                    <th>Cấp lớp</th>
                    <th>Trạng thái</th>
                    <th>SLA Deadline</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <!-- Dữ liệu render từ JS -->
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // LƯU Ý: Thay token này bằng token thực tế khi đã đăng nhập
    const apiToken = localStorage.getItem('api_token') || 'YOUR_TOKEN_HERE';

    function loadRequests() {
        fetch('/api/yeu-cau', {
            headers: {
                'Authorization': 'Bearer ' + apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('table-body');
            tbody.innerHTML = '';
            
            data.data.forEach(req => {
                // Logic kiểm tra SLA (Bôi đỏ nếu quá hạn)
                const now = new Date();
                const deadline = new Date(req.sla_deadline);
                const isSlaViolated = req.status === 'pending' && now > deadline;
                
                const trClass = isSlaViolated ? 'sla-violated' : '';
                const statusBadge = req.status === 'pending' ? 'bg-warning text-dark' : (req.status === 'confirmed' ? 'bg-success' : 'bg-danger');

                tbody.innerHTML += `
                    <tr class="${trClass}">
                        <td>#${req.tutor_request_id}</td>
                        <td>${req.subject}</td>
                        <td>${req.grade_level}</td>
                        <td><span class="badge ${statusBadge}">${req.status.toUpperCase()}</span></td>
                        <td class="${isSlaViolated ? 'text-danger fw-bold' : ''}">${new Date(req.sla_deadline).toLocaleString('vi-VN')}</td>
                        <td>
                            <button class="btn btn-sm btn-success ${req.status !== 'confirmed' ? 'disabled' : ''}" onclick="createClass(${req.tutor_request_id})">Tạo Lớp</button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteReq(${req.tutor_request_id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
    }

    function createClass(reqId) {
        if(confirm('Tạo lớp học cho yêu cầu này?')) {
            alert('Gọi API /api/yeu-cau/'+reqId+'/tao-lop ở đây');
        }
    }

    loadRequests();
</script>
@endpush