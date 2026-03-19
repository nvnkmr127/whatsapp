<div class="space-y-4">
    {{-- Trigger Type Dropdown --}}
    <div class="space-y-1">
        <label class="block text-xs font-bold text-slate-500 uppercase">When this happens:</label>
        <select wire:model.live="triggerType" wire:change="updateNodeData"
            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
            <option value="keyword">Keyword/Regex Match</option>
            <option value="referral">Ad/Referral Click</option>
            <option value="user_starts_conversation">User Starts Conversation</option>
            <option value="template_response">Template Response (Quick Reply)</option>
            <option value="template_selected">Template Selected</option>
            <option value="template_delivered">WhatsApp Template Delivered</option>
            <option value="contact_added">Contact Added</option>
            <option value="custom_field_updated">Custom Field Updated</option>
            <option value="tag_assigned">Tag Assigned</option>
            <option value="payment_capture">Payment Capture</option>
            <option value="order_received">Order Received</option>
        </select>
    </div>

    {{-- Keyword Match Config --}}
    <div x-show="['keyword'].includes($wire.triggerType)" class="space-y-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
        <div class="space-y-1">
            <label class="block text-[10px] font-bold text-slate-400 uppercase">Keywords (Comma separated)</label>
            <input type="text" wire:model.blur="triggerKeywordsString" 
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold px-3 py-2 focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                placeholder="hi, hello, order, status">
            <p class="text-[9px] text-slate-400 mt-1">Separate multiple keywords with commas.</p>
        </div>
        
        <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
            <span class="text-[10px] font-bold uppercase text-slate-500">Regex Mode</span>
            <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                <input type="checkbox" wire:model.live="triggerConfig.is_regex" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                <label class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
            </div>
        </div>
        <p class="text-[9px] text-slate-400">If enabled, keywords are treated as Regular Expressions.</p>
    </div>

    {{-- Ad/Referral Config --}}
    <div x-show="['referral'].includes($wire.triggerType)" class="space-y-4">
        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800">
            <p class="text-xs text-slate-500 leading-relaxed">
                Triggers when a user clicks a "Click to WhatsApp" ad or referral link. Leave fields empty to match ANY referral.
            </p>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Ad Source ID (Exact)</label>
            <input type="text" wire:model.blur="triggerConfig.source_id" placeholder="e.g. 1234567890"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Headline (Contains)</label>
            <input type="text" wire:model.blur="triggerConfig.headline" placeholder="e.g. 20% Off"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
    </div>

    {{-- User Starts Conversation --}}
    <div x-show="['user_starts_conversation'].includes($wire.triggerType)" class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-xl text-xs">
        This flow triggers when a user sends the first message or a message after 24h window expiry.
    </div>

    {{-- Template Selected / Response --}}
    <div x-show="['template_selected', 'template_response'].includes($wire.triggerType)" class="space-y-4">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Select Template</label>
            <select wire:model.live="triggerConfig.template_name"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                <option value="">Choose a template...</option>
                @foreach($approvedTemplates as $tmpl)
                    <option value="{{ data_get($tmpl, 'name') }}">{{ data_get($tmpl, 'name') }} ({{ data_get($tmpl, 'language') }})</option>
                @endforeach
            </select>
        </div>
        <div x-show="$wire.triggerType === 'template_response'" class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Button Text to Match</label>
            <input type="text" wire:model.blur="triggerConfig.button_text" placeholder="e.g. Yes, Interested"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
        </div>
    </div>
</div>
