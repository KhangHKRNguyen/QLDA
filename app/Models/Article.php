<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    // Tên bảng trong database
    protected $table = 'posts';

    // Khóa chính của bảng
    protected $primaryKey = 'post_id';

    // Bảng posts không có created_at và updated_at
    public $timestamps = false;

    // Các trường được phép thêm hoặc cập nhật dữ liệu
    protected $fillable = [
        'title',
        'content',
        'visibility_status',
        'account_id',
    ];
}
