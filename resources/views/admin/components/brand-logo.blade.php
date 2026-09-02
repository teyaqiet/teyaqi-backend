@props([
    'wordmark' => true,
    'markClass' => 'h-9 w-9',
    'textClass' => 'text-lg',
])

<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>

    {{-- Teyaqi Logo --}}
    <img
        src="{{ asset('img/Teyaqi-favicon.svg') }}"
        alt="{{ config('app.name', 'Teyaqi') }}"
        class="{{ $markClass }} shrink-0 object-contain"
    >

    @if ($wordmark)
        <span class="{{ $textClass }} font-bold leading-none tracking-tight">
            Teyaqi<span class="text-primary-500"> | ጠያቂ</span>
        </span>
    @endif

</span>