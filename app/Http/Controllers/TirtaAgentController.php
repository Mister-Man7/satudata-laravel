<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TirtaAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class TirtaAgentController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Pesan wajib diisi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $participant = $this->guestParticipant($request);
        $conversationId = $this->validConversationId(
            $validated['conversation_id'] ?? $request->session()->get('tirta_agent_conversation_id'),
            $participant->id,
        );

        $agent = TirtaAgent::make();

        if ($conversationId !== null) {
            $agent->continue($conversationId, $participant);
        } else {
            $agent->forUser($participant);
        }

        try {
            $response = $agent->prompt($validated['message']);
        } catch (Throwable $exception) {
            report($exception);

            try {
                $fallback = $this->chatWithOpenAiCompatibleProvider(
                    $agent,
                    $validated['message'],
                    $participant->id,
                    $conversationId,
                );
            } catch (Throwable $fallbackException) {
                report($fallbackException);

                return response()->json([
                    'message' => 'TirtaAgent belum bisa menjawab sekarang. Cek konfigurasi provider AI lalu coba lagi.',
                ], 502);
            }

            $request->session()->put('tirta_agent_conversation_id', $fallback['conversation_id']);

            return response()->json($fallback);
        }

        $request->session()->put('tirta_agent_conversation_id', $response->conversationId);

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }

    private function guestParticipant(Request $request): object
    {
        if (! $request->session()->has('tirta_agent_guest_id')) {
            $request->session()->put('tirta_agent_guest_id', random_int(1, 2_147_483_647));
        }

        return (object) [
            'id' => $request->session()->get('tirta_agent_guest_id'),
        ];
    }

    private function validConversationId(?string $conversationId, int $participantId): ?string
    {
        if ($conversationId === null) {
            return null;
        }

        $exists = DB::table(config('ai.conversations.tables.conversations', 'agent_conversations'))
            ->where('id', $conversationId)
            ->where('user_id', $participantId)
            ->exists();

        return $exists ? $conversationId : null;
    }

    /**
     * ChatAnywhere supports OpenAI-compatible chat completions, while the
     * Laravel AI OpenAI gateway uses the newer Responses API.
     *
     * @return array{message: string, conversation_id: string}
     */
    private function chatWithOpenAiCompatibleProvider(
        TirtaAgent $agent,
        string $message,
        int $participantId,
        ?string $conversationId,
    ): array {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $conversationId ??= (string) Str::uuid7();

        // 1. Get history from DB (including user, assistant, and tool messages)
        $historyData = DB::table($messagesTable)
            ->where('conversation_id', $conversationId)
            ->whereIn('role', ['user', 'assistant', 'tool'])
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->reverse();

        $history = [];
        foreach ($historyData as $item) {
            $msg = [
                'role' => $item->role,
                'content' => $item->content,
            ];
            
            if ($item->role === 'assistant') {
                $toolCalls = json_decode($item->tool_calls, true);
                if (!empty($toolCalls)) {
                    $msg['tool_calls'] = $toolCalls;
                    if ($msg['content'] === '') {
                        $msg['content'] = null;
                    }
                }
            }
            
            if ($item->role === 'tool') {
                $meta = json_decode($item->meta, true);
                $msg['tool_call_id'] = $meta['tool_call_id'] ?? '';
            }
            
            $history[] = $msg;
        }

        // 2. Map tools
        $tools = $agent->tools();
        $openAiTools = $this->mapAgentToolsForOpenAi($tools);

        // 3. Prepare messages for API
        $messages = [
            ['role' => 'system', 'content' => (string) $agent->instructions()],
            ...$history,
            ['role' => 'user', 'content' => $message],
        ];

        // 4. Send request to OpenAI
        $requestPayload = [
            'model' => config('ai.providers.openai.models.text.default', 'gpt-4o-mini'),
            'messages' => $messages,
        ];
        
        if (!empty($openAiTools)) {
            $requestPayload['tools'] = $openAiTools;
        }

        $response = Http::timeout(60)
            ->acceptJson()
            ->withToken(config('ai.providers.openai.key'))
            ->post(rtrim((string) config('ai.providers.openai.url'), '/').'/chat/completions', $requestPayload)
            ->throw()
            ->json();

        // Check if the response wants to call tools
        $choice = data_get($response, 'choices.0.message');
        $toolCalls = $choice['tool_calls'] ?? [];

        if (!empty($toolCalls)) {
            // A. Store the user's message in DB first
            DB::transaction(function () use ($conversationsTable, $messagesTable, $conversationId, $participantId, $message): void {
                $conversationExists = DB::table($conversationsTable)->where('id', $conversationId)->exists();
                if ($conversationExists) {
                    DB::table($conversationsTable)->where('id', $conversationId)->update(['updated_at' => now()]);
                } else {
                    DB::table($conversationsTable)->insert([
                        'id' => $conversationId,
                        'user_id' => $participantId,
                        'title' => Str::limit($message, 100, preserveWords: true),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->storeConversationMessage($messagesTable, $conversationId, $participantId, 'user', $message);
            });

            // B. Store assistant tool-call message in DB
            $assistantContent = $choice['content'] ?? '';
            DB::transaction(function () use ($messagesTable, $conversationId, $participantId, $assistantContent, $toolCalls): void {
                DB::table($messagesTable)->insert([
                    'id' => (string) Str::uuid7(),
                    'conversation_id' => $conversationId,
                    'user_id' => $participantId,
                    'agent' => TirtaAgent::class,
                    'role' => 'assistant',
                    'content' => $assistantContent,
                    'attachments' => '[]',
                    'tool_calls' => json_encode($toolCalls),
                    'tool_results' => '[]',
                    'usage' => '[]',
                    'meta' => '[]',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            // Append assistant tool-call message to the API messages array
            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantContent === '' ? null : $assistantContent,
                'tool_calls' => $toolCalls,
            ];

            // C. Execute each tool call and store the tool result message
            foreach ($toolCalls as $toolCall) {
                $callId = $toolCall['id'] ?? '';
                $funcName = $toolCall['function']['name'] ?? '';
                $funcArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                // Find matching tool
                $toolOutput = '';
                foreach ($tools as $t) {
                    if (\Laravel\Ai\Tools\ToolNameResolver::resolve($t) === $funcName) {
                        try {
                            $toolOutput = (string) $t->handle(new \Laravel\Ai\Tools\Request($funcArgs));
                        } catch (Throwable $e) {
                            $toolOutput = json_encode(['error' => 'Tool execution failed: '.$e->getMessage()]);
                        }
                        break;
                    }
                }

                if ($toolOutput === '') {
                    $toolOutput = json_encode(['error' => 'Tool not found.']);
                }

                // Store tool result message in DB
                DB::transaction(function () use ($messagesTable, $conversationId, $participantId, $toolOutput, $callId): void {
                    DB::table($messagesTable)->insert([
                        'id' => (string) Str::uuid7(),
                        'conversation_id' => $conversationId,
                        'user_id' => $participantId,
                        'agent' => TirtaAgent::class,
                        'role' => 'tool',
                        'content' => $toolOutput,
                        'attachments' => '[]',
                        'tool_calls' => '[]',
                        'tool_results' => '[]',
                        'usage' => '[]',
                        'meta' => json_encode(['tool_call_id' => $callId]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

                // Append tool response to the API messages array
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $callId,
                    'content' => $toolOutput,
                ];
            }

            // D. Request final answer from OpenAI
            $finalRequestPayload = [
                'model' => config('ai.providers.openai.models.text.default', 'gpt-4o-mini'),
                'messages' => $messages,
            ];
            if (!empty($openAiTools)) {
                $finalRequestPayload['tools'] = $openAiTools;
            }

            $finalResponse = Http::timeout(60)
                ->acceptJson()
                ->withToken(config('ai.providers.openai.key'))
                ->post(rtrim((string) config('ai.providers.openai.url'), '/').'/chat/completions', $finalRequestPayload)
                ->throw()
                ->json();

            $answer = data_get($finalResponse, 'choices.0.message.content');

            if (! is_string($answer) || trim($answer) === '') {
                throw new \RuntimeException('OpenAI-compatible provider returned an empty response.');
            }

            // Store the final assistant answer in DB
            DB::transaction(function () use ($messagesTable, $conversationId, $participantId, $answer): void {
                $this->storeConversationMessage($messagesTable, $conversationId, $participantId, 'assistant', $answer);
            });

            return [
                'message' => $answer,
                'conversation_id' => $conversationId,
            ];
        }

        // Standard flow when NO tool calls are returned by the AI
        $answer = $choice['content'];

        if (! is_string($answer) || trim($answer) === '') {
            throw new \RuntimeException('OpenAI-compatible provider returned an empty response.');
        }

        DB::transaction(function () use ($conversationsTable, $messagesTable, $conversationId, $participantId, $message, $answer): void {
            $conversationExists = DB::table($conversationsTable)->where('id', $conversationId)->exists();

            if ($conversationExists) {
                DB::table($conversationsTable)->where('id', $conversationId)->update(['updated_at' => now()]);
            } else {
                DB::table($conversationsTable)->insert([
                    'id' => $conversationId,
                    'user_id' => $participantId,
                    'title' => Str::limit($message, 100, preserveWords: true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->storeConversationMessage($messagesTable, $conversationId, $participantId, 'user', $message);
            $this->storeConversationMessage($messagesTable, $conversationId, $participantId, 'assistant', $answer);
        });

        return [
            'message' => $answer,
            'conversation_id' => $conversationId,
        ];
    }

    private function mapAgentToolsForOpenAi(iterable $tools): array
    {
        $mapped = [];
        foreach ($tools as $tool) {
            if ($tool instanceof \Laravel\Ai\Contracts\Tool) {
                $name = \Laravel\Ai\Tools\ToolNameResolver::resolve($tool);
                $schema = $tool->schema(new \Illuminate\JsonSchema\JsonSchemaTypeFactory());
                $schemaArray = !empty($schema)
                    ? (new \Laravel\Ai\ObjectSchema($schema))->toSchema()
                    : [];

                $mapped[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $name,
                        'description' => (string) $tool->description(),
                        'parameters' => [
                            'type' => 'object',
                            'properties' => $schemaArray['properties'] ?? (object) [],
                            'required' => $schemaArray['required'] ?? [],
                            'additionalProperties' => false,
                        ],
                    ],
                ];
            }
        }
        return $mapped;
    }

    private function storeConversationMessage(
        string $messagesTable,
        string $conversationId,
        int $participantId,
        string $role,
        string $content,
    ): void {
        DB::table($messagesTable)->insert([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $conversationId,
            'user_id' => $participantId,
            'agent' => TirtaAgent::class,
            'role' => $role,
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
