<x-guest-layout>
    <div class="max-w-md mx-auto bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/60 dark:border-slate-800 p-8 mt-10">
        <h1 class="font-serif text-2xl text-slate-900 dark:text-white">Allow access?</h1>
        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
            <span class="font-semibold text-slate-900 dark:text-white">{{ $client->name }}</span>
            wants to connect to your workspace as
            <span class="font-semibold text-slate-900 dark:text-white">{{ auth()->user()->email }}</span>.
        </p>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            It will be able to use your WhatsApp tools: read contacts and conversations, send messages,
            and manage automations — within your plan limits.
        </p>

        <form method="POST" action="{{ route('oauth.approve') }}" class="mt-6 flex gap-3">
            @csrf
            @foreach(['client_id', 'redirect_uri', 'state', 'code_challenge', 'code_challenge_method', 'response_type', 'scope'] as $key)
                <input type="hidden" name="{{ $key }}" value="{{ $query[$key] ?? '' }}">
            @endforeach
            <button type="submit" name="decision" value="approve"
                class="flex-1 px-4 py-2.5 rounded-xl bg-[#D97757] hover:bg-[#C4633F] text-white text-sm font-medium transition-colors">
                Allow
            </button>
            <button type="submit" name="decision" value="deny"
                class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium transition-colors">
                Deny
            </button>
        </form>

        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
            You can revoke this access anytime from Developer → API Tokens.
        </p>
    </div>
</x-guest-layout>
