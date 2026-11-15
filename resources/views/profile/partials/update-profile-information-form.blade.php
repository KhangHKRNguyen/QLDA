<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Thông tin cá nhân
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Cập nhật thông tin cá nhân của bạn.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Họ và tên --}}
        <div>
            <x-input-label for="full_name" value="Họ và tên" />

            <x-text-input
                id="full_name"
                name="full_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('full_name', $person?->full_name)"
                required
                autofocus
                autocomplete="name"
            />

            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
        </div>

        {{-- Tên đăng nhập (chỉ xem) --}}
        <div>
            <x-input-label for="username" value="Tên đăng nhập" />

            <x-text-input
                id="username"
                type="text"
                class="mt-1 block w-full bg-gray-100"
                :value="$account->username"
                disabled
            />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" value="Email" />

            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $person?->email)"
                required
                autocomplete="email"
            />

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Số điện thoại --}}
        <div>
            <x-input-label for="phone_number" value="Số điện thoại" />

            <x-text-input
                id="phone_number"
                name="phone_number"
                type="text"
                class="mt-1 block w-full"
                :value="old('phone_number', $person?->phone_number)"
                required
                autocomplete="tel"
            />

            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        {{-- Ngày sinh --}}
        <div>
            <x-input-label for="date_of_birth" value="Ngày sinh" />

            <x-text-input
                id="date_of_birth"
                name="date_of_birth"
                type="date"
                class="mt-1 block w-full"
                :value="old('date_of_birth', $person?->date_of_birth ? \Illuminate\Support\Carbon::parse($person->date_of_birth)->format('Y-m-d') : '')"
            />

            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>

        {{-- Giới tính --}}
        <div>
            <x-input-label for="gender" value="Giới tính" />

            <select
                id="gender"
                name="gender"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">Chọn giới tính</option>

                @foreach (['Nam', 'Nữ', 'Khác'] as $gender)
                    <option
                        value="{{ $gender }}"
                        @selected(old('gender', $person?->gender) === $gender)
                    >
                        {{ $gender }}
                    </option>
                @endforeach
            </select>

            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>

        {{-- Địa chỉ --}}
        <div>
            <x-input-label for="address" value="Địa chỉ" />

            <textarea
                id="address"
                name="address"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >{{ old('address', $person?->address) }}</textarea>

            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        {{-- Khu vực --}}
        <div>
            <x-input-label for="district" value="Quận / Huyện" />

            <x-text-input
                id="district"
                name="district"
                type="text"
                class="mt-1 block w-full"
                :value="old('district', $person?->district)"
            />

            <x-input-error :messages="$errors->get('district')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>
                Lưu thay đổi
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600"
                >
                    Đã cập nhật thông tin.
                </p>
            @endif
        </div>
    </form>
</section>