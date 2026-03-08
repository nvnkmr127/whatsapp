<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Automation Workflows</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Automate your CRM processes with triggers and actions.</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            New Workflow
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($workflows as $workflow)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 relative group hover:border-indigo-500 transition-colors">
                <div class="absolute top-4 right-4">
                    <span class="px-2 py-1 text-xs rounded-full {{ $workflow->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $workflow->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">{{ $workflow->description }}</p>

                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        Trigger: {{ str_replace('_', ' ', $workflow->trigger_type) }}
                    </span>
                    <span>•</span>
                    <span>{{ $workflow->actions_count }} actions</span>
                </div>

                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-400">Run {{ $workflow->execution_count }} times</span>
                    <button wire:click="edit({{ $workflow->id }})" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        Edit Workflow
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create/Edit Modal -->
    <x-dialog-modal wire:model="showCreateModal" maxWidth="4xl">
        <x-slot name="title">
            {{ $activeWorkflowId ? 'Edit Workflow' : 'Create Workflow' }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[600px]">
                <!-- Sidebar: Config -->
                <div class="col-span-1 border-r border-gray-200 dark:border-gray-700 pr-6 space-y-4">
                    <div>
                        <x-label for="name" value="Workflow Name" />
                        <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                    </div>

                    <div>
                        <x-label for="description" value="Description" />
                        <textarea id="description" wire:model="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"></textarea>
                    </div>

                    <div>
                        <x-label for="triggerType" value="Trigger Event" />
                        <select id="triggerType" wire:model="triggerType" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="contact_created">Contact Created</option>
                            <option value="deal_stage_changed">Deal Stage Changed</option>
                            <option value="tag_added">Tag Added</option>
                            <option value="onboarding_incomplete_24h">Incomplete Setup (24h)</option>
                            <option value="onboarding_no_activity_3d">No Activity (3 Days)</option>
                        </select>
                    </div>

                    <!-- Trigger Config -->
                    @if($triggerType === 'deal_stage_changed')
                        <div>
                            <x-label for="triggerStage" value="Target Stage" />
                            <select id="triggerStage" wire:model="triggerConfig.stage_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">Any Stage</option>
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($triggerType === 'tag_added')
                        <div>
                            <x-label for="triggerTag" value="Specific Tag" />
                            <select id="triggerTag" wire:model="triggerConfig.tag_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="">Any Tag</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center">
                            <x-checkbox wire:model="isActive" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                        </label>
                    </div>
                </div>

                <!-- Main: Actions -->
                <div class="col-span-2 pl-2 overflow-y-auto relative">
                    <!-- Start Node -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="bg-slate-900 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl z-10 border-4 border-white dark:border-gray-800">
                            Trigger: {{ str_replace('_', ' ', $triggerType) }}
                        </div>
                        <div class="h-8 w-0.5 bg-slate-200 dark:bg-slate-700"></div>
                        <div class="flex gap-2 relative z-10">
                            <button wire:click="addAction" class="bg-white border-2 border-slate-200 text-slate-400 rounded-full p-1 hover:border-indigo-500 hover:text-indigo-500 transition-colors" title="Add Action">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                            <button wire:click="addCondition" class="bg-white border-2 border-slate-200 text-slate-400 rounded-full p-1 hover:border-amber-500 hover:text-amber-500 transition-colors" title="Add Condition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-0 relative">
                        <!-- Vertical Line -->
                        <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-slate-200 dark:bg-slate-700 -translate-x-1/2 z-0"></div>

                        @foreach($actions as $index => $action)
                            @if(($action['type'] ?? 'action') === 'condition')
                                <!-- Condition Block -->
                                <div class="relative z-10 py-4">
                                    <div class="bg-amber-50 dark:bg-gray-800 p-6 rounded-2xl border-2 border-amber-200 dark:border-amber-700 shadow-sm relative group">
                                        <button wire:click="removeAction({{ $index }})" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>

                                        <div class="mb-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="bg-amber-100 text-amber-600 p-1.5 rounded-lg">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                </div>
                                                <h4 class="text-xs font-black uppercase tracking-widest text-amber-600">Condition Check</h4>
                                            </div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <input type="text" wire:model="actions.{{ $index }}.config.field" placeholder="Field (e.g. contact.tags)" class="text-xs border-amber-200 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                                <select wire:model="actions.{{ $index }}.config.operator" class="text-xs border-amber-200 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                                    <option value="=">Equals</option>
                                                    <option value="!=">Not Equals</option>
                                                    <option value="contains">Contains</option>
                                                    <option value="not_contains">Not Contains</option>
                                                    <option value=">">Greater Than</option>
                                                    <option value="<">Less Than</option>
                                                </select>
                                                <input type="text" wire:model="actions.{{ $index }}.config.value" placeholder="Value" class="text-xs border-amber-200 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-6 border-t border-amber-200/50 pt-4">
                                            <!-- True Branch -->
                                            <div class="border-r border-amber-100 pr-4 space-y-3">
                                                <h5 class="text-[10px] font-bold text-emerald-600 uppercase flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> If True (Then)
                                                </h5>
                                                @foreach($action['true_nodes'] as $childIndex => $child)
                                                    <div class="bg-white p-3 rounded-xl border border-emerald-100 shadow-sm relative group/child">
                                                        <button wire:click="removeNestedAction({{ $index }}, 'true_nodes', {{ $childIndex }})" class="absolute top-1 right-1 text-gray-300 hover:text-red-500 opacity-0 group-hover/child:opacity-100 transition-opacity">×</button>
                                                        <select wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.action_type" class="block w-full text-xs border-gray-200 rounded-lg mb-2">
                                                            <option value="add_tag">Add Tag</option>
                                                            <option value="remove_tag">Remove Tag</option>
                                                            <option value="create_task">Create Task</option>
                                                            <option value="send_email">Send Email</option>
                                                            <option value="send_whatsapp">Send WhatsApp</option>
                                                        </select>
                                                        <!-- Simplified Config for Nested -->
                                                        @if($child['action_type'] === 'send_whatsapp')
                                                            <input type="text" wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.template_name" placeholder="Template" class="w-full text-xs border-gray-200 rounded mb-1">
                                                        @elseif($child['action_type'] === 'add_tag')
                                                            <select wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.tag_id" class="w-full text-xs border-gray-200 rounded">
                                                                <option value="">Select Tag</option>
                                                                @foreach($tags as $tag) <option value="{{ $tag->id }}">{{ $tag->name }}</option> @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                <button wire:click="addNestedAction({{ $index }}, 'true_nodes')" class="w-full py-2 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors">+ Add Action</button>
                                            </div>

                                            <!-- False Branch -->
                                            <div class="pl-2 space-y-3">
                                                <h5 class="text-[10px] font-bold text-rose-600 uppercase flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> If False (Else)
                                                </h5>
                                                @foreach($action['false_nodes'] as $childIndex => $child)
                                                    <div class="bg-white p-3 rounded-xl border border-rose-100 shadow-sm relative group/child">
                                                        <button wire:click="removeNestedAction({{ $index }}, 'false_nodes', {{ $childIndex }})" class="absolute top-1 right-1 text-gray-300 hover:text-red-500 opacity-0 group-hover/child:opacity-100 transition-opacity">×</button>
                                                        <select wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.action_type" class="block w-full text-xs border-gray-200 rounded-lg mb-2">
                                                            <option value="add_tag">Add Tag</option>
                                                            <option value="remove_tag">Remove Tag</option>
                                                            <option value="create_task">Create Task</option>
                                                            <option value="send_email">Send Email</option>
                                                            <option value="send_whatsapp">Send WhatsApp</option>
                                                        </select>
                                                        <!-- Simplified Config for Nested -->
                                                        @if($child['action_type'] === 'send_whatsapp')
                                                            <input type="text" wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.template_name" placeholder="Template" class="w-full text-xs border-gray-200 rounded mb-1">
                                                        @elseif($child['action_type'] === 'add_tag')
                                                            <select wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.tag_id" class="w-full text-xs border-gray-200 rounded">
                                                                <option value="">Select Tag</option>
                                                                @foreach($tags as $tag) <option value="{{ $tag->id }}">{{ $tag->name }}</option> @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                <button wire:click="addNestedAction({{ $index }}, 'false_nodes')" class="w-full py-2 text-xs font-bold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors">+ Add Action</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="relative z-10 py-4">
                                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm relative group hover:border-indigo-500 hover:shadow-md transition-all">
                                        <button wire:click="removeAction({{ $index }})" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>

                                        <div class="flex gap-4 mb-3">
                                            <div class="w-1/3">
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Action Type</label>
                                                <select wire:model="actions.{{ $index }}.action_type" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                    <option value="add_tag">Add Tag</option>
                                                    <option value="remove_tag">Remove Tag</option>
                                                    <option value="update_deal_stage">Update Deal Stage</option>
                                                    <option value="create_task">Create Task</option>
                                                    <option value="send_email">Send Email</option>
                                                    <option value="send_whatsapp">Send WhatsApp (System)</option>
                                                </select>
                                            </div>
                                            <div class="w-1/4">
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Delay (mins)</label>
                                                <input type="number" wire:model="actions.{{ $index }}.delay" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                            </div>
                                        </div>

                                        <!-- Dynamic Config Fields -->
                                        <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700/50">
                                            @if(($actions[$index]['action_type'] ?? '') === 'send_whatsapp')
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="col-span-2">
                                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Template Name</label>
                                                        <input type="text" wire:model="actions.{{ $index }}.config.template_name" placeholder="e.g. onboarding_welcome" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                        <p class="text-[10px] text-gray-400 mt-1">Must match a template in your System WABA.</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Language Code</label>
                                                        <input type="text" wire:model="actions.{{ $index }}.config.language" placeholder="en_US" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'add_tag' || ($actions[$index]['action_type'] ?? '') === 'remove_tag')
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tag</label>
                                                    <select wire:model="actions.{{ $index }}.config.tag_id" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                        <option value="">Select Tag</option>
                                                        @foreach($tags as $tag)
                                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'update_deal_stage')
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">New Stage</label>
                                                    <select wire:model="actions.{{ $index }}.config.stage_id" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                        <option value="">Select Stage</option>
                                                        @foreach($stages as $stage)
                                                            <option value="{{ $stage->id }}">{{ $stage->name }} ({{ $stage->pipeline->name }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'create_task')
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div class="col-span-2">
                                                        <input type="text" wire:model="actions.{{ $index }}.config.title" placeholder="Task Title" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                    </div>
                                                    <div>
                                                        <select wire:model="actions.{{ $index }}.config.priority" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                            <option value="low">Low Priority</option>
                                                            <option value="medium">Medium Priority</option>
                                                            <option value="high">High Priority</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <select wire:model="actions.{{ $index }}.config.assigned_to_id" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                                            <option value="">Assign To...</option>
                                                            @foreach($users as $user)
                                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @if(empty($actions))
                            <div class="text-center py-8 relative z-10">
                                <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl p-6">
                                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No actions added yet</p>
                                    <p class="text-xs text-slate-400 mt-1">Click the + button above to start</p>
                                </div>
                            </div>
                        @else
                            <!-- End Node -->
                            <div class="flex flex-col items-center mt-4 pb-12 relative z-10">
                                <div class="h-6 w-0.5 bg-slate-200 dark:bg-slate-700"></div>
                                <div class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-100">
                                    End Workflow
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCreateModal', false)" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>

            <x-button class="ml-2" wire:click="save" wire:loading.attr="disabled">
                Save Workflow
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
