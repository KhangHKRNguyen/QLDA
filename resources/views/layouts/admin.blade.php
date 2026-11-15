<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Gia sư</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { height: 100vh; background: #343a40; color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px 20px; }
        .sidebar a:hover { background: #495057; }
        .sla-violated { background-color: #ffe6e6 !important; border-left: 4px solid red; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar position-fixed">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <a href="/admin/yeu-cau"><i class="fas fa-users"></i> Quản lý Khách hàng</a>
                <a href="/admin/lop-hoc"><i class="fas fa-chalkboard-teacher"></i> Quản lý Lớp học</a>
            </div>
            
            <!-- Nội dung chính -->
            <div class="col-md-10 offset-md-2 p-4">
                @yield('content')
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>