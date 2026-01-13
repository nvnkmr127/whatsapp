<div class="flex flex-col h-full bg-[#efe7dd] relative">
    <!-- Simplified Header -->
    <div class="px-4 py-2 border-b border-gray-200 bg-white flex justify-between items-center shadow-sm z-10">
        <div class="flex items-center">
            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold mr-2">
                {{ substr($conversation->contact->name ?? '?', 0, 1) }}
            </div>
            <div>
                <h2 class="font-bold text-sm text-gray-800">{{ $conversation->contact->name ?? 'Unknown' }}</h2>
            </div>
        </div>
    </div>

    <!-- Messages Area (Reused Logic) -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container" x-data
        x-init="$el.scrollTop = $el.scrollHeight" @scroll-bottom.window="$el.scrollTop = $el.scrollHeight">
        @if($conversation)
            @foreach($conversation->messages as $message)
                <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                    <div
                        class="max-w-[85%] {{ $message->direction === 'outbound' ? 'bg-[#d9fdd3] rounded-l-lg rounded-tr-lg' : 'bg-white rounded-r-lg rounded-tl-lg' }} rounded-b-lg p-2 shadow-sm text-sm relative">
                        <!-- Media Logic ... (simplified for brevity, can reuse partial) -->
                        @if($message->media_url)
                            <div class="mb-1 text-xs text-blue-600 underline">Sent Media</div>
                        @endif

                        <p class="whitespace-pre-wrap leading-relaxed">{{ $message->content }}</p>
                        <span
                            class="text-[10px] text-gray-400 block text-right mt-1">{{ $message->created_at->format('H:i') }}</span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="flex justify-center items-center h-full text-gray-500 text-sm">
                Start a conversation...
            </div>
        @endif
    </div>

    <!-- Input Area -->
    @if(in_array('write', $permissions))
        <div class="p-2 bg-white border-t border-gray-200">
            <form wire:submit.prevent="sendMessage" class="flex items-end space-x-2">
                <input type="text" wire:model="messageBody"
                    class="flex-1 rounded-full border-gray-300 focus:border-green-500 focus:ring-green-500 text-sm py-2 px-4 bg-gray-50"
                    placeholder="Type a message...">
                <button type="submit" class="p-2 bg-green-500 text-white rounded-full hover:bg-green-600">
                    <svg class="w-5 h-5 transform rotate-90 translate-x-[1px]" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z">
                        </path>
                    </svg>
                </button>
            </form>
        </div>
    @else
        <div class="p-3 bg-gray-50 border-t border-gray-200 text-center text-xs text-gray-500">
            Read Only Mode
        </div>
    @endif
</div>