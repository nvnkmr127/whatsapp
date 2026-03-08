<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Create Click-to-WhatsApp Ad</h2>
                <p class="text-gray-600">Launch a new ad campaign in minutes.</p>
            </div>
            <a href="{{ route('ads.manager') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">Cancel</a>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                @foreach($steps as $step => $label)
                    <div class="flex flex-col items-center bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm mb-2
                            {{ $currentStep >= $step ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                            {{ $step }}
                        </div>
                        <span class="text-xs font-medium {{ $currentStep >= $step ? 'text-blue-600' : 'text-gray-400' }}">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Form Container -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            
            <!-- Step 1: Campaign Details -->
            @if($currentStep === 1)
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Campaign Name</label>
                        <input type="text" wire:model="campaignName" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Summer Sale 2024">
                        @error('campaignName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Campaign Objective</label>
                        <select wire:model="objective" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 bg-gray-50 text-gray-500 cursor-not-allowed" disabled>
                            <option value="OUTCOME_TRAFFIC">Traffic (Click to WhatsApp)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Automatically set to optimize for WhatsApp conversations.</p>
                    </div>
                </div>
            @endif

            <!-- Step 2: Targeting & Budget -->
            @if($currentStep === 2)
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Daily Budget ($)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" wire:model="dailyBudget" class="w-full pl-8 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" placeholder="10.00">
                        </div>
                        @error('dailyBudget') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <h4 class="font-bold text-blue-800 text-sm mb-1">Audience Targeting</h4>
                        <p class="text-xs text-blue-600">Currently set to "Broad" (Auto). Custom audience selection coming soon.</p>
                    </div>
                </div>
            @endif

            <!-- Step 3: Creative & Message -->
            @if($currentStep === 3)
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Ad Image</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition-colors cursor-pointer relative">
                                <input type="file" wire:model="adImage" class="absolute inset-0 opacity-0 cursor-pointer">
                                @if($adImage)
                                    <img src="{{ $adImage->temporaryUrl() }}" class="h-32 mx-auto object-cover rounded-lg">
                                @else
                                    <div class="text-gray-400">
                                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-sm">Click to upload image</span>
                                    </div>
                                @endif
                            </div>
                            @error('adImage') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Primary Text</label>
                            <textarea wire:model="primaryText" rows="3" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" placeholder="What should the ad say?"></textarea>
                            @error('primaryText') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Headline</label>
                            <input type="text" wire:model="headline" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Chat with us">
                            @error('headline') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Connect Automation Bot</label>
                            <select wire:model="selectedAutomationId" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a Bot Flow...</option>
                                @foreach($automations as $auto)
                                    <option value="{{ $auto->id }}">{{ $auto->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">This bot will trigger instantly when a user clicks the ad.</p>
                            @error('selectedAutomationId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Ad Preview -->
                    <div class="bg-gray-100 rounded-xl p-4 flex items-center justify-center">
                        <div class="bg-white w-full max-w-xs rounded-lg shadow-sm overflow-hidden border border-gray-200">
                            <div class="p-3 flex items-center gap-2 border-b border-gray-100">
                                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                                <div>
                                    <div class="h-3 w-20 bg-gray-200 rounded mb-1"></div>
                                    <div class="h-2 w-12 bg-gray-100 rounded"></div>
                                </div>
                            </div>
                            <div class="p-3 text-sm text-gray-800">
                                {{ $primaryText ?: 'Ad text will appear here...' }}
                            </div>
                            <div class="bg-gray-200 h-48 w-full flex items-center justify-center text-gray-400">
                                @if($adImage)
                                    <img src="{{ $adImage->temporaryUrl() }}" class="w-full h-full object-cover">
                                @else
                                    Image Preview
                                @endif
                            </div>
                            <div class="p-3 bg-gray-50 flex justify-between items-center">
                                <div>
                                    <div class="text-xs text-gray-500 uppercase">WhatsApp</div>
                                    <div class="font-bold text-sm">{{ $headline ?: 'Headline' }}</div>
                                </div>
                                <div class="px-3 py-1 bg-gray-200 text-gray-600 text-xs font-bold rounded">WhatsApp</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Step 4: Review -->
            @if($currentStep === 4)
                <div class="p-8 text-center space-y-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Ready to Launch!</h3>
                    <p class="text-gray-500">Your ad campaign <strong>{{ $campaignName }}</strong> is ready to be published to Facebook & Instagram.</p>
                    
                    <div class="bg-gray-50 rounded-xl p-6 max-w-md mx-auto text-left space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Daily Budget:</span>
                            <span class="font-bold">${{ $dailyBudget }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Bot Connected:</span>
                            <span class="font-bold text-blue-600">
                                @php
                                    $bot = collect($automations)->firstWhere('id', $selectedAutomationId);
                                @endphp
                                {{ $bot ? $bot->name : 'None' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Footer Navigation -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                @if($currentStep > 1)
                    <button wire:click="previousStep" class="text-gray-600 font-bold hover:text-gray-900">Back</button>
                @else
                    <div></div>
                @endif

                @if($currentStep < 4)
                    <button wire:click="nextStep" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition-all">
                        Next Step
                    </button>
                @else
                    <button wire:click="launchCampaign" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2 rounded-xl font-bold shadow-lg shadow-green-600/20 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Launch Campaign
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
