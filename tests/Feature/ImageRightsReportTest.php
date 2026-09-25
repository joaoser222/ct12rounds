<?php

namespace Tests\Feature;

use App\DTOs\Clients\ClientImageRightsData;
use App\Models\Client;
use App\Models\User;
use App\Services\PrintableReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageRightsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_image_rights_report(): void
    {
        $client = Client::factory()->create();

        $response = $this->get(route('clients.image-rights', $client));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_generate_image_rights_pdf(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.view');
        $client = Client::factory()->create([
            'name' => 'Maria da Silva',
            'document' => '12345678901',
            'email' => 'maria@example.com',
            'address' => 'Rua das Palmeiras',
            'address_number' => '100',
            'address_city' => 'Palmas',
            'address_state' => 'TO',
        ]);

        $response = $this->actingAs($user)->get(route('clients.image-rights', $client));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_image_rights_report_requires_view_permission(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.image-rights', $client));

        $response->assertForbidden();
    }

    public function test_image_rights_report_replaces_fields_and_escapes_user_input(): void
    {
        $client = Client::factory()->create([
            'name' => 'Maria da Silva',
            'document' => '12345678901',
            'email' => 'maria@example.com',
            'address' => 'Rua das Palmeiras',
            'address_number' => '100',
            'address_city' => 'Palmas',
            'address_state' => 'TO',
        ]);

        $html = app(PrintableReportService::class)->render(
            'templates/image_rights.md',
            ClientImageRightsData::from($client, [
                'image_description' => 'Foto "especial" & aérea',
            ])->toArray(),
        );

        $this->assertStringContainsString('Maria da Silva', $html);
        $this->assertStringContainsString('Foto &quot;especial&quot; &amp; aérea', $html);
        $this->assertStringNotContainsString('<b>aérea</b>', $html);
        $this->assertStringNotContainsString('{{', $html);
    }
}
