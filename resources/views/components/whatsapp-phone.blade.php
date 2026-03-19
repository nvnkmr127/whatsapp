@props([
    'bg' => 'bg-wa-chat-bg',
    'darkBg' => 'dark:bg-wa-dark-bg',
    'showNotch' => true,
    'pattern' => true
])

<div {{ $attributes->merge(['class' => "relative mx-auto w-full max-w-[320px] {$bg} {$darkBg} rounded-wa-mockup p-4 shadow-2xl border-8 border-slate-900 dark:border-slate-800 overflow-hidden min-h-[580px]"]) }}>
    @if($pattern)
        <div class="absolute inset-0 opacity-10 pointer-events-none" 
             style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat; background-size: 400px;">
        </div>
    @endif

    {{-- Phone Notch --}}
    @if($showNotch)
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-900 dark:bg-slate-800 rounded-b-3xl z-30"></div>
    @endif

    <div class="relative z-10 flex flex-col h-full">
        {{ $slot }}
    </div>
</div>
