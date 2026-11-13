<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TutorRequest extends Model {
    // 1. Khai báo lại tên Primary Key
    protected $primaryKey = 'tutor_request_id';
    protected $table = 'tutor_requests';
    protected $guarded = ['tutor_request_id'];
    
    protected $casts = [
        'sla_deadline' => 'datetime',
    ];

    public function tutorClass() {
        // 2. Khai báo rõ tên khóa ngoại do không dùng chuẩn mặc định
        return $this->hasOne(TutorClass::class, 'tutor_request_id', 'tutor_request_id');
    }

    public function getIsSlaViolatedAttribute() {
        return $this->status === 'pending' && now()->greaterThan($this->sla_deadline);
    }
}