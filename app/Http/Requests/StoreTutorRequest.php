<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutorRequest extends FormRequest {
    public function authorize() {
        return true; 
    }

    public function rules() {
        return [
            'customer_name' => 'required|string|max:100',
            'phone_number'  => ['required', 'regex:/(84|0[3|5|7|8|9])+([0-9]{8})\b/'],
            'email'         => 'nullable|email',
            'subject'       => 'required|string',
            'grade'         => 'required|string',
            'location'      => 'required|string',
            'schedule'      => 'required|string',
            'tuition_fee'   => 'required|numeric|min:0',
        ];
    }
}