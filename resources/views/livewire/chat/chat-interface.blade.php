<div class="h-[calc(100vh-4rem)] flex overflow-hidden bg-white dark:bg-gray-800" x-data="{ 
        showDetails: window.innerWidth >= 1024,
        mobileShowList: true 
     }" wire:poll.keep-alive.3000ms="pollMessages" @play-notification.window="
        if (Notification.permission === 'granted') {
            new Notification('New WhatsApp Message');
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('New WhatsApp Message');
                }
            });
        }
     ">

    <!-- Left Sidebar: Contact List -->
    <div :class="{'hidden md:flex': !mobileShowList, 'flex w-full md:w-80': true}"
        class="flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <!-- Filter Bar -->
        <div class="p-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <select wire:model.live="filter"
                class="w-full rounded-full border-none bg-white dark:bg-gray-800 text-sm pl-4 focus:ring-wa-teal dark:text-gray-300 shadow-sm">
                <option value="all">All Chats</option>
                <option value="mine">My Chats</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <!-- List -->
        <div class="flex-1 overflow-y-auto">
            @foreach($contacts as $contact)
                <button @click="mobileShowList = false; $wire.selectContact({{ $contact->id }})"
                    class="w-full text-left p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 focus:outline-none flex items-center
                            {{ $selectedContact && $selectedContact->id === $contact->id ? 'bg-gray-100 dark:bg-gray-700' : '' }}">

                    <!-- Avatar -->
                    <div class="flex-shrink-0 mr-3">
                        <div
                            class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($contact->name, 0, 1) }}
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $contact->name }}</span>
                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                {{ $contact->messages->first()?->created_at->format('H:i') ?? '' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                            <span
                                class="truncate w-full {{ $contact->has_pending_reply ? 'font-semibold text-gray-900 dark:text-gray-200' : '' }}">
                                {{ $contact->messages->first()?->content ?? 'No messages' }}
                            </span>

                            <div class="flex space-x-1 ml-2">
                                @if($contact->sla_breached_at)
                                    <span class="text-red-500" title="SLA Breached">⚠️</span>
                                @elseif($contact->has_pending_reply)
                                    <span class="bg-wa-green text-white text-[10px] px-1.5 py-0.5 rounded-full"
                                        title="Needs Reply">1</span>
                                @endif

                                @if($contact->assignedTo)
                                    <img src="{{ $contact->assignedTo->profile_photo_url }}" class="w-4 h-4 rounded-full"
                                        title="{{ $contact->assignedTo->name }}">
                                @endif
                            </div>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Middle: Chat Area -->
    <div :class="{'hidden md:flex': mobileShowList, 'flex': !mobileShowList}"
        class="flex-1 flex-col min-w-0 bg-wa-bg wa-bg-pattern relative">
        @if($selectedContact)
            <!-- Header -->
            <div
                class="h-16 px-4 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm z-10">
                <div class="flex items-center">
                    <!-- Back Button (Mobile) -->
                    <button @click="mobileShowList = true"
                        class="md:hidden mr-3 text-wa-teal hover:text-wa-dark dark:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>

                    <div class="flex items-center cursor-pointer" @click="showDetails = !showDetails">
                        <div
                            class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold mr-3">
                            {{ substr($selectedContact->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                                {{ $selectedContact->name }}</h2>
                            <span class="text-xs text-gray-500 block">{{ $selectedContact->phone_number }}</span>
                        </div>
                    </div>
                </div>
                <!-- Toggle Details Button -->
                <div class="flex items-center space-x-4">
                    <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    <button @click="showDetails = !showDetails"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-2">
                @foreach($messages as $msg)
                    <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[85%] md:max-w-xl px-2 py-1.5 rounded-lg shadow-sm relative group
                                            {{ $msg->direction === 'outbound' ? 'bg-wa-light text-gray-900 rounded-tr-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-tl-none' }}">

                            <!-- Tail Svg (Optional cosmetic) -->

                            <p class="text-sm leading-relaxed px-1 whitespace-pre-wrap">{{ $msg->content }}</p>
                            <div class="flex items-center justify-end mt-0.5 space-x-1 select-none">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 min-w-[3rem] text-right">
                                    {{ $msg->created_at->format('H:i') }}
                                    @if($msg->direction === 'outbound')
                                        <span class="ml-0.5 inline-block">
                                            @if($msg->status === 'sent') <span class="text-gray-500">✓</span> @endif
                                            @if($msg->status === 'delivered') <span class="text-gray-500">✓✓</span> @endif
                                            @if($msg->status === 'read') <span class="text-blue-400">✓✓</span> @endif
                                            @if($msg->status === 'failed') <span class="text-red-500">!</span> @endif
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Input Area -->
            <div
                class="px-4 py-3 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-end space-x-3">
                <button class="text-gray-500 hover:text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
                <button class="text-gray-500 hover:text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                        </path>
                    </svg>
                </button>

                <div
                    class="flex-1 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <textarea wire:model="newMessage" wire:keydown.enter="sendMessage" rows="1" placeholder="Type a message"
                        class="w-full border-none focus:ring-0 resize-none py-3 px-4 bg-transparent dark:text-white max-h-32"
                        style="min-height: 48px;"></textarea>
                </div>

                @if(!empty(trim($newMessage)))
                    <button wire:click="sendMessage"
                        class="p-3 bg-wa-green text-white rounded-full hover:bg-green-600 shadow-md transition transform hover:scale-105 mb-1">
                        <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                @else
                    <button
                        class="p-3 bg-wa-green text-white rounded-full hover:bg-green-600 shadow-md transition transform hover:scale-105 mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z">
                            </path>
                        </svg>
                    </button>
                @endif
            </div>
        @else
            <!-- Empty State -->
            <div
                class="hidden md:flex flex-1 items-center justify-center text-gray-500 dark:text-gray-400 flex-col bg-wa-bg border-b-8 border-wa-green">
                <div class="text-center p-10">
                    <h1 class="text-3xl font-light text-gray-600 mb-4">WhatsApp Web</h1>
                    <p>Send and receive messages without keeping your phone online.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Right Sidebar: Details & Notes -->
    <div x-show="showDetails" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-full"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-full"
        class="fixed inset-y-0 right-0 w-80 bg-gray-100 dark:bg-gray-800 shadow-xl z-20 md:relative md:shadow-none md:border-l md:border-gray-200 dark:md:border-gray-700 flex flex-col overflow-y-auto"
        style="display: none;">

        <!-- Mobile Close Button -->
        <div class="md:hidden absolute top-4 left-4 z-50">
            <button @click="showDetails = false"
                class="bg-gray-200 dark:bg-gray-700 rounded-full p-2 text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        @if($selectedContact)
            <div class="bg-white dark:bg-gray-800 p-8 flex flex-col items-center shadow-sm mb-2">
                <div
                    class="w-32 h-32 rounded-full bg-gray-200 mb-4 flex items-center justify-center text-5xl text-gray-500">
                    {{ substr($selectedContact->name, 0, 1) }}
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $selectedContact->name }}</h2>
                <p class="text-gray-500">{{ $selectedContact->phone_number }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm p-4 mb-2">
                <h3 class="text-sm font-bold text-wa-teal uppercase mb-2">Internal Assignment</h3>
                @if($selectedContact->assignedTo)
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 rounded">
                        <div class="flex items-center">
                            <img src="{{ $selectedContact->assignedTo->profile_photo_url }}" class="w-6 h-6 rounded-full mr-2">
                            <span
                                class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ $selectedContact->assignedTo->name }}</span>
                        </div>
                        <button wire:click="unassign" class="text-xs text-red-600 hover:text-red-800">Unassign</button>
                    </div>
                @else
                    <button wire:click="assignToMe" class="w-full py-2 bg-wa-light text-green-800 rounded text-sm font-medium">
                        Assign to Me
                    </button>
                @endif

                <div class="mt-3">
                    <select wire:change="assignToAgent($event.target.value)"
                        class="w-full text-sm border-gray-200 bg-gray-50 rounded">
                        <option value="">Transfer to...</option>
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm p-4 flex-1">
                <h3 class="text-sm font-bold text-wa-teal uppercase mb-4">Notes</h3>

                <div class="space-y-3 mb-4">
                    @foreach($notes as $note)
                        <div class="bg-yellow-50 p-2 rounded text-sm border border-yellow-100">
                            <p class="text-gray-800">{{ $note->body }}</p>
                            <span class="text-xs text-gray-400 mt-1 block">{{ $note->user->name }} •
                                {{ $note->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>

                <textarea wire:model="newNote" rows="2" class="w-full text-sm border-gray-200 rounded p-2 bg-gray-50"
                    placeholder="Add note..."></textarea>
                <button wire:click="addNote"
                    class="mt-2 w-full py-1.5 bg-wa-teal text-white rounded text-sm hover:bg-teal-700">Save Note</button>
            </div>
        @endif
    </div>
</div>