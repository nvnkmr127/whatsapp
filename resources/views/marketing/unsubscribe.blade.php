<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50 dark:bg-slate-900">
        <div
            class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white dark:bg-slate-800 shadow-2xl shadow-indigo-500/10 overflow-hidden sm:rounded-3xl border border-slate-100 dark:border-slate-700">

            <div class="flex flex-col items-center text-center">
                <div
                    class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl flex items-center justify-center mb-6">
                    @if($success)
                        <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    @endif
                </div>

                @if($success)
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Unsubscribed Successfully</h1>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                        We've removed your email from our marketing list. You'll still receive critical system notifications
                        like OTPs and security alerts.
                    </p>
                @else
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Invalid Request</h1>
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                        The unsubscribe token is invalid or has expired. If you're still receiving emails, please contact
                        support.
                    </p>
                @endif

                <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700 w-full">
                    <a href="/"
                        class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-2xl hover:bg-indigo-700 transition w-full shadow-lg shadow-indigo-600/20">
                        Back to Home
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-guest-layout>