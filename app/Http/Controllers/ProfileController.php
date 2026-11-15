<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Hiển thị thông tin cá nhân.
     */
    public function edit(Request $request): View
    {
        $account = $request->user()->loadMissing(['person', 'role']);

        return view('profile.edit', [
            'account' => $account,
            'person' => $account->person,
        ]);
    }

    /**
     * Cập nhật thông tin cá nhân.
     */
    public function update(Request $request): RedirectResponse
    {
        $account = $request->user();

        $data = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:150',
            ],

            'phone_number' => [
                'required',
                'regex:/^0\d{9}$/',
                Rule::unique('people', 'phone_number')
                    ->ignore($account->person_id, 'person_id'),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('people', 'email')
                    ->ignore($account->person_id, 'person_id'),
            ],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'nullable',
                Rule::in(['Nam', 'Nữ', 'Khác']),
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        DB::table('people')
            ->where('person_id', $account->person_id)
            ->update($data);

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }
}