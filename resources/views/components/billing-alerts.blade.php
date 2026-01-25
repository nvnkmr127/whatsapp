@if(count($billingWarnings) > 0)
    <div class="space-y-2 mb-4">
        @foreach($billingWarnings as $warning)
            <div class="flex items-center p-4 text-sm rounded-lg border {{ $warning['level'] === 'danger' ? 'bg-red-50 text-red-800 border-red-200' : ($warning['level'] === 'warning' ? 'bg-yellow-50 text-yellow-800 border-yellow-200' : 'bg-blue-50 text-blue-800 border-blue-200') }}"
                role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <span class="font-bold">{{ $warning['message'] }}</span>
                    @if($warning['level'] === 'danger')
                        <a href="{{ route('billing') }}" class="underline font-medium hover:text-red-900 ms-2">Upgrade Now</a>
                    @else
                        <a href="{{ route('billing') }}" class="underline font-medium hover:text-yellow-900 ms-2">View Usage</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif