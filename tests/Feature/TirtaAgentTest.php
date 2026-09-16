<?php

namespace Tests\Feature;

use App\Ai\Agents\TirtaAgent;
use App\Ai\Tools\GetAcademicStats;
use App\Ai\Tools\GetActiveStudentStats;
use App\Ai\Tools\GetAssetStats;
use App\Ai\Tools\GetPegawaiStats;
use App\Ai\Tools\SearchActiveStudents;
use App\Ai\Tools\SearchGraduates;
use App\Ai\Tools\SearchPegawai;
use App\Ai\Tools\SearchPublications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class TirtaAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_tirta_agent_registers_all_domain_tools(): void
    {
        $agent = new TirtaAgent();
        $tools = iterator_to_array($agent->tools());

        $this->assertCount(8, $tools);
        $toolClasses = array_map(fn($t) => get_class($t), $tools);

        $this->assertContains(GetAcademicStats::class, $toolClasses);
        $this->assertContains(GetActiveStudentStats::class, $toolClasses);
        $this->assertContains(SearchActiveStudents::class, $toolClasses);
        $this->assertContains(SearchGraduates::class, $toolClasses);
        $this->assertContains(GetPegawaiStats::class, $toolClasses);
        $this->assertContains(SearchPegawai::class, $toolClasses);
        $this->assertContains(GetAssetStats::class, $toolClasses);
        $this->assertContains(SearchPublications::class, $toolClasses);
    }

    public function test_get_academic_stats_returns_dynamic_json_without_hardcoding(): void
    {
        $tool = new GetAcademicStats();
        $outputJson = (string) $tool->handle(new Request([]));

        $data = json_decode($outputJson, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('ringkasan_universitas', $data);
        $this->assertArrayHasKey('sebaran_per_fakultas', $data);
    }

    public function test_get_active_student_stats_returns_json(): void
    {
        $tool = new GetActiveStudentStats();
        $outputJson = (string) $tool->handle(new Request([]));

        $data = json_decode($outputJson, true);
        $this->assertIsArray($data);
    }

    public function test_get_pegawai_stats_returns_json(): void
    {
        $tool = new GetPegawaiStats();
        $outputJson = (string) $tool->handle(new Request([]));

        $data = json_decode($outputJson, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('total_pegawai', $data);
    }

    public function test_get_asset_stats_returns_json(): void
    {
        $tool = new GetAssetStats();
        $outputJson = (string) $tool->handle(new Request([]));

        $data = json_decode($outputJson, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('total_aset_bmn', $data);
    }

    public function test_chat_endpoint_validates_message(): void
    {
        $response = $this->postJson(route('tirta-agent.chat'), [
            'message' => '',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }
}
