@props(['title', 'actionUrl'])

<x-mail :actionUrl="$actionUrl ?? null">
  <div style="color: #374151; line-height: 1.6;">
    {{-- Header --}}
    @if (!empty($greeting))
      <h2
        style="font-size: 1.5rem; font-weight: 600; margin-top: 0; color: #1f2937; font-family: 'Lexend', sans-serif;">
        {{ $greeting }}
      </h2>
    @else
      <h2
        style="font-size: 1.5rem; font-weight: 600; margin-top: 0; color: #1f2937; font-family: 'Lexend', sans-serif;">
        @lang('Hello!')
      </h2>
    @endif

    {{-- Content --}}
    <div style="margin-bottom: 2rem;">
      @foreach ($introLines as $line)
        <p style="margin: 0.5rem 0;">{{ $line }}</p>
      @endforeach
    </div>

    {{-- Action Button --}}
    @isset($actionText)
      <div style="text-align: center; margin: 2rem 0;">
        <a href="{{ $actionUrl }}" class="button-primary">
          {{ $actionText }}
        </a>
      </div>
    @endisset

    {{-- Outro Lines --}}
    <div style="margin-bottom: 2rem;">
      @foreach ($outroLines as $line)
        <p style="margin: 0.5rem 0;">{{ $line }}</p>
      @endforeach
    </div>

    {{-- Salutation --}}
    <div style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; color: #6b7280;">
      <p style="margin: 0;">
        @if (!empty($salutation))
          {{ $salutation }}
        @else
          @lang('Regards'),<br>
          {{ config('app.name') }}
        @endif
      </p>
    </div>
  </div>
</x-mail>
