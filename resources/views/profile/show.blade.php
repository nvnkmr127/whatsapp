<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <!-- Page Header -->
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    User <span class="text-indigo-500">Profile</span>
                </h1>
            </div>

            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                                    Profile Information</h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Update your account's profile information and email address.
                                </p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2">
                                @livewire('profile.update-profile-information-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                                    Update Password</h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Ensure your account is using a long, random password to stay secure.
                                </p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2">
                                @livewire('profile.update-password-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                                    Two-Factor Authentication</h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Add additional security to your account using two-factor authentication.
                                </p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2">
                                @livewire('profile.two-factor-authentication-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div class="p-8 md:p-10">
                    <div class="md:grid md:grid-cols-3 md:gap-6">
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                                Browser Sessions</h3>
                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Manage and log out your active sessions on other browsers and devices.
                            </p>
                        </div>
                        <div class="mt-5 md:mt-0 md:col-span-2">
                            @livewire('profile.logout-other-browser-sessions-form')
                        </div>
                    </div>
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="md:grid md:grid-cols-3 md:gap-6">
                            <div class="md:col-span-1">
                                <h3 class="text-lg font-black uppercase tracking-tight text-rose-500">Delete Account</h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Permanently delete your account.
                                </p>
                            </div>
                            <div class="mt-5 md:mt-0 md:col-span-2">
                                @livewire('profile.delete-user-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>