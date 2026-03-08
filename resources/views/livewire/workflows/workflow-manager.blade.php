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
                <div class="col-span-2 pl-2 overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-medium text-gray-900 dark:text-white">Actions Sequence</h3>
                        <button wire:click="addAction" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Action
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach($actions as $index => $action)
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 relative group">
                                <button wire:click="removeAction({{ $index }})" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>

                                <div class="flex gap-4 mb-3">
                                    <div class="w-1/3">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Action Type</label>
                                        <select wire:model="actions.{{ $index }}.type" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                            <option value="add_tag">Add Tag</option>
                                            <option value="remove_tag">Remove Tag</option>
                                            <option value="update_deal_stage">Update Deal Stage</option>
                                            <option value="create_task">Create Task</option>
                                            <option value="send_email">Send Email</option>
                                        </select>
                                    </div>
                                    <div class="w-1/4">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Delay (mins)</label>
                                        <input type="number" wire:model="actions.{{ $index }}.delay" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    </div>
                                </div>

                                <!-- Dynamic Config Fields -->
                                <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700/50">
                                    @if($action['type'] === 'add_tag' || $action['type'] === 'remove_tag')
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

                                    @if($action['type'] === 'update_deal_stage')
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

                                    @if($action['type'] === 'create_task')
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
                        @endforeach

                        @if(empty($actions))
                            <div class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                <p class="text-sm text-gray-500">No actions added yet.</p>
                                <button wire:click="addAction" class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm font-medium">Add your first action</button>
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
