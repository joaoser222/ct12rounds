<?php

namespace Tests\Feature\LandingAssets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAssetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_serves_a_local_landing_asset_with_immutable_cache(): void
    {
        $this->assertFileExists(resource_path('views/landing-assets/img/treinos (1).webp'));

        $response = $this->get('/landing-assets/img/treinos%20(1).webp');

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $response->assertHeader('Content-Type', 'image/webp');
    }

    public function test_returns_404_for_an_unknown_asset(): void
    {
        $this->get('/landing-assets/img/nonexistent.webp')->assertNotFound();
        $this->get('/landing-assets/img/other.txt')->assertNotFound();
    }

    public function test_blocks_path_traversal(): void
    {
        $this->get('/landing-assets/img/..%2F..%2Fcomposer.json')->assertNotFound();
        $this->get('/landing-assets/img%2F..%2F..%2Fcomposer.json')->assertNotFound();
    }
}