<?php

use App\Ai\Agents\TirtaAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('tirta agent menyimpan dan melanjutkan percakapan browser yang sama', function () {
    TirtaAgent::fake([
        'Halo, saya bantu arahkan data akademik.',
        'Iya, masih melanjutkan pertanyaan akademik sebelumnya.',
    ]);

    $firstResponse = $this
        ->withSession(['tirta_agent_guest_id' => 12345])
        ->postJson(route('tirta-agent.chat'), [
            'message' => 'Saya butuh data akademik.',
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Halo, saya bantu arahkan data akademik.',
        ]);

    $conversationId = $firstResponse->json('conversation_id');

    expect($conversationId)->toBeString()->not->toBeEmpty();

    $this
        ->withSession([
            'tirta_agent_guest_id' => 12345,
            'tirta_agent_conversation_id' => $conversationId,
        ])
        ->postJson(route('tirta-agent.chat'), [
            'message' => 'Kalau yang lulus ada di mana?',
            'conversation_id' => $conversationId,
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Iya, masih melanjutkan pertanyaan akademik sebelumnya.',
            'conversation_id' => $conversationId,
        ]);

    expect(DB::table('agent_conversations')->count())->toBe(1);
    expect(DB::table('agent_conversation_messages')->count())->toBe(4);

    $storedMessages = DB::table('agent_conversation_messages')
        ->orderBy('created_at')
        ->pluck('content')
        ->all();

    expect($storedMessages)->toContain('Saya butuh data akademik.');
    expect($storedMessages)->toContain('Kalau yang lulus ada di mana?');
});

test('tirta agent menolak pesan kosong', function () {
    $this->postJson(route('tirta-agent.chat'), [
        'message' => '',
    ])->assertStatus(422);
});
