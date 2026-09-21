<?php

namespace App\DTOs\Clients;

use App\Models\Client;
use Spatie\LaravelData\Data;

class ClientResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $phone,
        public string $document,
        public string $gender,
        public string $birth_date,
        public ?string $audience_category,
        public ?string $legal_representative_name,
        public ?string $legal_representative_document,
        public ?string $legal_representative_birth_date,
        public ?string $address_postal_code,
        public ?string $address,
        public ?string $address_number,
        public ?string $address_complement,
        public ?string $address_district,
        public ?string $address_state,
        public ?string $address_city,
        public string $status,
        public string $created_at,
    ) {}

    public static function fromModel(Client $client): static
    {
        return new static(
            id: $client->id,
            name: $client->name,
            email: $client->email,
            phone: $client->phone,
            document: $client->document,
            gender: $client->gender,
            birth_date: $client->birth_date?->format('Y-m-d') ?? '',
            audience_category: $client->audience_category?->value,
            legal_representative_name: $client->legal_representative_name,
            legal_representative_document: $client->legal_representative_document,
            legal_representative_birth_date: $client->legal_representative_birth_date?->format('Y-m-d'),
            address_postal_code: $client->address_postal_code,
            address: $client->address,
            address_number: $client->address_number,
            address_complement: $client->address_complement,
            address_district: $client->address_district,
            address_state: $client->address_state,
            address_city: $client->address_city,
            status: $client->status->value,
            created_at: $client->created_at?->toISOString() ?? '',
        );
    }
}
