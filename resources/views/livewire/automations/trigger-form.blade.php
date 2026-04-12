<div class="space-y-4">
    {{-- Trigger Type Dropdown --}}
    <div class="space-y-1">
        <label class="block text-xs font-bold text-slate-500 uppercase">When this happens:</label>
        <select wire:model.live="triggerType" wire:change="updateNodeData"
            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
            <option value="keyword">Keyword/Regex Match</option>
            <option value="multi">Multi-Trigger (OR Logic)</option>
            <option value="referral">Ad/Referral Click</option>
            <option value="user_starts_conversation">User Starts Conversation</option>
            <option value="template_response">Template Response (Quick Reply)</option>
            <option value="template_selected">Template Selected</option>
            <option value="template_delivered">WhatsApp Template Delivered</option>
            <optgroup label="CRM & Lifecycle">
                <option value="contact_created">Contact Created (T1)</option>
                <option value="contact_field_updated">Field Updated (T2)</option>
                <option value="tag_assigned">Tag Assigned</option>
                <option value="deal_stage_changed">Deal Stage Changed (T3)</option>
                <option value="deal_won">Deal Won (T3.1)</option>
                <option value="deal_lost">Deal Lost (T3.2)</option>
                <option value="inactivity">Inactivity (T5)</option>
                <option value="contact_birthday">Birthday/Date (T6)</option>
            </optgroup>
            <optgroup label="Commerce">
                <option value="cart_abandoned">Cart Abandoned (T7)</option>
                <option value="order_received">Order Received</option>
                <option value="order_status_changed">Order Status Changed (T8)</option>
                <option value="low_stock">Low Stock Alert (T9)</option>
                <option value="payment_capture">Payment Capture</option>
            </optgroup>
            <optgroup label="Engagement & Webhooks">
                <option value="email_opened">Email Opened (T10)</option>
                <option value="email_link_clicked">Email Link Clicked (T10)</option>
                <option value="campaign_message_read">Marketing Msg Read (T11)</option>
                <option value="webhook_payload_received">Inbound Webhook (T12)</option>
            </optgroup>
            <optgroup label="Scheduled & Event">
                <option value="scheduled_trigger">📅 Scheduled / Cron Trigger</option>
                <option value="tag_added">🏷️ Tag Added to Contact</option>
                <option value="form_submitted">📋 Web Form Submitted</option>
            </optgroup>
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
                @foreach($this->approvedTemplates as $tmpl)
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
    {{-- Multi-Trigger Sections --}}
    <div x-show="$wire.triggerType === 'multi'" class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 min-h-[300px]">
        <p class="text-[10px] font-black uppercase text-slate-500 mb-4 tracking-widest">Active Multi-Triggers</p>
        <div class="space-y-4">
            <template x-for="(st, idx) in ($wire.triggerConfig.triggers || [])" :key="idx">
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 relative group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 bg-wa-teal/10 text-wa-teal rounded" x-text="st.type"></span>
                        <button type="button" @click="$wire.removeSubTrigger(idx)" class="text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300" x-text="st.type === 'keyword' ? st.keywords.join(', ') : 'Event: ' + st.type"></p>
                </div>
            </template>
            <button type="button" wire:click="addSubTrigger" class="w-full py-2 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase text-slate-400 hover:text-wa-teal hover:border-wa-teal transition-all">
                + Add Sub-Trigger
            </button>
        </div>
    </div>

    {{-- Field Updated Trigger --}}
    <div x-show="$wire.triggerType === 'contact_field_updated'" class="space-y-4">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Field to Monitor</label>
            <input type="text" wire:model.blur="triggerConfig.field_name" placeholder="e.g. status, score"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Wait for Specific Value (Optional)</label>
            <input type="text" wire:model.blur="triggerConfig.field_value" placeholder="e.g. qualified"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
    </div>

    {{-- Inactivity Trigger --}}
    <div x-show="$wire.triggerType === 'inactivity'" class="space-y-4">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Inactivity Period (Days)</label>
            <input type="number" wire:model.blur="triggerConfig.days" min="1" max="90"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
    </div>

    {{-- Birthday / Date --}}
    <div x-show="$wire.triggerType === 'contact_birthday'" class="space-y-4">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Field Name</label>
            <input type="text" wire:model.blur="triggerConfig.field_name" placeholder="default: birthday"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Days Before (Lead time)</label>
            <input type="number" wire:model.blur="triggerConfig.days_before" min="0" max="30"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
    </div>

    {{-- Template Delivered T4 --}}
    <div x-show="['template_delivered'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Listen for Template</label>
        <select wire:model.live="triggerConfig.template_name" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Any Template</option>
            @foreach($this->approvedTemplates as $tmpl)
                <option value="{{ data_get($tmpl, 'name') }}">{{ data_get($tmpl, 'name') }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tag Assigned --}}
    <div x-show="['tag_assigned'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">When Tag is Assigned</label>
        <input type="text" wire:model.blur="triggerConfig.tag_name" list="available-stage-tags" 
            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="Select or type tag...">
    </div>

    {{-- Deal Triggers T3, T3.1, T3.2 --}}
    <div x-show="['deal_stage_changed', 'deal_won', 'deal_lost'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Select Pipeline</label>
        <select wire:model.live="triggerConfig.pipeline_id" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Any Pipeline</option>
            @foreach($this->availablePipelines as $pipe)
                <option value="{{ $pipe['id'] }}">{{ $pipe['name'] }}</option>
            @endforeach
        </select>
        <div x-show="$wire.triggerType === 'deal_stage_changed'">
            <label class="block text-xs font-bold text-slate-500 uppercase mt-2">To Stage (Optional)</label>
            <input type="text" wire:model.blur="triggerConfig.stage_id" placeholder="e.g. Qualified" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
        </div>
    </div>

    {{-- Commerce T7, T8, T9 --}}
    <div x-show="['cart_abandoned'].includes($wire.triggerType)" class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
        <p class="text-xs text-slate-500">Triggers when a user adds items but doesn't checkout after the window set in Store Settings.</p>
    </div>
    <div x-show="['order_status_changed'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">New Status</label>
        <select wire:model.live="triggerConfig.status" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div x-show="['low_stock'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Threshold Level</label>
        <input type="number" wire:model.blur="triggerConfig.threshold" min="1" max="100" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm" placeholder="e.g. 5">
    </div>

    {{-- Email & Outreach T10, T11 --}}
    <div x-show="['email_opened', 'email_link_clicked'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Match Email Template</label>
        <select wire:model.live="triggerConfig.template_id" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Any Template</option>
            @foreach($this->availableEmailTemplates as $et)
                <option value="{{ $et['id'] }}">{{ $et['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="['campaign_message_read'].includes($wire.triggerType)" class="space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase">Listen for Campaign</label>
        <select wire:model.live="triggerConfig.campaign_id" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <option value="">Any Campaign</option>
            @foreach($this->availableCampaigns as $c)
                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Webhook Received T12 --}}
    <div x-show="$wire.triggerType === 'webhook_payload_received'" class="space-y-4">
        <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-xl text-[11px] text-amber-700 dark:text-amber-300">
            Use this to trigger flows when data arrives from other apps. You can add conditions based on the JSON payload.
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Webhook Source</label>
            <select wire:model.live="triggerConfig.source_id" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="">Any Source</option>
            </select>
        </div>
    </div>

    {{-- Scheduled / Cron Trigger --}}
    <div x-show="$wire.triggerType === 'scheduled_trigger'" class="space-y-4">
        <div class="p-3 bg-sky-50 dark:bg-sky-900/10 border border-sky-100 dark:border-sky-900/30 rounded-xl text-[11px] text-sky-700 dark:text-sky-300">
            📅 Fires on a recurring schedule for a filtered contact segment. Use for newsletters, billing reminders, check-ins, etc.
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Schedule</label>
            <select wire:model.live="triggerConfig.cron_preset" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="daily">🌅 Daily (every day at set time)</option>
                <option value="weekly_mon">📆 Weekly — Monday</option>
                <option value="weekly_fri">📆 Weekly — Friday</option>
                <option value="monthly_1">🗓️ Monthly — 1st of month</option>
                <option value="monthly_15">🗓️ Monthly — 15th of month</option>
                <option value="custom">⚙️ Custom Cron Expression</option>
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Time of Day</label>
            <input type="time" wire:model.blur="triggerConfig.schedule_time"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                value="09:00">
        </div>
        <div x-show="$wire.triggerConfig.cron_preset === 'custom'" class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Custom Cron Expression</label>
            <input type="text" wire:model.blur="triggerConfig.cron_expression"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono"
                placeholder="e.g. 0 9 * * 1">
            <p class="text-[9px] text-slate-400">Standard cron format: minute hour day month weekday</p>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Contact Filter (Tag)</label>
            <input type="text" wire:model.blur="triggerConfig.filter_tag" list="available-stage-tags"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="Only run for contacts with this tag...">
            <p class="text-[9px] text-slate-400">Leave blank to run for ALL active contacts.</p>
        </div>
        <div class="p-3 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/20 rounded-xl">
            <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400">⚠️ Make sure <code>php artisan schedule:run</code> is in your server crontab, otherwise this trigger will not fire.</p>
        </div>
    </div>

    {{-- Tag Added Trigger --}}
    <div x-show="$wire.triggerType === 'tag_added'" class="space-y-4">
        <div class="p-3 bg-teal-50 dark:bg-teal-900/10 border border-teal-100 dark:border-teal-900/30 rounded-xl text-[11px] text-teal-700 dark:text-teal-300">
            🏷️ Fires automatically when a specific tag is added to a contact — either by an agent, another automation, or a webhook.
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Trigger when Tag is Added <span class="text-rose-400">*</span></label>
            <input type="text" wire:model.blur="triggerConfig.tag_name" list="available-stage-tags"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. hot-lead, payment-due, churned">
            <p class="text-[9px] text-slate-400">The flow starts the moment this tag is added to a contact.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Who can add this tag?</label>
            <select wire:model.live="triggerConfig.tag_source" class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="any">Any source (agent, automation, API)</option>
                <option value="agent">Agent / Manual only</option>
                <option value="automation">Automation only</option>
                <option value="api">API / Webhook only</option>
            </select>
        </div>
        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div>
                <span class="text-xs font-bold">Deduplicate</span>
                <p class="text-[10px] text-slate-400">Don't fire again if contact already ran this flow</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.live="triggerConfig.deduplicate" class="sr-only peer" checked>
                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-wa-teal"></div>
            </label>
        </div>
    </div>

    {{-- Web Form Submission Trigger --}}
    <div x-show="$wire.triggerType === 'form_submitted'" class="space-y-4">
        <div class="p-3 bg-violet-50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-900/30 rounded-xl text-[11px] text-violet-700 dark:text-violet-300">
            📋 Fires when a web form (Google Forms, Typeform, custom HTML) is submitted. The form must send a POST request to the inbound webhook endpoint below.
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Webhook Endpoint URL <span class="text-slate-400 normal-case font-normal">(use in your form)</span></label>
            <div class="flex items-center gap-2 p-3 bg-slate-900 dark:bg-black rounded-xl font-mono text-[10px] text-wa-teal break-all">
                {{ url('/api/webhook/inbound') }}
            </div>
            <p class="text-[9px] text-slate-400">Configure your form to POST to this URL with <code>phone</code> and optionally <code>name</code>, <code>email</code> fields.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Form Source Label <span class="text-slate-400 normal-case font-normal">(optional)</span></label>
            <input type="text" wire:model.blur="triggerConfig.form_source"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                placeholder="e.g. lead-gen-form, contact-page">
            <p class="text-[9px] text-slate-400">Used to filter which form submission fires this automation. Must match the <code>source</code> field in the POST payload.</p>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-500 uppercase">Field Mappings <span class="text-slate-400 normal-case font-normal">(form field → contact field)</span></label>
            <div class="space-y-2">
                <div class="grid grid-cols-2 gap-2 text-[9px] font-black uppercase text-slate-400 px-1">
                    <span>Form Field Name</span>
                    <span>Contact / Variable</span>
                </div>
                @foreach([['phone','phone_number'],['first_name','name'],['email','email'],['city','city']] as [$form, $contact])
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" value="{{ $form }}"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono py-1.5 px-2" readonly>
                    <input type="text" value="{{ $contact }}"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono py-1.5 px-2" readonly>
                </div>
                @endforeach
            </div>
            <p class="text-[9px] text-slate-400">Default mappings shown above. Customize via the Inbound Webhook settings page.</p>
        </div>
    </div>
</div>
