<div>
    <div class="flex flex-col lg:flex-row gap-6 items-start mb-20">
        <div class="w-full lg:w-3/5">
            <form wire:submit.prevent="changeProfile" class="mt-3">
                <x-card class="relative rounded-lg overflow-hidden">
                    <x-slot:header>
                        <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
                            {{ t('profile') }}
                        </h1>
                    </x-slot:header>
                    <x-slot:content>
                        <!-- Profile Image Section -->
                        <div class="w-full">
                            <div x-data="{
                                photoName: null,
                                photoPreview: null,
                                maxSizeMB: 5,
                                allowedTypes: ['jpeg', 'png'],
                                errorMessage: '',
                                validateFile(event) {
                                    this.errorMessage = ''; // Clear previous errors
                                    let file = event.target.files[0];
                            
                                    if (!file) return;
                            
                                    // Check file extension
                                    let fileExtension = file.name.split('.').pop().toLowerCase();
                                    if (!this.allowedTypes.includes(fileExtension)) {
                                        this.errorMessage = `Invalid file type. Allowed: ${this.allowedTypes.join(', ')}`;
                                        event.target.value = ''; // Clear the input
                                        return;
                                    }
                            
                                    // Check file size (5MB = 5 * 1024 * 1024 bytes)
                                    const maxSizeBytes = this.maxSizeMB * 1024 * 1024;
                                    if (file.size > maxSizeBytes) {
                                        this.errorMessage = `File size exceeds ${this.maxSizeMB}MB limit`;
                                        event.target.value = ''; // Clear the input
                                        return;
                                    }
                            
                                    this.photoName = file.name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => this.photoPreview = e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            }">
                                <x-label class="mt-[2px]" for="profile_image_url" :value="t('profile_image')" />
                                <p class="text-xs text-gray-500 mt-1">{{ t('allowed_fromats_jpeg_png_max_5') }} </p>
                                <div class="flex items-center gap-8 mt-1">
                                    <div x-show="!photoPreview">
                                        <img src="{{ file_exists(public_path('storage/' . $user->profile_image_url)) && $user->profile_image_url ? asset('storage/' . $user->profile_image_url) : asset('img/user-placeholder.jpg') }}"
                                            alt="{{ $user->first_name }} {{ $user->last_name }}"
                                            class="h-12 w-12 rounded-full object-cover glightbox cursor-pointer">
                                    </div>
                                    <div x-cloak x-show="photoPreview">
                                        <span class="block h-12 w-12 rounded-full bg-cover bg-center"
                                            x-bind:style="'background-image: url(' + photoPreview + ');'"></span>
                                    </div>
                                    <div>
                                        <input wire:model="profile_image_url" x-ref="photo"
                                            x-on:change="validateFile($event)" type="file" class="hidden"
                                            accept=".jpg,.jpeg,.png" />
                                        <x-button.secondary
                                            x-on:click="$refs.photo.click();">{{ t('change') }}</x-button.secondary>
                                        @if (file_exists(public_path('storage/' . $user->profile_image_url)) && $user->profile_image_url)
                                            <x-button.text wire:click="removeProfileImage"
                                                class="ml-3">{{ t('remove_img') }}</x-button.text>
                                        @endif
                                    </div>
                                </div>
                                <!-- Error Message -->
                                <p x-show="errorMessage" class="text-red-500 text-sm mt-1" x-text="errorMessage">
                                </p>
                                <x-input-error class="mt-1" for="profile_image_url" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                            <div>
                                <div class="flex items-center gap-1">
                                    <span class="text-red-500">*</span>
                                    <x-label for="firstname" :value="t('firstname')" />
                                </div>
                                <x-input type="text" wire:model.defer="firstname" class="mt-1 block w-full" />
                                <x-input-error for="firstname" class="mt-2" />
                            </div>
                            <div>
                                <div class="flex items-center gap-1">
                                    <span class="text-red-500">*</span>
                                    <x-label for="lastname" :value="t('lastname')" />
                                </div>
                                <x-input type="text" wire:model.defer="lastname" class="mt-1 block w-full" />
                                <x-input-error for="lastname" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                            <div>
                                <div class="flex items-center gap-1">
                                    <span class="text-red-500">*</span>
                                    <x-label for="email" :value="t('email')" />
                                </div>
                                <x-input type="text" wire:model.defer="email" class="mt-1 block w-full" />
                                <x-input-error for="email" class="mt-2" />
                            </div>
                            <div class="mt-1">
                                <div class="flex items-center gap-1">
                                    <span class="text-red-500">*</span>
                                    <x-label for="phoneNumberInput" :value="t('phone')" />
                                </div>
                                <div wire:ignore x-data="{ phone: @entangle('phone'), errorMessage: '' }">
                                    <x-input class="phone-input mt-[2px]" x-ref="phone" id="phone" type="tel"
                                        wire:model.defer="phone" maxlength="18" x-model="phone"
                                        x-on:change="
                                                    if (phone.length == 18) {
                                                        errorMessage = 'You can only enter up to 18 digits';
                                                        phone = phone.slice(0, 18);
                                                    } else {
                                                        errorMessage = '';
                                                    }
                                                " />
                                    <p x-show="errorMessage" class="text-sm text-red-600 dark:text-red-400 mt-1"
                                        x-text="errorMessage"></p>
                                </div>
                                <x-input-error for="phone" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-4" wire:ignore>
                            <x-label for="default_language" :value="t('default_language')" />
                            <x-select wire:model.defer="default_language" id="default_language"
                                class="block w-full mt-1 tom-select">
                                <option value="">{{ t('select_language') }}</option>
                                @foreach (getLanguage(null, ['code', 'name']) as $language)
                                    <option value="{{ $language->code }}"> {{ $language->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </x-slot:content>
                    <!-- Submit Button -->
                    <x-slot:footer class="bg-slate-50 dark:bg-transparent">
                        <div class="flex justify-end">
                            <x-button.loading-button type="submit" target="changeProfile">
                                {{ t('save') }}
                            </x-button.loading-button>
                        </div>
                    </x-slot:footer>
                </x-card>
            </form>
        </div>

        <div class="w-full lg:w-2/5">
            <form wire:submit.prevent="changePassword" class="mt-3">
                <x-card class="rounded-lg shadow-sm">
                    <x-slot:header>
                        <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
                            {{ t('change_password_heading') }}
                        </h1>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="mt-3">
                            <div class="flex items-center gap-1">
                                <span class="text-red-500">*</span>
                                <x-label for="current_password" :value="t('current_password')" />
                            </div>
                            <x-input id="current_password" wire:model.defer="current_password" type="password"
                                class="mt-1 block w-full" />
                            <x-input-error for="current_password" class="mt-2" />
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center gap-1">
                                <span class="text-red-500">*</span>
                                <x-label for="password" :value="t('new_password')" />
                            </div>
                            <x-input id="password" wire:model.defer="password" type="password"
                                class="mt-1 block w-full" />
                            <x-input-error for="password" class="mt-2" />
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center gap-1">
                                <span class="text-red-500">*</span>
                                <x-label for="password_confirmation" :value="t('confirm_password')" />
                            </div>
                            <x-input id="password_confirmation" wire:model.defer="password_confirmation"
                                type="password" class="mt-1 block w-full" />
                            <x-input-error for="password_confirmation" class="mt-2" />
                        </div>
                    </x-slot:content>
                    <!-- Submit Button -->
                    <x-slot:footer class="bg-slate-50 dark:bg-transparent">
                        <div class="flex justify-end">
                            <x-button.loading-button type="submit" target="changePassword">
                                {{ t('save') }}
                            </x-button.loading-button>
                        </div>
                    </x-slot:footer>
                </x-card>
            </form>
        </div>
    </div>
</div>
