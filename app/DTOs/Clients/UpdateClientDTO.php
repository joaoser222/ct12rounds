<?php

namespace App\DTOs\Clients;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\AudienceCategory;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateClientDTO extends BaseDTO
{
    public function __construct(
        #[Required, Integer, Min(1)]
        public int $id,

        #[Sometimes, StringType, Max(255)]
        public ?string $name = null,

        #[Sometimes, Email, Max(255)]
        public ?string $email = null,

        #[Nullable, String, Min(10), Max(11)]
        public ?string $phone = null,

        #[Nullable, String, Size(11)]
        public ?string $document = null,

        #[Nullable, Enum(GenderType::class)]
        public ?string $gender = null,

        #[Nullable, Date]
        public ?string $birth_date = null,

        #[Nullable, Enum(AudienceCategory::class)]
        public ?string $audience_category = null,

        #[Nullable, String, Max(255)]
        public ?string $legal_representative_name = null,

        #[Nullable, String, Size(11)]
        public ?string $legal_representative_document = null,

        #[Nullable, Date]
        public ?string $legal_representative_birth_date = null,

        #[Nullable, String, Max(8)]
        public ?string $address_postal_code = null,

        #[Nullable, String, Max(200)]
        public ?string $address = null,

        #[Nullable, String, Max(10)]
        public ?string $address_number = null,

        #[Nullable, String, Max(100)]
        public ?string $address_complement = null,

        #[Nullable, String, Max(100)]
        public ?string $address_district = null,

        #[Nullable, String, Size(2)]
        public ?string $address_state = null,

        #[Nullable, String, Max(100)]
        public ?string $address_city = null,

        #[Nullable, Enum(ClientStatus::class)]
        public ?string $status = null,
    ) {}
}
