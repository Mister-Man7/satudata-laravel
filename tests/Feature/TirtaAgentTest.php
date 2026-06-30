<?php

use App\Ai\Agents\TirtaAgent;

test('tirta agent has the correct system instructions', function () {
    $agent = new TirtaAgent;
    $instructions = (string) $agent->instructions();

    expect($instructions)
        ->toContain('asisten virtual resmi Universitas Sultan Ageng Tirtayasa')
        ->toContain('Data akademik')
        ->toContain('Data aset')
        ->toContain('Data pegawai')
        ->toContain('Data infrastruktur')
        ->toContain('Maaf, saya tidak menemukan informasi yang Anda minta');
});
