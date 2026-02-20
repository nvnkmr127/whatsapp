<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900 bg-opacity-70 backdrop-blur-sm"
            x-data @keydown.escape.prevent="" @click.prevent="">

            <div class="relative w-full max-w-md p-4 animate-in fade-in zoom-in duration-300">
                <div
                    class="relative bg-white rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 dark:bg-gray-800">
                    <div class="p-8">
                        <!-- Icon / Avatar -->
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-wa-primary/10 mb-6 border-4 border-white dark:border-gray-800 shadow-sm">
                            <svg class="w-8 h-8 text-wa-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>

                        <div class="text-center mb-6">
                            <h3 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">Welcome Aboard!</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Let's set up your business profile before we
                                launch you into the wizard.</p>
                        </div>

                        <form wire:submit.prevent="save" class="space-y-5 text-left">
                            <div>
                                <x-label for="company_name" value="Business Name" class="font-medium" />
                                <x-input id="company_name" type="text" class="mt-1 block w-full shadow-sm rounded-lg"
                                    wire:model="company_name" placeholder="Acme Inc." required autofocus
                                    autocomplete="organization" />
                                <x-input-error for="company_name" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="phone" value="Your Phone Number" class="font-medium" />
                                <x-input id="phone" type="tel" class="mt-1 block w-full shadow-sm rounded-lg"
                                    wire:model="phone" placeholder="+1234567890" required autocomplete="tel" />
                                <x-input-error for="phone" class="mt-2" />
                            </div>

                            <div class="pt-4">
                                <button type="submit"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-wa-primary hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wa-primary transition duration-150 ease-in-out">
                                    <span wire:loading.remove wire:target="save">Continue to Setup Wizard &rarr;</span>
                                    <span wire:loading wire:target="save">Saving...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>