@props(['href' => '/', 'subtitle' => 'Trung tâm gia sư'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
        <x-icon name="cap" class="size-5" />
    </span>
    <span class="flex flex-col leading-tight">
        <span class="text-sm font-bold text-foreground">{{ config('app.name') }}</span>
        <span class="text-xs text-muted-foreground">{{ $subtitle }}</span>
    </span>
</a>
