{{-- Dùng: <x-status-badge tone="success">Đang dạy</x-status-badge>
     tone: success | warning | danger | info | neutral --}}
@props(['tone' => 'neutral'])
<span {{ $attributes->merge(['class' => "badge badge-{$tone}"]) }}>{{ $slot }}</span>
