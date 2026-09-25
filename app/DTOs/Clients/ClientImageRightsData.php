<?php

namespace App\DTOs\Clients;

use App\Enums\AudienceCategory;
use App\Models\Client;

final class ClientImageRightsData
{
    /**
     * @param  array<string, string>  $values
     */
    private function __construct(private readonly array $values) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(Client $client, array $data = []): self
    {
        $authorizedPersonName = $client->audience_category === AudienceCategory::CHILD
            ? ($client->legal_representative_name ?: $client->name)
            : $client->name;
        $authorizedPersonDocument = $client->audience_category === AudienceCategory::CHILD
            ? ($client->legal_representative_document ?: $client->document)
            : $client->document;

        return new self([
            'authorized_person_name' => (string) $authorizedPersonName,
            'authorized_person_cpf' => (string) $authorizedPersonDocument,
            'authorized_person_address' => self::address($client),
            'authorized_person_email' => (string) $client->email,
            'image_producer_name' => (string) ($data['image_producer_name'] ?? config('app.name')),
            'image_usage_purpose' => (string) ($data['image_usage_purpose'] ?? 'divulgação institucional e promocional'),
            'image_description' => (string) ($data['image_description'] ?? 'imagem do cliente'),
            'image_material_type' => (string) ($data['image_material_type'] ?? 'fotografia e/ou filmagem'),
            'site_owner_name' => (string) ($data['site_owner_name'] ?? config('app.name')),
            'site_name' => (string) ($data['site_name'] ?? config('app.name')),
            'site_domain' => (string) ($data['site_domain'] ?? self::siteDomain()),
            'forum_city' => (string) ($data['forum_city'] ?? $client->address_city),
            'authorization_date' => now()->format('d/m/Y'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    private static function address(Client $client): string
    {
        return implode(', ', array_filter([
            $client->address,
            $client->address_number,
            $client->address_complement,
            $client->address_district,
            $client->address_city,
            $client->address_state,
            $client->address_postal_code,
        ], fn (mixed $part): bool => filled($part)));
    }

    private static function siteDomain(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : (string) config('app.name');
    }
}
