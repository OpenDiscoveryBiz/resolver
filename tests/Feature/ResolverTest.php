<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_frontpage_redirects_to_github(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('https://github.com/OpenDiscoveryBiz/resolver');
    }

    public function test_lookup_requires_id(): void
    {
        $response = $this->get('/lookup');

        $response->assertStatus(400)
            ->assertJson(['error' => 'missing_id']);
    }

    public function test_lookup_rejects_invalid_id(): void
    {
        $response = $this->get('/lookup?id=X');

        $response->assertStatus(400)
            ->assertJson(['error' => 'invalid_id']);
    }

    public function test_lookup_follows_redirect_provider_chain(): void
    {
        Http::fake([
            'https://root.example.test/*' => Http::response([
                'type' => 'redirect',
                'id' => 'DK',
                'providers' => ['https://dk.example.test'],
                'ttl' => 3600,
            ]),
            'https://dk.example.test/*' => Http::response([
                'type' => 'official',
                'id' => 'DK123',
                'ttl' => 3600,
                'voluntaryProviders' => [],
            ]),
        ]);

        $response = $this->get('/lookup?id=DK123');

        $response->assertOk()
            ->assertJsonPath('id', 'DK123')
            ->assertJsonPath('official.type', 'official')
            ->assertJsonPath('voluntary.error', 'official_not_available');
    }
}
