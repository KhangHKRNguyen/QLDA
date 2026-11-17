<?php

namespace App\Http\Requests;

use App\Models\VerificationProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TutorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $rules = [
            'full_name'     => ['required', 'string', 'max:150'],
            'phone_number'  => ['required', 'regex:/^0\d{9}$/'],
            // Email dùng làm tên đăng nhập; được đăng ký lại nếu tài khoản cũ đã bị hủy / từ chối
            'email'         => ['required', 'email', 'max:255',
                Rule::unique('accounts', 'username')->whereNotIn('status', ['cancelled', 'rejected'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender'        => ['required', Rule::in(['Nam', 'Nữ', 'Khác'])],
            'address'       => ['required', 'string', 'max:500'],
            'district'      => ['nullable', 'string', 'max:100'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ];

        foreach (VerificationProfile::TYPES as $type => [$label, $required]) {
            $rules["documents.$type"] = [$required ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'required'           => ':Attribute không được để trống.',
            'email'              => ':Attribute không hợp lệ.',
            'unique'             => ':Attribute đã được sử dụng cho một tài khoản khác.',
            'date'               => ':Attribute không hợp lệ.',
            'before'             => ':Attribute phải trước ngày hôm nay.',
            'in'                 => ':Attribute không hợp lệ.',
            'min'                => ':Attribute phải có ít nhất :min ký tự.',
            'max'                => ':Attribute tối đa :max ký tự.',
            'phone_number.regex' => 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'file'               => ':Attribute phải là tệp tải lên.',
            'mimes'              => ':Attribute chỉ nhận tệp JPG, PNG hoặc PDF.',
            'uploaded'           => 'Tải tệp lên thất bại (tệp quá lớn). Mỗi tệp tối đa 5 MB.',
        ];
        foreach (array_keys(VerificationProfile::TYPES) as $type) {
            $messages["documents.$type.max"] = 'Mỗi tệp tối đa 5 MB.';
        }

        return $messages;
    }

    public function attributes(): array
    {
        $attributes = [
            'full_name' => 'họ tên', 'phone_number' => 'số điện thoại', 'email' => 'email',
            'date_of_birth' => 'ngày sinh', 'gender' => 'giới tính', 'address' => 'địa chỉ',
            'district' => 'khu vực', 'password' => 'mật khẩu',
        ];
        foreach (VerificationProfile::TYPES as $type => [$label]) {
            $attributes["documents.$type"] = 'giấy tờ "' . $label . '"';
        }

        return $attributes;
    }
}
