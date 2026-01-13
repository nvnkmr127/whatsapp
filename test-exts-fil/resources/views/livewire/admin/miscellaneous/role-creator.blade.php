<div class="px-4 md:px-0">
  <x-slot:title>
    {{ t('create_role') }}
  </x-slot:title>

  <div class="pb-6">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
      {{ $role->exists ? t('edit_role') : t('add_role') }}
    </h1>
  </div>

  <form wire:submit.prevent="save">
    <div class="flex flex-col lg:flex-row gap-6 mb-20">
      <!-- Left Column - Role Details and Permissions -->
      <div class="w-full lg:w-8/12">
        <x-card class="rounded-lg shadow-sm">
          <x-slot:content>
            <!-- Role Name Input -->
            <div class="mb-6">
              <div class="flex items-center gap-1">
                <span class="text-red-500">*</span>
                <x-label for="role.name" class="font-medium">
                  {{ t('role') }}
                </x-label>
              </div>
              <x-input wire:model.defer="role.name" type="text" id="role.name"
                placeholder="{{ t('enter_role_name') }}" class="mt-1 block w-full"
                autocomplete="off" />
              <x-input-error for="role.name" class="mt-1" />
            </div>

            <!-- Permissions Table -->
            <div class="border rounded-lg border-gray-200 dark:border-gray-700 overflow-hidden">
              <!-- Table Header -->
              <div
                class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:grid sm:grid-cols-12 sm:gap-0">
                  <div
                    class="p-4 font-medium text-gray-700 dark:text-gray-300 flex items-center border-b sm:border-b-0 sm:border-r sm:col-span-4 border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-puzzle-piece
                      class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400 flex-shrink-0" />
                    {{ t('features') }}
                  </div>
                  <div
                    class="p-4 font-medium text-gray-700 dark:text-gray-300 flex items-center sm:col-span-8">
                    <x-heroicon-o-key
                      class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400 flex-shrink-0" />
                    {{ t('capabilities') }}
                  </div>
                </div>
              </div>

              <!-- Table Body -->
              <div class="max-h-[500px] overflow-y-auto">
                @php
                  $groupedPermissions = [];

                  // Grouping permissions by module
                  foreach ($this->permission as $permission) {
                      $parts = explode('.', $permission->name);
                      $module = ucwords(str_replace(['_', '-'], ' ', $parts[0]));
                      $action = ucfirst(str_replace(['_', '-'], ' ', $parts[1] ?? ''));

                      // Store actions under the same module
                      $groupedPermissions[$module][] = [
                          'id' => $permission->name,
                          'name' => $action,
                      ];
                  }
                @endphp

                @foreach ($groupedPermissions as $module => $actions)
                  <div
                    class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                    <div class="grid grid-cols-12 gap-0">
                      <!-- Module Name -->
                      <div
                        class="col-span-12 sm:col-span-4 p-4 text-gray-700 dark:text-gray-300 font-medium border-b sm:border-b-0 sm:border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        {{ $module }}
                      </div>

                      <!-- Permissions -->
                      <div class="col-span-12 sm:col-span-8 p-4">
                        <div class="flex flex-wrap gap-3">
                          @foreach ($actions as $action)
                            <label
                              class="flex items-center p-2 bg-white dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150 cursor-pointer">
                              <input type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600"
                                value="{{ $action['id'] }}" wire:model.defer="selectedPermissions">
                              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $action['name'] }}
                              </span>
                            </label>
                          @endforeach
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </x-slot:content>
        </x-card>
      </div>

      <!-- Right Column - Users with this role -->
      <div class="w-full lg:w-4/12">
        <x-card class="rounded-lg shadow-sm">
          <x-slot:header>
            <div class="flex items-center">
              <x-heroicon-o-users class="w-6 h-6 mr-2 text-indigo-600 dark:text-indigo-400" />
              <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ t('users_using_this_role') }}
              </h2>
            </div>
          </x-slot:header>

          <x-slot:content>
            <livewire:admin.table.role-assignee-table :role_id="$role->id" />
          </x-slot:content>
        </x-card>
      </div>

      <!-- Footer Action Bar -->
      <div
        class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 z-10">
        <div class="flex justify-end px-6 py-3">
          <x-button.secondary class="mx-2"><a
              href="{{ route('admin.roles.list') }}">{{ t('cancel') }}</a></x-button.secondary>
          <x-button.loading-button type="submit" target="save">
            {{ $role->exists ? t('update_button') : t('save_changes_button') }}
          </x-button.loading-button>
        </div>
      </div>
    </div>
  </form>
</div>
