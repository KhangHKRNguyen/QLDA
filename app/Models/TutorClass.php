<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TutorClass extends Model {
    // 1. Khai báo lại tên Primary Key
    protected $primaryKey = 'class_id';
    
    protected $guarded = ['class_id'];
    
    protected $casts = [
        'locked_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function tutorRequest() {
        return $this->belongsTo(TutorRequest::class, 'tutor_request_id', 'tutor_request_id');
    }

    // 2. Cập nhật lại thuật toán kiểm tra Khóa 5 phút dựa trên locked_at
    public function isLocked() {
        if (!$this->locked_at) {
            return false;
        }
        // Trả về true nếu thời gian hiện tại vẫn nằm trong khoảng 5 phút kể từ lúc khóa
        return now()->diffInMinutes($this->locked_at) < 5;
    }
}