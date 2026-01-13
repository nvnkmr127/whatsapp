<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add New Company Workspace') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1">
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Company Details</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Create a new isolated workspace for a client. This will check "Tenant Isolation" and create
                            a new Super User for them.
                        </p>
                    </div>
                </div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="shadow sm:rounded-md sm:overflow-hidden">
                        <form action="{{ route('admin.tenants.store') }}" method="POST">
                            @csrf
                            <div class="px-4 py-5 bg-white dark:bg-gray-800 space-y-6 sm:p-6">

                                <div class="grid grid-cols-6 gap-6">
                                    <!-- Company Name -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label for="company_name"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company
                                            Name</label>
                                        <input type="text" name="company_name" id="company_name"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                                    </div>

                                    <!-- Plan -->
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="plan"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription
                                            Plan</label>
                                        <select id="plan" name="plan"
                                            class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-white">
                                            <option value="basic">Basic</option>
                                            <option value="pro">Pro (Recommended)</option>
                                            <option value="enterprise">Enterprise</option>
                                        </select>
                                    </div>

                                    <div class="col-span-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Owner Account
                                        </h4>
                                        <p class="text-xs text-gray-500 mb-4">This user will be the Administrator of the
                                            new company.</p>
                                    </div>

                                    <!-- Owner Name -->
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="owner_name"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner
                                            Name</label>
                                        <input type="text" name="owner_name" id="owner_name"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                                    </div>

                                    <!-- Owner Email -->
                                    <div class="col-span-6 sm:col-span-3">
                                        <label for="owner_email"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner
                                            Email</label>
                                        <input type="email" name="owner_email" id="owner_email"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                                    </div>

                                    <!-- Password -->
                                    <div class="col-span-6 sm:col-span-4">
                                        <label for="owner_password"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Temporary
                                            Password</label>
                                        <input type="password" name="owner_password" id="owner_password"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-md">
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6">
                                <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Create Workspace
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>