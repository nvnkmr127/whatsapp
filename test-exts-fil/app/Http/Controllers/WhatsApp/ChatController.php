<?php

namespace App\Http\Controllers\WhatsApp;

use App\Enums\Languages;
use App\Http\Controllers\Controller;
use App\Models\AiPrompt;
use App\Models\CannedReply;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Contact;
use App\Models\Source;
use App\Models\User;
use App\Services\MergeFields;
use App\Traits\Ai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use Ai;

    /**
     * Display the chat interface
     */
    public function index()
    {
        if (! checkPermission(['chat.view', 'chat.read_only'])) {
            session()->flash('notification', ['type' => 'danger', 'message' => t('access_denied_note')]);

            return redirect()->route('admin.dashboard');
        }

        // Update assignees and agents from contacts
        $this->syncAgentsWithContacts();

        // Get all chats with unread message count
        $chats = $this->getChats();

        // Load all necessary data for the view
        $data = [
            'chats'               => $chats,
            'ai_prompt'           => AiPrompt::select('id', 'name', 'action')->get(),
            'canned_reply'        => CannedReply::select('id', 'added_from', 'description', 'title', 'is_public')->get(),
            'users'               => User::select('id', 'firstname', 'lastname', 'is_admin')->get(),
            'sources'             => Source::all(),
            'languages'           => Languages::all(),
            'selectedAgent'       => [],
            'readOnlyPermission'  => (! (Auth::user()->is_admin) && checkPermission('chat.read_only')) ? 0 : 1,
            'user_is_admin'       => Auth::user()->is_admin,
            'enable_supportagent' => get_setting('whats-mark.only_agents_can_chat'),
            'login_user'          => Auth::id(),

        ];

        return view('chat.manageChat', $data);
    }

    /**
     * Get messages for a specific chat
     */
    public function messagesGet($chatId, $lastMessageId = 0)
    {
        $query = ChatMessage::where('interaction_id', $chatId);

        // If lastMessageId is provided, get messages older than this ID
        if (! empty($lastMessageId)) {
            $query->where('id', '<', $lastMessageId);
        }

        $messages = $query->orderBy('id', 'desc')
            ->take(20)
            ->get()
            ->map(function ($message) {
                if (! empty($message->url)) {
                    $message->url = asset('storage/whatsapp-attachments/' . ltrim($message->url, '/'));
                }

                return $message;
            })
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $chat->messages()->where('is_read', 0)->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove a message
     */
    public function removeMessage($messageId)
    {
        $chat = Chat::whereHas('messages', function ($query) use ($messageId) {
            $query->where('id', $messageId);
        })->first();

        if ($chat) {
            $chat->messages()->where('id', $messageId)->delete();

            return response()->json([
                'success' => true,
                'message' => t('message_deleted_successfully'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => t('message_not_found'),
        ], 404);
    }

    /**
     * Delete a chat
     */
    public function removeChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => t('chat_delete_successfully'),
        ]);
    }

    /**
     * Assign support agent to chat
     */
    public function assignSupportAgent(Request $request, $chatId)
    {
        $agentsId = $request->input('agent_ids');
        try {
            $chat = Chat::findOrFail($chatId);

            $agents = is_array($agentsId) ? implode(',', $agentsId) : $agentsId;

            if ($chat->type == 'lead' || $chat->type == 'customer') {
                $assign_id = Contact::where('id', $chat->type_id)->value('assigned_id');
            }

            $chat->update([
                'agent' => json_encode([
                    'assign_id' => $assign_id ?? 0,
                    'agents_id' => $agents    ?? '',
                ]),
            ]);

            $agent_layout = $this->getSupportAgentView($chatId, true);

            return response()->json([
                'success'      => true,
                'message'      => t('support_agent_assigned_successfully'),
                'agent_layout' => $agent_layout['agent_layout'],
            ]);
        } catch (\Throwable $e) {
            // Log the error with context
            whatsapp_log('Error assigning support agent: ' . $e->getMessage(), 'error', [
                'selected_agent' => $request->input('agent_ids'),
                'chat_id'        => $chatId,
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign agent: ' . $e->getMessage(),
            ]);
        }
    }

    public function userInformation(Request $request)
    {
        $type       = $request->input('type');
        $contact_id = $request->input('type_Id');
        if (! empty($contact_id) && $type != 'guest') {
            return Contact::where(['type' => $type, 'id' => $contact_id])->get()->toArray();
        }

        return [];
    }

    /**
     * Get support agent view
     */
    public function getSupportAgentView($chatId, $isReturn = false)
    {
        $chat = Chat::find($chatId);

        ChatMessage::where('interaction_id', $chatId)->update(['is_read' => 1]);

        if (! $chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $agentData = json_decode($chat->agent, true) ?? [];

        // Ensure 'agents_id' is an array
        $agentsIds = isset($agentData['agents_id']) && is_array($agentData['agents_id'])
            ? $agentData['agents_id']
            : explode(',', $agentData['agents_id'] ?? '');

        // Collect unique user IDs (assign_id + agents_id)
        $userIds = collect(array_merge([$agentData['assign_id'] ?? null], $agentsIds))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Fetch users with profile images and names
        $users = User::whereIn('id', $userIds)
            ->get(['id', 'firstname', 'lastname', 'profile_image_url'])
            ->keyBy('id');

        // Generate agent layout
        $layout = '<div id="agent-container" x-data="{ openDropdown: false }" class="relative" wire:ignore>
                        <div class="flex items-center">';

        if ($users->count() === 1) {
            $user         = $users->first();
            $profileImage = $this->getProfileImage($user->profile_image_url);
            $fullName     = e(trim($user->firstname . ' ' . $user->lastname));

            $layout .= "<img src='{$profileImage}' class='rounded-full h-7 w-7 object-cover ring-1 bg-gray-200 dark:bg-gray-700 cursor-pointer'
                        x-on:click.prevent='openDropdown = !openDropdown' data-tippy-content='{$fullName}'>";
        } else {
            $isMobile  = request()->header('User-Agent') && preg_match('/(Mobile|Android|iPhone|iPad)/i', request()->header('User-Agent'));
            $maxToShow = $isMobile ? 0 : 3;
            $i         = 0;

            foreach ($users as $user) {
                if ($i >= $maxToShow) {
                    break;
                }
                $profileImage = $this->getProfileImage($user->profile_image_url);
                $fullName     = e(trim($user->firstname . ' ' . $user->lastname));

                $layout .= "<img src='{$profileImage}' class='rounded-full h-7 w-7 object-cover ring-1 -ml-2 first:ml-0'
                            x-on:click.prevent='openDropdown = !openDropdown' data-tippy-content='{$fullName}'>";
                $i++;
            }

            if ($users->count() > $maxToShow) {
                $layout .= " <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'
                            class='rounded-full bg-[#f4f4f4] dark:bg-[#050b14] dark:text-slate-400 w-7 h-7 object-cover cursor-pointer ring-1  -ml-2 first:ml-0'
                            x-on:click.prevent='openDropdown = !openDropdown' data-tippy-content='More'>
                    <path stroke-linecap='round' stroke-linejoin='round' d='M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z' />
                </svg>";
            }
        }

        $layout .= '</div>
                    <ul x-show="openDropdown" x-on:click.away="openDropdown = false"
                        class="absolute flex right-0 mt-2 bg-white dark:bg-gray-800 rounded-md shadow-lg p-2 z-20">
                        <li>
                            <div class="m-2 flex space-x-2 w-56 overflow-x-auto">';

        foreach ($users as $user) {
            $profileImage = $this->getProfileImage($user->profile_image_url);
            $fullName     = e(trim($user->firstname . ' ' . $user->lastname));

            $layout .= "<div class='flex items-center space-x-2 shrink-0'>
                            <img src='{$profileImage}' class='rounded-full h-8 w-8 object-cover ring-1 text-xs my-2' data-tippy-content='{$fullName}'>
                        </div>";
        }

        $layout .= '</div>
                        </li>
                    </ul>
                </div>';

        if ($isReturn ?? false) {
            return [
                'chat_id'      => $chatId,
                'agent_layout' => $layout,
            ];
        }

        return response()->json([
            'chat_id'      => $chatId,
            'agent_layout' => $layout,
        ]);

    }

    /**
     * Process AI response
     */
    public function processAiResponse(Request $request)
    {
        try {
            $data = [
                'menu'      => $request->input('menu'),
                'submenu'   => $request->input('submenu'),
                'input_msg' => $request->input('input_msg'),
            ];

            $response = $this->aiResponse($data);

            if ($response['status']) {
                return response()->json([
                    'success' => true,
                    'message' => $response['message'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => t('error_processing_ai_response'),
                ]);
            }
        } catch (\Throwable $e) {
            whatsapp_log('Exception in AI response processing: ' . $e->getMessage(), 'error', [
                'menu'      => $request->input('menu'),
                'submenu'   => $request->input('submenu'),
                'input_msg' => $request->input('input_msg'),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => t('error_processing_ai_response'),
            ]);
        }
    }

    /**
     * Load merge fields
     */
    public function loadMergeFields(Request $request, $group = '')
    {

        $mergeFieldsService = app(MergeFields::class);

        // Always include 'other-group'
        $field = $mergeFieldsService->getFieldsForTemplate('other-group');

        // Include 'contact-group' only if group is 'lead' or 'customer'
        if (in_array($group, ['lead', 'customer'])) {
            $field = array_merge($field, $mergeFieldsService->getFieldsForTemplate('contact-group'));
        }

        $mergeFields = array_map(fn ($value) => [
            'key'   => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field);

        return response()->json($mergeFields);
    }

    /**
     * Get new chat message for pusher
     */
    public static function newChatMessage($chat_id, $message_id)
    {
        $chat = Chat::withCount([
            'messages as unreadmessagecount' => function ($query) {
                $query->where('is_read', 0);
            },
        ])
            ->with([
                'messages' => function ($query) use ($message_id) {
                    $query->where('id', $message_id);
                },
            ])
            ->findOrFail($chat_id);

        // Modify message URLs after retrieval
        $chat->messages->transform(function ($message) {
            if (! empty($message->url)) {
                $message->url = asset('storage/whatsapp-attachments/' . ltrim($message->url, '/'));
            }

            return $message;
        });

        return $chat;
    }

    /**
     * Get formatted profile image URL or default image.
     */
    private function getProfileImage($profileUrl)
    {
        return $profileUrl
            ? asset('storage/' . $profileUrl)
            : asset('img/avatar-agent.svg');
    }

    /**
     * Check if user has permission to access chat
     */
    private function hasPermission()
    {
        return checkPermission(['chat.view', 'chat.read_only']);
    }

    /**
     * Sync agents with contacts
     */
    private function syncAgentsWithContacts()
    {
        Chat::join('contacts', 'contacts.id', '=', 'chat.type_id')
            ->whereIn('chat.type', ['lead', 'customer'])
            ->update([
                'chat.agent' => DB::raw("JSON_SET(COALESCE(NULLIF(chat.agent, ''), '{}'),'$.assign_id', contacts.assigned_id,'$.agents_id', IF(JSON_CONTAINS_PATH(COALESCE(NULLIF(chat.agent, ''), '{}'), 'one', '$.agents_id'), JSON_UNQUOTE(JSON_EXTRACT(COALESCE(NULLIF(chat.agent, ''), '{}'), '$.agents_id')), 0))"),
            ]);
    }

    /**
     * Get all chats with filtering if necessary
     */
    private function getChats()
    {
        $query = Chat::withCount([
            'messages as unreadmessagecount' => function ($query) {
                $query->where('is_read', 0);
            },
        ]);

        $onlyAgentsCanChat = get_setting('whats-mark.only_agents_can_chat', false);

        if ($onlyAgentsCanChat && ! Auth::user()->is_admin) {
            $userId = auth()->id();

            $query->where(function ($q) use ($userId) {
                $q->whereRaw("JSON_CONTAINS_PATH(COALESCE(NULLIF(agent, ''), '{}'), 'one', '$.assign_id')")
                    ->whereRaw("CAST(JSON_EXTRACT(agent, '$.assign_id') AS UNSIGNED) = ?", [$userId])
                    ->orWhere(function ($sq) use ($userId) {
                        $sq->whereRaw("JSON_CONTAINS_PATH(COALESCE(NULLIF(agent, ''), '{}'), 'one', '$.agents_id')")
                            ->whereRaw("FIND_IN_SET(?, JSON_UNQUOTE(JSON_EXTRACT(agent, '$.agents_id')))", [$userId]);
                    });
            });
        }

        return $query->get()->toArray();

    }
}
