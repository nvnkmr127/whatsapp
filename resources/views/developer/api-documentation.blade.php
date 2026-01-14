<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
            </div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight uppercase tracking-tight">
                API <span class="text-purple-600">Documentation</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Introduction -->
            <div
                class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-tight">WhatsApp
                    Business API</h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    Our RESTful API allows you to programmatically manage contacts, send messages, and integrate
                    WhatsApp messaging into your applications.
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4">
                    <p class="text-sm font-bold text-blue-700 dark:text-blue-400">
                        <strong>Base URL:</strong> <code
                            class="bg-blue-100 dark:bg-blue-900/40 px-2 py-1 rounded">{{ $baseUrl }}</code>
                    </p>
                </div>
            </div>

            <!-- Authentication -->
            <div
                class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                <h3
                    class="text-xl font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                    Authentication
                </h3>
                <p class="text-slate-600 dark:text-slate-400 mb-4">All API requests require authentication using Bearer
                    tokens (Laravel Sanctum).</p>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 font-mono text-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-500 text-xs font-bold uppercase">Example Request Header</span>
                        <button onclick="copyToClipboard('auth-example')"
                            class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                    </div>
                    <pre id="auth-example"
                        class="text-slate-700 dark:text-slate-300">Authorization: Bearer YOUR_API_TOKEN</pre>
                </div>
                <p class="text-xs text-slate-500 mt-3">
                    Generate API tokens from the <a href="{{ route('profile.show') }}"
                        class="text-purple-600 hover:underline font-bold">Profile / API Tokens</a> page.
                </p>
            </div>

            <!-- Endpoints -->
            <div class="space-y-6">

                <!-- Contacts -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Contacts
                    </h3>

                    <!-- List Contacts -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-xs font-black uppercase">GET</span>
                            <code class="text-sm font-mono text-slate-700 dark:text-slate-300">/contacts</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Retrieve a paginated list of all
                            contacts.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('contacts-list')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="contacts-list"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X GET "{{ $baseUrl }}/contacts" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</pre>
                        </div>
                    </div>

                    <!-- Create/Update Contact -->
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-black uppercase">POST</span>
                            <code class="text-sm font-mono text-slate-700 dark:text-slate-300">/contacts</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Create or update a contact.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">Request Body</span>
                            </div>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "phone_number": "+1234567890",
  "name": "John Doe",
  "email": "john@example.com",
  "custom_attributes": {
    "source": "website",
    "plan": "premium"
  },
  "opt_in": true,
  "opt_in_source": "WEBSITE"
}</pre>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('contacts-create')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="contacts-create"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X POST "{{ $baseUrl }}/contacts" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+1234567890",
    "name": "John Doe",
    "opt_in": true
  }'</pre>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Messages
                    </h3>

                    <!-- Send Message -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-black uppercase">POST</span>
                            <code class="text-sm font-mono text-slate-700 dark:text-slate-300">/messages</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Send a text message or template to a
                            contact.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">Text Message Example</span>
                            </div>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "phone_number": "+1234567890",
  "type": "text",
  "message": "Hello from our API!"
}</pre>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">Template Message Example</span>
                            </div>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "phone_number": "+1234567890",
  "type": "template",
  "template_name": "welcome_message",
  "language": "en_US",
  "variables": {
    "name": "John",
    "code": "ABC123"
  }
}</pre>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('messages-send')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="messages-send"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X POST "{{ $baseUrl }}/messages" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+1234567890",
    "type": "text",
    "message": "Hello from our API!"
  }'</pre>
                        </div>
                    </div>

                    <!-- Get Conversation -->
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-xs font-black uppercase">GET</span>
                            <code
                                class="text-sm font-mono text-slate-700 dark:text-slate-300">/conversations/{phone}</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Retrieve conversation history for a
                            contact.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('conversations-get')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="conversations-get"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X GET "{{ $baseUrl }}/conversations/+1234567890" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</pre>
                        </div>
                    </div>
                </div>

                <!-- Templates -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Templates
                    </h3>

                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-xs font-black uppercase">GET</span>
                            <code class="text-sm font-mono text-slate-700 dark:text-slate-300">/templates</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">List all approved WhatsApp message
                            templates.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('templates-list')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="templates-list"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X GET "{{ $baseUrl }}/templates" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</pre>
                        </div>
                    </div>
                </div>

                <!-- Webhook Events -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Outbound
                        Webhook Events</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                        Subscribe to real-time events by configuring webhooks in the <a
                            href="{{ route('developer.webhooks') }}"
                            class="text-purple-600 hover:underline font-bold">Webhook Manager</a>.
                    </p>

                    <div class="space-y-4">
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-2 text-sm">message.received</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-3">Triggered when a new message is
                                received from a contact.</p>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "event": "message.received",
  "timestamp": "2026-01-14T08:30:00Z",
  "data": {
    "id": 123,
    "whatsapp_message_id": "wamid.xxx",
    "contact": {
      "id": 456,
      "phone_number": "+1234567890",
      "name": "John Doe"
    },
    "content": "Hello!",
    "type": "text"
  }
}</pre>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-2 text-sm">message.status_updated
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-3">Triggered when a message status
                                changes (sent, delivered, read, failed).</p>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "event": "message.status_updated",
  "timestamp": "2026-01-14T08:31:00Z",
  "data": {
    "id": 789,
    "whatsapp_message_id": "wamid.yyy",
    "status": "read",
    "read_at": "2026-01-14T08:31:00Z"
  }
}</pre>
                        </div>
                    </div>

                    <div
                        class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4">
                        <p class="text-sm font-bold text-blue-700 dark:text-blue-400">
                            <strong>Webhook Signature:</strong> All webhooks are signed with HMAC-SHA256 if you provide
                            a secret. Verify the <code
                                class="bg-blue-100 dark:bg-blue-900/40 px-2 py-1 rounded">X-Webhook-Signature</code>
                            header.
                        </p>
                    </div>
                </div>

                <!-- Inbound Webhooks (from External Software) -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Inbound
                        Webhooks (Receive from External Software)</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                        Send webhooks from your external software (CRM, e-commerce, etc.) to trigger actions in this system.
                    </p>

                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-3">
                            <span
                                class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-black uppercase">POST</span>
                            <code class="text-sm font-mono text-slate-700 dark:text-slate-300">/webhooks/inbound</code>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Send webhook data from external systems.</p>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">Example Payload</span>
                            </div>
                            <pre class="text-xs font-mono text-slate-700 dark:text-slate-300">{
  "event": "order.created",
  "data": {
    "order_id": "12345",
    "customer_phone": "+1234567890",
    "customer_name": "John Doe",
    "total": 99.99,
    "items": [...]
  }
}</pre>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-slate-500 text-xs font-bold uppercase">cURL Example</span>
                                <button onclick="copyToClipboard('inbound-webhook')"
                                    class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                            </div>
                            <pre id="inbound-webhook"
                                class="text-xs font-mono text-slate-700 dark:text-slate-300 overflow-x-auto">curl -X POST "{{ $baseUrl }}/webhooks/inbound" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "order.created",
    "data": {
      "order_id": "12345",
      "customer_phone": "+1234567890"
    }
  }'</pre>
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4">
                        <p class="text-sm font-bold text-blue-700 dark:text-blue-400">
                            <strong>Use Cases:</strong> Trigger WhatsApp messages when orders are placed, send notifications when support tickets are created, sync data from your CRM, etc.
                        </p>
                    </div>
                </div>

                <!-- Inbound Webhook (WhatsApp) -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 uppercase tracking-tight">Inbound
                        Webhook (WhatsApp)</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                        This is the webhook URL to configure in your Meta Business Manager for receiving WhatsApp
                        events.
                    </p>

                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-slate-500 text-xs font-bold uppercase">Webhook URL</span>
                            <button onclick="copyToClipboard('whatsapp-webhook')"
                                class="text-xs font-bold text-purple-600 hover:text-purple-700 uppercase">Copy</button>
                        </div>
                        <pre id="whatsapp-webhook"
                            class="text-sm font-mono text-slate-700 dark:text-slate-300">{{ $webhookUrl }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;

            navigator.clipboard.writeText(text).then(() => {
                // Show a brief success message
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('text-green-600');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('text-green-600');
                }, 2000);
            });
        }
    </script>
</x-app-layout>